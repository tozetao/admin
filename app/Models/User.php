<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    const Admin = 1;

    const Enable = 0;
    const Disable = 1;
    const Lock = 2;

    protected $connection = 'central';

    protected $table = 'web_users';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account', 'password', 'remark',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public function isAdmin()
    {
        return $this->type == User::Admin;
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'pid', 'id');
    }

    public function setPermissions($value)
    {
        $this->permissions = \json_encode($value);
    }

    public function getPermissions(): array
    {
        if (empty($this->permissions)) {
            return [];
        }
        return \json_decode($this->permissions, true);
    }

    public function belongsToCentralServer(): bool
    {
        return $this->server_no == config('app.central_server_id');
    }

    // 中央服管理员
    public function isCentralAdmin(): bool
    {
        return $this->type == self::Admin && $this->server_no == config('app.central_server_id');
    }

    public function isChildAdmin(): bool
    {
        return $this->server_no != config('app.central_server_id') && $this->type == self::Admin;
    }

    public function isAvailable(): bool
    {
        $status = (int) $this->status;
        return $status === self::Enable;
    }

}
