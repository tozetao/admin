<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PermissionController extends Controller
{
    // 获取用户自身的权限，超管将会获取所有权限。
    public function all(Permission $model)
    {
        $user = Auth::user();
        $keys = $user->getPermissions();
        return $this->data($model->getPermissionsByKeys($keys));
    }
}
