<?php

namespace App\SDK;

use App\Models\Machine;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

class GameApi
{
    private $url;
    private $host;
    private $port;

    public function init($host, $port, $timeout = 5)
    {
        $this->url  = "http://%s:%d/web_rpc?%s";
        $this->host = $host;
        $this->port = $port;
        $this->client = new Client([
            'timeout' => $timeout
        ]);
        return $this;
    }

    public function get($mod, $function, $args = [])
    {
        $query = [
            'mod' => $mod,
            'function' => $function,
            'args' => \json_encode($args)
        ];
        $url = sprintf($this->url, $this->host, $this->port, http_build_query($query));

        try {
            $request = new Request('get', $url);
            $response = $this->client->send($request);

            $content = $response->getBody()->getContents();
            $result = \json_decode($content, true);

//            Log::info($url);
//            Log::info($content);

            if (is_array($result)) {
                if (isset($result['value']) && $result['value'] === 'not_set') {
                    return null;
                }
                return $result;
            }
            return $content;
//            if (isset($result['value']) && $result['value'] === 'not_set') {
//                return null;
//            }
//            return $result;
        } catch (ServerException $exception) {
            // 500状态码的处理
            $response = $exception->getResponse();
            $code = $response->getStatusCode();
            if ($code == 500) {
                $result = $response->getBody()->getContents();
                $error = sprintf('500 status code returned when requesting game interface, url: %s, result: %s', $url, $result);
                Log::error($error);
                return \json_decode($result);
            } else {
                Log::error(sprintf('status code: %d, url: %s, %s', $code, $url, $exception->getMessage()));
            }
            return false;
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return false;
        }
    }




































    // 下面是要删除的

    public function reloadServers()
    {
        // 'http://127.0.0.1:10001/web_rpc?args=[]&mod=cross_mgr&function=reload_cfg';
        return $this->get('cross_mgr', 'reload_cfg');
    }

    public function reloadMachines($ids)
    {
        // 'http://127.0.0.1:10001/web_rpc?args='.json_encode(['value'=>$type],JSON_UNESCAPED_UNICODE).'&mod=setting_mgr&function=reload';

        // machine_data  init  []
        // machine_data  do_init [ID1, id2]

        return $this->get('machine_data', 'do_init', [
            'value' => $ids
        ]);
    }

    public function reloadAllMachine()
    {
        return $this->get('setting_mgr', 'reload', ['type' => 11]);
    }

    // id, seat, type, value
    // type，1是运营模式、2是pulse、3是投推币开关、4是结算模式。
    // 设置板子信息
    public function setMotherboard(int $machineId, int $seat, int $type, int $value)
    {
        // ['room_id'=>$post['room_id'],'seat_id'=>$post['seat'],'code'=>$row['code'],'time'=>time()];
        return $this->get('machine', 'set_machine_info', [
            'machine_id' => $machineId,
            'seat' => $seat,
            'type' => $type,
            'value' => $value
        ]);
    }

    public function allOnlinePlayers()
    {
        // args=[]&mod=role_data&function=get_all_online
        return $this->get('role_data', 'get_all_online');
    }

    // 在线玩家数量
    public function onlinePlayersNumber(): int
    {
        $array = $this->allOnlinePlayers();
        if (empty($array) || !is_array($array)) {
            return 0;
        }
        return count($array);
    }

    public function lockPlayer(int $id, int $days)
    {
        // ['api_host'].'?args='.json_encode(['id'=>$post['black_role'],'time'=>$post['black_date']*86400],JSON_UNESCAPED_UNICODE).'&mod=role_data&function=set_black';
        $args = [
            'id' => $id,
            'time' => $days * 86400
        ];
        return $this->get('role_data', 'set_black', $args);
    }

    public function unlockPlayer(int $id)
    {
        // args='.json_encode(['role_id'=>$post['role_id']],JSON_UNESCAPED_UNICODE).'&mod=youcaihua&function=unbind';
        return $this->get('youcaihua', 'unbind', [
            'id' => $id
        ]);
    }

    const Goods = 4;
    public function reloadGoods()
    {
        // http://127.0.0.1:10001/web_rpc?args='.json_encode(['value'=>$type],JSON_UNESCAPED_UNICODE).'&mod=setting_mgr&function=reload
        return $this->get('setting_mgr', 'reload', ['value' => self::Goods]);
    }

