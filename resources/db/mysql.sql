-- 1.1
alter table web_users add column promoter_level tinyint default 0 not null comment '推广员等级';
update web_users set web_users.promoter_level = 1 where type = 3;


-- 1.0
create table web_server_config(
      server_no int not null primary key comment '服务器编号，从1开始',
      db_host varchar(100) default '',
      db_name varchar(60) default '',
      db_user varchar(50) default '',
      db_password varchar(50) default '',
      db_port int default 3306,
      game_ip varchar(15) comment '游戏ip',
      game_port int default 0 comment '游戏端口',
      start int default 0 comment '统计的起始时间',
      end int default 0 comment '统计的结束时间',
      `code` varchar(100) not null comment '服务器编码',
      server_name varchar(30) not null comment '服务器名称',
      merge_room tinyint default 1 comment '房间合并，1合并，0不合并',
      stream_url varchar(200) not null comment '视频推流地址',
      mch_id varchar(100) not null comment '商户号',
      agent_type tinyint default 0 comment '代理类型，0未知、1是晞娱科技、2是蓝迪科技、100运营',
      platform_type int default 0 comment '第三方平台类型，10001 酷酷宝、10003 翠花、10005 油菜花、10002 鑫丰系统、10004 八达系统、10006 世软系统、10007 翰轩系统、10008 汤姆熊、10009 自然源',
      expired_at int default 0 comment '平台到期时间，0表示不过期',
      created_at int not null,
      unique key(code)
)engine=innodb, charset=utf8mb4;

-- insert into web_server_config(server_no, db_host, db_name, db_user, db_password, db_port, game_port,code, server_name, stream_url, mch_id)
--     values (1, '192.168.1.72', 'game_server', 'root', '123456', 3306, 10001, 'vWeSdyt6tYtpzuT9qKIaBI2blW4sxJ', '1服', '', '');

-- rm-wz922y51en36p4j895o.mysql.rds.aliyuncs.com
-- root
-- Yi_jia123456

-- 服务器、机台关联表
create table web_server_machine(
    id int not null primary key auto_increment,
    machine_id int not null comment '机台id',
    server_no int not null comment '机台所属子服id',
    key(machine_id),
    key(server_no)
)engine=innodb, charset=utf8mb4;

-- username、server_no作为唯一的账号
create table web_users(
    id int primary key auto_increment,
    account varchar(60) not null comment '账号名',
    password varchar(100) not null comment '密码',
    api_token varchar(80) default '',
    type tinyint not null comment '账号类型，1是管理员，2是代理，3是推广员',
    status tinyint not null comment '0是正常，1是禁用，2是锁定',
    pid int default 0 comment '上级账号id',
    permissions text comment '权限列表',
    locale varchar(10) default 'zh_CN' comment '本地化',
    server_no int not null comment '服务配置id',
    created_at int not null default 0,
    updated_at int not null default 0,
    player_id int not null default 0 comment '推广员绑定的玩家id',
    unique key(account, server_no),
    key(server_no)
)engine=innodb, charset=utf8mb4;
alter table web_users add column default_sc_id int not null default 0 comment '默认要操作的子服id，用于前端传参';
-- alter table web_users add column player_id int not null default 0 comment '推广员绑定的玩家id';

-- $2y$10$v5Yhk8A8LI96.EPCkuoCVegyfZTLAiPot8dttMrnNvc3OIDrJT2Iy
-- insert into web_users(id, account, password, api_token, `type`, status, pid, permissions, server_no, created_at, updated_at)
--     values(1, 'admin', '$2y$10$v5Yhk8A8LI96.EPCkuoCVegyfZTLAiPot8dttMrnNvc3OIDrJT2Iy', 'nP29CCCzrMR38SIbNgQG25wCWcRHiQ79D4IfiMoJtY1Umeyzu7QGijhmV02I',
--            1, 0, 0, '["admin_management","player_management","game_log", "machine_management", "shop", "statistics"]', 1, 1673575523, 0);

create table web_machine_profit_stats(
    id int primary key auto_increment,
    machine_id int not null comment '机器id',
    put_coin int not null default 0 comment '投入的币总数',
    refund_ticket int not null default 0 comment '退的票总数',
    put_share_coin int not null default 0 comment '投入的分享币总数',
    refund_share_ticket int null default 0 comment '退的乐享票总数',
    prize_num int not null default 0 comment '娃娃机的中奖数量',
    `start` int not null comment '统计的起始时间，整点时间戳',
    `end` int not null comment '统计的结束时间，整点时间戳',
    `date` varchar(30) not null comment '统计的日期',
    server_no int not null comment '记录所属的服务编号',
    key(`date`),
    key(`start`)
)engine=innodb, charset=utf8mb4;


create table web_system_exchange_stats(
     id int primary key auto_increment,
     in_coin int not null default 0 comment '兑入的5G币',
     out_ticket int not null default 0 comment '兑出的5G票',
     in_share_coin int default 0 comment '兑入的乐享币',
     out_share_ticket int default 0 comment '兑出的乐享票',
     `start` int not null comment '统计的起始时间，整点时间戳',
     `end` int not null comment '统计的结束时间，整点时间戳',
     `date` varchar(30) not null comment '统计的日期',
     server_no int not null comment '记录所属的服务编号',
     key(`date`),
     key(`start`)
)engine=innodb, charset=utf8mb4;

-- 注：start、end不能一致。
create table web_mainboard_extra(
    code varchar(50) primary key comment '主板code',
    toggle tinyint not null default 0 comment '主板的自动切换开关，1表示开启自动切换，0则关闭自动切换',
    start tinyint not null comment '线上开始时间，0-23点',
    end tinyint not null comment '线下开始时间，0-23点',
    key(toggle)
)engine=innodb,charset=utf8mb4;


CREATE TABLE `web_action_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(200) default '',
  `content` varchar(2048) default '',
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` int(11) NOT NULL,
  server_no int not null,
  PRIMARY KEY (`id`),
  key(server_no, created_at)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


truncate web_users;
truncate web_server_config;
truncate web_server_machine;
truncate web_mainboard_extra;
truncate web_machine_profit_stats;
truncate web_system_exchange_stats;

drop table web_users;
drop table web_server_config;
drop table web_server_machine;
drop table web_mainboard_extra;
drop table web_machine_profit_stats;
drop table web_system_exchange_stats;




-- http://wms-images1.oss-cn-hangzhou.aliyuncs.com/upload/wms.wuliancentre.com/20230225/e1f0e4a00a013a9f12e0f90918f7177e.jpeg


-- 插入代理商
insert into web_users values(default, 'YunWei100', '$2y$10$7YpFkEWj5VVNdET0M3E0Eup/bkoM6ndyyGBF4iQGGHE6RIn6s2u3.', 'token',
    2, 0, 0, '["server_config","promoter","statistics","machine_management","player_management","shop","game_log","game_setting","email","chart"]',
    'zh_CN', 1, UNIX_TIMESTAMP(), 0, 0, 25);

-- 插入1服配置
insert into web_server_config(
    server_no, db_host, db_name, db_user, db_password, db_port, game_ip, game_port, start, end, code, server_name, merge_room, stream_url, mch_id,
    agent_type, platform_type, expired_at, created_at)
    value(1, '127.0.0.1', 'game', 'root', '', 3306, '127.0.0.1', 10001, 0, 0, '0_foreign', '海外服务器', 0, '', 'foreign', 1, 0, UNIX_TIMESTAMP() + 31536000, UNIX_TIMESTAMP());
