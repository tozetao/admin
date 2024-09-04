<?php

return [
    'super_admin' => [
        'server_config', // 普通服管理员绝对不能有该权限。
        'admin_management',
    ],
    'admin' => [
        'admin_management',
    ],
];
