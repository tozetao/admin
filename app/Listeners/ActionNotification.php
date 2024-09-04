<?php

namespace App\Listeners;

use App\Biz\ClassifyBiz;
use App\Events\Action;
use App\Models\ActionLog;
use App\Models\Machine;
use App\Models\MainboardExtra;
use App\Models\TypeConversion\CoinSwitchConversion;
use App\Models\TypeConversion\MachineConversion;
use Illuminate\Support\Facades\Log;

class ActionNotification
{
    const PlayerGive = 'PlayerController@give';
    const PlayerDeduct = 'PlayerController@deduct';
    const PlayerLock = 'PlayerController@lock';
    const PlayerUnLock = 'PlayerController@unlock';
    const PlayerKick = 'PlayerController@kick';
    const MachineCreate = 'MachineController@create';
    const MachineUpdate = 'MachineController@update';
    const MachineKick = 'MachineController@kick';
    const MachineClear = 'MachineController@clear';
    const MachineChangeDeleteStatus = 'MachineController@changeDeleteStatus';
    const ShareMachineCreate = 'ShareMachineController@create';
    const ShareMachineUpdate = 'ShareMachineController@update';

    const MotherboardBind = 'MotherboardController@bind';
    const MotherboardUnbind = 'MotherboardController@unbind';
    const MotherboardUpdate = 'MotherboardController@update';

    private $actionMap = [
        'PlayerController@give' => '赠送玩家货币',
        'PlayerController@deduct' => '扣除玩家货币',
        'MachineController@create' => '创建机台',

        'MachineController@changeDeleteStatus' => '机台上下架',
        'ShareMachineController@create' => '创建共享机台',
        'ShareMachineController@update' => '编辑共享机台',

        self::MotherboardBind => '绑定板子',
        self::MotherboardUnbind => '板子解绑',
        self::MotherboardUpdate => '编辑板子设置',
        self::MachineUpdate => '编辑机台',
        self::MachineKick  => '机台踢人',
        self::MachineClear => '清空机台',
        self::PlayerLock => '锁定玩家',
        self::PlayerUnLock => '解绑玩家',
        self::PlayerKick => '玩家下机',
    ];

    /**
     * Handle the event.
     *
     * @param  Action  $event
     * @return void
     */
    public function handle(Action $event)
    {
        $model = new ActionLog();
        $model->fill([
            'ip' => $event->ip,
            'data' => \json_encode($event->postData),
            'user_id' => $event->userId,
            'action' => $event->action,
            'server_no' => $event->serverNo,
            'created_at' => time(),
            'title' => $this->translateTitle($event->action),
            'content' => $this->translateContent($event->action, $event->postData, $event->oldData),
        ]);
        $model->save();
    }

    private function translateTitle($action): string
    {
        return $this->actionMap[$action] ?? $action;
    }

    // 解析内容，判断有哪些内容发生了变化。
    private function translateContent($action, $postData, $oldData)
    {
        if ($action === self::PlayerGive || $action === self::PlayerDeduct) {
            $id = $postData['id'] ?? 0;
            $type = $postData['type'] ?? 0;
            $value = $postData['value'] ?? 0;
            return sprintf('玩家id: %d, 货币类型: %s, 数额: %d', $id, $this->translateCurrencyType($type), $value);
        }

        if ($action === self::PlayerLock) {
            $id = $postData['id'] ?? 0;
            $days = $postData['days'] ?? 0;
            return sprintf('玩家id: %d, 天数: %d', $id, $days);
        }

        if ($action == self::PlayerUnLock) {
            $id = $postData['id'] ?? 0;
            return sprintf('玩家id: %d', $id);
        }

        if ($action == self::MachineCreate) {
            return sprintf('机台名: %s', $postData['name'] ?? '');
        }

        if ($action == self::MachineUpdate) {
            return $this->parseMachineUpdateColumns($postData, $oldData);
        }

        if ($action == self::MachineKick || $action == self::MachineClear) {
            $machineId = $postData['id'] ?? 0;
            $machineName = $oldData['name'] ?? '-';
            return sprintf('机台id: %d, 机台名: %s', $machineId, $machineName);
        }

        if ($action == self::MachineChangeDeleteStatus) {
            return sprintf('机台id: %d, 状态: %d', $postData['id'] ?? 0, $postData['status'] ?? 0);
        }

        if ($action == self::MotherboardUpdate) {
            return $this->parseMotherboardUpdateColumns($postData, $oldData);
        }

        if ($action == self::MotherboardBind) {
            $code = $postData['code'] ?? '';
            $machineId = $postData['machine_id'] ?? 0;
            $seat = $postData['seat'] ?? 0;
            return sprintf('板子id: %s, 机台id: %s, 座位: [%dP]', $code, $machineId, $seat);
        }

        if ($action == self::MotherboardUnbind) {
            return sprintf('板子id: %s, 机台id: %s', $postData['code'] ?? '-', $oldData['machine_id'] ?? 0);
        }

        return \json_encode($postData);
    }

