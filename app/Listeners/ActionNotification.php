<?php

namespace App\Listeners;

use App\Events\Action;
use App\Models\ActionLog;

class ActionNotification
{
    /**
     * Handle the event.
     *
     * @param  Action  $event
     * @return void
     */
    public function handle(Action $event)
    {
        $model = new ActionLog();
        $model->fill([
            'ip' => $event->ip,
            'data' => serialize($event->postData),
            'user_id' => $event->userId,
            'action' => $event->action,
            'created_at' => time()
        ]);
        $model->save();
    }
}
