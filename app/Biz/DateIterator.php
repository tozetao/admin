<?php

namespace App\Biz;

use Illuminate\Support\Carbon;

class DateIterator
{
    public function getMonth()
    {
    }

    public function dayOfMonth($month, $data, \Closure $closure)
    {
        $date = $month . '-1';
        $start = Carbon::parse($date, config('app.timezone'));
        $start->
        $end = Carbon::parse($date, config('app.timezone'))->addMonth();

        while ($start->timestamp < $end->timestamp) {
            $key = $start->toDateString();
            $data = $closure($data, $key);
            $start->addDay();
        }

        return $data;
    }
}
