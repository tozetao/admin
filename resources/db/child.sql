create table web_machine_snapshot(
    id int primary key auto_increment,
    player_id int not null,
    machine_id int not null,
    image varchar(200) default '' comment '图片地址',
    md5 varchar(64) default '',
    msg varchar(100) default '',
    num int not null comment '结算时的票数',
    created_at int not null,
    type tinyint not null comment '1表示机台结算时，2表示机台掉线',
    key(player_id),
    key(machine_id)
)engine=innodb,charset=utf8mb4;

create table web_machine_failure(
    id int primary key auto_increment,
    player_id int not null,
    machine_id int not null,
    image varchar(200) default '' comment '图片地址',
    msg varchar(100) default '',
    trigger_time int not null,
    status tinyint not null comment '机台状态，0表示未处理，1表示已处理',
    key(player_id),
    key(machine_id)
)engine=innodb,charset=utf8mb4;
