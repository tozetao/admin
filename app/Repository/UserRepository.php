<?php

namespace App\Repository;

use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class UserRepository
{
    public function findOneBy(array $criteria, $columns = '*')
    {
        $query = User::query()
            ->select($columns);

        if ($criteria) {
            foreach ($criteria as $key => $value) {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    // 注：不要使用该方法返回的model来更新数据库中的数据，而是要使用下面的update方法来进行更新。
    public function findByToken($token)
    {
        return $this->findOneBy(['api_token' => $token]);
    }

    public function update(User $user, ?array $data): int
    {
        return User::query()
            ->where('id', $user->getAuthIdentifier())
            ->update($data);
    }

    public function findIn(array $ids, $columns = '*')
    {
        return User::query()
            ->whereIn('id', $ids)
            ->select($columns)
            ->get();
    }

    public function removeApiToken(User $user): int
    {
        return User::query()
            ->where('api_token', $user->api_token)
            ->update([
                'api_token' => $this->generateToken()
            ]);
    }

    public function generateApiToken(User $user): string
    {
        $token = $this->generateToken();
        if (!$this->update($user, ['api_token' => $token])) {
            throw new \ErrorException('The Api token generate failed.');
        }
        return $token;
    }

    public function generateToken(): string
    {
        return Str::random(64);
    }
}
