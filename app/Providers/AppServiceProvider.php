<?php

namespace App\Providers;

use App\Biz\ClassifyBiz;
use App\Biz\GameConfigFile;
use App\Biz\ServerConfigManager;
use App\Extend\Auth\TokenUserProvider;
use App\Repository\AdminRepository;
use App\Repository\ServerConfigRepository;
use App\Repository\UserRepository;
use App\SDK\GameApi;
use App\Util\Excel\ExcelHandler;
use App\Util\LoginManager;
use App\Util\Shell;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\RedisStore;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(AdminRepository::class, function () {
            return new AdminRepository();
        });

        $this->app->singleton(UserRepository::class, function() {
            return new UserRepository();
        });

        $this->app->singleton('login_manager', function () {
            return new LoginManager();
        });

        $this->app->singleton('shell', function() {
            return new Shell('', 'root', '');
        });

        $this->app->singleton('server_config_manager', function () {
            return new ServerConfigManager(new ServerConfigRepository());
        });

        $this->app->singleton(ServerConfigRepository::class, function () {
            return new ServerConfigRepository();
        });

        // GameApi的参数是动态配置的。
        $this->app->singleton('game_api', function ($app) {
            $user = Auth::user();
            $manager = $app->make('server_config_manager');
            $serverConfig = $manager->getServerConfig($app->request, $user);
            $gameApi = new GameApi();
            $gameApi->init($serverConfig->game_ip, $serverConfig->game_port);
            return $gameApi;
        });

        $this->app->singleton('game_api.central', function () {
            $gameApi = new GameApi();
            $gameApi->init(env('GAME_HOST'), env('GAME_PORT'));
            return $gameApi;
        });

        $this->app->singleton('excel_handler', function() {
            return new ExcelHandler();
        });

        $this->app->singleton('app_redis', function () {
            $config = config('database.redis.default');
            $redis = new \Redis();
            $redis->connect($config['host'], $config['port']);
            return $redis;
        });

        $this->app->singleton('lock_factory', function() {
            $redis = app('app_redis');
            $store = new RedisStore($redis);
            return new LockFactory($store);
        });
    }


    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        $this->dbBoot();
        $this->validatorBoot();
        $this->authProviderBoot();
    }

    protected function dbBoot()
    {
        if (env('APP_DEBUG')) {
            DB::listen(function ($query) {
                $route = app('request')->route();
                if ($route) {
                    $action = $route->getAction();
                    if (strpos($action['controller'] ?? '', 'PollingController') !== false) {
                        return;
                    }
                }
                Log::info($query->sql, $query->bindings);
            });
        }
    }

    // 新项目的命名
    protected function validatorBoot()
    {
        Validator::extend('app_alpha', function($attribute, $value, $parameters, $validator) {
            return is_string($value) && preg_match('/^[a-zA-Z]+$/u', $value);
        });
        // 字母、纯数字
        Validator::extend('app_alpha_num', function($attribute, $value, $parameters, $validator) {
            return is_string($value) && preg_match('/^[a-zA-Z\d]+$/u', $value);
        });
        // 字母、纯数字、横杠和下划线
        Validator::extend('app_alpha_dash', function($attribute, $value, $parameters, $validator) {
            return is_string($value) && preg_match('/^[a-zA-Z\d\-_]+$/u', $value);
        });
        Validator::extend('is_int_number', function($attribute, $value) {
            return is_int_number($value);
        });
    }

    protected function authProviderBoot()
    {
        Auth::provider('token', function ($app, array $config) {
            return new TokenUserProvider($app['hash'], $config['model']);
        });
    }
}
