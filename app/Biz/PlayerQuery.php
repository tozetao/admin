<?php

namespace App\Biz;

use App\Models\Player;
use App\Models\ShareTicketLog;
use App\Repository\MachineRepository;
use App\Repository\PlayerRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlayerQuery
{
    const Online = 1;

    private $defaultResult;
    private $gameApi;
    private $playerRepository;
    private $machineRepository;

    public const RegisterTimeColumn = 1;
    public const LoginTimeColumn = 2;
    public const OfflineTimeColumn = 3;

    public const OrderByCoin = 1;
    public const OrderByTicket = 2;

    public const OrderByShareTicket = 3;

    public function __construct(PlayerRepository $playerRepository, MachineRepository $machineRepository)
    {
        $this->defaultResult = [
            'total' => 0,
            'list' => []
        ];
        $this->gameApi = app('game_api');
        $this->playerRepository = $playerRepository;
        $this->machineRepository = $machineRepository;
    }

    public function execute(int $queryType, string $keyword, int $status, $startDate, $endDate, $timeColumn, $orderBy, $sort, $page, $pageSize = 10): array
    {
        $start = parse_from_date($startDate);
        $end = parse_to_date($endDate);
        $offset = ($page - 1) * $pageSize;

        if (self::OrderByShareTicket == $orderBy) {
            return $this->queryShareTickets($sort, $offset, $pageSize);
        }

        if (self::Online == $queryType) {
            return $this->queryOnlinePlayers($keyword, $offset, $pageSize);
        }
        return $this->queryLocalPlayers($keyword, $status, $start, $end, $timeColumn, $orderBy, $sort, $offset, $pageSize);
    }

    // 仅对玩家的乐享票进行排序
    private function queryShareTickets($sort, $offset, $pageSize)
    {
        $query = ShareTicketLog::query()
            ->select('role_id')
            ->groupBy('role_id');

        $subSql = $query->toSql();
        $stdClass = DB::table(DB::raw("($subSql) as tab"))
            ->select(DB::raw('count(*) as total'))
            ->get()
            ->first();
        $total = $stdClass->total ?: 0;

        $direction = $sort === Query::ASC ? 'asc' : 'desc';
        $models = ShareTicketLog::query()->selectRaw('role_id, (sum(add_value) - sum(cost_value)) as remaining_tickets')
            ->groupBy('role_id')
            ->orderBy('remaining_tickets', $direction)
            ->offset($offset)
            ->limit($pageSize)
            ->get();

        // 查询玩家数据。
        $playerIds = $models->pluck('role_id')->all();
        $players = $this->playerRepository->findIn($playerIds);

        $fn = function ($model) use($players) {
            $player = $players->where('role_id', $model->role_id)->first();

            $status = Player::Disabled;
            if ($player->black <= time()) {
                $status = Player::Enabled;
            }

            return [
                'id' => $player->role_id,
                'name' => $player->name,
                'icon' => $player->icon,
                'login_time' => to_datetime_string($player->login_time),
                'register_time' => to_datetime_string($player->register_time),
                'offline_time' => to_datetime_string($player->off_time),
                'coin' => $player->gold,
                'diamond' => $player->diamond,
                'ticket' => $player->score,
                'share_ticket' => $model->remaining_tickets,
                'vip' => $player->vip,
                'phone' => $player->phone,
                'status' => $status,
                'locked_time' => $player->black ? to_datetime_string($player->black) : '-'
            ];
        };

        $list = $models->map($fn);

        return [
            'list' => $list,
            'total' => $total
        ];
    }


    private function queryOnlinePlayers(string $keyword, $offset, $limit): array
    {
        // 应该有玩家id、金币、钻石、票
        $players = $this->gameApi->allOnlinePlayers();

        if (empty($players)) {
            return $this->defaultResult;
        }

//        'room_id' => 1694
//        'seat_id' => 4

        $total = count($players);
        $players = collect($players);

        $foundIds = null;
        if ($keyword) {
            $models = $this->findByKeyword($keyword);
            if ($models->isEmpty()) {
                return $this->defaultResult;
            }
            $foundIds = $models->pluck('role_id')->all();
        }

        if ($foundIds) {
            $players = $players->filter(function ($item) use($foundIds) {
                $playerId = $item['role_id'] ?? 0;
                return in_array($playerId, $foundIds);
            });

            if ($players->count() != 1) {
                return $this->defaultResult;
            }
            $one = $players->first();
            $foundPlayers = $this->playerRepository->findIn([$one['role_id']]);

            $machines = $this->machineRepository->findIn([$one['room_id']]);

            return [
                'total' => 1,
                'list' => $this->translateOnlinePlayers($players, $foundPlayers, $machines)
            ];
        }

        // 分页
        $players = $players->slice($offset, $limit);

        $ids = $players->pluck('role_id')->all();
        $foundPlayers = $this->playerRepository->findIn($ids);

        $machineIds = $players->pluck('room_id')->unique()->all();
        $machines = $this->machineRepository->findIn($machineIds);
        $list = $this->translateOnlinePlayers($players, $foundPlayers, $machines);
        return [
            'list' => $list,
            'total' => $total
        ];
    }

    private function translateOnlinePlayers(Collection $players, Collection $foundPlayers, Collection $machines): array
    {
        $result = $players->map(function ($item) use($foundPlayers, $machines) {
            $rest = [
                'name' => '',
                'icon' => '',
                'login_time' => '-',
                'register_time' => '-',
                'vip' => '',
                'phone' => '',
                'status' => Player::Disabled,
                'locked_time' => '-'
            ];

            // 如果存在room_id，表示玩家在这个机台上。seat_id = 0表示围观，否则表示所在P位
            $position = '大厅';
            $roomId = $item['room_id'] ?? 0;
            $seat = $item['seat_id'] ?? 0;
            if ($roomId) {
                $machine = $machines->where('id', $roomId)->first();
                if ($machine) {
                    if ($seat) {
                        $position = sprintf('%s [上机]', $machine->name);
                    } else {
                        $position = sprintf('%s [围观]', $machine->name);
                    }
                }
            }

            $model = $foundPlayers->where('role_id', $item['role_id'])->first();
            if ($model) {
                $rest['name'] = $model->name;
                $rest['icon'] = $model->icon;
                $rest['login_time'] = to_datetime_string($model->login_time);
                $rest['register_time'] = to_datetime_string($model->register_time);
                $rest['vip'] = $model->vip;
                $rest['phone'] = $model->phone;

                if ($model->black <= time()) {
                    $rest['status'] = Player::Enabled;
                }
                $rest['locked_time'] = $model->black ? to_datetime_string($model->black) : '-';
            }
            $array = [
                'id' => $item['role_id'],
                'coin' => $item['gold'],
                'diamond' => $item['diamond'],
                'ticket' => $item['score'],
                'position' => $position,
                'machine_id' => $roomId,
                'seat' => $seat
            ];
            return array_merge($array, $rest);
        })->all();
        return array_values($result);
    }

    private function findByKeyword($keyword)
    {
        return Player::query()
            ->where('role_id', 'like', '%' . $keyword . '%')
            ->orWhere('phone', $keyword)
            ->orWhere('name', 'like', '%' . $keyword . '%')
            ->get();
    }

    private function queryLocalPlayers(string $keyword, int $status, $start, $end, $timeColumn, $orderBy, $sort, $offset, $limit): array
    {
        $query = Player::query();

        if ($keyword) {
            $models = $this->findByKeyword($keyword);
            if ($models->isEmpty()) {
                return $this->defaultResult;
            }
            return [
                'total' => 1,
                'list' => $this->translateLocalPlayers($models)
            ];
        }

        // black是封禁到指定的时间。
        if (Player::Enabled === $status) {
            $query->where('black', '<=', time());
        } else {
            $query->where('black', '>', time());
        }

        if ($status && $end) {
            switch ($timeColumn) {
                case self::LoginTimeColumn:
                    $query->where('login_time', '>=', $start)
                        ->where('login_time', '<=', $end);
                    break;
                case self::OfflineTimeColumn:
                    $query->where('off_time', '>=', $start)
                        ->where('off_time', '<=', $end);
                    break;
                case self::RegisterTimeColumn:
                default:
                    $query->where('register_time', '>=', $start)
                        ->where('register_time', '<=', $end);
            }
        }

        $total = $query->count();

        $direction = $sort === Query::ASC ? 'asc' : 'desc';
        switch ($orderBy) {
            case self::OrderByCoin:
                $query->orderBy('gold', $direction);
                break;
            case self::OrderByTicket:
                $query->orderBy('score', $direction);
                break;
            default:
                $query->orderBy('role_id', 'desc');
                break;
        }

        $collection = $query->offset($offset)
            ->limit($limit)
            ->get();
        return [
            'total' => $total,
            'list' => $this->translateLocalPlayers($collection)
        ];
    }

    public function translateLocalPlayers(Collection $collection): array
    {
        return $collection->map(function ($model) {
            $status = Player::Disabled;
            if ($model->black <= time()) {
                $status = Player::Enabled;
            }

            return [
                'id' => $model->role_id,
                'name' => $model->name,
                'icon' => $model->icon,
                'login_time' => to_datetime_string($model->login_time),
                'register_time' => to_datetime_string($model->register_time),
                'offline_time' => to_datetime_string($model->off_time),
                'coin' => $model->gold,
                'diamond' => $model->diamond,
                'ticket' => $model->score,
                'vip' => $model->vip,
                'phone' => $model->phone,
                'status' => $status,
                'locked_time' => $model->black ? to_datetime_string($model->black) : '-'
            ];
        })->all();
    }

    public function translatePlayer(Player $model)
    {
        if ($model->black <= time()) {
            $status = Player::Enabled;
        } else {
            $status = Player::Disabled;
        }

        return [
            'id' => $model->role_id,
            'name' => $model->name,
            'icon' => $model->icon,
            'login_time' => to_datetime_string($model->login_time),
            'register_time' => to_datetime_string($model->register_time),
            'coin' => $model->gold,
            'diamond' => $model->diamond,
            'ticket' => $model->score,
            'vip' => $model->vip,
            'phone' => $model->phone,
            'status' => $status,
            'locked_time' => $model->black ? to_datetime_string($model->black) : '-'
        ];
    }
}
