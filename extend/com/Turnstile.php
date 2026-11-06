<?php
namespace com;

/**
 * Cloudflare Turnstile 服务端校验
 */
class Turnstile
{
    const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * @param string $token 前端 cf-turnstile-response
     * @param string $remoteIp 访客 IP（可选）
     * @return bool
     */
    public static function verify($token, $remoteIp = '')
    {
        $token = trim((string)$token);
        $secret = trim((string)config('turnstile_secret_key'));
        if ($token === '' || $secret === '') {
            return false;
        }

        $post = [
            'secret'   => $secret,
            'response' => $token,
        ];
        $remoteIp = trim((string)$remoteIp);
        if ($remoteIp !== '') {
            $post['remoteip'] = $remoteIp;
        }

        $raw = self::httpPost(self::VERIFY_URL, $post);
        if ($raw === false || $raw === '') {
            return false;
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['success'])) {
            return false;
        }
        return true;
    }

    /**
     * @param string $url
     * @param array  $fields
     * @return string|false
     */
    protected static function httpPost($url, array $fields)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            $body = curl_exec($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            return $errno ? false : $body;
        }

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($fields),
                'timeout' => 8,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        return $body === false ? false : $body;
    }
}
