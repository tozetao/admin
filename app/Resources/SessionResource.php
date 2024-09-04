<?php

namespace App\Resources;

use App\Models\User;
use App\Repository\ServerConfigRepository;

class SessionResource
{
    private $repository;

    public function __construct(ServerConfigRepository $repository)
    {
        $this->repository = $repository;
    }

    // 缓存用户数据
    public function one(User $user): array
    {
        $parent = $user->parent ? $user->parent->account: '-';

        $userData = [
            'id' => $user->id,
            'pid' => $user->pid,
            'account' => $user->account,
            'type' => $user->type,
            'parent' => $parent,
            'timezone' => $user->timezone,
            'locale' => $user->locale,
            'permissions' => $user->getPermissions(),
            'created_at' => $user->created_at,
            'status' => $user->status,
            'server_no' => $user->server_no,
            'active_server_id' => 0,
            'active_server_name' => 'Center Server',
            'belongs_to_central_server' => $user->belongsToCentralServer(),
            'is_central_admin' => $user->isCentralAdmin(),
        ];

        return [
            'token' => $user->api_token,
            'body' => $userData,
            'config' => [
                'version' => '1.00'
            ]
        ];
    }

}
