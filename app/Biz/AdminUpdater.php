<?php

namespace App\Biz;

use App\Exceptions\Api\ApiException;
use App\Models\User;
use App\Repository\AdminRepository;
use App\Repository\UserRepository;
use App\Validator\Admin\PermissionValidator;
use App\Validator\Admin\RelationValidator;

class AdminUpdater
{
    private $relationValidator;
    private $permissionValidator;

    private $adminRepository;
    private $userRepository;

    public function __construct(AdminRepository $adminRepository, UserRepository $userRepository,
        RelationValidator $relationValidator, PermissionValidator $permissionValidator)
    {
        $this->userRepository = $userRepository;
        $this->adminRepository = $adminRepository;
        $this->relationValidator = $relationValidator;
        $this->permissionValidator = $permissionValidator;
    }

    // 执行单个账号的更新操作
    public function execute(User $operator, User $admin, array $postPermissions, string $password)
    {
        $this->relationValidator->validate($operator, $admin);

        $this->handlePermissions($operator, $admin, $postPermissions);

        if ($password) {
            $admin->password = encrypt_password($password);
        }

        return $admin->save() ? $admin: false;
    }

    private function handlePermissions(User $operator, User $admin, array $postPermissions)
    {
        $this->permissionValidator->validate($operator, $postPermissions);

        // 编辑当前账号权限
        $admin->setPermissions($postPermissions);

        // 提交的权限与后台账号的权限做交集对比
        $ids = $this->adminRepository->findAllDescendantIds($admin->getAuthIdentifier());

        if ($ids) {
            $children = $this->adminRepository->findIn($ids);
            $children->each(function (User $child) use($postPermissions) {
                if (empty($postPermissions)) {
                    $child->setPermissions([]);
                } else {
                    $childPermissions = $child->getPermissions();
                    $intersect = array_intersect($postPermissions, $childPermissions);
                    $child->setPermissions(array_values($intersect));
                }
                $child->save();
            });
        }
    }

    /**
     * 更改多个账号的状态
     * @param array $statuses 要设置的多个账号的状态，格式为：[[id, status]...]
     * @return void
     * @throws ApiException
     */
    public function changeStatuses(User $operator, array $statuses)
    {
        $ids = array_column($statuses, 0);
        $this->multiRelationValidate($operator, $ids);

        foreach ($statuses as $item) {
            $admin = $this->adminRepository->findOneBy(['id' => $item[0]]);
            $this->userRepository->update($admin, ['status' => $item[1]]);
        }
    }

    /**
     * 用于验证操作者是否对多个账号有操作的权限。
     * @throws ApiException
     */
    private function multiRelationValidate(User $operator, array $ids)
    {
        // $oid, ids
        $oid = $operator->getAuthIdentifier();

        $descendantIds = $this->adminRepository->findAllDescendantIds($oid, $operator->server_no);
        $diff = array_diff($ids, $descendantIds);
        if ($diff) {
            throw new ApiException(trans('err.authorize'));
        }
    }
}
