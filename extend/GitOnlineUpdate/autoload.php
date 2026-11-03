<?php
/**
 * 简易 PSR-4 自动加载（无需 Composer）
 */
spl_autoload_register(function ($class) {
    $prefix = 'GitOnlineUpdate\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
