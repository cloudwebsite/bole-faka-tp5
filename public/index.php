<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2015 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
if (!file_exists(__DIR__ . '/../data/install.lock'))
{
    require_once __DIR__.'/../data/install.php';
    exit;
}
if (file_exists(__DIR__ . '/../data/update.php'))
{
    require_once __DIR__.'/../data/update.php';
    exit;
}
// [ 应用入口文件 ]

require __DIR__ . '/waf.php';
// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 定义上传目录
define('UPLOAD_PATH', __DIR__. '/' );
// 定义应用缓存目录
define('RUNTIME_PATH', __DIR__ . '/../runtime/');
// 加载框架引导文件

require __DIR__ . '/../thinkphp/start.php';

?>