<?php
namespace com;

use think\Db;

/**
 * 销售 / 卡密统计（原 api Salesstatistics / KeySalesstatistics，后台会话鉴权）
 */
class StatsService
{
    /** 销售统计维度 */
    public static $salesTypes = [
        0 => '商品',
        1 => '联系手机',
        2 => '联系邮箱',
        3 => '本地缓存',
        4 => '支付途径',
        5 => '微信公众号',
        6 => '会员帐号',
        7 => '订单号',
    ];

    /** 卡密统计维度 */
    public static $keyTypes = [
        0 => '商品',
        1 => '联系手机',
        2 => '联系邮箱',
        3 => '本地缓存',
        4 => '支付途径',
        5 => '微信公众号',
        6 => '会员帐号',
        7 => '订单号',
    ];

    public static $maddtypeNames = [
        0 => '积分兑换',
        1 => '管理员',
        2 => '支付宝扫码',
        3 => '微信扫码',
        4 => '云闪付',
        5 => '余额支付',
        6 => 'QQ钱包',
        7 => '财付通',
        8 => 'USDT',
        88 => '后台导出',
    ];

    /**
     * 解析起止时间（支持日期字符串或时间戳）
     * @return array [start, end]
     */
    public static function parseRange($starttime, $endtime)
    {
        $st = 0;
        $et = time();
        if ($starttime !== null && $starttime !== '' && $starttime !== 0 && $starttime !== '0') {
            $st = is_numeric($starttime) ? intval($starttime) : strtotime($starttime);
            if ($st === false) {
                $st = 0;
            }
        }
        if ($endtime !== null && $endtime !== '' && $endtime !== 0 && $endtime !== '0') {
            $et = is_numeric($endtime) ? intval($endtime) : strtotime($endtime);
            if ($et === false) {
                $et = time();
            }
            // 仅日期时含当天
            if (!is_numeric($endtime) && strlen((string)$endtime) <= 10) {
                $et = $et + 86400 - 1;
            }
        }
        if ($st > 0 && $et < $st) {
            $tmp = $st;
            $st = $et;
            $et = $tmp;
        }
        if ($st < 1) {
            // 默认近 30 天，避免全表扫
            $st = time() - 86400 * 30;
        }
        return [$st, $et];
    }

    /**
     * 规范化分页
     * @return array [page, limit]
     */
    public static function normalizePage($page, $limit)
    {
        $page = intval($page);
        $limit = intval($limit);
        if ($page < 1) {
            $page = 1;
        }
        if ($limit < 1) {
            $limit = 20;
        }
        if ($limit > 200) {
            $limit = 200;
        }
        return [$page, $limit];
    }

