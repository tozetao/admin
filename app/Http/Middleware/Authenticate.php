<?php

namespace App\Http\Middleware;

use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\ErrCode;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// 身份认证
class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }

    /**
     * Determine if the user is logged in to any of the given guards.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws ApiException
     */
    protected function authenticate($request, array $guards)
    {
        if (empty($guards)) {
            $guards = [null];
        }

        // 用户认证
        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return $this->auth->shouldUse($guard);
            }
        }
        // 判断是否异地登录
        $this->differentLocationHandle($request);

        // 抛出异常
        $this->unauthenticated($request, $guards);
    }

    /**
     * 处理异地登录
     * @throws ApiException
     */
    private function differentLocationHandle(Request $request)
    {
        $apiToken = $request->bearerToken();

        $loginManager = app('login_manager');
        if ($apiToken && $loginManager->isFromAnotherPeer($apiToken)) {
            $loginManager->removeMark($apiToken);
            throw new ApiException(trans('err.from_other_place'), 200, ErrCode::DifferentLocation);
        }
    }

}
