<?php

namespace App\Extend\Auth;

use App\Repository\UserRepository;
use Illuminate\Auth\EloquentUserProvider;

class TokenUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        $apiToken = $credentials['api_token'] ?? false;
        if (empty($apiToken)) {
            return null;
        }

        $query =  $this->newModelQuery();
        return $query->where('api_token', $apiToken)->get()->first();

//        // 从缓存中查找
//        $repository = app()->make(UserRepository::class);
//        return $repository->findByToken($apiToken);
    }
}
