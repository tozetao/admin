<?php

use App\Factory\AdminFactory;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $factory = new AdminFactory();

        $superPermissions = config('permission.super_admin');
        $superAdmin = $factory->create('superadmin', '123456', $superPermissions, 0);
        $superAdmin->save();

        // 新建普通管理员
        $permissions = config('permission.admin');
        $admin = $factory->create('admin', '123456', $permissions, $superAdmin->id);
        $admin->save();


    }
}
