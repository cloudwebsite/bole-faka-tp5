-- ============================================================
-- 升级 SQL：2026.11.07
-- Cloudflare Turnstile 配置（CODE_TYPE=2）
-- 幂等：按 name 判断；不写死 id，避免主键冲突
-- ============================================================
SET NAMES utf8mb4;

INSERT INTO `think_config` (`name`,`value`) SELECT 'turnstile_site_key', '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_config` WHERE `name`='turnstile_site_key');
INSERT INTO `think_config` (`name`,`value`) SELECT 'turnstile_secret_key', '' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_config` WHERE `name`='turnstile_secret_key');
INSERT INTO `think_config` (`name`,`value`) SELECT 'turnstile_fallback_image', '1' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `think_config` WHERE `name`='turnstile_fallback_image');

UPDATE `think_config` SET `value`='26.11.07.01' WHERE `name`='version';
