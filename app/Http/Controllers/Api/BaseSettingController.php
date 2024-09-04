<?php

namespace App\Http\Controllers\Api;

use App\Events\Action;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BaseSettingController extends Controller
{
    public function index()
    {
        $gameApi = app('game_api');

        $registerBonus = $gameApi->getRegisterBonus();

        // 返回充值链接
        $gameConfigFile = app('game_config_file');

        $freeDraws = $gameApi->getFreeDraws();

        $shade = $gameConfigFile->getShade();

        return $this->data([
            'register_bonus' => $registerBonus,
            'recharge_url' => $gameConfigFile->getRechargeUrl(),
            'image' => $gameConfigFile->getLoadingIcon(),
            'free_draws' => $freeDraws,
            'shade' => $shade
        ]);
    }

    public function update(Request $request)
    {
        $gameConfigFile = app('game_config_file');
        $gameApi = app('game_api');

        $shade = (bool)$request->post('shade');
        if (!$gameConfigFile->setShade($shade)) {
            return $this->fail('隐藏炮台分数设置失败!');
        }

        $registerBonus = $request->post('register_bonus');

        if (my_is_int($registerBonus) && $registerBonus >= 0) {
            if (!$gameApi->setRegisterBonus(intval($registerBonus))) {
                return $this->fail('注册奖励更新失败');
            }
        }

        $rechargeUrl = $request->post('recharge_url');
        if (is_string($rechargeUrl)) {
            if (!$gameConfigFile->setRechargeUrl($rechargeUrl)) {
                return $this->fail('充值链接设置失败!');
            }
        }

        $image = $request->post('image');
        if (is_string($image)) {
            if (!$gameConfigFile->setLoadingIcon($image)) {
                return $this->fail('加载Icon设置失败!');
            }
        }

        $freeDraws = (int)$request->post('free_draws');
        if($freeDraws >= 0) {
            if (!$gameApi->setFreeDraws($freeDraws)) {
                return $this->fail(trans('err.game_call_failed'));
            }
            event(new Action($request, Auth::user()));
        }



        return $this->success();
    }

    // 设置白名单
    public function setWhiteListIP(Request $request)
    {
        $val = $request->post('val', '');
        $val = explode(',', $val);

        $gameApi = app('game_api');
        $result = $gameApi->setWhiteListIP($val);
        if (!$result) {
            return $this->fail('设置失败!');
        }
        return $this->success();
    }

    public function getWhiteListIP()
    {
        $gameApi = app('game_api');
        $result = $gameApi->getWhiteListIP();
        $result = $result['value'] ?? '';

        $val = '';
        if (!empty($result)) {
            $val = implode(',', $result);
        }

        return $this->data([
            'val' => $val
        ]);
    }
}