    const LockingMachineSetting = 13;
    public function reloadLockingMachineSetting()
    {
        // $url = $GLOBALS['hostConfig']['api_host'].'?args='.json_encode(['value'=>$type],JSON_UNESCAPED_UNICODE).'&mod=setting_mgr&function=reload';
        return $this->get('setting_mgr', 'reload', ['value' => self::LockingMachineSetting]);
    }

    public function recharge(int $playerId, int $type, int $num)
    {
        // &mod=charge&function=web_charge'
        $data = [
            'role_id' => $playerId,
            'type' => $type,
            'num' => $num
        ];
        return $this->get('charge', 'web_charge', $data);
    }

    // 改变玩家的资产
    public function changeAssert(int $playerId, int $type, int $num, int $actorId)
    {
        // mod=charge&function=web_change_assets
        $data = [
            'role_id' => $playerId,
            'type' => $type,
            'num' => $num,
            'actor_id' => $actorId
        ];
        return $this->get('charge', 'web_change_assets', $data);
    }

    // 之前是129，不知道设置到哪个配置了。
    const SystemTicketTo5GTicket = 109;

    // 5G票兑换比例
    // 系统票：5G票
    // $url = $GLOBALS['hostConfig']['api_host'].'?args={"type":109,"value":['.implode(',',$g_score).']}&mod=setting_mgr&function=web_set';
    public function setSystemTicketTo5GTicket(int $leftOperand, int $rightOperand)
    {
        return $this->get('setting_mgr', 'web_set', [
            'type' => self::SystemTicketTo5GTicket,
            'value' => [$leftOperand, $rightOperand]
        ]);
    }

    public function getSystemTicketTo5GTicket(): string
    {
        $result = $this->get('setting_mgr', 'web_get', [
            'type' => self::SystemTicketTo5GTicket
        ]);
        if ($value = $result['value'] ?? false) {
            return implode(':', $value);
        }
        return '';
    }

    const ThirdPartyExchange = 102;

    // 第三方兑换比例
    // $GLOBALS['hostConfig']['api_host'].'?args={"type":102,"value":['.implode(',',$exchange).']}&mod=setting_mgr&function=web_set';
    public function setThirdPartyExchange(int $leftOperand, int $rightOperand)
    {
        return $this->get('setting_mgr', 'web_set', [
            'type' =>  self::ThirdPartyExchange,
            'value' => [$leftOperand, $rightOperand]
        ]);
    }

    public function getThirdPartyExchange(): string
    {
        $result = $this->get('setting_mgr', 'web_get', [
            'type' =>  self::ThirdPartyExchange
        ]);
        if ($value = $result['value'] ?? null) {
            return implode(':', $value);
        }
        return '';
    }

    const SystemTicketToPoint = 118;

    public function setSystemTicketToPoint(int $leftOperand, int $rightOperand)
    {
        return $this->get('setting_mgr', 'web_set', [
            'type' =>  self::SystemTicketToPoint,
            'value' => [$leftOperand, $rightOperand]
        ]);
    }

    public function getSystemTicketToPoint(): string
    {
        $result = $this->get('setting_mgr', 'web_get', [
            'type' =>  self::SystemTicketToPoint
        ]);
        if ($value = $result['value'] ?? null) {
            return implode(':', $value);
        }
        return '';
    }

    const SystemTicketToShareTicket = 112;
    // 系统票：5g蓝票
    // 系统票：5G分享票
    // $GLOBALS['hostConfig']['api_host'].'?args={"type":112,"value":['.implode(',',$blueTicket).']}&mod=setting_mgr&function=web_set';
    public function setSystemTicketToShareTicket(int $leftOperand, int $rightOperand)
    {
        return $this->get('setting_mgr', 'web_set', [
            'type' =>  self::SystemTicketToShareTicket,
            'value' => [$leftOperand, $rightOperand]
        ]);
    }

    public function getSystemTicketToShareTicket()
    {
        $result = $this->get('setting_mgr', 'web_get', [
            'type' =>  self::SystemTicketToShareTicket
        ]);
        $value = $result['value'] ?? '';
        if ($value) {
            return implode(':', $value);
        }
        return $value;
    }

    const SystemCoinToShareCoin = 111;

    // 门店币：5g赢币
    // 系统币：5G乐享币
    // $url = $GLOBALS['hostConfig']['api_host'].'?args={"type":111,"value":['.implode(',',$zhuazhuabi).']}&mod=setting_mgr&function=web_set';
    public function setSystemCoinToShareCoin(int $leftOperand, int $rightOperand)
    {
        return $this->get('setting_mgr', 'web_set', [
            'type' => self::SystemCoinToShareCoin,
            'value' => [$leftOperand, $rightOperand]
        ]);
    }

