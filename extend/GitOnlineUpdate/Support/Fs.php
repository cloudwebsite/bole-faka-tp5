<?php
namespace GitOnlineUpdate\Support;

/**
 * 基础文件操作（无框架依赖）
 */
class Fs
{
    public static function mkdir($path)
    {
        if (is_dir($path)) {
            return true;
        }
        return @mkdir($path, 0755, true);
    }

    public static function write($file, $content)
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            self::mkdir($dir);
        }
        return file_put_contents($file, $content) !== false;
    }

    public static function read($file, $default = '')
    {
        if (!is_file($file)) {
            return $default;
        }
        $data = file_get_contents($file);
        return $data === false ? $default : trim($data);
    }

    public static function delete($path)
    {
        if (is_file($path) || is_link($path)) {
            return @unlink($path);
        }
        if (!is_dir($path)) {
            return true;
        }
        $items = scandir($path);
        if ($items === false) {
            return false;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            self::delete($path . DIRECTORY_SEPARATOR . $item);
        }
        return @rmdir($path);
    }

    public static function copyDir($src, $dst)
    {
        if (!is_dir($src)) {
            return false;
        }
        self::mkdir($dst);
        $dir = opendir($src);
        if (!$dir) {
            return false;
        }
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $from = $src . DIRECTORY_SEPARATOR . $file;
            $to   = $dst . DIRECTORY_SEPARATOR . $file;
            if (is_dir($from)) {
                if (!self::copyDir($from, $to)) {
                    closedir($dir);
                    return false;
                }
            } else {
                self::mkdir(dirname($to));
                if (!@copy($from, $to)) {
                    closedir($dir);
                    return false;
                }
            }
        }
        closedir($dir);
        return true;
    }

    /**
     * 解压时无论回调如何，始终禁止覆盖的本机敏感路径（basename 或相对路径）
     * 防止旧版 skip 列表未含 conn.php 时整包把库配置盖掉
     */
    public static function criticalSkipPaths()
    {
        return [
            'files' => [
                'conn.php',
                '.env',
                'favicon.ico',
                'public/favicon.ico',
                'application/config.php',
                'application/admin.php',
                'application/database.php',
                'application/admin/database.php',
                'application/jingdian/database.php',
                'application/mobile/database.php',
                'application/madmin/database.php',
                'application/pay/database.php',
            ],
            'prefixes' => [
                'data/',
                'runtime/',
                'public/upload/',
                'public/uploads/',
                '.git/',
            ],
        ];
    }

    /**
     * @param string $relNorm 已规范化的相对路径（/ 分隔、无前导 /）
     */
    public static function isCriticalSkip($relNorm)
    {
        $relNorm = ltrim(str_replace('\\', '/', (string) $relNorm), '/');
        if ($relNorm === '' || strpos($relNorm, '..') !== false) {
            return true;
        }
        $crit = self::criticalSkipPaths();
        $base = basename($relNorm);
        if (in_array($base, $crit['files'], true) || in_array($relNorm, $crit['files'], true)) {
            return true;
        }
        // 任意层级的 database.php / conn.php
        if ($base === 'database.php' || $base === 'conn.php' || $base === '.env') {
            return true;
        }
        foreach ($crit['prefixes'] as $prefix) {
            if ($prefix !== '' && strpos($relNorm, $prefix) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * 检测 GitHub/Gitee 归档中的统一顶层目录（owner-repo-sha/）
     */
    protected static function detectZipRootPrefix(\ZipArchive $zip)
    {
        $top = null;
        $n = $zip->numFiles;
        for ($i = 0; $i < $n; $i++) {
            $name = str_replace('\\', '/', (string) $zip->getNameIndex($i));
            $name = ltrim($name, '/');
            if ($name === '' || strpos($name, '..') !== false) {
                continue;
            }
            $parts = explode('/', $name);
            $first = $parts[0];
            if ($first === '') {
                continue;
            }
            if ($top === null) {
                $top = $first;
            } elseif ($top !== $first) {
                return '';
            }
        }
        return $top !== null ? ($top . '/') : '';
    }

    /**
     * @param string        $zipFile
     * @param string        $dest
     * @param callable|null $shouldSkip function(string $relPath): bool
     */
    public static function unzip($zipFile, $dest, $shouldSkip = null)
    {
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('需要 PHP ZipArchive 扩展');
        }
        $zip = new \ZipArchive();
        if ($zip->open($zipFile) !== true) {
            return false;
        }
        self::mkdir($dest);

        $rootPrefix = self::detectZipRootPrefix($zip);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) {
                continue;
            }
            $nameNorm = str_replace('\\', '/', $name);
            $rel = $rootPrefix !== '' && strpos($nameNorm, $rootPrefix) === 0
                ? substr($nameNorm, strlen($rootPrefix))
                : $nameNorm;
            if ($rel === '' || $rel === false) {
                continue;
            }
            $relNorm = ltrim(str_replace('\\', '/', $rel), '/');
            if ($relNorm === '' || strpos($relNorm, '..') !== false) {
                continue;
            }
            // 硬编码敏感路径：即使旧回调漏配也绝不解压覆盖
            if (self::isCriticalSkip($relNorm)) {
                continue;
            }
            if (is_callable($shouldSkip) && call_user_func($shouldSkip, $relNorm)) {
                continue;
            }
            $target = rtrim($dest, '/\\') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relNorm);
            // 再保险：目标已存在且属敏感文件则跳过
            if (is_file($target) && self::isCriticalSkip($relNorm)) {
                continue;
            }
            if (substr($nameNorm, -1) === '/') {
                self::mkdir($target);
                continue;
            }
            self::mkdir(dirname($target));
            $content = $zip->getFromIndex($i);
            if ($content === false) {
                $zip->close();
                return false;
            }
            if (file_put_contents($target, $content) === false) {
                $zip->close();
                return false;
            }
        }
        $zip->close();
        return true;
    }

    public static function isWritablePath($path)
    {
        if (is_file($path) || is_dir($path)) {
            return is_writable($path);
        }
        $parent = dirname($path);
        while ($parent !== '' && $parent !== '/' && $parent !== '.' && !is_dir($parent)) {
            $parent = dirname($parent);
        }
        return is_dir($parent) && is_writable($parent);
    }

    public static function fileSize($file)
    {
        return is_file($file) ? filesize($file) : 0;
    }

    public static function join()
    {
        $parts = array_filter(array_map(function ($p) {
            return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, rtrim((string) $p, "/\\"));
        }, func_get_args()), function ($p) {
            return $p !== '' && $p !== null;
        });
        return implode(DIRECTORY_SEPARATOR, $parts);
    }
}
