<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class Env
{
    public function handle($request, Closure $next)
    {
        // 从缓存中获取用户数据
        $user = Auth::user();

        if ($user) {
            // 设置默认时区
            Config::set('app.timezone', 'PRC');
            date_default_timezone_set('PRC');

//            $timezones = \config('app.timezones');
//            $timezone = $timezones[$user->timezone] ?? null;
//
//            // 时区初始化
//            if ($timezone) {
//                Config::set('app.timezone', $timezone);
//                date_default_timezone_set($timezone);
//            }

            // 本地化
            if (!empty($user->locale)) {
                // hack
                $locale = str_replace('_', '-', $user->locale);
                App::setLocale($locale);
            }
        } else {
            // 默认本地化为中文
            App::setLocale(\config('app.locale'));
        }

        return $next($request);
    }
}
