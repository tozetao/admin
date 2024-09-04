<?php

namespace App\Factory;

use App\Models\User;
use App\Repository\UserRepository;

class AdminFactory
{
    public function create(string $account, string $password, array $postPermissions, int $pid): User
    {
        $repository = new UserRepository();

        $model = new User();

        $model->account = $account;
        $model->password = encrypt_password($password);
        $model->api_token = $repository->generateToken();

        $model->type = User::Admin;
        $model->status = User::Enable;
        $model->pid = $pid;
        $model->permissions = \json_encode($postPermissions);
        $model->locale = 'zh_CN';
        $model->created_at = time();
        $model->updated_at = 0;

//        $model->server_no = $serverNo;
//        $model->default_sc_id = $defaultServerId;

        return $model;
    }
}
