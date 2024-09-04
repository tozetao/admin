<?php

namespace App\Http\Middleware;

use Closure;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // return $next($request);
        $response = $next($request);
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Headers', 'Origin,Content-Type,Cookie,Accept');
        $response->header('Access-Control-Allow-Methods', 'GET,POST,PATCH,PUT,DELETE');
        $response->header('Access-Control-Allow-Credentials', 'false');
        return $response;
    }
}
