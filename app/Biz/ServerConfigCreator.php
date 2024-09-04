<?php

namespace App\Biz;

use App\Exceptions\Api\ApiException;
use App\Factory\AdminFactory;
use App\Models\CrossServer;
use App\Models\ServerConfig;
use App\Models\User;
use Facade\FlareClient\Api;
use Illuminate\Support\Facades\DB;

class ServerConfigCreator
{
    /**
     * @var ServerConfigManager
     */
    private $serverConfigManager;

    private $adminFactory;

    public function __construct(ServerConfigManager $serverConfigManager, AdminFactory $adminFactory)
    {
        $this->serverConfigManager = $serverConfigManager;
        $this->adminFactory = $adminFactory;
    }

    /**
     * @throws ApiException
     */
    public function execute(User $user, $serverName, $streamUrl, $mchId, int $mergeRoom, int $platformType, int $agentType, $expiredAt = 0)
    {
        if (!$user->isCentralAdmin()) {
            return new ApiException(trans('err.authorize'));
        }

        $code = $this->serverConfigManager->generateCode($platformType, $mchId);
        if (!$code) {
            throw new ApiException('服务器code生成失败.');
        }
        $nextServerNo = $this->serverConfigManager->getNextServerNo();

        // 1. cross_server新增记录
        $crossServer = new CrossServer();
        $crossServer->fill([
            'server_no' => $nextServerNo,
            'node_name' => $nextServerNo,
            'name' => $serverName
        ]);
        if (!$crossServer->save()) {
            throw new ApiException('中央服配置创建失败');
        }

        // 2. 新增服务配置
        $databaseConfig = config('database.connections.child');
        $serverConfig = new ServerConfig();
        $serverConfig->server_no = $nextServerNo;
        $serverConfig->fill([
            'code' => $code,
            'db_host' => $databaseConfig['host'],
            'db_user' => $databaseConfig['username'],
            'db_password' => $databaseConfig['password'],
            'db_port' => $databaseConfig['port'],
            'db_name' => $this->serverConfigManager->generateGameDBName($nextServerNo),
            'game_ip' => $this->serverConfigManager->getGameIp(),
            'game_port' => $this->serverConfigManager->generateGamePort($nextServerNo),
            'server_name' => $serverName,
            'stream_url' => $streamUrl,
            'mch_id' => $mchId,
            'agent_type' => $agentType,
            'platform_type' => $platformType,
            'expired_at' => $expiredAt,
            'merge_room' => $mergeRoom,
            'created_at' => time()
        ]);

        if (!$serverConfig->save()) {
            $crossServer->delete();
            throw new ApiException('服务配置创建失败.');
        }

        // 3. 新增用户
        $permissions = config('permission.admin');
        $admin = $this->adminFactory->create('admin', $nextServerNo, '123456', $permissions, 0, $nextServerNo);
        if (!$admin->save()) {
            throw new ApiException('后台管理员创建失败.');
        }

        // 4. 创建配置文件
        $gameConfigFile = new GameConfigFile($code, $serverConfig, env('SERVER_IP'));
        $gameConfigFile->selfChecking();

        // 5. 重载游戏接口
        $gameApi = app('game_api.central');
        $result = $gameApi->reloadServers();
        if (empty($result)) {
            throw new ApiException(trans('err.game_call_failed'));
        }

        // 6. 执行shell脚本，创建erlang服务器
        $this->newGameServer($nextServerNo);

        // 7. 初始化PHP数据库，执行初始化工作。
        if (!$this->initDatabase($nextServerNo)) {
            throw new ApiException('PHP SQL脚本初始化失败');
        }

        return true;
    }


    private function newGameServer($serverNo)
    {
        $command = sprintf('cd /mnt/server/scripts; sh srv.sh -new %d', $serverNo);
        $shell = app('shell');
        $shell->exec($command);
    }

    // 初始化数据库。
    private function initDatabase($serverNo)
    {
        $n = 0;
        $limit = 10;
        do {
            if ($this->hasDatabase($serverNo)) {
                $config = config('database.connections.child');

                $this->serverConfigManager->changeDBConnection($config['host'], $this->serverConfigManager->generateGameDBName($serverNo),
                    $config['username'], $config['password'], $config['port']);

                $query = file_get_contents(resource_path('db/child.sql'));

                DB::unprepared($query);
                return true;
            }
            sleep(1);
        } while (++$n < $limit);
        return false;
    }

    // 目前是通过默认数据库（中央服）来判断其他库是否创建。
    private function hasDatabase($serverNo): bool
    {
        $result = DB::connection('child')->select('select * from information_schema.SCHEMATA where SCHEMA_NAME = ?',
            [$this->serverConfigManager->generateGameDBName($serverNo)]);
        return is_array($result) && count($result) === 1;
    }

}
