-- ============================================================
-- 升级 SQL：2026.11.06
-- 会员登录频率限制配置（同 IP / 同账号防刷）
-- 幂等：按 name 判断；不写死 id，避免主键冲突
-- ============================================================
SET NAMES utf8mb4;

INSERT INTO `think_config` (`name`,`value`) SELECT 'login_ip_max', '15' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_config` WHERE `name`='login_ip_max');
INSERT INTO `think_config` (`name`,`value`) SELECT 'login_ip_window', '60' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_config` WHERE `name`='login_ip_window');
INSERT INTO `think_config` (`name`,`value`) SELECT 'login_ip_ban', '900' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_config` WHERE `name`='login_ip_ban');
INSERT INTO `think_config` (`name`,`value`) SELECT 'login_account_max', '20' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_config` WHERE `name`='login_account_max');
INSERT INTO `think_config` (`name`,`value`) SELECT 'login_account_window', '300' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_config` WHERE `name`='login_account_window');

UPDATE `think_config` SET `value`='26.11.06.01' WHERE `name`='version';
