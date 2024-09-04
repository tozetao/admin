<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\ErrCode;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repository\ServerConfigRepository;
use App\Repository\UserRepository;
use App\Resources\SessionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class SessionController extends Controller
{
    /**
     * @throws ApiException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request, UserRepository $userRepository): \Illuminate\Http\JsonResponse
    {
        $this->validate($request, [
            'account' => 'required|app_alpha_num|min:4',
            'password' => 'required|string|min:4',
//            'code' => 'required|string',
        ]);

//        $serverConfig = $serverConfigRepository->findOneBy(['code' => $request->post('code')]);
//        if (!$serverConfig) {
//            return $this->fail(trans('err.server_config_not_found'));
//        }
//        if ($serverConfig->isExpired()) {
//            return $this->fail('该平台已过期，请联系商务客服!');
//        }

        $account = $request->post('account');
        $user = $userRepository->findOneBy([
            'account' => $account,
        ]);

        if (empty($user)) {
            return $this->fail(trans('err.account_pwd_error'));
        }

        // 验证密码。
        $password = $request->post('password');
        if (!validate_password($password, $user->password)) {
            return $this->fail(trans('err.account_pwd_error'));
        }

        // 状态验证
        if (!$user->isAvailable()) {
            return $this->fail(trans('err.lock_account'));
        }

        $loginManager = app('login_manager');
        $loginManager->makeMark($user->api_token);

        // 销毁上次登录的token
        $newToken = $userRepository->generateApiToken($user);

        return $this->data([
            'token' => $newToken
        ]);
    }

    public function logout(UserRepository $userRepository): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        if ($userRepository->removeApiToken($user)) {
            return $this->success();
        }
        return $this->fail('logout failed');
    }

    // 显示用户个人数据
    public function show(SessionResource $resource)
    {
        $user = Auth::user();
        return $this->data($resource->one($user));
    }

    // 更新用户信息
    public function update(Request $request, UserRepository $userRepository): \Illuminate\Http\JsonResponse
    {
        $this->validate($request, [
            'timezone' => 'nullable|string|max:20',
            'locale' => 'nullable|string|max:10',
            'default_sc_id' => 'nullable|integer',
        ]);

        $user = Auth::user();
        $data = [];
        if ($timezone = $request->post('timezone')) {
            $config = config('app.timezones');
            if ($config[$timezone]) {
                $data['timezone'] = $timezone;
            }
        }

        if ($locale = $request->post('locale')) {
            $data['locale'] = $locale;
        }

        if ($defaultScId = $request->post('default_sc_id')) {
            $data['default_sc_id'] = $defaultScId;
        }

        if (!$userRepository->update($user, $data)) {
            return $this->fail('failed');
        }
        return $this->success();
    }

    // 更新密码
    public function changePassword(Request $request, UserRepository $userRepository): \Illuminate\Http\JsonResponse
    {
        $this->validate($request, [
            'old_password' => 'required|string|max:50',
            'new_password' => 'required|string|min:6|max:50',
            'confirm_new_password' => 'required|string|min:6|max:50'
        ]);

        $user = $userRepository->findOneBy(['id' => (Auth::user())->getAuthIdentifier()]);

        // 旧密码验证
        $originPwdError = trans('err.origin_pwd_error');
        if (!Hash::check($request->old_password, $user->getAuthPassword())) {
            return $this->fail($originPwdError);
        }

        // 确认密码错误
        if (strcasecmp($request->new_password, $request->confirm_new_password) !== 0) {
            return $this->fail(trans('err.repeat_pwd_error'));
        }

        // 更新密码
        if ($userRepository->update($user, [
            'password' => encrypt_password($request->new_password)
        ])) {
            return $this->success();
        }
        return $this->fail(trans('err.update_failed'));
    }

    // 用于幂等性判断的key
    public function key()
    {
        $expire = 60;
        $key = Str::random(64);
        if (Redis::set($key, 1, 'EX', $expire)) {
            return $this->data([
                'key' => $key,
                'expire' => $expire
            ]);
        }
        return $this->fail(ErrCode::ServerBusy);
    }

    // 锁定，将状态改为2
    public function lock(UserRepository $userRepository, SessionResource $resource)
    {
        $user = Auth::user();

        $result = $userRepository->update($user, [
            'status' => User::Lock
        ]);

        if (!$result) {
            return $this->fail('lock failed');
        }
        return $this->data($resource->one($user));
    }

    // 解锁，验证密码并将状态改为1
    public function unlock(Request $request, UserRepository $userRepository, SessionResource $resource)
    {
        $password = $request->post('password');
        $user = Auth::user();
        $user = $userRepository->findOneBy(['id' => $user->getAuthIdentifier()]);

        if (!validate_password($password, $user->getAuthPassword())) {
            throw new ApiException(trans('err.pwd_error'));
        }

        $result = $userRepository->update($user, [
            'status' => User::Enable
        ]);

        if (!$result) {
            return $this->fail('lock failed');
        }

        return $this->data($resource->one($user));
    }

}
