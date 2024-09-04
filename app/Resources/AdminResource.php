<?php

namespace App\Resources;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Collection;


class AdminResource
{
    private $fn;

    public function __construct(Permission $permissionModel)
    {
        $this->fn = function (User $admin) use($permissionModel) {
            $keys = $admin->getPermissions();
            $parent = $admin->parent ? $admin->parent->account: '-';
            return [
                'id' => $admin->id,
                'account' => $admin->account,
                'pid' => $admin->pid,
                'created_at' => to_datetime_string($admin->created_at),
                'status' => $admin->status,
                'permissions' => $keys,
                'parent' => $parent,
                'locale' => $admin->locale
            ];
        };
    }

    public function one(User $admin)
    {
        $fn = $this->fn;
        return $fn($admin);
    }

    public function collection(Collection $collection)
    {
        return $collection->map($this->fn)->all();
    }
}
