<?php

namespace App\Util;

use Illuminate\Support\Facades\Redis;

class LoginManager
{
    private $expire = 60 * 5;

    /**
     * 下面几个方面是用于判断用户是否异地登录的。
     * 实现思路：当一个用户登录后，上一次登录的token将被会删除。这是上一次登录的用户如果继续发出请求，
     * 用户身份认证的中间件发现用户认证失败时，会调用isFromAnotherPeer方法来判断是否异地登录。
     */

    // 是否来自其他端的登录
    public function isFromAnotherPeer(string $apiToken)
    {
        $key = $this->getLoginMarkKey($apiToken);
        return Redis::expire($key, $this->expire);
    }

    // 做一个异地登录的标记
    public function makeMark(string $apiToken)
    {
        $key = $this->getLoginMarkKey($apiToken);
        return Redis::command('set', [$key, '1', ['ex' => $this->expire]]);
    }

    public function removeMark(string $apiToken)
    {
        $key = $this->getLoginMarkKey($apiToken);
        return Redis::del($key);
    }

    // 登录标记key
    private function getLoginMarkKey(string $token): string
    {
        return 'login:mark' . $token;
    }
}
