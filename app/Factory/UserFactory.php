<?php

namespace App\Factory;

use App\Models\User;
use Illuminate\Support\Str;

class UserFactory
{
    public function createPromoter( int $serverNo,string $account, string $password, int $pid, int $playerId, $level, $defaultScId): User
    {
        $permissions = config('permission.promoter');

        $model = new User();

        $model->account = $account;
        $model->password = encrypt_password($password);
        $model->api_token = Str::random(60);

        $model->type = User::Promoter;
        $model->status = User::Enable;
        $model->pid = $pid;
        $model->permissions = \json_encode($permissions);
        $model->locale = 'zh_CN';
        $model->server_no = $serverNo;
        $model->player_id = $playerId;
        $model->default_sc_id = $defaultScId;
        $model->promoter_level = $level;

        $model->created_at = time();
        $model->updated_at = 0;
        return $model;
    }

    public function createDistributor(string $account, int $serverNo, string $password, int $pid, $defaultScId)
    {
        $permissions = config('permission.distributor');

        $model = new User();

        $model->account = $account;
        $model->password = encrypt_password($password);
        $model->api_token = Str::random(60);

        $model->type = User::Distributor;
        $model->status = User::Enable;
        $model->pid = $pid;
        $model->permissions = \json_encode($permissions);
        $model->locale = 'zh_CN';
        $model->server_no = $serverNo;
        $model->player_id = 0;
        $model->default_sc_id = $defaultScId;

        $model->created_at = time();
        $model->updated_at = 0;
        return $model;
    }
}
