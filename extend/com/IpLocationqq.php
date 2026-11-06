<?php

namespace com;

/**
 * IP 地理位置查询
 * - IPv4：优先 qqwry.ipdb，回退 qqwry.dat
 * - IPv6：使用 qqwry.ipdb（dat 不支持，会误显示版本信息）
 * 返回结构保持兼容：ip / country / area
 */
class IpLocationqq
{
    /** @var resource|int */
    private $fp = 0;
    private $firstip;
    private $lastip;
    private $totalip;

    /** @var \ipip\db\City|null */
    private $ipdb = null;

    /** @var bool */
    private $ipdbTried = false;

    /**
     * @param string $filename 兼容旧调用，默认仍传 qqwry.dat
     */
    public function __construct($filename = "qqwry.dat")
    {
        $datPath = __DIR__ . '/qqwry.dat';
        if (!file_exists($datPath)) {
            $fp = fopen($datPath, 'w+');
            if ($fp) {
                fclose($fp);
                @chmod($datPath, 0755);
            }
        }

        // 新建空文件时从 git 镜像补全（nmgliangwei/qqwry 简体版）
        if (file_exists($datPath) && (time() - filemtime($datPath) <= 5) && filesize($datPath) < 1024) {
            $this->httpcopy('https://cdn.1008.site/gh/nmgliangwei/qqwry@main/qqwry.dat', 'qqwry.dat');
        }

        $openName = basename((string)$filename) ?: 'qqwry.dat';
        $openPath = __DIR__ . '/' . $openName;
        if (!is_readable($openPath)) {
            $openPath = $datPath;
        }
        if (($this->fp = @fopen($openPath, 'rb')) !== false) {
            $this->firstip = $this->getlong();
            $this->lastip = $this->getlong();
            $this->totalip = ($this->lastip - $this->firstip) / 7;
        }

        $this->initIpdb();
    }

    /**
     * 按 IP 返回归属地
     * @param string $ip
     * @return array|null
     */
    public function getlocation($ip = '')
    {
        $ip = $this->normalizeIp($ip);
        if ($ip === '') {
            return $this->unknownLocation('');
        }

        if ($this->isIpv6($ip)) {
            return $this->getlocationFromIpdb($ip);
        }

        // IPv4：优先 IPDB（与 IPv6 同源、字段更干净）
        if ($this->ipdb) {
            $fromDb = $this->getlocationFromIpdb($ip);
            if ($fromDb && !$this->isJunkCountry($fromDb['country'] ?? '')) {
                return $fromDb;
            }
        }

        return $this->getlocationFromDat($ip);
    }

    /**
     * 规范化 IP：去空格、方括号、zone id、多 IP 取第一个，并尽量标准化写法
     */
    private function normalizeIp($ip)
    {
        $ip = trim((string)$ip);
        if ($ip === '') {
            return '';
        }
        // X-Forwarded-For: a, b
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        // [2001:db8::1]:443 或 [2001:db8::1]
        if ($ip !== '' && $ip[0] === '[') {
            if (preg_match('/^\[([^\]]+)\](?::\d+)?$/', $ip, $m)) {
                $ip = $m[1];
            } else {
                $ip = trim($ip, '[]');
            }
        }
        // IPv6 zone id：fe80::1%eth0 / fe80::1%25eth0
        if (strpos($ip, '%') !== false) {
            $ip = str_replace('%25', '%', $ip);
            $ip = strstr($ip, '%', true);
        }
        // 全角冒号
        $ip = str_replace('：', ':', $ip);
        $ip = trim($ip);

        // inet_pton/ntop 标准化（失败则保留原串交给后续校验）
        $bin = @inet_pton($ip);
        if ($bin !== false) {
            $norm = @inet_ntop($bin);
            if ($norm !== false) {
                return $norm;
            }
        }
        return $ip;
    }

