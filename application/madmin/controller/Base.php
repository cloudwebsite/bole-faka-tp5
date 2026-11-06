<?php
namespace app\madmin\controller;
use think\Controller;
use think\Request;
use think\Db;

class Base extends Controller
{
    public function _initialize()
    {
        $request = Request::instance();
        // 站点配置缓存：只写入库表配置项
        $config = cache('db_config_data');
        if (!$config) {
            $config = rebuild_db_config_cache();
        }
        config($config);

        $module     = strtolower(request()->module());
        $controller = strtolower(request()->controller());
        $action     = strtolower(request()->action());
        $url        = $module . "/" . $controller . "/" . $action;

        // 登录相关免鉴权
        $loginurl = [
            "madmin/index/login",
            "madmin/index/dologin",
            "madmin/index/checkverify",
            "madmin/index/loginout",
        ];
        if (in_array($url, $loginurl, true)) {
            return;
        }

        $loginPath = 'm' . getadminpath();
        if (!session('uid') || !session('username')) {
            if ($request->path() != $loginPath) {
                $this->redirect(url('index/login'));
            }
            return;
        }

        $hasAdmin = Db::name('admin')->where('id', session('uid'))->find();
        if (empty($hasAdmin)) {
            session(null);
            $this->redirect(url('index/login'));
            return;
        }

        $token = md5($hasAdmin['username'] . $hasAdmin['password'] . $_SERVER['HTTP_HOST'] . date("Y-m-d") . getIP());
        if ($token != session('admintoken')) {
            session(null);
            $this->redirect(url('index/login'));
            return;
        }

        if (config('web_site_close') == 0 && session('uid') != 1) {
            $this->error('站点已经关闭，请稍后访问~');
        }
        // 后台 IP 白名单：已填写时仅名单内可进（超级管理员除外）
        if (config('admin_allow_ip') && session('uid') != 1) {
            $allow = array_filter(array_map('trim', explode('#', (string)config('admin_allow_ip'))));
            if ($allow && !in_array(getIP(), $allow, true)) {
                $this->error('403:禁止访问');
            }
        }
    }
}
