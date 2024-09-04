<?php

namespace App\Biz;

use App\Exceptions\Api\ApiException;
use App\Models\CrossServer;
use App\Models\ServerConfig;
use App\Models\User;
use App\Repository\ServerConfigRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ServerConfigManager
{
    private $serverConfigRepository;

    private $serverConfig = null;

    public function __construct(ServerConfigRepository $repository)
    {
        $this->serverConfigRepository = $repository;
    }

    /**
     * 该方法是用于初始化各种配置用的，比如DB配置，GameApi配置，绝对不要用于控制器或者业务逻辑中。
     *
     * @param Request $request
     * @param User $user
     * @return ServerConfig|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     * @throws ApiException
     */
    public function getServerConfig(Request $request, User $user)
    {
        if ($user->belongsToCentralServer()) {
            if ($user->isCentralAdmin()) {
                $scId = $request->header('X-Default-Sc-Id');
                if (empty($scId)) {
                    $scId = $user->default_sc_id;
                }
                return $this->find($scId);
            }

            if ($user->isDistributor()) {
                $scId = $request->header('X-Default-Sc-Id');
                if (!my_is_int($scId)) {
                    $scId = $user->default_sc_id;
                }
                $serverConfig = $this->find($scId);
                if (!check_distributor_config($serverConfig)) {
                    throw new ApiException('你没有权操作该服务配置!', 200, 1, [$serverConfig]);
                }
                return $serverConfig;
            }
            throw new ApiException('未知的中央服用户类型!');
        }

        return $this->find($user->server_no);
    }

    private function find($serverId)
    {
        if ($this->serverConfig && $this->serverConfig->server_no == $serverId) {
            return $this->serverConfig;
        }

        $this->serverConfig = $this->serverConfigRepository->find($serverId);

        if (!$this->serverConfig) {
            throw new ApiException('找不到可用的服务配置.');
        }

        return $this->serverConfig;
    }

    // 切换数据库连接
    public function changeDBConnection($host, $database, $username, $password, $port = 3306)
    {
        Config::set('database.connections.mysql.host', $host);
        Config::set('database.connections.mysql.database', $database);
        Config::set('database.connections.mysql.username', $username);
        Config::set('database.connections.mysql.password', $password);
        Config::set('database.connections.mysql.port', $port);
        DB::reconnect();
    }

    public function changeDBConnectionById(int $scId)
    {
        $serverConfig = $this->serverConfigRepository->find($scId);

        if (empty($serverConfig)) {
            throw new ApiException('找不到可用的服务配置!');
        }

        $this->changeDBConnection($serverConfig->db_host, $serverConfig->db_name,
            $serverConfig->db_user, $serverConfig->db_password, $serverConfig->db_port);
    }

    // 生成一个16位的服务器配置标识
    public function generateCode($platformType, string $mchId): string
    {
        if (empty($mchId)) {
            throw new \ErrorException('服务配置code生成失败!');
        }
        return $platformType . '_' . $mchId;
    }

    public function getNextServerNo()
    {
        return 1;
//        return ServerConfig::query()->max('server_no') + 1;
//        return CrossServer::query()->max('server_no') + 1;
    }

    public function generateGameDBName(int $serverNo): string
    {
        return 'game_' . $serverNo;
    }

    public function getGameIp(): string
    {
        return '127.0.0.1';
    }

    public function generateGamePort(int $serverNo): int
    {
        return 10000 + $serverNo;
    }
}
