<?php

namespace app\admin\controller;
use app\admin\Model\DemoModel;
use think\Db;

class Demo extends Base
{

    /**
     * 发送短信
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function sms(){

        if(request()->isAjax()){
            $param = inputself();
            $mobile = $param['mobile'];     //手机号
            $tplCode = $param['tplcode'];   //模板ID
            //$tplParam['tit'] = $param['code'];//验证码
            $msgStatus = sendMsg($mobile,$tplCode,$param);
            return json(['code' => $msgStatus['Code'], 'msg' => $msgStatus['Message']]);
        }
        return $this->fetch();      
    }
    
    /**
     * 发送短信
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function email(){

        if(request()->isAjax()){
        	$param = inputself();
            $to = $param['to'];     //对方邮箱
            $msg="";
        	$json=SendMail($to,"我是标题","我是正文，测试发送。。。收到请回答",'');

           
            return json(['code' => $json['code'], 'msg' =>$json['msg']]);
        }
        return $this->fetch();      
    }




    /**
     * 生成二维码
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function qrcode(){

        return $this->fetch();
        
    }

}