<?php

namespace App\Console\Commands;

use App\Biz\GameConfigFile;
use App\Biz\Migration\ConfigMigrate;
use App\Biz\Migration\DBMigrate;
use App\Biz\Migration\EmptyMchHandler;
use App\Biz\Migration\Estimate;
use App\Biz\Migration\InitGameFile;
use App\Biz\Migration\MachineMigrate;
use App\Biz\Migration\RechargeUrlMigrate;
use App\Biz\Migration\ServerConfigMigrate;
use App\Biz\Migration\UserMigrate;
use App\Models\ServerConfig;
use App\Models\ServerMachine;
use App\Models\User;
use App\SDK\GameApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class App extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app {action} {--extra=""}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Commands for product modules';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \ErrorException
     */
    public function handle()
    {
//        $action = $this->argument('action');
//        $result = null;
//
//        $params = parse_command_params($this->option('extra'));
//        $serverNos = isset($params['server_nos']) ? explode(',', $params['server_nos']) : null;
//
//        switch ($action) {
//            case 'reload_game_setting':
////                $this->reloadGameSetting();
//                break;
//        }
    }

}
