<?php

namespace App\Models\Key;

class CacheKey
{

    public const MINI_APP_PATH = '5g.mini_app_path';
    public const MINI_APP_USERNAME = '5g.mini_app_username';

    public const MINI_APP_URI = '5g.mini_app_uri';

    public static function miniAppPath($serverId):string
    {
        return self::MINI_APP_PATH . '_' . $serverId;
    }

    public static function miniAppUsername($serverId):string
    {
        return self::MINI_APP_USERNAME  . '_' . $serverId;
    }

    public static function miniAppUri($serverId):string
    {
        return self::MINI_APP_URI . '_' . $serverId;
    }
}
