<?php

namespace app\admin\controller;
use think\Config;
use think\Loader;
use think\Db;

class Index extends Base
{
    public function index()
    {             
       return $this->fetch('/index');       
    }


    /**
     * [indexPage 后台首页]
     * @return [type] [description]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function indexPage()
    {      
        
        $today = strtotime(date("Y-m-d"),time());
        $month_start = strtotime(date("Y-m-01"));
        $month_end = strtotime("+1 month -1 seconds", $month_start);
        $shang_start = strtotime("-1 month", $month_start);;
        $shang_end = strtotime("+1 month -1 seconds", $shang_start);
        $ben = Db::name("info")
            ->where("mstatus <>2")
            ->where("update_time >=".$month_start)
            ->where("update_time <=".$month_end)
            ->sum("mamount");
            
        $shang = Db::name("info")
            ->where("mstatus <>2")
            ->where("update_time >=".$shang_start)
            ->where("update_time <=".$shang_end)
            ->sum("mamount");
        $benyue = Db::name("info")
            ->where("mstatus <>2")
            ->where("maddtype =5")
            ->where("update_time >=".$today)
            ->where("update_time <=".($today+86400))
            ->sum("mamount");
            
        $shangyue = Db::name("info")
            ->where("mstatus <>2")
            ->where("maddtype =5")
            ->where("update_time >=".($today-86400))
            ->where("update_time <=".($today-1))
            ->sum("mamount");
            
         //今日收益
        $sql="SELECT sum(mamount) as jinri FROM `think_info` where mstatus<>2 and to_days(date_format(from_UNIXTIME(`update_time`),'%Y-%m-%d')) = to_days(now())";
        $info_jinri = Db::query($sql);
         //昨日收益
        $sql="SELECT sum(mamount) as zuori FROM `think_info` where mstatus<>2 and to_days(now())-to_days(date_format(from_UNIXTIME(`update_time`),'%Y-%m-%d')) =1 ";
        $info_zuori = Db::query($sql);
        //商品数目
        $sql="SELECT count(1) as flnum from think_fl";
        $info_fl = Db::query($sql);
         //订单数目
         $noti='';
        if(getadminpath()=="houtai"){
            $noti.="请尽快修改默认后台目录！";
        }
        if(session('username')=="admin"){
            $noti.="请尽快修改后台初始管理员账号！";
        }
        $sql="SELECT count(1) as infonum from think_info";
        $info_info = Db::query($sql);
        $info = array(
            'web_server' => $_SERVER['SERVER_SOFTWARE'],
            'onload'     => ini_get('upload_max_filesize'),
            'think_v'    => THINK_VERSION,
            'phpversion' => phpversion(),
            'ben' => $ben,
            'shang' => $shang,
            'benyue' => $benyue,
            'shangyue' => $shangyue,
            'noti' => $noti,
        );
        //检查版本
        $this->assign('info_jinri', $info_jinri);
        $this->assign('info_zuori', $info_zuori);
        $this->assign('info_fl', $info_fl);
        $this->assign('info_info', $info_info);
        $this->assign('info',$info);
        return $this->fetch('index');
    }


	/**
     * [userEdit 修改用户名]
     * @return [type] [description]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function editadminname(){

        if(request()->isAjax()){
            $param = input('post.');
            $user=Db::name('admin')->where('id='.session('uid'))->find();
            if(md5(md5($param['password']) . config('auth_key'))!=$user['password']){
               return json(['code' => -1, 'url' => '', 'msg' => '密码错误']);
            }else{
                $pwd['username']=$param['username'];
                Db::name('admin')->where('id='.$user['id'])->update($pwd);
                session(null);
                cache('db_config_data',null);//清除缓存中网站配置信息
                return json(['code' => 1, 'url' => 'index/index', 'msg' => '用户名修改成功']);
            }
        }
        return $this->fetch();
    }
    
    /**
     * [userEdit 修改密码]
     * @return [type] [description]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function editpwd(){

        if(request()->isAjax()){
            $param = input('post.');
            $user=Db::name('admin')->where('id='.session('uid'))->find();
            if(md5(md5($param['old_password']) . config('auth_key'))!=$user['password']){
               return json(['code' => -1, 'url' => '', 'msg' => '旧密码错误']);
            }else{
                $pwd['password']=md5(md5($param['password']) . config('auth_key'));
                Db::name('admin')->where('id='.$user['id'])->update($pwd);
                session(null);
                cache('db_config_data',null);//清除缓存中网站配置信息
                return json(['code' => 1, 'url' => 'index/index', 'msg' => '密码修改成功']);
            }
        }
        return $this->fetch();
    }
    
     /**
     * [userEdit 设置超级密码]
     * @return [type] [description]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function setsuperpwd(){

        if(request()->isAjax()){
            $param = input('post.');
            $user=Db::name('admin')->where('id='.session('uid'))->find();
            if(!empty($user['superpassword'])){
            	return json(['code' => -1, 'url' => '', 'msg' => '小子你想干啥？超级密码已经有了']);
            }
            if(md5(md5($param['old_password']) . config('auth_key'))!=$user['password']){
               return json(['code' => -1, 'url' => '', 'msg' => '登录密码错误']);
            }else{
                $pwd['superpassword']=md5(md5($param['password']) . config('auth_key'));
                if($pwd['superpassword']==md5(md5($param['old_password']) . config('auth_key'))){
                	 return json(['code' => -1, 'url' => '', 'msg' => '超级密码不能和登录密码一样']);
                }
                Db::name('admin')->where('id='.$user['id'])->update($pwd);
                cache('db_config_data',null);//清除缓存中网站配置信息
                return json(['code' => 1, 'url' => 'index/index', 'msg' => '超级密码设置成功']);
            }
        }
        return $this->fetch();
    }
     /**
     * [userEdit 修改超级密码]
     * @return [type] [description]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function editsuperpwd(){

        if(request()->isAjax()){
            $param = input('post.');
            $user=Db::name('admin')->where('id='.session('uid'))->find();
            if(md5(md5($param['old_password']) . config('auth_key'))!=$user['superpassword']){
               return json(['code' => -1, 'url' => '', 'msg' => '旧超级密码错误']);
            }else{
                $pwd['superpassword']=md5(md5($param['password']) . config('auth_key'));
                if($pwd['superpassword']==$user['password']){
                	 return json(['code' => -1, 'url' => '', 'msg' => '超级密码不能和登录密码一样']);
                }
                Db::name('admin')->where('id='.$user['id'])->update($pwd);
                session(null);
                cache('db_config_data',null);//清除缓存中网站配置信息
                return json(['code' => 1, 'url' => 'index/index', 'msg' => '超级密码修改成功']);
            }
        }
        return $this->fetch();
    }
    
    
     /**
     * 修改后台目录
     */
    public function changeAdminPath() {
        if(request()->isAjax()){
            if ((int)session('uid') !== 1) {
                return json(['code' => 0, 'msg' => '仅超级管理员可修改后台目录']);
            }
        	$newpath=input('param.newadminpath');
        	$newpath=myTrim($newpath);
        	if($newpath=="admin"){
        		return json(['code' => 0, 'msg' => '修改失败，admin不能作为后台目录']); 
        	}
        	if (!preg_match("/^[\x{4e00}-\x{9fa5}A-Za-z0-9]+$/u", $newpath)) {			  	
                return json(['code' => 0, 'msg' => '修改失败，目录只支持数字，字母，汉字']); 
			}
            $reserved = ['api', 'jingdian', 'mobile', 'pay', 'index', 'static', 'uploads', 'runtime'];
            if (in_array(strtolower($newpath), $reserved, true)) {
                return json(['code' => 0, 'msg' => '修改失败，目录与系统保留路径冲突']);
            }
			$content="<?php use think\Route;Route::rule('".$newpath."','admin/login/index');";
        	$mcontent="<?php use think\Route;Route::rule('m".$newpath."','madmin/index/login');";
			if(file_put_contents(ROOT_PATH.'application/admin.php',$content)){
				file_put_contents(ROOT_PATH.'application/madmin.php',$mcontent);
                \think\Log::write('[changeAdminPath] uid=1 path=' . $newpath . ' ip=' . getIP(), 'notice');
				return json(['code' => 1, 'msg' => '修改成功，pc目录'.$newpath.'  手机版目录m'.$newpath]);
			}else{
				return json(['code' => 0, 'msg' => '修改失败']);
			}
        }
        return $this->fetch('adminpath');
    }


    /**
     * 清除缓存
     */
    public function clear() {
        if (delete_dir_file(CACHE_PATH) && delete_dir_file(TEMP_PATH)) {
            return json(['code' => 1, 'msg' => '清除缓存成功']);
        } else {
            return json(['code' => 0, 'msg' => '清除缓存失败']);
        }
    }

}
