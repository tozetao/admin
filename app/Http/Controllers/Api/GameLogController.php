<?php

namespace App\Http\Controllers\Api;

use App\Biz\GameLogQuery;
use App\Exceptions\Api\ApiException;
use App\Http\Controllers\Controller;
use App\Models\GameLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameLogController extends Controller
{
    public function index(Request $request, GameLogQuery $query)
    {
        $page = $request->get('page');
        $pageSize = $request->get('page_size', 10);

        $modelType = $request->get('model_type', GameLogQuery::Coin);
        $startDate = $request->get('start');
        $endDate = $request->get('end');

        $this->validateDate($startDate, $endDate);

        $type = $request->get('type');
        $playerId = $request->get('player_id');
        $machineId = $request->get('machine_id');

        $serverNo = $this->getActiveServerNo(Auth::user(), $request);

        $data = $query->execute($serverNo, $modelType, $playerId, $machineId, $type, $startDate, $endDate, $page, $pageSize);
        return $this->data($data);
    }



    public function export(Request $request, GameLogQuery $query)
    {
        $page = $request->get('page');
        $pageSize = $request->get('page_size', 10);

        $modelType = $request->get('model_type', GameLogQuery::Coin);
        $startDate = $request->get('start');
        $endDate = $request->get('end');

        $this->validateDate($startDate, $endDate);

        $type = $request->get('type');
        $playerId = $request->get('player_id');
        $machineId = $request->get('machine_id');

        $serverNo = $this->getActiveServerNo(Auth::user(), $request);

        $data = $query->getExportData($serverNo, $modelType, $playerId, $machineId, $type, $startDate, $endDate, $page, $pageSize);

        $data = array_map(function ($row) use($modelType) {
            $type = $row['type'];
            // BackendSend要根据值的正负来判断
            if ($type == GameLog::BackendSend) {
                if ($row['add_value'] > 0) {
                    $row['value'] = '+' . $row['add_value'];
                } else {
                    $row['value'] = '-' . $row['cost_value'];
                }
                return $row;
            }
            // ThirdPartyExchange币的第三方兑换+，票的第三方兑换-
            if ($type == GameLog::ThirdPartyExchange) {
                if ($modelType == GameLogQuery::Ticket || $modelType === GameLogQuery::ShareTicket) {
                    $row['value'] = '-' . $row['cost_value'];
                } else {
                    $row['value'] = '+' . $row['add_value'];
                }
                return $row;
            }
            // const incrTypes = [1, 3, 4, 5, 1117, 1118, 1123, 1125, 1138, 1502, 1505, 1140, 1150]
            $incrTypes = [
                GameLog::BackendSend, GameLog::BackendRecharge, GameLog::AutoRefund, GameLog::MachineRefund, GameLog::DailyTaskReward,
                GameLog::AchievementAwards, GameLog::ExchangeTicket, GameLog::FriendReward, GameLog::BindPhone, GameLog::MailReward, GameLog::ReceiveEmail,
                GameLog::DollToTicket, GameLog::SignIn, GameLog::GetRestReward, GameLog::GetTaskReward];
            if (in_array($row['type'], $incrTypes)) {
                $row['value'] = '+' . $row['add_value'];
            } else {
                $row['value'] = '-' . $row['cost_value'];
            }
            return $row;
        }, $data);

        $columns = [
            'player_id' => '玩家ID',
            'player_name' => '玩家昵称',
            'type' => '操作类型',
            'value_before' => '变更前',
            'value_after' => '变更后',
            'value' => '增加/减少',
            'machine_id' => '机台ID',
            'machine_name' => '机台名',
            'machine_seat' => '机台座位',
            'time' => '创建时间'
        ];
        $excelHandler = app('excel_handler');
        $excelHandler->write($columns, $data, '游戏日志', [
            'type' => function($type) {
                return GameLog::translateType($type);
            },
            'machine_seat' => function($val) {
                return $val . 'P';
            }
        ]);
    }
}