    private function initIpdb()
    {
        if ($this->ipdbTried) {
            return;
        }
        $this->ipdbTried = true;
        $path = __DIR__ . '/qqwry.ipdb';
        if (!is_readable($path)) {
            return;
        }
        try {
            if (!class_exists('\\ipip\\db\\City', false)) {
                // ThinkPHP extend 自动加载：extend/ipip/db/City.php
                // 兜底手动加载，避免个别入口未注册命名空间
                $cityFile = dirname(__DIR__) . '/ipip/db/City.php';
                $readerFile = dirname(__DIR__) . '/ipip/db/Reader.php';
                $infoFile = dirname(__DIR__) . '/ipip/db/CityInfo.php';
                if (is_readable($readerFile)) {
                    require_once $readerFile;
                }
                if (is_readable($infoFile)) {
                    require_once $infoFile;
                }
                if (is_readable($cityFile)) {
                    require_once $cityFile;
                }
            }
            $this->ipdb = new \ipip\db\City($path);
        } catch (\Throwable $e) {
            $this->ipdb = null;
        }
    }

    private function isIpv6($ip)
    {
        return (bool)filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    private function isIpv4($ip)
    {
        return (bool)filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    private function unknownLocation($ip)
    {
        return [
            'ip' => $ip,
            'country' => '未知',
            'area' => '',
            'beginip' => '',
            'endip' => '',
        ];
    }

    /**
     * 过滤 dat 末条版本信息 / 无效文案
     */
    private function isJunkCountry($country)
    {
        $country = trim((string)$country);
        if ($country === '' || $country === '未知' || $country === 'CZNET' || $country === 'CZ88.NET') {
            return true;
        }
        if (strpos($country, '纯真网络') !== false) {
            return true;
        }
        if (preg_match('/IP数据|IP数据库/u', $country)) {
            return true;
        }
        return false;
    }

    private function getlocationFromIpdb($ip)
    {
        $this->initIpdb();
        if (!$this->ipdb) {
            return $this->unknownLocation($ip);
        }
        try {
            $map = $this->ipdb->findMap($ip, 'CN');
        } catch (\Throwable $e) {
            return $this->unknownLocation($ip);
        }
        if (!is_array($map) || empty($map)) {
            return $this->unknownLocation($ip);
        }

        // 拼接：国家/省/市/区（与原先 dat 展示习惯一致，紧凑无分隔符）
        $countryParts = [];
        foreach (['country_name', 'region_name', 'city_name', 'district_name'] as $key) {
            $v = isset($map[$key]) ? trim((string)$map[$key]) : '';
            if ($v !== '') {
                $countryParts[] = $v;
            }
        }
        $countryParts = array_values(array_unique($countryParts));
        $country = implode('', $countryParts);

        $areaParts = [];
        foreach (['isp_domain', 'owner_domain'] as $key) {
            $v = isset($map[$key]) ? trim((string)$map[$key]) : '';
            if ($v !== '') {
                $areaParts[] = $v;
            }
        }
        $area = implode(' ', array_values(array_unique($areaParts)));

        if ($this->isJunkCountry($country)) {
            return $this->unknownLocation($ip);
        }

        return [
            'ip' => $ip,
            'country' => $country !== '' ? $country : '未知',
            'area' => $area,
            'beginip' => '',
            'endip' => '',
        ];
    }

    private function getlocationFromDat($ip = '')
    {
        if (!$this->fp) {
            return $this->unknownLocation($ip);
        }

        // 仅 IPv4；非法地址勿走 gethostbyname 以免污染
        if (!$this->isIpv4($ip)) {
            $resolved = gethostbyname($ip);
            if (!$this->isIpv4($resolved)) {
                return $this->unknownLocation($ip);
            }
            $location = ['ip' => $resolved];
        } else {
            $location = ['ip' => $ip];
        }

        $packed = $this->packip($location['ip']);
        $l = 0;
        $u = $this->totalip;
        $findip = $this->lastip;
        while ($l <= $u) {
            $i = floor(($l + $u) / 2);
            fseek($this->fp, $this->firstip + $i * 7);
            $beginip = strrev(fread($this->fp, 4));
            if ($packed < $beginip) {
                $u = $i - 1;
            } else {
                fseek($this->fp, $this->getlong3());
                $endip = strrev(fread($this->fp, 4));
                if ($packed > $endip) {
                    $l = $i + 1;
                } else {
                    $findip = $this->firstip + $i * 7;
                    break;
                }
            }
        }

        fseek($this->fp, $findip);
        $location['beginip'] = long2ip($this->getlong());
        $offset = $this->getlong3();
        fseek($this->fp, $offset);
        $location['endip'] = long2ip($this->getlong());
        $byte = fread($this->fp, 1);
        switch (ord($byte)) {
            case 1:
                $countryOffset = $this->getlong3();
                fseek($this->fp, $countryOffset);
                $byte = fread($this->fp, 1);
                switch (ord($byte)) {
                    case 2:
                        fseek($this->fp, $this->getlong3());
                        $location['country'] = $this->getstring();
                        fseek($this->fp, $countryOffset + 4);
                        $location['area'] = $this->getarea();
                        break;
                    default:
                        $location['country'] = $this->getstring($byte);
                        $location['area'] = $this->getarea();
                        break;
                }
                break;
            case 2:
                fseek($this->fp, $this->getlong3());
                $location['country'] = $this->getstring();
                fseek($this->fp, $offset + 8);
                $location['area'] = $this->getarea();
                break;
            default:
                $location['country'] = $this->getstring($byte);
                $location['area'] = $this->getarea();
                break;
        }

        $location['country'] = iconv('GB2312', 'UTF-8//IGNORE', (string)$location['country']);
        $location['area'] = iconv('GB2312', 'UTF-8//IGNORE', (string)$location['area']);

        if ($this->isJunkCountry($location['country']) || $location['country'] === 'CZNET') {
            $location['country'] = '未知';
        }
        if ($location['area'] === 'CZNET' || $this->isJunkCountry($location['area'])) {
            $location['area'] = '';
        }

        return $location;
    }

    private function getlong()
    {
        $result = unpack('Vlong', fread($this->fp, 4));
        return $result['long'];
    }

    private function getlong3()
    {
        $result = unpack('Vlong', fread($this->fp, 3) . chr(0));
        return $result['long'];
    }

    private function packip($ip)
    {
        return pack('N', intval(ip2long($ip)));
    }

    private function getstring($data = '')
    {
        $char = fread($this->fp, 1);
        while (ord($char) > 0) {
            $data .= $char;
            $char = fread($this->fp, 1);
        }
        return $data;
    }

    private function getarea()
    {
        $byte = fread($this->fp, 1);
        switch (ord($byte)) {
            case 0:
                $area = '';
                break;
            case 1:
            case 2:
                fseek($this->fp, $this->getlong3());
                $area = $this->getstring();
                break;
            default:
                $area = $this->getstring($byte);
                break;
        }
        return $area;
    }

    public function __destruct()
    {
        if ($this->fp) {
            fclose($this->fp);
        }
        $this->fp = 0;
    }

    private function httpcopy($url, $file = '', $timeout = 60)
    {
        $file = empty($file) ? pathinfo($url, PATHINFO_BASENAME) : $file;
        $ch = curl_init();
        $fp = fopen(__DIR__ . '/' . $file, 'wb');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_NOPROGRESS, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 64000);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_exec($ch);
        $curl_info = curl_getinfo($ch);

        if (curl_errno($ch) || ($curl_info['http_code'] != 200 && $curl_info['http_code'] != 301 && $curl_info['http_code'] != 302)) {
            curl_error($ch);
            return false;
        }
        curl_close($ch);
        fclose($fp);
        return true;
    }
}