    /**
     * 销售统计（排除未付款 mstatus=2、排除导出单 maddtype=88）
     * @param int $page 页码，从 1 起
     * @param int $limit 每页分组条数
     */
    public static function sales($type, $starttime, $endtime, $page = 1, $limit = 20)
    {
        $type = intval($type);
        if ($type < 0 || $type > 7) {
            $type = 0;
        }
        list($st, $et) = self::parseRange($starttime, $endtime);
        list($page, $limit) = self::normalizePage($page, $limit);

        $map = [];
        $map['think_info.mstatus'] = ['neq', 2];
        $map['think_info.maddtype'] = ['neq', 88];
        $map['think_info.create_time'] = ['between', [$st, $et]];

        $lines = ['名称', '订单数量', '总金额'];
        $groupby = 'think_info.mflid';
        $field = "IFNULL(think_fl.mnamebie,'自用') as name,count(distinct think_info.id) as count,sum(think_info.mamount) as jine";

        if ($type === 1) {
            $field = "IF(think_info.lianxi='','无',think_info.lianxi) as name,count(distinct think_info.id) as count,sum(think_info.mamount) as jine";
            $lines = ['联系手机', '订单数量', '总金额'];
            $groupby = 'think_info.lianxi';
        } elseif ($type === 2) {
            $field = "IF(think_info.email='','无',think_info.email) as name,count(distinct think_info.id) as count,sum(think_info.mamount) as jine";
            $lines = ['联系邮箱', '订单数量', '总金额'];
            $groupby = 'think_info.email';
        } elseif ($type === 3) {
            $field = "IF(think_info.cookie='','无',think_info.cookie) as name,count(distinct think_info.id) as count,sum(think_info.mamount) as jine";
            $lines = ['本地缓存', '订单数量', '总金额'];
            $groupby = 'think_info.cookie';
        } elseif ($type === 4) {
            $field = "think_info.maddtype as name,count(distinct think_info.id) as count,sum(think_info.mamount) as jine";
            $lines = ['支付途径', '订单数量', '总金额'];
            $groupby = 'think_info.maddtype';
        } elseif ($type === 5) {
            $field = "IF(think_info.openid='','无',think_info.openid) as name,count(distinct think_info.id) as count,sum(think_info.mamount) as jine";
            $lines = ['微信公众号ID', '订单数量', '总金额'];
            $groupby = 'think_info.openid';
        } elseif ($type === 6) {
            $field = "IFNULL(think_member.account,'非会员') as name,count(distinct think_info.id) as count,sum(think_info.mamount) as jine";
            $lines = ['会员名称', '订单数量', '总金额'];
            $groupby = 'think_info.memberid';
        } elseif ($type === 7) {
            $field = "IFNULL(think_info.mcard,'无') as name,count(distinct think_info.id) as count,sum(think_info.mamount) as jine";
            $lines = ['订单号', '订单数量', '总金额'];
            $groupby = 'think_info.id';
        } else {
            $lines = ['商品名称', '订单数量', '总金额'];
        }

        // 合计（全量，不受分页影响）
        $sumRow = Db::name('info')->alias('think_info')
            ->field('count(distinct think_info.id) as cnt,IFNULL(sum(think_info.mamount),0) as jine')
            ->where($map)
            ->find();
        $sumCount = intval(isset($sumRow['cnt']) ? $sumRow['cnt'] : 0);
        $sumMoney = round(floatval(isset($sumRow['jine']) ? $sumRow['jine'] : 0), 4);

        // 分组总行数（页数）
        $groupCount = (int)Db::name('info')->alias('think_info')
            ->join('think_fl', 'think_fl.id = think_info.mflid', 'LEFT')
            ->join('think_member', 'think_info.memberid = think_member.id', 'LEFT')
            ->where($map)
            ->group($groupby)
            ->count();

        $allpage = $groupCount > 0 ? intval(ceil($groupCount / $limit)) : 1;
        if ($page > $allpage) {
            $page = $allpage;
        }

        $rows = Db::name('info')->alias('think_info')
            ->field($field)
            ->join('think_fl', 'think_fl.id = think_info.mflid', 'LEFT')
            ->join('think_member', 'think_info.memberid = think_member.id', 'LEFT')
            ->where($map)
            ->group($groupby)
            ->order('jine desc')
            ->page($page, $limit)
            ->select();

        $list = [];
        foreach ($rows as $row) {
            $name = isset($row['name']) ? $row['name'] : '';
            if ($type === 4) {
                $mt = intval($name);
                $name = isset(self::$maddtypeNames[$mt]) ? self::$maddtypeNames[$mt] : ('类型' . $mt);
            }
            $list[] = [
                'name' => $name === '' || $name === null ? '无' : $name,
                'count' => intval($row['count']),
                'jine' => round(floatval($row['jine']), 4),
            ];
        }

        return [
            'code' => 1,
            'lines' => $lines,
            'type' => $type,
            'start' => $st,
            'end' => $et,
            'start_fmt' => date('Y-m-d H:i:s', $st),
            'end_fmt' => date('Y-m-d H:i:s', $et),
            'sum_count' => $sumCount,
            'sum_jine' => $sumMoney,
            'page' => $page,
            'limit' => $limit,
            'count' => $groupCount,
            'allpage' => $allpage,
            'data' => $list,
        ];
    }

