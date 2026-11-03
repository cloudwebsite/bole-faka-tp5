<?php
namespace com;

use app\jingdian\model\OrderModel;
use app\jingdian\model\UserModel;
use think\Db;
use think\Log;

/**
 * 支付回调统一处理：核对订单金额后履约，避免重复到账
 */
class PayFulfill
{
    /** 金额容差（元） */
    const AMOUNT_TOLERANCE = '0.01';

    /** 免签监控默认密钥（禁止使用） */
    const MQ_DEFAULT_KEY = '7i7p7M84pPiWjYjGi6h3W6Tx5XJ4SbR4';

    /**
     * 金额是否一致（元，两位小数，容差 0.01）
     */
    public static function amountsEqual($expected, $actual, $tolerance = null)
    {
        if ($tolerance === null) {
            $tolerance = self::AMOUNT_TOLERANCE;
        }
        if (!is_numeric($expected) || !is_numeric($actual)) {
            return false;
        }
        $a = round((float)$expected, 2);
        $b = round((float)$actual, 2);
        $diff = abs($a - $b);
        return $diff <= (float)$tolerance + 1e-9;
    }

    /**
     * 校验免签通讯密钥强度
     * @return array{ok:bool,msg?:string}
     */
    public static function assertMqKey($key)
    {
        $key = (string)$key;
        if ($key === '' || $key === self::MQ_DEFAULT_KEY) {
            return ['ok' => false, 'msg' => '请修改通讯密钥'];
        }
        if (strlen($key) < 16) {
            return ['ok' => false, 'msg' => '通讯密钥长度至少 16 位'];
        }
        if (preg_match('/^(.)\1+$/', $key) || preg_match('/^(123456|password|admin|test)/i', $key)) {
            return ['ok' => false, 'msg' => '通讯密钥过于简单，请更换'];
        }
        return ['ok' => true];
    }

    /**
     * 解析商户订单应付金额（会员充值优先，否则商品单 mamount）
     * @return array{ok:bool,amount?:float,member?:array|null,msg?:string}
     */
    public static function resolveExpectedAmount($payId)
    {
        $payId = (string)$payId;
        if ($payId === '') {
            return ['ok' => false, 'msg' => '订单号为空'];
        }
        $member = Db::name('member_payorder')->where('orderno|outorderno', $payId)->find();
        if (!empty($member['memberid'])) {
            return [
                'ok' => true,
                'amount' => (float)$member['money'],
                'member' => $member,
            ];
        }
        $info = Db::name('info')->where('mcard|morder', $payId)->find();
        if (!$info) {
            return ['ok' => false, 'msg' => '订单不存在'];
        }
        return [
            'ok' => true,
            'amount' => (float)$info['mamount'],
            'member' => null,
            'info' => $info,
        ];
    }

    /**
     * 验金额后履约（发卡或会员充值）
     * @param string $payId 商户订单号
     * @param float|string $callbackAmount 回调金额（元）
     * @param string $tradeNo 渠道交易号（写入 morder）
     * @param array $extra 可选：maddtype 等
     * @return array{ok:bool,msg:string}
     */
    public static function fulfill($payId, $callbackAmount, $tradeNo = '', array $extra = [])
    {
        $resolved = self::resolveExpectedAmount($payId);
        if (!$resolved['ok']) {
            self::logReject($payId, $callbackAmount, $resolved['msg']);
            return ['ok' => false, 'msg' => $resolved['msg']];
        }
        if (!self::amountsEqual($resolved['amount'], $callbackAmount)) {
            self::logReject(
                $payId,
                $callbackAmount,
                '金额不一致 expected=' . $resolved['amount'] . ' got=' . $callbackAmount
            );
            return ['ok' => false, 'msg' => '金额不一致'];
        }

        $OrderM = new OrderModel();
        $UserM = new UserModel();
        $member = $resolved['member'];

        if (!empty($member['memberid'])) {
            $data = [
                'memberid' => $member['memberid'],
                'money' => $resolved['amount'],
                'r2' => $payId,
                'r6' => $payId,
                'userip' => isset($member['ip']) ? $member['ip'] : '',
            ];
            $UserM->payMember($payId, $data);
        } else {
            $data = [
                'mstatus' => 0,
                'update_time' => time(),
                'mcard' => $payId,
                'morder' => $tradeNo !== '' ? $tradeNo : $payId,
                'mamount' => $resolved['amount'],
            ];
            if (isset($extra['maddtype'])) {
                $data['maddtype'] = $extra['maddtype'];
            }
            $OrderM->updateOrderStatus($payId, $data);
        }
        return ['ok' => true, 'msg' => 'ok'];
    }

