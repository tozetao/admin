<?php

namespace App\Http\Middleware;

use App\Biz\ServerConfigManager;
use Closure;
use Illuminate\Support\Facades\Auth;

class ServerConfigInitializer
{
    private $manager;

    public function __construct(ServerConfigManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        $serverConfig = $this->manager->getServerConfig($request, $user);

        $this->manager->changeDBConnection($serverConfig->db_host, $serverConfig->db_name,
            $serverConfig->db_user, $serverConfig->db_password, $serverConfig->db_port);

        return $next($request);
    }

}
