<?php

namespace app\admin\controller;
use app\admin\model\Node;
use think\Controller;
use think\Db;

class Base extends Controller
{
    public function _initialize()
    {
		
        if(!session('uid')||!session('username')){
           //$this->redirect(url("/login"));          
           $this->error('抱歉，请先登录');
        }
        $hasAdmin=Db::name('admin')->where('id', session('uid'))->find();
        $token=md5($hasAdmin['username'] . $hasAdmin['password'].$_SERVER['HTTP_HOST'].date("Y-m-d").getIP());
        if($token!=session('admintoken')){
        	$this->redirect(url("/"));
        }
        
        
        $auth = new \com\Auth();   
        $module     = strtolower(request()->module());
        $controller = strtolower(request()->controller());
        $action     = strtolower(request()->action());
        $url        = $module."/".$controller."/".$action;

        //跳过检测以及主页权限
        if(session('uid')!=1){
            if(!in_array($url, ['admin/index/index','admin/index/indexpage'])){
                if(!$auth->check($url,session('uid'))){
                    $this->error('抱歉，您没有操作权限');
                }
            }
        }
        
        $node = new Node();
        $this->assign([
            'username' => session('username'),
            'portrait' => session('portrait'),
            'rolename' => session('rolename'),
            'menu' => $node->getMenu(session('rule'))
        ]);
        

        // 站点配置缓存：只写入库表配置项，避免把运行时框架配置混入缓存
        $config = cache('db_config_data');
        if (!$config) {
            $config = rebuild_db_config_cache();
        }
        config($config);

        if(config('web_site_close') == 0 && session('uid') !=1 ){
            $this->error('站点已经关闭，请稍后访问~');
        }

        // 后台 IP 白名单：已填写时仅名单内可进（超级管理员除外）
        if (config('admin_allow_ip') && session('uid') != 1) {
            $allow = array_filter(array_map('trim', explode('#', (string)config('admin_allow_ip'))));
            if ($allow && !in_array(getIP(), $allow, true)) {
                $this->error('403:禁止访问');
            }
        }
      
        if(empty($hasAdmin['superpassword']) && $url!="admin/index/setsuperpwd"){
        	$this->redirect('index/setsuperpwd');
        }

        // 表单提交校验令牌（上传/编辑器除外）
        if (request()->isPost()) {
            $skipCsrf = ['upload', 'ueditor'];
            if (!in_array($controller, $skipCsrf, true) && !check_form_token(false)) {
                if (request()->isAjax()) {
                    header('Content-Type: application/json; charset=utf-8');
                    exit(json_encode(['code' => 0, 'msg' => '令牌验证失败，请刷新页面后重试'], JSON_UNESCAPED_UNICODE));
                }
                $this->error('令牌验证失败，请刷新页面后重试');
            }
        }

        if (!\think\Session::has('__token__')) {
            \think\Request::instance()->token();
        }
        $this->assign('csrf_token', \think\Session::get('__token__'));
    }
}