<?php
namespace GitOnlineUpdate\Http;

/**
 * 轻量 HTTP 客户端（curl）
 */
class Client
{
    protected $timeout = 60;
    protected $userAgent = 'GitOnlineUpdate/1.0';
    protected $defaultHeaders = [];

    public function __construct(array $options = [])
    {
        if (isset($options['timeout'])) {
            $this->timeout = (int) $options['timeout'];
        }
        if (!empty($options['user_agent'])) {
            $this->userAgent = $options['user_agent'];
        }
        if (!empty($options['headers']) && is_array($options['headers'])) {
            $this->defaultHeaders = $options['headers'];
        }
    }

    public function get($url, array $headers = [])
    {
        return $this->request('GET', $url, null, $headers);
    }

    public function head($url, array $headers = [])
    {
        return $this->request('HEAD', $url, null, $headers);
    }

    public function post($url, $body = null, array $headers = [])
    {
        return $this->request('POST', $url, $body, $headers);
    }

    public function put($url, $body = null, array $headers = [])
    {
        return $this->request('PUT', $url, $body, $headers);
    }

    public function download($url, $savePath, array $headers = [])
    {
        $dir = dirname($savePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $fp = fopen($savePath, 'wb');
        if (!$fp) {
            throw new \RuntimeException('无法写入文件: ' . $savePath);
        }
        try {
            $result = $this->request('GET', $url, null, $headers, $fp);
            if ($result['status'] >= 400) {
                throw new \RuntimeException('下载失败 HTTP ' . $result['status']);
            }
            return $result;
        } finally {
            fclose($fp);
        }
    }

    public function request($method, $url, $body = null, array $headers = [], $sink = null)
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('需要 PHP cURL 扩展');
        }

        $ch = curl_init();
        $headerLines = $this->buildHeaders(array_merge($this->defaultHeaders, $headers));

        $opts = [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => $sink ? false : true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_CONNECTTIMEOUT => min(30, $this->timeout),
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_USERAGENT      => $this->userAgent,
            CURLOPT_HTTPHEADER     => $headerLines,
            CURLOPT_HEADER         => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        if ($sink) {
            $opts[CURLOPT_FILE] = $sink;
        }

        if ($body !== null) {
            if (is_array($body)) {
                $opts[CURLOPT_POSTFIELDS] = http_build_query($body);
            } else {
                $opts[CURLOPT_POSTFIELDS] = $body;
            }
        }

        if (strtoupper($method) === 'HEAD') {
            $opts[CURLOPT_NOBODY] = true;
            $opts[CURLOPT_HEADER] = true;
            $opts[CURLOPT_RETURNTRANSFER] = true;
        }

        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($errno) {
            throw new \RuntimeException('HTTP 请求失败: ' . $error, $errno);
        }

        $responseHeaders = [];
        $bodyStr = $raw;
        if (strtoupper($method) === 'HEAD' && is_string($raw)) {
            $responseHeaders = $this->parseHeaders($raw);
            $bodyStr = '';
        }

        return [
            'status'       => $status,
            'headers'      => $responseHeaders,
            'body'         => $bodyStr === false ? '' : $bodyStr,
            'content_type' => $contentType,
        ];
    }

    public function getJson($url, array $headers = [])
    {
        $res = $this->get($url, $headers);
        $data = json_decode($res['body'], true);
        return [
            'status' => $res['status'],
            'data'   => $data,
            'raw'    => $res['body'],
        ];
    }

    protected function buildHeaders(array $headers)
    {
        $lines = [];
        foreach ($headers as $k => $v) {
            if (is_int($k)) {
                $lines[] = $v;
            } else {
                $lines[] = $k . ': ' . $v;
            }
        }
        return $lines;
    }

    protected function parseHeaders($raw)
    {
        $headers = [];
        foreach (explode("\r\n", $raw) as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }
            list($k, $v) = explode(':', $line, 2);
            $headers[strtolower(trim($k))] = trim($v);
        }
        return $headers;
    }
}
