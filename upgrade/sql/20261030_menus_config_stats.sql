-- ============================================================
-- 升级 SQL：2026.10.28 ~ 2026.10.30
-- 商品卡密入口/入库日志/按批删未用、销售统计、卡密统计、
-- 数据清理、USDT 相关权限、前台公告/下单免责配置、版本号
-- 幂等：按 name / id 判断；可由在线更新自动执行
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 1) 后台菜单 / 权限节点
-- ----------------------------
-- [123] 商品卡密  name=admin/kami/goods  pid=85
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 123, 'admin/kami/goods', '商品卡密', 1, 1, 'fa fa-key', '', 85, 15, 1647587438, 1647587438 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=123 OR `name`='admin/kami/goods');
-- [124] 查看商品卡密  name=admin/kami/index  pid=123
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 124, 'admin/kami/index', '查看商品卡密', 1, 1, '', '', 123, 50, 1647587438, 1647587438 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=124 OR `name`='admin/kami/index');
-- [125] 补货页  name=admin/kami/add_kami  pid=123
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 125, 'admin/kami/add_kami', '补货页', 1, 1, '', '', 123, 50, 1647587438, 1647587438 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=125 OR `name`='admin/kami/add_kami');
-- [126] 导入补货  name=admin/kami/save_kami  pid=123
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 126, 'admin/kami/save_kami', '导入补货', 1, 1, '', '', 123, 50, 1647587438, 1647587438 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=126 OR `name`='admin/kami/save_kami');
-- [127] 删除单条卡密  name=admin/kami/del_kami  pid=123
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 127, 'admin/kami/del_kami', '删除单条卡密', 1, 1, '', '', 123, 50, 1647587438, 1647587438 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=127 OR `name`='admin/kami/del_kami');
-- [128] 删除全部卡密  name=admin/kami/del_all  pid=123
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 128, 'admin/kami/del_all', '删除全部卡密', 1, 1, '', '', 123, 50, 1647587438, 1647587438 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=128 OR `name`='admin/kami/del_all');
-- [129] 删除已用卡密  name=admin/kami/del_guoqi  pid=123
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 129, 'admin/kami/del_guoqi', '删除已用卡密', 1, 1, '', '', 123, 50, 1647587438, 1647587438 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=129 OR `name`='admin/kami/del_guoqi');
-- [130] 导出卡密  name=admin/kami/export_kami  pid=123
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 130, 'admin/kami/export_kami', '导出卡密', 1, 1, '', '', 123, 50, 1647587438, 1647587438 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=130 OR `name`='admin/kami/export_kami');
-- [131] 导出查询  name=admin/kami/export_log  pid=123
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 131, 'admin/kami/export_log', '导出查询', 1, 1, '', '', 123, 50, 1647587438, 1647587438 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=131 OR `name`='admin/kami/export_log');
-- [132] USDT统计  name=admin/trc20/getmain  pid=116
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 132, 'admin/trc20/getmain', 'USDT统计', 1, 1, '', '', 116, 50, 1761628800, 1761628800 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=132 OR `name`='admin/trc20/getmain');
-- [133] USDT列表数据  name=admin/trc20/getorders  pid=116
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 133, 'admin/trc20/getorders', 'USDT列表数据', 1, 1, '', '', 116, 50, 1761628800, 1761628800 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=133 OR `name`='admin/trc20/getorders');
-- [134] USDT补单  name=admin/trc20/setbd  pid=116
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 134, 'admin/trc20/setbd', 'USDT补单', 1, 1, '', '', 116, 50, 1761628800, 1761628800 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=134 OR `name`='admin/trc20/setbd');
-- [135] USDT删单  name=admin/trc20/delorder  pid=116
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 135, 'admin/trc20/delorder', 'USDT删单', 1, 1, '', '', 116, 50, 1761628800, 1761628800 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=135 OR `name`='admin/trc20/delorder');
-- [136] USDT删过期  name=admin/trc20/delgqorder  pid=116
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 136, 'admin/trc20/delgqorder', 'USDT删过期', 1, 1, '', '', 116, 50, 1761628800, 1761628800 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=136 OR `name`='admin/trc20/delgqorder');
-- [137] USDT删七天前  name=admin/trc20/dellastorder  pid=116
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 137, 'admin/trc20/dellastorder', 'USDT删七天前', 1, 1, '', '', 116, 50, 1761628800, 1761628800 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=137 OR `name`='admin/trc20/dellastorder');
-- [138] 入库日志  name=admin/kami/addlog  pid=123
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 138, 'admin/kami/addlog', '入库日志', 1, 1, '', '', 123, 40, 1761796800, 1761796800 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=138 OR `name`='admin/kami/addlog');
-- [139] 按批删除未用卡密  name=admin/kami/del_by_addid  pid=123
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 139, 'admin/kami/del_by_addid', '按批删除未用卡密', 1, 1, '', '', 123, 50, 1761796800, 1761796800 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=139 OR `name`='admin/kami/del_by_addid');
-- [140] 数据清理  name=admin/data_clean/index  pid=1
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 140, 'admin/data_clean/index', '数据清理', 1, 1, 'fa fa-trash', '', 1, 90, 1761796800, 1761796800 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=140 OR `name`='admin/data_clean/index');
-- [141] 清理预览  name=admin/data_clean/preview  pid=140
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 141, 'admin/data_clean/preview', '清理预览', 1, 1, '', '', 140, 50, 1761796800, 1761796800 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=141 OR `name`='admin/data_clean/preview');
-- [142] 执行清理  name=admin/data_clean/run  pid=140
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 142, 'admin/data_clean/run', '执行清理', 1, 1, '', '', 140, 50, 1761796800, 1761796800 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=142 OR `name`='admin/data_clean/run');
-- [143] 销售统计  name=admin/order/sales_stats  pid=88
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 143, 'admin/order/sales_stats', '销售统计', 1, 1, 'fa fa-bar-chart', '', 88, 40, 1761796800, 1761796800 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=143 OR `name`='admin/order/sales_stats');
-- [144] 销售统计数据  name=admin/order/sales_stats_data  pid=88
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 144, 'admin/order/sales_stats_data', '销售统计数据', 1, 1, '', '', 88, 50, 1761796800, 1761796800 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=144 OR `name`='admin/order/sales_stats_data');
-- [145] 卡密消耗统计  name=admin/kami/key_stats  pid=123
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 145, 'admin/kami/key_stats', '卡密消耗统计', 1, 1, 'fa fa-bar-chart', '', 123, 35, 1761796800, 1761796800 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=145 OR `name`='admin/kami/key_stats');
-- [146] 卡密统计数据  name=admin/kami/key_stats_data  pid=123
INSERT INTO `think_auth_rule` (`id`,`name`,`title`,`type`,`status`,`css`,`condition`,`pid`,`sort`,`create_time`,`update_time`) SELECT 146, 'admin/kami/key_stats_data', '卡密统计数据', 1, 1, '', '', 123, 50, 1761796800, 1761796800 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_auth_rule` WHERE `id`=146 OR `name`='admin/kami/key_stats_data');

-- 仅当角色 rules 非空（按节点鉴权）时追加；rules 为空表示超管不校验，勿改写
UPDATE `think_auth_group`
SET `rules` = TRIM(BOTH ',' FROM CONCAT(`rules`, ',', '123,124,125,126,127,128,129,130,131,132,133,134,135,136,137,138,139,140,141,142,143,144,145,146'))
WHERE IFNULL(`rules`,'') <> ''
  AND CONCAT(',', `rules`, ',') NOT LIKE '%,123,%';

-- ----------------------------
-- 2) 系统设置
-- ----------------------------
INSERT INTO `think_config` (`id`,`name`,`value`) SELECT 144, 'order_disclaimer', '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_config` WHERE `name`='order_disclaimer');
INSERT INTO `think_config` (`id`,`name`,`value`) SELECT 145, 'order_disclaimer_wait', '10' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_config` WHERE `name`='order_disclaimer_wait');
INSERT INTO `think_config` (`id`,`name`,`value`) SELECT 146, 'tangg_content', '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_config` WHERE `name`='tangg_content');
UPDATE `think_config` SET `value`='26.11.03.01' WHERE `name`='version';

