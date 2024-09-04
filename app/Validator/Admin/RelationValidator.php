<?php

namespace App\Validator\Admin;

use App\Exceptions\Api\ApiException;
use App\Models\User;
use App\Repository\AdminRepository;

// 关系验证器。验证当前操作者是否有权限去编辑该账号。
class RelationValidator
{
    private $adminRepository;

    public function __construct(AdminRepository $adminRepository)
    {
        $this->adminRepository = $adminRepository;
    }

    /**
     * @throws ApiException
     */
    public function validate(User $operator, User $admin)
    {
        $oid = $operator->getAuthIdentifier();
        $aid = $admin->getAuthIdentifier();

        $ids = $this->adminRepository->findAllDescendantIds($oid);

        if (!in_array($aid, $ids)) {
            throw new ApiException(trans('err.admin.relation'));
        }
    }
}