    public function getSystemCoinToShareCoin()
    {
        $result = $this->get('setting_mgr', 'web_get', [
            'type' => self::SystemCoinToShareCoin
        ]);
        $value = $result['value'] ?? '';
        if ($value) {
            return implode(':', $value);
        }
        return $value;
    }

    const SystemTicketToSystemCoin = 119;

    public function setSystemTicketToSystemCoin(int $leftOperand, int $rightOperand)
    {
        return $this->get('setting_mgr', 'web_set', [
            'type' => self::SystemTicketToSystemCoin,
            'value' => [$leftOperand, $rightOperand]
        ]);
    }

    public function getSystemTicketToSystemCoin()
    {
        $result = $this->get('setting_mgr', 'web_get', [
            'type' => self::SystemTicketToSystemCoin
        ]);
        $value = $result['value'] ?? '';
        if ($value) {
            return implode(':', $value);
        }
        return $value;
    }

    // 微信客服号设置
    // $url = $GLOBALS['hostConfig']['api_host'].'?args={"type":101,"value":"'.$post['customer'].'"}&mod=setting_mgr&function=web_set';

    const ShareGiveCoin = 110;
    // 分享赠送币
    //$url = $GLOBALS['hostConfig']['api_host'].'?args={"type":110,"value":"'.intval($post['share_gold']).'"}&mod=setting_mgr&function=web_set';
    public function setShareGiveCoin(int $value)
    {
        return $this->get('setting_mgr', 'web_set', [
            'type' => self::ShareGiveCoin,
            'value' => $value
        ]);
    }

    public function getShareGiveCoin()
    {
        $result = $this->get('setting_mgr', 'web_get', [
            'type' => self::ShareGiveCoin
        ]);
        return $result['value'] ?? 0;
    }

    const ShareGiveDiamond = 105;

    // 分享赠送钻石数的设置
    // $url = $GLOBALS['hostConfig']['api_host'].'?args={"type":105,"value":"'.$post['share_diamond'].'"}&mod=setting_mgr&function=web_set';

    public function setShareGiveDiamond(int $value)
    {
        return $this->get('setting_mgr', 'web_set', ['type' => self::ShareGiveDiamond, 'value' => $value]);
    }

    public function getShareGiveDiamond()
    {
        $result = $this->get('setting_mgr', 'web_get', ['type' => self::ShareGiveDiamond]);
        return $result['value'] ?? 0;
    }

    // 钻石赠送：钻石赠送比例、钻石赠送数量上限、钻石赠送次数上限
    // $url = $GLOBALS['hostConfig']['api_host'].'?args={"type":103,"value":['.intval($post['diamonds']).','.intval($post['diamonds_max']).','.intval($post['diamonds_num']).']}&mod=setting_mgr&function=web_set';

    // 娃娃兑换开关
    // $url = $GLOBALS['hostConfig']['api_host'].'?args={"type":104,"value":"'.$post['wawa_exchange'].'"}&mod=setting_mgr&function=web_set';

    // 关服
    // $url = $GLOBALS['hostConfig']['api_host'].'?args={"type":106,"value":0}&mod=setting_mgr&function=web_set';
    // 开服
    // $url = $GLOBALS['hostConfig']['api_host'].'?args={"type":106,"value":1}&mod=setting_mgr&function=web_set';

    const ExchangeUpperLimit = 10002;

    public function setExchangeUpperLimit(int $value)
    {
        return $this->get('setting_mgr', 'web_set', [
            'type' => self::ExchangeUpperLimit,
            'value' => $value
        ]);
    }

    public function getExchangeUpperLimit()
    {
        $result = $this->get('setting_mgr', 'web_get', ['type' => self::ExchangeUpperLimit]);
        return $result['value'] ?? 0;
    }

    const StopStatus = 106;

    public function getStopServiceStatus(): int
    {
        // $url = $GLOBALS['hostConfig']['api_host'].'?args={"type":106,"value":1}&mod=setting_mgr&function=web_set';
        $result = $this->get('setting_mgr', 'web_get', [
            'type' => self::StopStatus
        ]);
        $value = $result['value'] ?? false;
        return $value ? 1: 0;
    }

    public function setStopServiceStatus(int $value)
    {
        return $this->get('setting_mgr', 'web_set', [
            'type' => self::StopStatus,
            'value' => $value
        ]);
    }

    const AutoStart = 107;

