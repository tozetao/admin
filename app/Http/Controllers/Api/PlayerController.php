<?php

namespace App\Http\Controllers\Api;

use App\Biz\PlayerQuery;
use App\Biz\PlayerShow;
use App\Events\Action;
use App\Exceptions\Api\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Repository\PlayerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlayerController extends Controller
{
    public function index(Request $request, PlayerQuery $query)
    {
        $queryType = $request->get('query_type', 0);
        $keyword = $request->get('keyword', '');

        $start = $request->get('start');
        $end = $request->get('end');

        $status = $request->get('status', Player::Enabled);

        $orderBy = $request->get('order_by');
        $direction = $request->get('direction');
        $timeColumn = $request->get('time_column');

        $page = $request->get('page');
        $pageSize = $request->get('page_size');

        $data = $query->execute($queryType, $keyword, $status, $start, $end, $timeColumn, $orderBy, $direction, $page, $pageSize);

        $export = (int)$request->get('export');

        if ($export) {
            $columns = [
                'id' => '玩家ID',
                'name' => '昵称',
                'coin' => '5G币',
                'ticket' => '5G票',
                'register_time' => '注册时间',
            ];
            $excelHandler = app('excel_handler');
            $excelHandler->write($columns, $data['list'], '玩家列表');
        } else {
            return $this->data($data);
        }
    }

    // 玩家详情
    public function show(Request $request, PlayerShow $playerShow)
    {
        $playerId = $request->get('id');
        return $this->data($playerShow->execute($playerId));
    }

    private function findPlayer($id, PlayerRepository $repository)
    {
        $player = $repository->findOneBy(['role_id' => $id]);
        if (!$player) {
            throw new ApiException(trans('err.not_found'));
        }
        return $player;
    }

    // 改变玩家状态，启用、禁用
    public function changeStatus()
    {
    }

    // 赠送
    public function give(Request $request, PlayerRepository $repository)
    {
        $this->validateChangeAssert($request, $repository);
        $type = $request->post('type');
        $playerId = $request->post('id');
        $amount = $request->post('value');
        return $this->changePlayerAssert($request, $playerId, $type, $amount);
    }

    // 扣除玩家的货币
    public function deduct(Request $request, PlayerRepository $repository)
    {
        $this->validateChangeAssert($request, $repository);

        $type = $request->post('type');
        $playerId = $request->post('id');
        $amount = $request->post('value');
        $amount *= -1;

        return $this->changePlayerAssert($request, $playerId, $type, $amount);
    }

    private function validateChangeAssert(Request $request, PlayerRepository $repository)
    {
        $this->validate($request, [
            'type' => 'required|integer|in:1,2,3,4,5',
            'value' => 'required|integer|gt:0',
            'id' => 'required|integer'
        ], [], [
            'type' => '赠送类型',
            'value' => '数额'
        ]);
        $playerId = $request->post('id');

        if (!$repository->exists($playerId)) {
            throw new ApiException('不存在的玩家');
        }

        $user = Auth::user();
        if (!$user->isCentralAdmin() && !$user->isDistributor()) {
            throw new ApiException(trans('err.authorize'));
        }
    }

    private function changePlayerAssert(Request $request, $playerId, $type, $amount)
    {
        $gameApi = app('game_api');
        $user = Auth::user();
        $uid = $user->getAuthIdentifier();
        if (!$gameApi->changeAssert($playerId, $type, $amount, $uid)) {
            return $this->fail('操作失败');
        }
        event(new Action($request, $user));
        return $this->success();
    }

    // 锁定
    public function lock(Request $request, PlayerRepository $repository)
    {
        $this->validate($request, [
            'days' => 'required|integer|min:0'
        ]);

        $id = $request->post('id');
        $days = $request->post('days');

        $player = $this->findPlayer($id, $repository);

        $gameApi = app('game_api');
        if (!$gameApi->lockPlayer($player->role_id, $days)) {
            return $this->fail('failed');
        }
        event(new Action($request, Auth::user()));
        return $this->success();
    }

    // 解除绑定
    public function unlock(Request $request, PlayerRepository $repository)
    {
        $id = $request->post('id');
        $days = $request->post('days');

        $player = $this->findPlayer($id, $repository);

        $gameApi = app('game_api');
        if (!$gameApi->unlockPlayer($player->role_id, $days)) {
            return $this->fail('failed');
        }
        event(new Action($request, Auth::user()));
        return $this->success();
    }

    public function kick(Request $request)
    {
        // 机器id
        $machineId = $request->post('machine_id');

        $gameApi = app('game_api');
        $result = $gameApi->kickPlayersOfMachine($machineId);
        if (!$result) {
            return $this->fail('操作失败!');
        }
        event(new Action($request, Auth::user()));
        return $this->success();
    }
}
