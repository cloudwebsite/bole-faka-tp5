<?php
namespace com;

/**
 * 后台「进入会员中心」的一次性登录凭证（短时有效，用后即废）
 */
class AdminLoginTicket
{
    const TTL = 60;
    const PREFIX = 'admin_ml_ticket_';

    /**
     * @return string ticket
     */
    public static function issue($memberId, $extra = [])
    {
        $ticket = bin2hex(random_bytes(16));
        $payload = array_merge([
            'memberid' => (int)$memberId,
            'exp' => time() + self::TTL,
        ], $extra);
        cache(self::PREFIX . $ticket, $payload, self::TTL);
        return $ticket;
    }

    /**
     * 读取并销毁
     * @return array|null
     */
    public static function consume($ticket)
    {
        $ticket = (string)$ticket;
        if ($ticket === '' || !preg_match('/^[a-f0-9]{32}$/', $ticket)) {
            return null;
        }
        $key = self::PREFIX . $ticket;
        $payload = cache($key);
        cache($key, null);
        if (!is_array($payload) || empty($payload['memberid'])) {
            return null;
        }
        if (!empty($payload['exp']) && (int)$payload['exp'] < time()) {
            return null;
        }
        return $payload;
    }
}
