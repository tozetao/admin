<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GameAPIException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * @throws GameAPIException
     * @throws \Exception
     */
    public function create(Request $request)
    {
//        throw new ModelNotFoundException('user not found.');
//        $this->validate($request, [
//            'id' => 'required',
//            'age' => 'required'
//        ]);

//        $user = $userFactory->create($request->post('account'), '123456');
//        dd($user->save());
    }
}
