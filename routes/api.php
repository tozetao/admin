<?php

use Illuminate\Support\Facades\Route;

Route::get('/test', 'TestController@index');
Route::post('/session/login', 'SessionController@login')
    ->middleware('env');

// Route::get('/platform', 'PlatformController@show');

Route::middleware(['auth:api', 'env'])->group(function () {
    Route::get('/session/show', 'SessionController@show');
    Route::post('/session/update', 'SessionController@update');
    Route::post('/session/logout', 'SessionController@logout');

    Route::get('/info', 'SessionController@info');
    Route::get('/refresh', 'SessionController@show');
    Route::post('/change_password', 'SessionController@changePassword');
    Route::post('/key', 'SessionController@key');

    Route::post('/lock', 'SessionController@lock');
    Route::post('/unlock', 'SessionController@unlock');

    Route::post('upload/image', 'HandlerController@imageUpload');

    // 服务器选项
    Route::get('server_options', 'ServerConfigController@options');
    // Route::get('jssdk/config', 'WechatController@jssdkConfig');

    Route::get('/dashboard', 'DashboardController@index');
    Route::get('/polling', 'PollingController@index');
});


// 只针对中央服的路由
Route::middleware(['auth:api', 'authorize', 'env'])->group(function () {
    // 管理员模块
    Route::get('admin/index', 'AdminController@index');
    Route::get('admin/show/{admin}', 'AdminController@show');
    Route::post('admin/create', 'AdminController@create');
    Route::post('admin/update/{admin}', 'AdminController@update');
    Route::post('admin/change_status', 'AdminController@changeStatus');
    Route::get('permission/index', 'PermissionController@all');

    // 服务配置
    Route::get('server_config/index', 'ServerConfigController@index');
    Route::post('server_config/create', 'ServerConfigController@create');
    Route::post('server_config/update', 'ServerConfigController@update');
    Route::get('server_config/show', 'ServerConfigController@show');
});

Route::middleware(['auth:api', 'authorize', 'env'])->group(function () {
    // 玩家 管理
    Route::get('player/index', 'PlayerController@index');
    Route::get('player/show', 'PlayerController@show');
    Route::post('player/lock', 'PlayerController@lock');
    Route::post('player/unlock', 'PlayerController@unlock');
    Route::post('player/give', 'PlayerController@give');
    Route::post('player/deduct', 'PlayerController@deduct');
    Route::post('player/kick', 'PlayerController@kick');

    // 游戏记录
    Route::get('game_log', 'GameLogController@index');
    Route::get('game_log/export', 'GameLogController@export');
});