-- ----------------------------
-- 3) 可选表（旧库通常已有；IF NOT EXISTS 幂等）
-- ----------------------------
CREATE TABLE IF NOT EXISTS `think_addmaillog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `addbiaoshi` varchar(100) NOT NULL DEFAULT '' COMMENT '补货标示',
  `addnum` int(11) NOT NULL DEFAULT 0 COMMENT '补货数量',
  `successnum` int(11) NOT NULL DEFAULT 0 COMMENT '成功数量',
  `failnum` int(11) NOT NULL DEFAULT 0 COMMENT '失败数量',
  `existnum` int(11) NOT NULL DEFAULT 0 COMMENT '存在数量',
  `userip` varchar(255) NOT NULL DEFAULT '' COMMENT '操作ip',
  `goodid` int(11) NOT NULL DEFAULT 0 COMMENT '商品ID',
  `addqudao` varchar(255) NOT NULL DEFAULT '' COMMENT '添加渠道',
  `create_time` int(11) NOT NULL DEFAULT 0,
  `update_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `addbiaoshi` (`addbiaoshi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `think_recharge_kami` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(64) NOT NULL DEFAULT '' COMMENT '卡密',
  `money` decimal(11,2) NOT NULL DEFAULT 0.00 COMMENT '面额',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0未用 1已用',
  `memberid` int(11) NOT NULL DEFAULT 0 COMMENT '兑换会员',
  `use_time` int(11) NOT NULL DEFAULT 0 COMMENT '兑换时间',
  `batch_no` varchar(32) NOT NULL DEFAULT '' COMMENT '批次号',
  `admin_id` int(11) NOT NULL DEFAULT 0 COMMENT '生成管理员',
  `userip` varchar(64) NOT NULL DEFAULT '' COMMENT '兑换IP',
  `create_time` int(11) NOT NULL DEFAULT 0,
  `update_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_batch` (`batch_no`),
  KEY `idx_memberid` (`memberid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员充值卡密';

SET FOREIGN_KEY_CHECKS = 1;

