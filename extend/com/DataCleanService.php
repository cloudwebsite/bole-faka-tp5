<?php
namespace com;

use think\Db;

    /**
     * 后台数据清理：按保留天数 + 勾选项预览/执行
     * 硬约束：只删「早于 cutoff」的数据（cutoff = 当前时间 − 保留天数），绝不触碰近 N 天内记录。
     * 不含 Login 整目录盲删；订单卡密 txt 仅清 public/upload/*.txt
     */
    class DataCleanService
    {
    /** @var array key => 中文名 */
    public static $itemLabels = [
        'pay_order' => '支付中间单 pay_order',
        'log' => '后台行为日志 log',
        'addmaillog' => '卡密入库日志 addmaillog',
        'member_integral_log' => '积分明细',
        'member_login_log' => '会员登录日志',
        'member_payorder' => '会员充值单',
        'member_money_log' => '财务明细',
        'info_unpaid' => '订单-仅未付款',
        'info_all' => '订单-全部(危险)',
        'mail_used' => '已用卡密 mail',
        'order_kami_txt' => '订单卡密 txt 文件',
    ];

    /** @var array 组 => keys */
    public static $groups = [
        'g_log' => ['log', 'addmaillog', 'member_login_log', 'member_integral_log', 'member_money_log'],
        'g_pay' => ['pay_order', 'member_payorder'],
        'g_order_safe' => ['info_unpaid'],
        'g_order_danger' => ['info_all'],
        'g_kami' => ['mail_used'],
        'g_kami_txt' => ['order_kami_txt'],
        'g_login_compat' => [
            'pay_order', 'log', 'addmaillog', 'member_integral_log', 'member_login_log',
            'member_payorder', 'member_money_log', 'info_unpaid', 'mail_used', 'order_kami_txt',
        ],
    ];

    public static function normalizeDays($days)
    {
        $days = intval($days);
        if ($days < 7 || $days > 3650) {
            return 90;
        }
        return $days;
    }

    public static function normalizeLimit($limit)
    {
        $limit = intval($limit);
        if ($limit < 1) {
            return 1000;
        }
        if ($limit > 50000) {
            return 50000;
        }
        return $limit;
    }

    /**
     * 清理截止时间戳：仅删除时间字段严格早于此值的数据
     */
    public static function cutoff($days)
    {
        $days = self::normalizeDays($days);
        $c = time() - $days * 86400;
        // 防御：cutoff 必须早于「现在」至少约 7 天
        $minGap = 7 * 86400;
        if ($c > time() - $minGap) {
            $c = time() - $minGap;
        }
        return $c;
    }

    /**
     * @param array $keys
     * @return array
     */
    public static function filterValidKeys($keys)
    {
        if (!is_array($keys)) {
            return [];
        }
        $allowed = array_keys(self::$itemLabels);
        $out = [];
        foreach ($keys as $k) {
            $k = (string)$k;
            if (in_array($k, $allowed, true)) {
                $out[] = $k;
            }
        }
        return array_values(array_unique($out));
    }

    public static function uploadDir()
    {
        return rtrim(ROOT_PATH, '/\\') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'upload';
    }

    /**
     * 枚举 public/upload 下过期 .txt（不递归子目录中的非 txt；仅顶层 txt，与发货写盘一致）
     * @return string[] 绝对路径
     */
    public static function listExpiredKamiTxt($cutoff)
    {
        $dir = self::uploadDir();
        $files = [];
        if (!is_dir($dir)) {
            return $files;
        }
        $dh = @opendir($dir);
        if (!$dh) {
            return $files;
        }
        while (false !== ($file = readdir($dh))) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            if (!preg_match('/\.txt$/i', $file)) {
                continue;
            }
            $full = $dir . DIRECTORY_SEPARATOR . $file;
            if (!is_file($full)) {
                continue;
            }
            $mtime = @filemtime($full);
            if ($mtime !== false && $mtime < $cutoff) {
                $files[] = $full;
            }
        }
        closedir($dh);
        return $files;
    }

    /**
     * @param int $days
     * @param array $keys
     * @return array key => count
     */
    public static function preview($days, $keys)
    {
        $cutoff = self::cutoff($days);
        $keys = self::filterValidKeys($keys);
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = self::countItem($key, $cutoff);
        }
        return $result;
    }

    /**
     * @param int $days
     * @param array $keys
     * @param int $limit
     * @param bool $loopUntilEmpty 大表是否循环到清完（单次请求内有上限轮次）
     * @return array key => deleted
     */
    public static function run($days, $keys, $limit = 1000, $loopUntilEmpty = false)
    {
        $cutoff = self::cutoff($days);
        // 无有效截止时间则拒绝执行，避免误删近期数据
        if ($cutoff < 1 || $cutoff >= time()) {
            return [];
        }
        $keys = self::filterValidKeys($keys);
        $limit = self::normalizeLimit($limit);
        $deleted = [];
        foreach ($keys as $key) {
            $deleted[$key] = self::deleteItem($key, $cutoff, $limit, $loopUntilEmpty);
        }
        return $deleted;
    }

    protected static function countItem($key, $cutoff)
    {
        switch ($key) {
            case 'pay_order':
                return (int)Db::name('pay_order')->where('create_date', '<', $cutoff)->count();
            case 'log':
                return (int)Db::name('log')->where('add_time', '<', $cutoff)->count();
            case 'addmaillog':
                return (int)Db::name('addmaillog')->where('create_time', '<', $cutoff)->count();
            case 'member_integral_log':
                return (int)Db::name('member_integral_log')->where('create_time', '<', $cutoff)->count();
            case 'member_login_log':
                return (int)Db::name('member_login_log')->where('create_time', '<', $cutoff)->count();
            case 'member_payorder':
                return (int)Db::name('member_payorder')->where('create_time', '<', $cutoff)->count();
            case 'member_money_log':
                return (int)Db::name('member_money_log')->where('create_time', '<', $cutoff)->count();
            case 'info_unpaid':
                return (int)Db::name('info')->where('create_time', '<', $cutoff)->where('mstatus', 2)->count();
            case 'info_all':
                return (int)Db::name('info')->where('create_time', '<', $cutoff)->count();
            case 'mail_used':
                return (int)Db::name('mail')->where('create_time', '<', $cutoff)->where('mis_use', 1)->count();
            case 'order_kami_txt':
                return count(self::listExpiredKamiTxt($cutoff));
            default:
                return 0;
        }
    }

    protected static function deleteItem($key, $cutoff, $limit, $loopUntilEmpty)
    {
        $total = 0;
        $maxRounds = $loopUntilEmpty ? 50 : 1;
        for ($r = 0; $r < $maxRounds; $r++) {
            $n = 0;
            switch ($key) {
                case 'pay_order':
                    $n = (int)Db::name('pay_order')->where('create_date', '<', $cutoff)->limit($limit)->delete();
                    break;
                case 'log':
                    $n = (int)Db::name('log')->where('add_time', '<', $cutoff)->limit($limit)->delete();
                    break;
                case 'addmaillog':
                    $n = (int)Db::name('addmaillog')->where('create_time', '<', $cutoff)->limit($limit)->delete();
                    break;
                case 'member_integral_log':
                    $n = (int)Db::name('member_integral_log')->where('create_time', '<', $cutoff)->limit($limit)->delete();
                    break;
                case 'member_login_log':
                    $n = (int)Db::name('member_login_log')->where('create_time', '<', $cutoff)->limit($limit)->delete();
                    break;
                case 'member_payorder':
                    $n = (int)Db::name('member_payorder')->where('create_time', '<', $cutoff)->limit($limit)->delete();
                    break;
                case 'member_money_log':
                    $n = (int)Db::name('member_money_log')->where('create_time', '<', $cutoff)->limit($limit)->delete();
                    break;
                case 'info_unpaid':
                    $n = (int)Db::name('info')->where('create_time', '<', $cutoff)->where('mstatus', 2)->limit($limit)->delete();
                    break;
                case 'info_all':
                    $n = (int)Db::name('info')->where('create_time', '<', $cutoff)->limit($limit)->delete();
                    break;
                case 'mail_used':
                    $n = (int)Db::name('mail')->where('create_time', '<', $cutoff)->where('mis_use', 1)->limit($limit)->delete();
                    break;
                case 'order_kami_txt':
                    $files = self::listExpiredKamiTxt($cutoff);
                    $batch = array_slice($files, 0, $limit);
                    foreach ($batch as $f) {
                        if (@unlink($f)) {
                            $n++;
                        }
                    }
                    break;
                default:
                    return $total;
            }
            if ($n === false || $n < 0) {
                $n = 0;
            }
            $total += $n;
            if ($n < 1 || !$loopUntilEmpty) {
                break;
            }
        }
        return $total;
    }
}
