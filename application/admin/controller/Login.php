<?php

namespace app\admin\controller;
use app\admin\model\UserType;
use think\Controller;
use think\Db;
use think\Request;
use org\Verify;
use com\Geetestlib;

class Login extends Controller
{


    public function _initialize()
    {
        $config = cache('db_config_data');
        if(!$config){            
            $config = load_config();                          
            cache('db_config_data',$config);
        }
        config($config); 
        
    }
    /**
     * 登录页面
     * @return
     */
    public function index()
    {
		$request = Request::instance();
		if($request->pathinfo()!=getadminpath().'.html' && $request->pathinfo()!=getadminpath()){
         	 abort(404,'页面不存在');
        }
        $this->assign('verify_type', config('verify_type'));        
        return $this->fetch('/login');
    }


    /**
     * 登录操作
     * @return
     */
    public function doLogin()
    {
        
        $referer=$_SERVER['HTTP_REFERER'];
        $referer=str_ireplace("http://","",$referer);
        $referer=str_ireplace("https://","",$referer);
        if(urldecode($referer)!=$_SERVER['HTTP_HOST'].'/'.getadminpath().'.html' && urldecode($referer)!=$_SERVER['HTTP_HOST'].'/'.getadminpath()){
         	 return json(['code' => 404, 'url' => '404.html', 'msg' => '404页面丢失']);
        }
        $username = input("param.username");
        $password = input("param.password");
        

        if (config('verify_type') == 1) {
            $code = input("param.code");
        }
        
        $result = $this->validate(compact('username', 'password'), 'AdminValidate');
        if(true !== $result){
            return json(['code' => -5, 'url' => '', 'msg' => $result]);
        }
        $verify = new Verify();
        if (config('verify_type') == 1) {
            if (!$code) {
                return json(['code' => -4, 'url' => '', 'msg' => '请输入验证码']);
            }
            if (!$verify->check($code)) {
                return json(['code' => -4, 'url' => '', 'msg' => '验证码错误']);
            }
        }

        $hasUser = Db::name('admin')->where('username', $username)->find();
        if(empty($hasUser)){
            return json(['code' => -1, 'url' => '', 'msg' => '管理员不存在']);
        }

         if(md5(md5($password) . config('auth_key')) != $hasUser['password']){
             writelog($hasUser['id'],$username,'用户【'.$username.'】登录失败：密码错误',2);
             return json(['code' => -2, 'url' => '', 'msg' => '账号或密码错误']);
         }

        if(1 != $hasUser['status']){
            writelog($hasUser['id'],$username,'用户【'.$username.'】登录失败：该账号被禁用',2);
            return json(['code' => -6, 'url' => '', 'msg' => '该账号被禁用']);
        }
        


        //获取该管理员的角色信息
        $user = new UserType();
        $info = $user->getRoleInfo($hasUser['groupid']);
        $token=md5($hasUser['username'] . $hasUser['password'].$_SERVER['HTTP_HOST'].date("Y-m-d").getIP());
        session('uid', $hasUser['id']);         //用户ID
        session('username', $hasUser['username']);  //用户名
        session('password', $hasUser['password']);  //用户名
        session('portrait', $hasUser['portrait']); //用户头像
        session('rolename', $info['title']);    //角色名
        session('rule', $info['rules']);        //角色节点
        session('name', $info['name']);         //角色权限
        session("admintoken",$token);
  
        //更新管理员状态
        $param = [
            'loginnum' => $hasUser['loginnum'] + 1,
            'last_login_ip' => getIP(),
            'last_login_time' => time(),
            'token' => $token
        ];

        Db::name('admin')->where('id', $hasUser['id'])->update($param);

        // 数据清理已迁至后台「数据清理」页（admin/DataClean），登录不再静默删库/删卡密 txt
        
        writelog($hasUser['id'],session('username'),'用户【'.session('username').'】登录成功',1);
        return json(['code' => 1, 'url' => url('index/index'), 'msg' => '登录成功！']);
    }


    /**
     * 验证码
     * @return
     */
    public function checkVerify()
    {
        $verify = new Verify();
        $verify->imageH = 32;
        $verify->imageW = 100;
        $verify->length = 4;
        $verify->useCurve = false;
        $verify->useNoise = false;
        $verify->fontSize = 14;
        return $verify->entry();
    }


    /**
     * 退出登录
     * @return
     */
    public function loginOut()
    {
        session(null);
        cache('db_config_data',null);//清除缓存中网站配置信息
        $this->redirect(url("/"));
    }
}