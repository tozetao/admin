<?php

namespace App\Validator\Admin;

use App\Exceptions\Api\ApiException;
use App\Models\User;

class PermissionValidator
{
    public function validate(User $operator, array $postPermissions)
    {
        $availablePermissions = $operator->getPermissions();
        $diff = array_diff($postPermissions, $availablePermissions);
        if (!empty($diff)) {
            throw new ApiException(trans('err.admin.permission'));
        }
    }
}
