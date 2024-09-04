<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class Action
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ip;

    public $oldData;

    public $postData;

    public $action;

    public $userId;

    public $serverNo;

    public function __construct(Request $request, User $user, $oldData = null)
    {
        // 超管：根据请求头的sc_id，经销商：根据请求头的sc_id，普通用户：根据自身绑定的sc_id
        if ($user->belongsToCentralServer()) {
            $this->serverNo = $request->header('X-Default-Sc-Id');
        } else {
            $this->serverNo = $user->server_no;
        }

        $this->ip = $request->getClientIp();
        $this->oldData = $oldData;
        $this->postData = $request->all();
        $this->userId = $user->getAuthIdentifier();
        $this->action = resolve_action($request);
    }

}
