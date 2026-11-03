<?php
namespace com;

use think\Db;
use think\Session;

/**
 * 取卡权限校验：登录会员、游客浏览记录、联系方式查单等
 */
class GetmailAuth
{
    const ALLOW_TTL = 900; // 15 分钟
    const SESSION_KEY = 'getmail_allow';

    /**
     * 写入短期允许提取标记
     */
    public static function grantAllow($mcard, $ttl = null)
    {
        $mcard = (string)$mcard;
        if ($mcard === '') {
            return false;
        }
        if ($ttl === null) {
            $ttl = self::ALLOW_TTL;
        }
        $map = Session::get(self::SESSION_KEY);
        if (!is_array($map)) {
            $map = [];
        }
        $map[$mcard] = time() + (int)$ttl;
        // 顺带按 morder 也可查找，调用方传入主单号即可
        Session::set(self::SESSION_KEY, $map);
        return true;
    }

    public static function hasAllow($mcard)
    {
        $mcard = (string)$mcard;
        $map = Session::get(self::SESSION_KEY);
        if (!is_array($map) || $mcard === '' || empty($map[$mcard])) {
            return false;
        }
        if ((int)$map[$mcard] < time()) {
            unset($map[$mcard]);
            Session::set(self::SESSION_KEY, $map);
            return false;
        }
        return true;
    }

    /**
     * 查单结果后：仅对「联系方式/cookie/openid 命中」的单发 allow；订单号命中标 need_lianxi
     * @param array $lists 订单列表（引用修改 need_lianxi）
     * @param string $key 查询关键字
     */
    public static function afterSelectOrder(array &$lists, $key)
    {
        $key = (string)$key;
        if ($key === '' || !$lists) {
            return;
        }
        foreach ($lists as $k => $order) {
            if (!is_array($order) || empty($order['mcard'])) {
                continue;
            }
            $grant = false;
            if ((string)config('select_mobile') === '1'
                && isset($order['lianxi']) && (string)$order['lianxi'] !== ''
                && (string)$order['lianxi'] === $key) {
                $grant = true;
            }
            if ((string)config('select_cookie') === '1'
                && isset($order['cookie']) && (string)$order['cookie'] !== ''
                && (string)$order['cookie'] === $key) {
                $grant = true;
            }
            if ((string)config('select_openid') === '1'
                && isset($order['openid']) && (string)$order['openid'] !== ''
                && (string)$order['openid'] === $key) {
                $grant = true;
            }
            if ($grant) {
                self::grantAllow($order['mcard']);
                if (!empty($order['morder'])) {
                    self::grantAllow($order['morder']);
                }
                $lists[$k]['need_lianxi'] = 0;
                continue;
            }
            $need = 0;
            if ((string)config('select_mcard') === '1') {
                $hitCard = ((string)$order['mcard'] === $key)
                    || (isset($order['morder']) && (string)$order['morder'] === $key);
                if ($hitCard && isset($order['lianxi']) && trim((string)$order['lianxi']) !== '') {
                    $need = 1;
                }
            }
            $lists[$k]['need_lianxi'] = $need;
        }
    }

    /**
     * 订单号查单后补验联系方式（通道 D）
     * @return array{ok:bool,msg:string,mcard?:string,mpid?:int}
     */
    public static function confirmByLianxi($number, $lianxi)
    {
        $number = trim((string)$number);
        $lianxi = trim((string)$lianxi);
        if ($number === '' || $lianxi === '') {
            return ['ok' => false, 'msg' => '请填写订单号与联系方式'];
        }
        if ((string)config('select_mcard') !== '1') {
            return ['ok' => false, 'msg' => '未开启订单号查询'];
        }
        $order = Db::name('info')->where('mcard|morder', $number)->find();
        if (!$order) {
            return ['ok' => false, 'msg' => '订单不存在'];
        }
        $stored = trim((string)$order['lianxi']);
        if ($stored === '') {
            return ['ok' => false, 'msg' => '该订单无联系方式，请使用下单设备提取或联系客服'];
        }
        if (!hash_equals($stored, $lianxi)) {
            return ['ok' => false, 'msg' => '联系方式不正确'];
        }
        self::grantAllow($order['mcard']);
        if (!empty($order['morder'])) {
            self::grantAllow($order['morder']);
        }
        return [
            'ok' => true,
            'msg' => 'ok',
            'mcard' => $order['mcard'],
            'mpid' => (int)$order['mflid'],
        ];
    }

    /**
     * 取卡前校验
     * @param array $order think_info 行（或 getShopnum data_card）
     * @return array{ok:bool,msg?:string,via?:string}
     */
    public static function assertCanPickup(array $order)
    {
        if (!$order) {
            return ['ok' => false, 'msg' => '订单不存在'];
        }
        $mcard = isset($order['mcard']) ? (string)$order['mcard'] : '';
        $morder = isset($order['morder']) ? (string)$order['morder'] : '';

        // A. 登录会员单
        $memberId = isset($order['memberid']) ? (int)$order['memberid'] : 0;
        $sessionUid = (int)session('useraccount.id');
        if ($memberId > 0 && $sessionUid > 0 && $sessionUid === $memberId) {
            return ['ok' => true, 'via' => 'A'];
        }

        // B. 游客本机 cookie
        if ((string)config('select_cookie') === '1') {
            $token = (string)cookie('tokenid');
            $oc = isset($order['cookie']) ? (string)$order['cookie'] : '';
            if ($token !== '' && $oc !== '' && hash_equals($oc, $token)) {
                return ['ok' => true, 'via' => 'B'];
            }
        }

        // openid（若开启）
        if ((string)config('select_openid') === '1') {
            $oid = isset($order['openid']) ? (string)$order['openid'] : '';
            $sessOid = (string)session('openid');
            if ($sessOid === '' && function_exists('cookie')) {
                $sessOid = (string)cookie('openid');
            }
            if ($oid !== '' && $sessOid !== '' && hash_equals($oid, $sessOid)) {
                return ['ok' => true, 'via' => 'openid'];
            }
        }

        // C/D. 查单写入的短期 allow
        if (($mcard !== '' && self::hasAllow($mcard)) || ($morder !== '' && self::hasAllow($morder))) {
            return ['ok' => true, 'via' => 'allow'];
        }

        // 会员单未登录且无其它凭证
        if ($memberId > 0) {
            return ['ok' => false, 'msg' => '请登录下单账号后提取'];
        }

        $hint = '无权提取。请使用下单浏览器，或通过联系方式查询后再提取';
        if ((string)config('select_mcard') === '1' && trim((string)(isset($order['lianxi']) ? $order['lianxi'] : '')) !== '') {
            $hint = '无权提取。请先查询订单并核对联系方式后再提取';
        }
        return ['ok' => false, 'msg' => $hint];
    }
}
