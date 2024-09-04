<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionLog extends Model
{
    public $timestamps = false;

    protected $table = 'web_action_log';

    protected $connection = 'central';

    protected $fillable = ['user_id', 'action', 'data', 'ip', 'created_at'];
}
