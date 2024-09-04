<?php

namespace App\Exceptions\Api;

class ErrCode
{
    public const Param        = 10000;

    // 非法token，一般是未登录或者在其他端登录。
    public const IllegalToken = 10001;

    // 未授权的操作
    public const Unauthorized = 10002;

    // 锁定账号
    public const LockedAccount = 10003;

    // 禁用账号
    public const DisableAccount = 10004;

    // 异地登录
    public const DifferentLocation = 10005;

    public const ServerBusy = 10006;

    public const GameApiFail = 10007;
}
