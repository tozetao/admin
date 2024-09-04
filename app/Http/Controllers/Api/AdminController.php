<?php

namespace App\Http\Controllers\Api;

use App\Biz\AdminQuery;
use App\Biz\AdminUpdater;
use App\Factory\AdminFactory;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Resources\AdminResource;
use App\Validator\Admin\PermissionValidator;
use App\Validator\Admin\RelationValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function index(Request $request, AdminQuery $query)
    {
        $viewBy  = $request->get('view_by', AdminQuery::ViewMy);
        $account = $request->get('account', '');
        $status  = $request->get('status', false);
        $page    = $request->get('page', 1);

        $user    = Auth::user();
        return $this->data($query->execute($user, $viewBy, $account, $status, $page, 10));
    }

    public function show(User $admin, RelationValidator $relationValidator, AdminResource $resource)
    {
        $relationValidator->validate(Auth::user(), $admin);
        return $this->data($resource->one($admin));
    }

    public function create(Request $request, PermissionValidator $validator, AdminFactory $adminFactory, AdminResource $resource)
    {
        $this->validate($request, [
            'account' => 'required|app_alpha_num|min:4|max:30',
            'password' => 'required|app_alpha_num|min:4|max:15',
            'permissions' => 'nullable|array'
        ]);
        $account = $request->post('account');
        $password = $request->post('password');
        $postPermissions = $this->filterPermissions($request->post('permissions', []));



        // 判断是否有授予超出当前账号的权限
        $user = Auth::user();
        $validator->validate($user, $postPermissions);

        // 判断账号是否存在
        $exists = User::query()
            ->where(['account' => $account])
            ->exists();
        if ($exists) {
            return $this->fail('该账号已经存在!');
        }

        $admin = $adminFactory->create($account, $password, $postPermissions, $user->getAuthIdentifier());
        if (!$admin->save()) {
            return $this->fail(trans('err.creation_failure'));
        }
        return $this->data($resource->one($admin));
    }

    // 避免键值对的数组
    private function filterPermissions($postPermissions): array
    {
        if (is_array($postPermissions) && count($postPermissions)) {
            return array_values($postPermissions);
        }
        return $postPermissions;
    }

    public function update(Request $request, User $admin, AdminUpdater $updater, AdminResource $resource)
    {
        $this->validate($request, [
            'permissions' => 'nullable|array',
            'password' => 'nullable|string|min:6'
        ]);

        $postPermissions = $this->filterPermissions($request->post('permissions'));
        $password = $request->post('password') ?: '';

        $admin = $updater->execute(Auth::user(), $admin, $postPermissions, $password);
        if (!$admin) {
            return $this->fail(trans('err.update_failed'));
        }
        return $this->data($resource->one($admin));
    }

    /**
     * @throws \App\Exceptions\Api\ApiException
     */
    public function changeStatus(Request $request, AdminUpdater $updater): \Illuminate\Http\JsonResponse
    {
        $this->validate($request, [
            'statuses' => 'required|array'
        ]);
        $statuses = $request->post('statuses');
        $updater->changeStatuses(Auth::user(), $statuses);
        return $this->success();
    }

}
