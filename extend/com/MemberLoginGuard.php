<?php
namespace com;

/**
 * 会员登录防刷：按 IP / 账号限流，超限临时封禁
 *
 * 配置项（均可空，空则用默认值）：
 * - login_ip_max          IP 窗口内最多尝试次数，默认 15
 * - login_ip_window       IP 计数窗口秒数，默认 60
 * - login_ip_ban          IP 超限封禁秒数，默认 900（15 分钟）
 * - login_account_max     同账号窗口内最多尝试次数，默认 20
 * - login_account_window  账号计数窗口秒数，默认 300
 */
class MemberLoginGuard
{
    const PREFIX_IP  = 'mlg_ip_';
    const PREFIX_ACC = 'mlg_acc_';
    const PREFIX_BAN = 'mlg_ban_';

    /**
     * @param string $account 登录账号（可空，仅 IP 限流）
     * @return array{ok:bool,code:int,msg:string}
     */
    public static function check($account = '')
    {
        $cfg = self::config();
        $ipKey = self::ipCacheKey();

        $banUntil = cache(self::PREFIX_BAN . $ipKey);
        if ($banUntil) {
            $left = max(1, (int)ceil((intval($banUntil) - time()) / 60));
            return [
                'ok'   => false,
                'code' => -8,
                'msg'  => '登录过于频繁，请' . $left . '分钟后再试',
            ];
        }

        $ipCnt = intval(cache(self::PREFIX_IP . $ipKey) ?: 0);
        if ($ipCnt >= $cfg['ip_max']) {
            self::banIp($ipKey, $cfg['ip_ban']);
            return [
                'ok'   => false,
                'code' => -8,
                'msg'  => '登录过于频繁，请稍后再试',
            ];
        }

        $account = trim((string)$account);
        if ($account !== '') {
            $accCnt = intval(cache(self::PREFIX_ACC . self::accountCacheKey($account)) ?: 0);
            if ($accCnt >= $cfg['account_max']) {
                return [
                    'ok'   => false,
                    'code' => -8,
                    'msg'  => '该账号登录过于频繁，请稍后再试',
                ];
            }
        }

        return ['ok' => true, 'code' => 1, 'msg' => ''];
    }

    /**
     * 记录一次登录尝试（成功/失败均计入）
     * @param string $account
     */
    public static function hit($account = '')
    {
        $cfg = self::config();
        $ipKey = self::ipCacheKey();

        $ipCnt = intval(cache(self::PREFIX_IP . $ipKey) ?: 0) + 1;
        cache(self::PREFIX_IP . $ipKey, $ipCnt, $cfg['ip_window']);
        if ($ipCnt >= $cfg['ip_max']) {
            self::banIp($ipKey, $cfg['ip_ban']);
        }

        $account = trim((string)$account);
        if ($account !== '') {
            $accKey = self::accountCacheKey($account);
            $accCnt = intval(cache(self::PREFIX_ACC . $accKey) ?: 0) + 1;
            cache(self::PREFIX_ACC . $accKey, $accCnt, $cfg['account_window']);
        }
    }

    /**
     * @return array
     */
    protected static function config()
    {
        return [
            'ip_max'          => max(5, intval(config('login_ip_max') ?: 15)),
            'ip_window'       => max(30, intval(config('login_ip_window') ?: 60)),
            'ip_ban'          => max(60, intval(config('login_ip_ban') ?: 900)),
            'account_max'     => max(5, intval(config('login_account_max') ?: 20)),
            'account_window'  => max(30, intval(config('login_account_window') ?: 300)),
        ];
    }

    protected static function ipCacheKey()
    {
        $ip = function_exists('getIP') ? (string)getIP() : '';
        return preg_replace('/[^0-9a-fA-F:\.]/', '_', $ip);
    }

    protected static function accountCacheKey($account)
    {
        return md5(strtolower(trim((string)$account)));
    }

    protected static function banIp($ipKey, $banSec)
    {
        cache(self::PREFIX_BAN . $ipKey, time() + $banSec, $banSec);
    }
}
