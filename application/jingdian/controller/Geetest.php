<?php
namespace app\jingdian\controller;
use think\Controller;
use think\Config;
use think\Loader;
use think\Db;
use com\Geetestlib;

class Geetest extends Controller
{
 	public $param=[];
 	function _initialize(){
 		parent::_initialize();
 		$config = cache('db_config_data');
        if(!$config){            
            $config = load_config();                          
            cache('db_config_data',$config);
        }
        config($config);
 		if(isMobilePc()){
 			$client_type="h5";
 		}else{
 			$client_type="web";
 		}
 		$this->param=[
 			'user_id'=>GetIP(),
 			'client_type'=>$client_type,
 			'ip_address'=>GetIP()
 		];
 	}
 	public function StartCaptchaServlet(){
 		$GtSdk=new Geetestlib(config('Geetest_ID'),config('Geetest_KEY'));
 		$status=$GtSdk->pre_process($this->param,1);
 		session('gtserver',$status);
 		session('user_id',$this->param['user_id']);
 		echo $GtSdk->get_response_str();
 	}
 	public function gtcheck(){
 		$GtSdk=new Geetestlib(config('Geetest_ID'),config('Geetest_KEY'));
 		$param=inputself();

 	
 		if(session('gtserver')==1){
 			$result=$GtSdk->success_validate($param['geetest_challenge'], $param['geetest_validate'], $param['geetest_seccode'], $this->param);
 			if ($result) {
		        return true;
		    } else {
		        return false;
		    }
 		}else{
 			$result=$GtSdk->fail_validate($param['geetest_challenge'], $param['geetest_validate'], $param['geetest_seccode']);
 			if ($result) {
		        return true;
		    } else {
		        return false;
		    }
 		}
 		
 	}
 	
}
