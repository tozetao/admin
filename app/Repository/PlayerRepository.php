<?php

namespace App\Repository;

use App\Models\Player;

class PlayerRepository
{
    public function find($id)
    {
        return Player::query()
            ->where('role_id', $id)
            ->first();
    }

    public function findIn(array $ids, $columns = '*')
    {
        return Player::query()->select($columns)->whereIn('role_id', $ids)->get();
    }

    public function findOneBy(array $criteria)
    {
        return Player::query()
            ->where($criteria)
            ->first();
    }

    public function exists($id): bool
    {
        return Player::query()
            ->where('role_id', $id)
            ->exists();
    }


    public function findAll()
    {
        return Player::all();
    }

    public function findBy(?array $criteria, $columns, ?array $orderBy = [], $limit = null, $offset = null)
    {
        $query = Player::query()
            ->select($columns);

        if ($criteria) {
            foreach ($criteria as $key => $value) {
                $query->where($key, $value);
            }
        }

        if ($orderBy) {
            foreach ($orderBy as $key => $value) {
                $query->orderBy($key, $value);
            }
        }

        return $query->limit($limit)->offset($offset)->get();
    }
}
