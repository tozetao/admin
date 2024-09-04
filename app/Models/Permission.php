<?php

namespace App\Models;

class Permission
{
    // 包含了系统中的所有权限配置。
    // 可用于系统中不同的角色，比如管理员、代理或客服。
    public function getConfig(): array
    {
        // key是权限名
        // actions对应着控制器方法名，比如AdminController的index方法对应的方法名为Admin.index
        return [
            'server_config' => [
                'name' => trans('common.permissions.server_config'),
                'actions' => [
                    'ServerConfig.index', 'ServerConfig.create', 'ServerConfig.show', 'ServerConfig.update',
                ]
            ],
            'admin_management' => [
                'name' => trans('common.permissions.admin_management'),
                'actions' => [
                    'Admin.index', 'Admin.show', 'Admin.create', 'Admin.update', 'Admin.changeStatus', 'Permission.all', 'System.clear'
                ],
            ],
            'player_management' => [
                'name' => trans('common.permissions.player_management'),
                'actions' => [
                    'Player.index', 'Player.show', 'Player.resetPassword', 'Player.changeStatus',
                    'Player.lock', 'Player.unlock', 'Player.kick'
                ]
            ],
            'game_log' => [
                'name' => trans('common.permissions.game_log'),
                'actions' => [
                    'GameLog.export', 'GameLog.index',
                    'ActionLog.index',
                ]
            ],
            'game_setting' => [
                'name' => trans('common.permissions.game_setting'),
                'actions' => [
                    'Setting.initConfig',
                    'BaseSetting.index', 'BaseSetting.update',
                ]
            ],
            'email' => [
                'name' => trans('common.permissions.email'),
                'actions' => [
                    'Email.index', 'Email.create',  'Email.delete',
                ]
            ]
        ];
    }

    public function allPermissions(): array
    {
        $permissions = [];
        $config = $this->getConfig();
        foreach ($config as $key => $item) {
            $permissions[] = [
                'name' => $item['name'],
                'key' => $key
            ];
        }
        return $permissions;
    }

    // 返回用户拥有的权限
    public function getPermissionsByKeys(array $keys): array
    {
        $result = [];
        $config = $this->getConfig();
        foreach ($keys as $key) {
            if (isset($config[$key])) {
                $result[] = [
                    'name' => $config[$key]['name'],
                    'key' => $key
                ];
            }
        }
        return $result;
    }

    /**
     * 返回权限数组所对应的action
     * @param array $permissions    权限数组
     * @return array|null
     */
    public function getActions(array $permissions): ?array
    {
        if (empty($permissions)) {
            return null;
        }

        $config = $this->getConfig();
        $actions = [];
        foreach ($permissions as $permission) {
            if (isset($config[$permission])) {
                $actions = array_merge($actions, $config[$permission]['actions']);
            }
        }
        return $actions;
    }

}