    public function setAutoStart(int $start, int $end)
    {
        // 自动模式，在开服时需要将其设置为0,0
        // $url = $GLOBALS['hostConfig']['api_host'].'?args={"type":107,"value":[0,0]}&mod=setting_mgr&function=web_set';
        return $this->get('setting_mgr', 'web_set', [
            'type' => self::AutoStart,
            'value' => [$start, $end]
        ]);
    }

    public function getAutoStart()
    {
        $result = $this->get('setting_mgr', 'web_get', ['type' => self::AutoStart]);
        return $result['value'] ?? false;
    }

    public function reloadShareMachines()
    {
        // $url = 'http://127.0.0.1:' . $port . '/web_rpc?args='.\json_encode(['value'=>11],JSON_UNESCAPED_UNICODE).'&mod=setting_mgr&function=reload';
        return $this->get('setting_mgr', 'reload', [
            'value' => 11
        ]);
    }

    public function reloadSevenDaySetting()
    {
        return $this->get('setting_mgr', 'reload', [
            'type' => 18
        ]);
    }

    public function reloadMonthRankingReward()
    {
        return $this->get('setting_mgr', 'reload', [
            'type' => 24
        ]);
    }

    public function reloadWeekRankingReward()
    {
        return $this->get('setting_mgr', 'reload', [
            'type' => 19
        ]);
    }

    public function reloadWeekTask()
    {
        return $this->get('setting_mgr', 'reload', [
            'type' => 20
        ]);
    }

    public function reloadWeekTaskReward()
    {
        return $this->get('setting_mgr', 'reload', [
            'type' => 21
        ]);
    }

    public function reloadNotice()
    {
        return $this->get('setting_mgr', 'reload', [
            'type' => 10
        ]);
    }

    public function reloadStartupBanner()
    {
        return $this->get('setting_mgr', 'reload', [
            'type' => 8
        ]);
    }

    public function reloadNewsSetting()
    {
        return $this->get('setting_mgr', 'reload', [
            'type' => 22
        ]);
    }

    // 重载口令
    public function reloadWordCommand(int $id)
    {
        // command_activity    reload  [ID]
        return $this->get('command_activity', 'reload', [
            'id' => $id
        ]);
    }

    public function reloadMachineGroup()
    {
        return $this->get('setting_mgr', 'reload', [
            'type' => 26
        ]);
    }

    // 每日转盘
    public function reloadDailyPrizeWheel()
    {
        return $this->get('setting_mgr', 'reload', [
            'type' => 25
        ]);
    }

    // 115:新用户注册赠送
    // 116:登录地区限制?
    // 117:

    public const ClawMachineFreeDraws = 120;

    public function setFreeDraws($value)
    {
        return $this->get('setting_mgr', 'web_set', [
            'type' => self::ClawMachineFreeDraws,
            'value' => $value
        ]);
    }

    public function getFreeDraws()
    {
        $result = $this->get('setting_mgr', 'web_get', [
            'type' => self::ClawMachineFreeDraws
        ]);
        return $result['value'] ?? 0;
    }

    // setting_mgr，115，注册赠送5G币
    const RegisterBonus = 115;

    public function getRegisterBonus()
    {
        $result = $this->get('setting_mgr', 'web_get', [
            'type' => self::RegisterBonus
        ]);
        return $result['value'] ?? 0;
    }

    public function setRegisterBonus(int $num)
    {
        return $this->get('setting_mgr', 'web_set', [
            'type' => self::RegisterBonus,
            'value' => $num
        ]);
    }

    const ShareCurrencyStatus = 117;

    public function getShareCurrencyStatus()
    {
        $result = $this->get('setting_mgr', 'web_get', [
            'type' => self::ShareCurrencyStatus
        ]);
        return $result['value'] ?? 0;
    }

    public function setShareCurrencyStatus(int $value)
    {
        return $this->get('setting_mgr', 'web_set', [
            'type' => self::ShareCurrencyStatus,
            'value' => $value
        ]);
    }

