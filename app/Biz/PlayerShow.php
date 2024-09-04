<?php

namespace App\Biz;

use App\Exceptions\Api\ApiException;
use App\Models\Player;
use App\Models\ShareCoinLog;
use App\Models\ShareTicketLog;
use App\Repository\MachineRepository;
use App\Repository\OrderRepository;
use App\Repository\PlayerRepository;
use App\Repository\RewardLogRepository;
use App\Repository\Statistics\PlayerStatistics;
use Illuminate\Support\Facades\DB;

class PlayerShow
{
    private $rewardLogRepository;
    private $machineRepository;
    private $orderRepository;
    private $playerStatistics;
    private $playerRepository;
    private $classifyBiz;

    public function __construct(PlayerRepository $playerRepository, RewardLogRepository $rewardLogRepository, ClassifyBiz $classifyBiz,
        MachineRepository $machineRepository, OrderRepository $orderRepository, PlayerStatistics $playerStatistics)
    {
        $this->playerRepository = $playerRepository;
        $this->playerStatistics = $playerStatistics;
        $this->rewardLogRepository = $rewardLogRepository;
        $this->machineRepository = $machineRepository;
        $this->orderRepository = $orderRepository;
        $this->classifyBiz = $classifyBiz;
    }

    public function execute($playerId)
    {
        $player = $this->playerRepository->findOneBy(['role_id' => $playerId]);

        if (!$player) {
            throw new ApiException(trans('err.not_found'));
        }

        // 玩家总奖品数
        $totalRewards = $this->rewardLogRepository->getPlayerTotalRewards($playerId);
        // 玩家总兑换，即玩家的order订单数
        $totalOrders = $this->orderRepository->getPlayerTotalOrders($playerId);

        // 玩家的机台投币统计
        $putCoins = $this->playerStatistics->machinesPutCoin($playerId);
        // 玩家的机台退票统计
        $refundTickets = $this->playerStatistics->machinesRefundTicket($playerId);

        // 机台类型，该机台类型的统计信息
        // 1. 查询这些机台的类型
        // 2. 所以机台按照机台类型进行分组。
        // 3. 根据分组去拿去数据。
        $putCoinMap = $putCoins->pluck('cost_value', 'machine_id')->all();
        $refundTicketMap = $refundTickets->pluck('add_value', 'machine_id')->all();
        $machineIds = $putCoins->pluck('machine_id')->merge($refundTickets->pluck('machine_id'))->unique()->all();
        $machines = $this->machineRepository->findIn($machineIds, ['id', 'classify_id']);
        $groupedMachines = $machines->groupBy('classify_id');

        $stats = [];
        foreach ($groupedMachines as $type => $models) {
            $stats[$type] = [
                'type' => $type,
                'type_name' => $this->classifyBiz->getChildTypeName($type),
                'cost_value' => 0,
                'add_value' => 0
            ];
            foreach ($models as $model) {
                // 先拿投币数据，再拿退票数据
                if (isset($putCoinMap[$model->id])) {
                    $stats[$type]['cost_value'] += $putCoinMap[$model->id];
                }
                if (isset($refundTicketMap[$model->id])) {
                    $stats[$type]['add_value'] += $refundTicketMap[$model->id];
                }
            }
        }

        // 玩家乐享币总投、乐享币总退
        $remaining = $this->remainingCoinsAndTickets($playerId);

        $data = [
            'total_rewards' => $totalRewards,
            'total_orders' => $totalOrders,
            'stats' => array_values($stats),
            'remaining_coin' => $remaining['remaining_coins'],
            'remaining_tickets' => $remaining['remaining_tickets'],
        ];
        return array_merge($this->translatePlayer($player), $data);
    }


    // 玩家剩余5G乐享币、5G乐享票的统计
    private function remainingCoinsAndTickets($playerId): array
    {
        $coinStat = ShareCoinLog::query()
            ->select(DB::raw('sum(add_value) as add_value, sum(cost_value) as cost_value'))
            ->where('role_id', $playerId)
            ->first();
        $remainingCoins = 0;
        if ($coinStat) {
            $addValue = $coinStat->add_value ?: 0;
            $costValue = $coinStat->cost_value ?: 0;
            $remainingCoins = $addValue - $costValue;
        }

        $ticketStat = ShareTicketLog::query()
            ->selectRaw('sum(add_value) as add_value, sum(cost_value) as cost_value')
            ->where('role_id', $playerId)
            ->first();

        $remainingTickets = 0;

        if ($ticketStat) {
            $addValue = $ticketStat->add_value ?: 0;
            $costValue = $ticketStat->cost_value ?: 0;
            $remainingTickets = $addValue - $costValue;
        }

        return [
            'remaining_coins' => $remainingCoins,
            'remaining_tickets' => $remainingTickets
        ];
    }

    public function translatePlayer(Player $model): array
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
            'off_time' => to_datetime_string($model->off_time),
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
