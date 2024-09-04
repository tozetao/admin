<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('web_users', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('account')->unique();
            $table->string('password');

            $table->string('api_token', 64)
                ->unique()
                ->nullable(false);

            $table->tinyInteger('type')
                ->index()
                ->default(0)
                ->comment('账号类型，1是管理员');

            $table->tinyInteger('status')
                ->default('1')->comment('1是启用，2是封禁');

            $table->integer('pid')
                ->comment('上级id');

            $table->text('permissions')->comment('权限列表');

            $table->string('locale', 10)->default('en');

            $table->integer('created_at')->nullable(false)->comment('创建时间');
            $table->integer('updated_at')->nullable(false)->comment('更新时间');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
