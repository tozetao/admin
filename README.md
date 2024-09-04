### 更新日志



v1.0（国内版本）

- v1.1

  增加月排行榜

  增加娃娃机免费奖励次数





v2.0（海外版本）





### 定时任务

```
- 设置定时任务
* * * * * cd /mnt/www/test_5g_v2 && /www/server/php/73/bin/php artisan schedule:run >> /dev/null 2>&1

- 统计最近一年的数据
php artisan stat last_year
```



### 迁移命令

```php
- 为日志表建立时间索引。
php artisan app db_migrate

- 服务器配置迁移
php artisan app server_config_migrate

- 测试时的服务配置迁移
php artisan app test_migrate
    
- 机台迁移
php artisan app machine_migrate
```




$2y$10$ReyRAy6O.iTMoH8TGF8/weW2MEPiVrHbAJsXFENVS0N.AwRl4t8i.