    /**
     * 后台补单：通道回调丢失时，按业务应付金额完成发卡/充值
     * @param string $payId 商户订单号
     * @param float|string $channelPrice 通道记录金额（元），须与业务单一致
     * @param string $tradeNo 通道订单号
     * @param int $channelState 通道单当前状态
     * @return array{ok:bool,msg:string,sync_only?:bool}
     */
    public static function adminReissue($payId, $channelPrice, $tradeNo = '', $channelState = 0)
    {
        $channelState = (int)$channelState;
        if ($channelState === 1) {
            return ['ok' => false, 'msg' => '通道订单已完成，无需补单'];
        }
        // 待支付、过期、通知失败可补
        if (!in_array($channelState, [0, -1, 2], true)) {
            return ['ok' => false, 'msg' => '当前通道状态不允许补单'];
        }

        $resolved = self::resolveExpectedAmount($payId);
        if (!$resolved['ok']) {
            return ['ok' => false, 'msg' => $resolved['msg']];
        }
        if (!self::amountsEqual($resolved['amount'], $channelPrice)) {
            self::logReject(
                $payId,
                $channelPrice,
                '补单金额不符 expected=' . $resolved['amount'] . ' channel=' . $channelPrice
            );
            return ['ok' => false, 'msg' => '金额与订单不符，请核对后处理'];
        }

        // 业务单已成功：只同步通道状态，不再履约
        if (self::isBusinessAlreadyPaid($resolved)) {
            return ['ok' => true, 'msg' => '订单已处理，无需补单', 'sync_only' => true];
        }

        $ret = self::fulfill($payId, $resolved['amount'], $tradeNo);
        if (!$ret['ok']) {
            return $ret;
        }
        return ['ok' => true, 'msg' => '补单成功', 'sync_only' => false];
    }

    /**
     * 业务单是否已支付成功（避免重复发卡/加款）
     */
    protected static function isBusinessAlreadyPaid(array $resolved)
    {
        if (!empty($resolved['member']['memberid'])) {
            return (int)$resolved['member']['status'] === 1;
        }
        if (!empty($resolved['info'])) {
            // 待支付一般为 mstatus=2；其它状态视为已处理或进行中
            return (int)$resolved['info']['mstatus'] !== 2;
        }
        // 再查一次
        $payId = '';
        return false;
    }

    public static function logAdminReissue($adminId, $payId, $amount, $resultMsg)
    {
        $line = date('c') . " admin={$adminId} payId={$payId} amount={$amount} result={$resultMsg}\n";
        $dir = RUNTIME_PATH . 'log' . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($dir . 'pay_admin_reissue.log', $line, FILE_APPEND);
    }

    /**
     * TRC20 支付页轮询 token（短 TTL，防匿名触发履约）
     */
    public static function makeTrc20CheckToken($orderId, $ttl = 7200)
    {
        $exp = time() + (int)$ttl;
        $payload = (string)$orderId . '|' . $exp;
        $sig = hash_hmac('sha256', $payload, self::trc20Secret());
        return $exp . '.' . substr($sig, 0, 32);
    }

    public static function verifyTrc20CheckToken($orderId, $token)
    {
        $token = (string)$token;
        if ($token === '' || strpos($token, '.') === false) {
            return false;
        }
        list($exp, $sig) = explode('.', $token, 2);
        if (!ctype_digit($exp) || (int)$exp < time()) {
            return false;
        }
        $payload = (string)$orderId . '|' . $exp;
        $expect = substr(hash_hmac('sha256', $payload, self::trc20Secret()), 0, 32);
        return hash_equals($expect, $sig);
    }

    /**
     * 温和限流：同一 IP+动作 在窗口内次数
     * @return bool true=允许
     */
    public static function softRateLimit($bucket, $limit = 30, $windowSec = 60)
    {
        $ip = '';
        if (function_exists('getIP')) {
            $ip = (string)getIP();
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = (string)$_SERVER['REMOTE_ADDR'];
        }
        $key = 'pay_rl_' . md5($bucket . '|' . $ip);
        $n = (int)cache($key);
        if ($n >= $limit) {
            return false;
        }
        cache($key, $n + 1, $windowSec);
        return true;
    }

    protected static function trc20Secret()
    {
        $t = (string)config('usdt_apitoken');
        if (strlen($t) >= 8) {
            return $t;
        }
        $t = (string)config('token');
        if ($t !== '') {
            return $t;
        }
        return (string)config('auth_key');
    }

    protected static function logReject($payId, $amount, $reason)
    {
        $line = date('c') . " payId={$payId} amount={$amount} reason={$reason}\n";
        $dir = RUNTIME_PATH . 'log' . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($dir . 'pay_fulfill_reject.log', $line, FILE_APPEND);
        if (class_exists('\think\Log')) {
            try {
                Log::record('[PayFulfill] ' . trim($line), 'error');
            } catch (\Exception $e) {
            }
        }
    }
}
