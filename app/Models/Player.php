<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    public const Enabled = 1;
    public const Disabled = 0;

    public $table = 'role';

    public $primaryKey = 'role_id';

    public function parent()
    {
        return $this->hasOne(Player::class, 'role_id', 'friend_id');
    }
}
