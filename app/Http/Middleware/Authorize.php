<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Exceptions\Api\ErrCode;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\Api\ApiException;

class Authorize
{
    private $permissionModel;

    public function __construct(Permission $model)
    {
        $this->permissionModel = $model;
    }
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param Permission $model
     * @return mixed
     * @throws ApiException
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        $permissions = $user->getPermissions();

        // 获取权限对应的actions
        $actions = $this->permissionModel->getActions($permissions);
        if (empty($actions)) {
            throw new ApiException(
                trans('err.authorize'), 200, ErrCode::Unauthorized);
        }

        // 获取当前请求的action名字
        $currentAction = $this->resolveRequestAction($request);

        if (!in_array($currentAction, $actions)) {
            throw new ApiException(
                trans('err.authorize'), 200, ErrCode::Unauthorized);
        }

        $this->checkUserStatus($user);

        return $next($request);
    }

    // 用户状态的判断
    private function checkUserStatus(User $user)
    {
        $status = (int) $user->status;
        if ($status !== User::Enable) {
            $errCode = 1;
            switch ($status) {
                case User::Lock:
                    $errCode = ErrCode::LockedAccount;
                    break;
                case User::Disable:
                    $errCode = ErrCode::DisableAccount;
                    break;
                default:
            }
            throw new ApiException(trans('err.lock_account'), 200, $errCode);
        }
    }

    private function resolveRequestAction(Request $request): string
    {
        $action = $request->route()->getAction();
        list($controller, $method) = explode('@', $action['controller']);

        $controller = str_replace(['Controller', '\\'], ['', '/'], $controller);
        return basename($controller) . '.' . $method;
    }
}
