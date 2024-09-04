<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameLog extends Model
{
    // 1114房间内投币
    public const PutCoin = 1114;

    // 1103房间占位
    public const TakeSeat = 1103;

    public const MachineRefundTicket = 4;
    public const AutoRefundTicket = 5;

    public const BackendSend = 1;
    public const GMModifyData = 2;
    public const BackendRecharge = 3;
    public const MachineRefund = 4;
    public const AutoRefund = 5;

    public const DailyTaskReward = 1117;
    public const AchievementAwards = 1118;
    public const SignIn = 1178;
    public const GetRestReward = 1186;
    public const GetTaskReward = 1185;
    public const ReceiveEmail = 1505;
    public const Lock = 1139;
    public const ThirdPartyExchange = 1150;
    public const MailReward = 1502;
    public const DollToTicket = 1140;
    public const ShopExchange = 1302;
    public const BindPhone = 1138;
    public const FriendReward = 1125;
    public const ExchangeTicket = 1123;

    public $timestamps = false;

    public function player()
    {
        return $this->belongsTo(Player::class, 'role_id', 'role_id');
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_id', 'id');
    }

    public static function translateType($value): string
    {
        $content = '-';
        switch ($value) {
            case self::BackendSend:
                $content = '后台赠送';
                break;
            case self::GMModifyData:
                $content = 'GM修改数据';
                break;
            case self::BackendRecharge:
                $content = '后台充值';
                break;
            case self::MachineRefund:
                $content = '机器退票';
                break;
            case self::AutoRefund:
                $content = '自动退票';
                break;
            case self::TakeSeat:
                $content = '房间占位';
                break;
            case self::PutCoin:
                $content = '房间内投币';
                break;
            case self::DailyTaskReward:
                $content = '每日任务奖励';
                break;
            case self::AchievementAwards:
                $content = '成就奖励';
                break;
            case self::ExchangeTicket:
                $content = '票兑换';
                break;
            case self::FriendReward:
                $content = '好友福利';
                break;
            case self::BindPhone:
                $content = '绑定手机';
                break;
            case self::ShopExchange:
                $content = '商城兑换';
                break;
            case self::DollToTicket:
                $content = '娃娃对换票';
                break;
            case self::MailReward:
                $content = '邮件奖励';
                break;
            case self::ThirdPartyExchange:
                $content = '第三方兑换';
                break;
            case self::Lock:
                $content = '锁机';
                break;
            case self::ReceiveEmail:
                $content = '邮件领取';
                break;
            case self::GetTaskReward:
                $content = '领取任务奖励';
                break;
            case self::GetRestReward:
                $content = '领取任务额外奖励';
                break;
            case self::SignIn:
                $content = '签到';
                break;
        }
        return $content;
    }
}