    // 发送邮件
    public function sendEmail($title, $content, int $roleId, int $coin, int $diamond, $shareCoin, $timeRange, $uid)
    {
        $args = [];

        if (!empty($roleId)) {
            $args['role_id'] = $roleId;
        } else {
            $args['role_id'] = 0;
        }

        $args['title'] = $title;
        $args['content'] = $content;

        // 附件
        // [[1,num],[2,num],[3,num]]，1是5G币、2是钻石、3是5G票
        $items = [];
        if ($coin) {
            $items[] = [1, $coin];
        }
        if ($diamond) {
            $items[] = [2, $diamond];
        }
        if ($shareCoin) {
            $items[] = [Machine::ShareCoin5G, $shareCoin];
        }
//        if ($ticket) {
//            $items[] = [3, $ticket];
//        }
        $args['items'] = $items;

        // 条件
        // [[3, start_time, end_time]]，3是注册时间
        $args['conditions'] = [];
        $start = $timeRange['start'] ?? 0;
        $end = $timeRange['end'] ?? 0;
        if ($start && $end) {
            $args['conditions'][] = [3, (int)$start, (int)$end];
        }

        $args['uid'] = $uid;

        return $this->get('mail_mgr', 'send_all', $args);
    }

    public function queryExchangeLog($orderId)
    {
        // web_callback   select_laili  [order_id]
        return $this->get('web_callback', 'select_laili', [
            'order_id' => $orderId
        ]);
    }

    public function addToWhiteList($playerId)
    {
        // 测试白名单
        // $yunwei_ip = $post['yunwei_ip'] ? '["'.str_replace(',','","',$post['yunwei_ip']).'"]' : '[]';
        // $url = $GLOBALS['hostConfig']['api_host'].'?args={"type":108,"value":'.$yunwei_ip.'}&mod=setting_mgr&function=web_set';
    }


    public function clearMachine($machineId)
    {
        // $url = $GLOBALS['hostConfig']['api_host'].'?args='.json_encode(['value'=>$id],JSON_UNESCAPED_UNICODE).'&mod=machine&function=clean';
        return $this->get('machine', 'clean', ['value' => $machineId]);
    }

    public function kickPlayersOfMachine($machineId)
    {
        // $url = $GLOBALS['hostConfig']['api_host'].'?args='.json_encode(['value'=>$id],JSON_UNESCAPED_UNICODE).'&mod=machine&function=kick_out';
        return $this->get('machine', 'kick_out', ['value' => $machineId]);
    }

    const WechatCustomer = 101;

    public function setWechatCustomer($value)
    {
        // $url = $GLOBALS['hostConfig']['api_host'].'?args={"type":101,"value":"'.$post['customer'].'"}&mod=setting_mgr&function=web_set';
        return $this->get('setting_mgr', 'web_set', [
            'type' => self::WechatCustomer,
            'value' => $value
        ]);
    }

    public function getWechatCustomer()
    {
        $result = $this->get('setting_mgr', 'web_get', [
            'type' => self::WechatCustomer
        ]);

        return $result['value'] ?? '';
    }

    public function reloadWechatAuth()
    {
        //$url = $GLOBALS['hostConfig']['api_host'].'?args='.json_encode(['value'=>$type],JSON_UNESCAPED_UNICODE).'&mod=setting_mgr&function=reload';
        return $this->get('setting_mgr', 'reload', [
            'value' => 12
        ]);
    }

    public function reloadHomeBanner()
    {
        // setting_mgr reload 7
        return $this->get('setting_mgr', 'reload', [
            'value' => 7
        ]);
    }

    public function reloadNote()
    {
        return $this->get('setting_mgr', 'reload', [
            'value' => 15
        ]);
    }

    public const WhiteListIP = 108;

    public function setWhiteListIP(array $value)
    {
//        $yunwei_ip = $post['yunwei_ip'] ? '["'.str_replace(',','","',$post['yunwei_ip']).'"]' : '[]';
//        $url = $GLOBALS['hostConfig']['api_host'].'?args={"type":108,"value":'.$yunwei_ip.'}&mod=setting_mgr&function=web_set';
        return $this->get('setting_mgr', 'web_set', [
            'type' => self::WhiteListIP,
            'value' => $value
        ]);
    }

    public function getWhiteListIP()
    {
        return $this->get('setting_mgr', 'web_get', [
            'type' => self::WhiteListIP
        ]);
    }

    public function removeMail(int $roleId, int $mailId)
    {
        return $this->get('mail_mgr', 'web_clean_mail', [
            'role_id' => $roleId,
            'mail_id' => $mailId
        ]);
    }

    const SevenDayStatus = 121;

    public function setSevenDayStatus(int $value)
    {
        return $this->get('setting_mgr', 'web_set', [
            'type' => self::SevenDayStatus,
            'value' => $value
        ]);
    }

    public function getSevenDayStatus()
    {
        $result = $this->get('setting_mgr', 'web_get', [
            'type' => self::SevenDayStatus
        ]);
        return $result['value'] ?? 0;
    }
}