    private function parseMachineUpdateColumns($postData, $oldData): string
    {
        unset($postData['server_nos']);

        $rules = [
            'name' => '机器名: %s',
            'classify_id' => [
                '类型: [%s]',
                function($value) {
                    $classifyBiz = app(ClassifyBiz::class);
                    return $classifyBiz->getChildTypeName($value);
                }
            ],
            'admission_currency_type' => [
                '入场货币: [%s]',
                function($value) { return $this->translateCurrencyType($value); }
            ],
            'admission_num' => '入场数额: [%d]',
            'win_type' => [
                '产出货币: [%s]',
                function($value) { return $this->translateCurrencyType($value); }
            ],
            'state' => [
                '状态: [%s]',
                function($value) { return MachineConversion::stateToLabel($value); }
            ],
            'wawa_num' => '保夹次数: [%d]',
            'balance_award_type' => [
                '结算奖品类型: [%s]',
                function($value) { return MachineConversion::prizeTypeToLabel($value); }
            ],
            'gifts_id' => '奖品id: [%d]',
            'machine_desc' => '游戏说明: [%s]',
            //            'desc' => '描述: [%s]',
        ];

        $diff = array_diff_assoc($postData, $oldData);

        $labels = [
            sprintf('机器id: %d', $postData['id'])
        ];
        foreach ($diff as $column => $value) {
            if (isset($rules[$column])) {
                $rule = $rules[$column];
                if (is_array($rule)) {
                    $labels[] = sprintf($rule[0], $rule[1]($value));
                } else {
                    $labels[] = sprintf($rules[$column], $value);
                }
            }
        }

        return implode(',', $labels);
    }

    // 解析板子更新字段
    private function parseMotherboardUpdateColumns($postData, $oldData): string
    {
        $labels = [];

//        $labels[] = '机台id: ' . $postData['code'] ?? '-';
        $machineId = $oldData['room_id'] ?? 0;
        $labels[] = '机台id: ' . $machineId;

        $pulse1 = $postData['pulse'] ?? 0;
        $pulse2 = $oldData['pulse'] ?? 0;
        if ($pulse1 != $pulse2) {
            $labels[] = '脉冲宽度: [' . $pulse1 . ']';
        }

        $coinSwitch1 = $postData['coin_switch'] ?? 0;
        $coinSwitch2 = $oldData['open_close'] ?? 0;
        if ($coinSwitch1 != $coinSwitch2) {
            $labels[] = '投币开关: [' . CoinSwitchConversion::toLabel($coinSwitch1) . ']';
        }

        $operationPattern1 = $postData['operation_pattern'] ?? 0;
        $operationPattern2 = $oldData['is_online'] ?? 0;
        if ($operationPattern1 != $operationPattern2) {
            $labels[] = '运营模式: ' . ($operationPattern1 ? '[线上]' : '[线下]');
        }

        $rewardType1 = $postData['reward_type'] ?: 0;
        $rewardType2 = $oldData['reward_type'] ?: 0;
        if ($rewardType1 != $rewardType2) {
            $labels[] = '结算模式: ' . ($rewardType1 ? '[退币]': '[退票]');
        }

        $toggle = $postData['toggle'] ?? 0;
        $extra = MainboardExtra::query()
            ->where('code', $postData['code'])
            ->first();

        if (empty($extra) || $extra->toggle != $toggle) {
            if ($toggle) {
                $start = $postData['start'] ?? 0;
                $end = $postData['end'] ?? 0;
                $labels[] = '自动切换: [启用], 上线时间: [' . $start . ']点, 下线时间: [' . $end . ']点';
            } else {
                $labels[] = '自动切换: [禁用]';
            }
        }

        return implode(',', $labels);
    }

    private function translateCurrencyType($type): string
    {
        if (Machine::Coin5G == $type) {
            return '5G币';
        }
        if (Machine::Ticket == $type) {
            return '5G票';
        }
        if (Machine::ShareCoin5G == $type) {
            return '5G乐享币';
        }
        if (Machine::ShareTicket5G == $type) {
            return '5G乐享票';
        }
        return $type;
    }
}
