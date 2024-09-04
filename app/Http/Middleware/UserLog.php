<?php

namespace App\Http\Middleware;

use App\Events\Action;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserLog
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        event(new Action($request, Auth::user()));

        return $response;
    }
}
