<?php

namespace App\Repository;

use App\Models\ServerConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ServerConfigRepository
{
    public function getConfigKey(int $id)
    {
        return 'server_config_' . $id;
    }

    public function clearCache($id)
    {
        $key = $this->getConfigKey($id);
        Redis::del($key);
    }

    public function find($id)
    {
        $key = $this->getConfigKey($id);

        $result = Redis::get($key);
        $result = \json_decode($result, true);

        if ($result) {
            $model = new ServerConfig();
            foreach ($result as $column => $value) {
                $model->$column = $value;
            }
            $model->exists = true;
            return $model;
        }

        // 从数据库中查询，如果存在将会更新内存数据。
        $model = ServerConfig::query()
            ->where('server_no', $id)
            ->first();

        if (!$model) {
            return null;
        }

        $data = $model->toArray();
        Log::info($data);
//        $key = $this->getConfigKey($id);

        Redis::set($key, \json_encode($data));
        return $model;
    }

    public function findOneBy(array $criteria)
    {
        return ServerConfig::query()
            ->where($criteria)
            ->first();
    }

    public function findBy(?array $criteria, $columns = '*', ?array $orderBy = [], $limit = null, $offset = null)
    {
        $query = ServerConfig::query()
            ->select($columns);

        foreach ($criteria as $key => $value) {
            $query->where($key, $value);
        }

        if ($orderBy) {
            foreach ($orderBy as $key => $value) {
                $query->orderBy($key, $value);
            }
        }

        if ($limit && $offset) {
            $query->limit($limit)->offset($offset);
        }

        return $query->get();
    }

    public function countFindBy(?array $criteria): int
    {
        $query = ServerConfig::query();

        foreach ($criteria as $key => $value) {
            if ($value) {
                $query->where($key, $value);
            }
        }

        return $query->count();
    }

    public function findIn($serverNos, $columns = '*')
    {
        return ServerConfig::query()
            ->select($columns)
            ->whereIn('server_no', $serverNos)
            ->get();
    }
}
