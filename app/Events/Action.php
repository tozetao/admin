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

    public function __construct(Request $request, User $user, $oldData = null)
    {
        $this->ip = $request->getClientIp();
        $this->oldData = $oldData;
        $this->postData = $request->all();
        $this->userId = $user->getAuthIdentifier();
        $this->action = resolve_action($request);
    }

}
