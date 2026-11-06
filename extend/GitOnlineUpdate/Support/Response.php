<?php
namespace GitOnlineUpdate\Support;

/**
 * 统一 JSON 响应（code/msg/data）
 */
class Response
{
    public static function make($code, $msg = '', $data = null, $go = '')
    {
        return [
            'code' => (int) $code,
            'msg'  => (string) $msg,
            'go'   => (string) $go,
            'data' => $data,
        ];
    }

    public static function ok($msg = 'success', $data = null)
    {
        return self::make(1, $msg, $data);
    }

    public static function fail($msg = '失败', $data = null)
    {
        return self::make(0, $msg, $data);
    }

    public static function output(array $payload, $exit = true)
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($exit) {
            exit;
        }
    }
}
