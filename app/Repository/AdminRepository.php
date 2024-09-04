<?php

namespace App\Repository;

use App\Models\User;

class AdminRepository
{
    public function findOneBy($criteria)
    {
        return User::query()
            ->where('type', User::Admin)
            ->where($criteria)
            ->first();
    }

    public function findIn(array $ids)
    {
        return User::query()
            ->where('type', User::Admin)
            ->whereIn('id', $ids)
            ->get();
    }

    private function buildQuery(string $account, int $pid, $status): \Illuminate\Database\Eloquent\Builder
    {
        $query = User::query()
            ->where('type', User::Admin);

        if ($account) {
            $query->where('account', $account);
        }

        if ($pid) {
            $query->where('pid', $pid);
        }

        if (is_int_number($status)) {
            $query->where('status', $status);
        }
        return $query;
    }

    /**
     * @param string $account 要查询的账号
     * @param int $pid 查询由pid创建的用户
     * @param mixed $status 查询的用户状态
     * @param $offset
     * @param $limit
     * @return mixed
     */
    public function findMore(string $account, int $pid, $status, $offset, $limit)
    {
        $query = $this->buildQuery($account, $pid, $status);
        return $query->orderby('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    // 统计findMore的数量
    public function countFindMore(string $account, int $pid, $status): int
    {
        $query = $this->buildQuery($account, $pid, $status);
        return $query->count();
    }

    public function findAllDescendantIds(int $id): ?array
    {
        $children = $this->findAllDescendants($id, ['id', 'pid']);
        if (!$children) {
            return null;
        }
        return array_column($children, 'id');
    }


    /**
     * 查询$id下的所有咨询账号
     *
     * @param int $id 账号id
     * @param mixed $columns
     * @return array|mixed|null
     */
    public function findAllDescendants(int $id, $columns = '*')
    {
        $models = User::query()
            ->select($columns)
            ->where('type', User::Admin)
            ->get();
        if (empty($models)) {
            return null;
        }
        return $this->recursion($models, $id);
    }

    public function recursion($models, int $pid): array
    {
        $result = [];
        foreach ($models as $model) {
            if ($model->pid == $pid) {
                $result[] = $model;
                if ($children = $this->recursion($models, $model->id)) {
                    $result = array_merge($result, $children);
                }
            }
        }
        return $result;
    }

}