    /**
     * 卡密消耗统计（按 mail.update_time；关联订单取联系方式等）
     * @param int $page 页码
     * @param int $limit 每页分组条数
     */
    public static function keySales($type, $starttime, $endtime, $page = 1, $limit = 20)
    {
        $type = intval($type);
        if ($type < 0 || $type > 7) {
            $type = 0;
        }
        list($st, $et) = self::parseRange($starttime, $endtime);
        list($page, $limit) = self::normalizePage($page, $limit);

        $map = [];
        $map['think_mail.update_time'] = ['between', [$st, $et]];
        // 仅统计已使用更贴近「销售」；无 update 的未用会被时间条件自然排除
        $map['think_mail.mis_use'] = 1;

        $lines = ['名称', '卡密数量'];
        $groupby = 'think_mail.mpid';
        $field = "IFNULL(think_fl.mnamebie,'未知') as name,count(distinct think_mail.id) as count";

        if ($type === 1) {
            $field = "IF(IFNULL(think_info.lianxi,'')='','无',think_info.lianxi) as name,count(distinct think_mail.id) as count";
            $lines = ['联系手机', '卡密数量'];
            $groupby = 'think_info.lianxi';
        } elseif ($type === 2) {
            $field = "IF(IFNULL(think_info.email,'')='','无',think_info.email) as name,count(distinct think_mail.id) as count";
            $lines = ['联系邮箱', '卡密数量'];
            $groupby = 'think_info.email';
        } elseif ($type === 3) {
            $field = "IF(IFNULL(think_info.cookie,'')='','无',think_info.cookie) as name,count(distinct think_mail.id) as count";
            $lines = ['本地缓存', '卡密数量'];
            $groupby = 'think_info.cookie';
        } elseif ($type === 4) {
            $field = "IFNULL(think_info.maddtype,-1) as name,count(distinct think_mail.id) as count";
            $lines = ['支付途径', '卡密数量'];
            $groupby = 'think_info.maddtype';
        } elseif ($type === 5) {
            $field = "IF(IFNULL(think_info.openid,'')='','无',think_info.openid) as name,count(distinct think_mail.id) as count";
            $lines = ['微信公众号ID', '卡密数量'];
            $groupby = 'think_info.openid';
        } elseif ($type === 6) {
            $field = "IFNULL(think_member.account,'非会员') as name,count(distinct think_mail.id) as count";
            $lines = ['会员名称', '卡密数量'];
            $groupby = 'think_info.memberid';
        } elseif ($type === 7) {
            $field = "IFNULL(think_info.mcard,'未知') as name,count(distinct think_mail.id) as count";
            $lines = ['订单编号', '卡密数量'];
            $groupby = 'think_info.mcard';
        } else {
            $lines = ['商品名称', '卡密数量'];
        }

        $sumCount = (int)Db::name('mail')->alias('think_mail')
            ->where($map)
            ->count('distinct think_mail.id');

        // mcard / syddhao 可能分属 utf8mb4_unicode_ci 与 utf8mb4_general_ci，直接 = 会 HY000/1267
        $mailInfoOn = 'think_info.mcard COLLATE utf8mb4_general_ci = think_mail.syddhao COLLATE utf8mb4_general_ci';
        $needInfo = ($type >= 1);
        $needMember = ($type === 6);

        $countQuery = Db::name('mail')->alias('think_mail')
            ->join('think_fl', 'think_fl.id = think_mail.mpid', 'LEFT');
        if ($needInfo) {
            $countQuery->join('think_info', $mailInfoOn, 'LEFT');
        }
        if ($needMember) {
            $countQuery->join('think_member', 'think_info.memberid = think_member.id', 'LEFT');
        }
        $groupCount = (int)$countQuery->where($map)->group($groupby)->count();

        $allpage = $groupCount > 0 ? intval(ceil($groupCount / $limit)) : 1;
        if ($page > $allpage) {
            $page = $allpage;
        }

        $listQuery = Db::name('mail')->alias('think_mail')
            ->field($field)
            ->join('think_fl', 'think_fl.id = think_mail.mpid', 'LEFT');
        if ($needInfo) {
            $listQuery->join('think_info', $mailInfoOn, 'LEFT');
        }
        if ($needMember) {
            $listQuery->join('think_member', 'think_info.memberid = think_member.id', 'LEFT');
        }
        $rows = $listQuery->where($map)
            ->group($groupby)
            ->order('count desc')
            ->page($page, $limit)
            ->select();

        $list = [];
        foreach ($rows as $row) {
            $name = isset($row['name']) ? $row['name'] : '';
            if ($type === 4) {
                $mt = intval($name);
                $name = isset(self::$maddtypeNames[$mt]) ? self::$maddtypeNames[$mt] : ($mt < 0 ? '无订单' : ('类型' . $mt));
            }
            $list[] = [
                'name' => $name === '' || $name === null ? '无' : $name,
                'count' => intval($row['count']),
            ];
        }

        return [
            'code' => 1,
            'lines' => $lines,
            'type' => $type,
            'start' => $st,
            'end' => $et,
            'start_fmt' => date('Y-m-d H:i:s', $st),
            'end_fmt' => date('Y-m-d H:i:s', $et),
            'sum_count' => $sumCount,
            'page' => $page,
            'limit' => $limit,
            'count' => $groupCount,
            'allpage' => $allpage,
            'data' => $list,
        ];
    }
}
