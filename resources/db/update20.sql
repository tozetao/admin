-- 玩家货币统计
create table web_player_currency_stat(
    id int primary key auto_increment,
    log_type tinyint not null comment '表示不同日志，1是5G币，5是5G票，3是乐享币，4是乐享票',
    player_id int not null,
    machine_id int not null,
    type int not null,
    cost_value int not null,
    add_value int not null,
    start int not null comment '起始时间，start和end间隔一小时，即每个小时统计一次数据',
    end int not null,
    key(log_type, player_id),
    key(log_type, start, type)
)engine=innodb,charset=utf8mb4;
