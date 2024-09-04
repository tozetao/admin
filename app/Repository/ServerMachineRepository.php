<?php

namespace App\Repository;

use App\Models\ServerMachine;

class ServerMachineRepository
{
    public function findBy($criteria, $columns = '*', $offset = null, $limit = null)
    {
        $query = ServerMachine::query()
            ->select($columns);

        foreach ($criteria as $column => $value) {
            $query->where($column, $value);
        }

        if ($offset && $limit) {
            $query->limit($limit)->offset($offset);
        }

        return $query->get();
    }

    public function findIn($serverNo, $machineIds)
    {
        return ServerMachine::query()
            ->where('server_no', $serverNo)
            ->whereIn('machine_id', $machineIds)
            ->get();
    }
}
