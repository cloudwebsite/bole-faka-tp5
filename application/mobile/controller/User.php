<?php
namespace app\mobile\controller;
use app\admin\model\MemberModel;
use app\admin\model\MemberGroupModel;
use app\jingdian\model\IndexModel;
use app\jingdian\model\UserModel;
use app\jingdian\model\IntegralModel;
use think\Config;
use think\Loader;
use org\Verify;
use think\Db;
use com\IpLocationqq;
class User extends Base
{ 
    public function index(){
       if(!request()->isPost()){
	       	if(empty($referer)){
	       		$referer=input("param.former_url");
	       	}
	       	if(empty($referer)){
	       		$referer=url('user/usCenter');
	       	}
       		if(session('useraccount.id')||session('useraccount.account')){
	            return $this->redirect(url('@mobile/index/index'),302);
	        }
	        $this->assign('former_url', $referer);
       		return $this->fetch();    
       }
       	
       	
       	$account = input("param.account");
       	$password = input("param.password");
        $code = input("param.code");
        $referer=input("param.former_url");
        
        $result = $this->validate(compact('account', 'password'), 'MemberloginValidate');
        if(true !== $result){
            return json(['code' => -5, 'url' => '', 'msg' => $result]);
        }
        
        if(config('CODE_TYPE')==0){
        	$code = input("param.code");
        	$verify = new Verify();
	       	if (!$code) {
	                return json(['code' => -4, 'url' => '', 'msg' => '请输入验证码']);
	        }
	        if (!$verify->check($code)) {
	                return json(['code' => -4, 'url' => '', 'msg' => '验证码错误']);
	        }
        }elseif(config('CODE_TYPE')==1){
        	$gtresult=action("jingdian/Geetest/gtcheck");
        	if($gtresult==false){
        		return json(['code' => -4, 'url' => '', 'msg' => '验证码错误']);
        	}
        	
        }
      
        

        $hasUser = Db::name('member')->where('account', $account)->find();
        if(empty($hasUser)){
            return json(['code' => -1, 'url' => '', 'msg' => '用户不存在']);
        }
        
        if($hasUser['login_error']>=config('loginerrornum')){
        	$minute=floor((time()-$hasUser['login_error_time'])%86400/60);
        	if($minute<config('frozentime')){
        		$cha=config('frozentime')-$minute;
        		 return json(['code' => -3, 'url' => '', 'msg' => '密码输入错误'.config('loginerrornum').'次，距离解冻还差'.$cha.'分']);
        	}
        }

        if(md5(md5($password) . config('auth_key')) != $hasUser['password']){
           	writeloginlog($hasUser['id'],"账号或密码错误");
            $errornum=$hasUser['login_error'];
            if($errornum>=config('loginerrornum')){
            	$errornum=0;
            }
            
            //更新用户状态
	        $param = [
	            'login_error' => $errornum + 1,
	            'login_error_time' => time(),
	        ];
            Db::name('member')->where('id', $hasUser['id'])->update($param);
            return json(['code' => -2, 'url' => '', 'msg' => '账号或密码错误']);
        }

        if(1 != $hasUser['status']){
            writeloginlog($hasUser['id'],"该账号未开通，请联系管理员");
            return json(['code' => -6, 'url' => '', 'msg' => '该账号未开通，请联系管理员']);
        }


       
        $token=md5(md5($hasUser['account'] . $hasUser['password']).md5(date("Y-m-d")). config('auth_key'). config('token').$_SERVER['HTTP_HOST']);
        session('useraccount', $hasUser);
        cookie('usertoken',$token);
        $openid=empty(session('openid'))?'':session('openid');
        if(empty($openid)){
           //更新用户状态
          $param = [
              'login_num' => $hasUser['login_num'] + 1,
              'last_login_ip' => getIP(),
              'last_login_time' => time(),
              'token' => $token,
              'login_error' => 0
          ];
        
        }else{
           //更新用户状态
          $param = [
              'login_num' => $hasUser['login_num'] + 1,
              'last_login_ip' => getIP(),
              'last_login_time' => time(),
              'token' => $token,
              'login_error' => 0,
              'openid'=>md5($openid)
          ];
        }
       
        
        Db::name('member')->where('id', $hasUser['id'])->update($param);
       	writeloginlog($hasUser['id'],"登录成功");
        return json(['code' => 1, 'url' => $referer, 'msg' => '登录成功！']);
            
    }
    public function reg(){
    	if(config('web_reg_xingshi')==1){
       		//仅手机号
       		return $this->redirect(url('@mobile/user/regmobile'),302);
       	}
       if(!request()->isPost()){
       		if(session('useraccount.account')){
	            return $this->redirect(url('@mobile/index/index'),302);
	        }
       		return $this->fetch('/user/reg');     
       }
	       $param = input('post.');
	       if(config('CODE_TYPE')==0){
	        	$code = input("param.code");
	        	$verify = new Verify();
		       	if (!$code) {
		                return json(['code' => -4, 'url' => '', 'msg' => '请输入验证码']);
		        }
		        if (!$verify->check($code)) {
		                return json(['code' => -4, 'url' => '', 'msg' => '验证码错误']);
		        }
	        }elseif(config('CODE_TYPE')==1){
	        	$gtresult=action("jingdian/Geetest/gtcheck");
	        	if($gtresult==false){
	        		return json(['code' => -4, 'url' => '', 'msg' => '验证码错误']);
	        	}
	        	
	        }
	        
        
        $param['password'] = md5(md5($param['password']) . config('auth_key'));
        $group = new MemberGroupModel();
        $res=$group->getdefault();
        if($res){
        	$group_id=$res['id'];
        }else{
        	$respoint0=$group->getpoint0();
        	if($respoint0){
        		$group_id=$respoint0['id'];
        	}else{
        		$group_id=0;
        	}
        	
        	
        }
        $param['group_id']=$group_id;
        $token=md5(md5($param['account'] . $param['password']).md5(date("Y-m-d")). config('auth_key'). config('token').$_SERVER['HTTP_HOST']);
        $integral=config('web_reg_point');
        $money=config('web_reg_money');
        if(config('web_reg_status')==0){
        	$regstatus=1;
        }else{
        	$regstatus=0;
        }
        //分销
        $pid1=0;
        if(session('userpid')){
        	$pid1=session('userpid');
        }       
        $pid2=0;
        $pid3=0;
        if($pid1!=0){
        	$pid2Result=Db::name('member')->where('id',$pid1)->find();
        	if($pid2Result){
        		if($pid2Result['is_distribut']==1){
        			$pid2=$pid2Result['pid1'];
	        		$pid3Result=Db::name('member')->where('id',$pid2)->find();
	        		if($pid3Result){
	        			if($pid3Result['is_distribut']==1){
	        				$pid3=$pid3Result['pid1'];
		        			$pid4Result=Db::name('member')->where('id',$pid3)->find();
		        			if($pid4Result==false){
		        				$pid3=0;
		        			}elseif($pid4Result['is_distribut']==0){
		        				$pid3=0;
		        			}
	        			}else{
	        				$pid2=0;
	        			}
	        			
	        		}else{
	        			$pid2=0;
	        		}
        		}else{
        			$pid1=0;
        		}
        		
        	}else{
        		$pid1=0;
        	}
        	
        }
        
        
        $data=[
        		'account'=>$param['account'],
        		'password'=>$param['password'],
        		'group_id'=>$param['group_id'],
        		'mobile'=>$param['mobile'],
        		'email'=>$param['email'],
        		'qq'=>$param['qq'],
        		'token'=>$token,
        		'integral'=>$integral,
        		'money'=>$money,
        		'status'=>$regstatus,
        		'pid1'=>$pid1,
        		'pid2'=>$pid2,
        		'pid3'=>$pid3,
        	  ];
        	  
        	  
        $member = new MemberModel();
        $flag = $member->insertMember($data);
        if($flag['code']==1){
        	$memberData=Db::name('member')->where('account',$param['account'])->find();      	
        	if($integral>0){
        		//记录积分log
	    		writeintegrallog($memberData['id'],"注册赠送积分",0,$integral);
        	}
        	if($money>0){
        		//记录金额log
	    		writemoneylog($memberData['id'],"注册赠送金额",0,$money);
	    		writeamounttotal($memberData['id'],$money,'zsmoney');
        	}
        	if($regstatus==1){
        		return json(['code' => 1, 'url' => url('mobile/user/index'), 'msg' => '注册成功']);
        	}else{
        		return json(['code' => 1, 'url' => url('mobile/user/index'), 'msg' => '注册成功,请联系客服开通']);
        	}
        	
        	if($_SERVER['HTTP_REFERER']!==''){
        		$referer=$_SERVER['HTTP_REFERER'];
        	}else{
        		$referer=url('mobile/index/index');
        	}
        	return json(['code' => 1, 'url' => $referer, 'msg' => '注册成功！']);
        }else{
        	return json(['code' => -2, 'url' => '', 'msg' => $flag['msg']]);
        	
        }
        
  
    }
    
    /*
     * 手机号注册
     */
    public function regmobile(){
    	if(config('web_reg_xingshi')==0){
       		//普通帐号
       		return $this->redirect(url('@mobile/user/reg'),302);
       }
    	
    	if(!request()->isPost()){
       		if(session('useraccount.account')){
	            return $this->redirect(url('@mobile/index/index'),302);
	        }
       		return $this->fetch();     
       	}
       	$param = input('post.');       
        $smscode=$param['smscode'];
        $account=$param['account'];
        if (!$smscode) {
        	return json(['code' => -4, 'url' => '', 'msg' => '请输入手机验证码']);
            
        }
        if ($smscode!=session('regcode')) {
           	return json(['code' => -4, 'url' => '', 'msg' => '手机验验证码错误']);
        }
        
        if (!$account) {
        	return json(['code' => -4, 'url' => '', 'msg' => '请输入手机号']);
            
        }
        if ($account!=session('regmobile')) {
           	return json(['code' => -4, 'url' => '', 'msg' => '手机号和验证码不匹配']);
        }
        
        
        
        $param['password'] = md5(md5($param['password']) . config('auth_key'));
        $group = new MemberGroupModel();
        $res=$group->getdefault();
        if($res){
        	$group_id=$res['id'];
        }else{
        	$respoint0=$group->getpoint0();
        	if($respoint0){
        		$group_id=$respoint0['id'];
        	}else{
        		$group_id=0;
        	}
        	
        	
        }
        $param['group_id']=$group_id;
        $token=md5(md5($param['account'] . $param['password']).md5(date("Y-m-d")). config('auth_key'). config('token').$_SERVER['HTTP_HOST']);
        $integral=config('web_reg_point');
        $money=config('web_reg_money');
        if(config('web_reg_status')==0){
        	$regstatus=1;
        }else{
        	$regstatus=0;
        }
         //分销begin
        $pid1=0;
        if(session('userpid')){
        	$pid1=session('userpid');
        }       
        $pid2=0;
        $pid3=0;
        if($pid1!=0){
        	$pid2Result=Db::name('member')->where('id',$pid1)->find();
        	if($pid2Result){
        		if($pid2Result['is_distribut']==1){
        			$pid2=$pid2Result['pid1'];
	        		$pid3Result=Db::name('member')->where('id',$pid2)->find();
	        		if($pid3Result){
	        			if($pid3Result['is_distribut']==1){
	        				$pid3=$pid3Result['pid1'];
		        			$pid4Result=Db::name('member')->where('id',$pid3)->find();
		        			if($pid4Result==false){
		        				$pid3=0;
		        			}elseif($pid4Result['is_distribut']==0){
		        				$pid3=0;
		        			}
	        			}else{
	        				$pid2=0;
	        			}
	        			
	        		}else{
	        			$pid2=0;
	        		}
        		}else{
        			$pid1=0;
        		}
        		
        	}else{
        		$pid1=0;
        	}
        	
        }
        //分销end
        
        
        $data=[
        		'account'=>$param['account'],
        		'password'=>$param['password'],
        		'group_id'=>$param['group_id'],
        		'mobile'=>$param['account'],
        		'email'=>$param['account'].'@qq.com',
        		'qq'=>$param['account'],
        		'token'=>$token,
        		'mobileauth'=>1,
        		'integral'=>$integral,
        		'money'=>$money,
        		'status'=>$regstatus,
        		'pid1'=>$pid1,
        		'pid2'=>$pid2,
        		'pid3'=>$pid3,
        	  ];
        	  
        $member = new MemberModel();
        $flag = $member->insertMember($data);
        if($flag['code']==1){
        	$memberData=Db::name('member')->where('account',$param['account'])->find();      	
        	if($integral>0){
        		//记录积分log
	    		writeintegrallog($memberData['id'],"注册赠送积分",0,$integral);
        	}
        	if($money>0){
        		//记录金额log
	    		writemoneylog($memberData['id'],"注册赠送金额",0,$money);
	    		writeamounttotal($memberData['id'],$money,'zsmoney');
        	}
        	if($regstatus==1){
        		return json(['code' => 1, 'url' => url('mobile/index/index'), 'msg' => '注册成功']);
        	}else{
        		return json(['code' => 1, 'url' => url('mobile/index/index'), 'msg' => '注册成功,请联系客服开通']);
        	}
        	
        	
        	$referer=url('mobile/index/index');
        	session('regcode',null);
        	session('regmoblie',null);
        	return json(['code' => 1, 'url' => $referer, 'msg' => '注册成功！']);
        }else{
        	return json(['code' => -2, 'url' => '', 'msg' => $flag['msg']]);
        	
        }
    }
    public function getpass(){
       return $this->fetch('/user/getpass');     
    }
    
     /**
     * 退出登录
     * @return
     */
    public function loginOut()
    {
        session(null);
        cookie('usertoken',null);
        $this->redirect('@mobile/index/index');
    }
    
     /**
     * 用户中心
     * @return
     */
    public function usCenter()
    {

       if(session('useraccount.id')){

	        $hasUser =Db::name('member')->where('id', session('useraccount.id'))->find();
	        $groupUser =Db::name('member_group')->where('id', $hasUser['group_id'])->find();
	        if($groupUser==false){
	        	$groupUser['group_name']="注册会员";
	        	$groupUser['discount']="100";
	
	        }
	        $Ip = new IpLocationqq('qqwry.dat'); // 实例化类 参数表示IP地址库文件
	        $hasUser['last_login_ipadd'] = $Ip->getlocation($hasUser['last_login_ip']);	        
	        if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){
	        	 if(config('fz_selectauth')==0){
		        	$childid=empty(session('child_useraccount.id'))?0:session('child_useraccount.id');
		        	$map['childid'] =$childid;
		        	$orderUser=Db::query("SELECT think_info.*,IFNULL(think_fl.mname,'未知') as name,IFNULL(think_fl.imgurl,'') as imgurl,IFNULL(think_fl.yunimgurl,'') as yunimgurl,think_fl.type as type from think_info LEFT JOIN think_fl on think_fl.id=think_info.mflid WHERE think_info.memberid=:memberid and think_info.childid=:childid order by think_info.id desc",["memberid"=>$hasUser['id'],'childid'=>$childid]);
		            $childData=Db::name('child_fl')->where('memberid',session('child_useraccount.id'))->select();		           
		            foreach($orderUser as &$vorder){
		            	foreach($childData as $vchild){
		            		if($vorder['mflid']==$vchild['goodid'] && $vchild['imgurl']!=-1){
		            			$vorder['imgurl']=$vchild['imgurl'];
		            			$vorder['yunimgurl']=$vchild['yunimgurl'];
		            		}
		            	}
		            }
		            $count=count($orderUser);
		            $zcount=Db::query("select count(id) as zcount from think_info where memberid=:memberid and mstatus not in (1,2,4,5) and think_info.childid=:childid",['memberid'=>session('useraccount.id'),'childid'=>$childid]);
		            $successcount=Db::query("select count(id) as successcount from think_info where memberid=:memberid and mstatus in (1,4,5) and think_info.childid=:childid",['memberid'=>session('useraccount.id'),'childid'=>$childid]); 
		        }else{
		        	$orderUser=Db::query("SELECT think_info.*,IFNULL(think_fl.mname,'未知') as name,IFNULL(think_fl.imgurl,'') as imgurl,IFNULL(think_fl.yunimgurl,'') as yunimgurl,think_fl.type as type from think_info LEFT JOIN think_fl on think_fl.id=think_info.mflid WHERE think_info.memberid=:memberid order by think_info.id desc",["memberid"=>$hasUser['id']]);
		            $childData=Db::name('child_fl')->where('memberid',session('child_useraccount.id'))->select();		           
		            foreach($orderUser as &$vorder){
		            	foreach($childData as $vchild){
		            		if($vorder['mflid']==$vchild['goodid'] && $vchild['imgurl']!=-1){
		            			$vorder['imgurl']=$vchild['imgurl'];
		            			$vorder['yunimgurl']=$vchild['yunimgurl'];
		            		}
		            	}
		            }
		            $count=count($orderUser);
		            $zcount=Db::query("select count(id) as zcount from think_info where memberid=:memberid and mstatus not in (1,2,4,5)",['memberid'=>session('useraccount.id')]);
		            $successcount=Db::query("select count(id) as successcount from think_info where memberid=:memberid and mstatus in (1,4,5)",['memberid'=>session('useraccount.id')]);
		        }	 
	        }else{
	        	$orderUser=Db::query("SELECT think_info.*,IFNULL(think_fl.mname,'未知') as name,IFNULL(think_fl.imgurl,'') as imgurl,IFNULL(think_fl.yunimgurl,'') as yunimgurl,think_fl.type as type from think_info LEFT JOIN think_fl on think_fl.id=think_info.mflid WHERE think_info.memberid=:memberid order by think_info.id desc",["memberid"=>$hasUser['id']]);
		        $count=count($orderUser);
		        $zcount=Db::query("select count(id) as zcount from think_info where memberid=:memberid and mstatus not in (1,2,4,5)",['memberid'=>session('useraccount.id')]);
		        $successcount=Db::query("select count(id) as successcount from think_info where memberid=:memberid and mstatus in (1,4,5)",['memberid'=>session('useraccount.id')]);
	        }
	       
	        
	        foreach ($orderUser as &$v) {          
	            $v=replaceImgurl($v);               
	        }	 
            $this->assign('zcount', $zcount[0]['zcount']);
            $this->assign('count', $count);
            $this->assign('successcount', $successcount[0]['successcount']);
            $this->assign('orderUser', $orderUser);
	        $this->assign('useraccount', $hasUser);
	        $this->assign('groupUser', $groupUser);
	        return $this->fetch('/user/uscenter'); 
	    }

        $this->assign('former_url', '');
       return $this->fetch('/user/index'); 
    }
    
    /*
     * 用户订单
     */
    public function usOrder(){
    	if(session('useraccount.id')){
	        $param=inputself();
	        $key = trim(session('useraccount.id'));
	        $map = [];
	        if(config('fz_selectauth')==0){
	        	$childid=empty(session('child_useraccount.id'))?0:session('child_useraccount.id');
	        	$map['childid'] =$childid; 
	        }
	        $count=0;
	        $Nowpage=1;
	        $allpage=0;
	        $NowIp=getIP();
	        $lists=[];
	        if($key&&$key!==""&&$key!=="undefined"&&$key!=="null"&&!empty($key))
	        {
				$map['memberid'] = $key;        
		       	if(isset($param['mcard'])){
		       		if($param['mcard']!=''){
		       			$map['mcard|morder']=trim($param['mcard']);
		       		}
		       		
		       	}
		       	if(isset($param['mstatus'])){
		       		if($param['mstatus']==999){
                      
		       		}elseif($param['mstatus']==666){
		       			$map['mstatus'] = ['in','0,1,5'];
		       		}elseif($param['mstatus']==888){
		       			$map['mstatus'] = ['not in','1,2,4,5'];
		       		}else{
		       			$map['mstatus'] = trim($param['mstatus']); 
		       		}
		       		
		       	}
		      
		       	$IndexM = new IndexModel();  
		        $Nowpage = input('get.page') ? input('get.page'):1;
		        $limits = config('list_rows');// 获取总条数
		        $count = $IndexM->getAllCount($map); 	
		        $allpage = intval(ceil($count / $limits));   //计算总页面    
		        $lists = $IndexM->getOrderByWhere($map, $Nowpage, $limits);
		      
	       
	        }
	        
	        $mcard="";
	        $mstatus=999;
	       
	        if(isset($param['mcard'])){		       
		       		$mcard=trim($param['mcard']);		       	
		    }
		    if(isset($param['mstatus'])){
       			$mstatus=trim($param['mstatus']);
		    }	
		    $this->assign('mcard', $mcard); 
		    $this->assign('mstatus', $mstatus); 
	        $this->assign('count', $count); 
	        $this->assign('Nowpage', $Nowpage); //当前页
	        $this->assign('allpage', $allpage); //总页数 
	        $this->assign('val', $key);
	        
	       if(input('get.page'))
	        {
	            return json(array_values($lists));
	        }
	        return $this->fetch('/user/usorder'); 

	    }
	    
        $this->assign('former_url', '');
        return $this->fetch('/user/index'); 
    	
    }
    
     /*
     * 用户订单
     */
    public function usjfOrder(){
    	if(session('useraccount.id')){
	        $param=inputself();
	        $key = trim(session('useraccount.id'));
	        $map = [];	        
	        $count=0;
	        $Nowpage=1;
	        $allpage=0;
	        $NowIp=getIP();
	        $lists=[];
	        if($key&&$key!==""&&$key!=="undefined"&&$key!=="null"&&!empty($key))
	        {
				$map['memberid'] = $key;        
		       	if(isset($param['orderno'])){
		       		if($param['orderno']!=''){
		       			$map['orderno']=trim($param['orderno']);
		       		}
		       		
		       	}
		       	if(isset($param['mstatus'])){
		       		if($param['mstatus']==999){
		       			
		       		}elseif($param['mstatus']==888){
		       			$map['mstatus'] = ['not in','1,2,4,5'];
		       		}else{
		       			$map['mstatus'] = trim($param['mstatus']); 
		       		}
		       		
		       	}
		      
		        $integral=new IntegralModel();  
		        $Nowpage = input('get.page') ? input('get.page'):1;
		        $limits = config('list_rows');// 获取总条数
		        $count = $integral->getAllCount($map); 	
		        $allpage = intval(ceil($count / $limits));   //计算总页面    
		        $lists = $integral->getOrderByWhere($map, $Nowpage, $limits);
		      
	       
	        }
	        
	        $mcard="";
	        $mstatus=999;
	       
	        if(isset($param['orderno'])){		       
		       		$mcard=trim($param['orderno']);		       	
		    }
		    if(isset($param['mstatus'])){
       			$mstatus=trim($param['mstatus']);
		    }	
		    $this->assign('orderno', $mcard); 
		    $this->assign('mstatus', $mstatus); 
	        $this->assign('count', $count); 
	        $this->assign('Nowpage', $Nowpage); //当前页
	        $this->assign('allpage', $allpage); //总页数 
	        $this->assign('val', $key);
	        
	       if(input('get.page'))
	        {
	            return json(array_values($lists));
	        }
	        return $this->fetch('/user/usjforder'); 

	    }
	    
        $this->assign('former_url', '');
        return $this->fetch('/user/index'); 
    	
    }
    
    /*
     * 用户充值
     */
    public function usPay()
 	{
		$givelist=Db::query("select * from think_pay_give order by paymoney asc");
    if(is_weixin()){
       $isweixin=1;
     }else{
       $isweixin=0;
    }
		$param['isweixin']=$isweixin;
		return $this->fetch('/user/uspay',['givelist'=>$givelist,'param'=>$param]); 
    }

    /**
     * 卡密兑换余额
     */
    public function redeemKami()
    {
        if (!session('useraccount.id')) {
            return json(['code' => -1, 'msg' => '请先登录']);
        }
        if (!request()->isPost()) {
            return json(['code' => -1, 'msg' => '非法请求']);
        }
        $code = input('post.code', '');
        $result = redeem_recharge_kami(session('useraccount.id'), $code);
        if ($result['code'] == 1) {
            $user = session('useraccount');
            $user['money'] = $result['money'];
            session('useraccount', $user);
        }
        return json($result);
    }
    
    /*
     * 财务明细
     */ 
    public function usPaylog(){
    	if(session('useraccount.id')){
	        $key = trim(session('useraccount.id'));
	        $map = [];
	        $count=0;
	        $Nowpage=1;
	        $allpage=0;
	        $NowIp=getIP();
	        $lists=[];
	        if($key&&$key!==""&&$key!=="undefined"&&$key!=="null"&&!empty($key))
	        {
				$map['memberid'] = $key;        
		       	$UserM = new UserModel();  
		        $Nowpage = input('get.page') ? input('get.page'):1;
		        $limits = config('list_rows');// 获取总条数
		        $count = $UserM->getPaylogAllCount($map); 	
		        $allpage = intval(ceil($count / $limits));   //计算总页面    
		        $lists = $UserM->getPaylogByWhere($map, $Nowpage, $limits);
		        foreach($lists as $k=>$v){
		            $lists[$k]['create_time']=date("Y-m-d H:i:s",$lists[$k]['create_time']);            
		        }
		        
		        $zhichu =Db::query("SELECT sum(money) as zhichu from think_member_money_log where memberid=:memberid and type=1",["memberid"=>session('useraccount.id')]);
				$shouru =Db::query("SELECT sum(money) as shouru from think_member_money_log where memberid=:memberid and type=0",["memberid"=>session('useraccount.id')]);	       
				$money =Db::query("SELECT money  from think_member where id=:memberid",["memberid"=>session('useraccount.id')]);
	        }
	        
	        
	        $this->assign('zhichu', $zhichu[0]['zhichu']); 
	        $this->assign('shouru', $shouru[0]['shouru']);
	        $this->assign('money', $money[0]['money']);  	
	        $this->assign('count', $count); 
	        $this->assign('Nowpage', $Nowpage); //当前页
	        $this->assign('allpage', $allpage); //总页数 
	        $this->assign('val', $key);
	        
	       if(input('get.page'))
	        {
	            return json(array_values($lists));
	        }
	        return $this->fetch('/user/uspaylog'); 

	    }
	    
        $this->assign('former_url', '');
        return $this->fetch('/user/index'); 
    	
    } 
    
     /*
     * 积分明细
     */ 
    public function usIntegrallog(){
    	if(session('useraccount.id')){
	        $key = trim(session('useraccount.id'));
	        $map = [];
	        $count=0;
	        $Nowpage=1;
	        $allpage=0;
	        $NowIp=getIP();
	        $lists=[];
	        if($key&&$key!==""&&$key!=="undefined"&&$key!=="null"&&!empty($key))
	        {
				$map['memberid'] = $key;        
		       	$UserM = new UserModel();  
		        $Nowpage = input('get.page') ? input('get.page'):1;
		        $limits = config('list_rows');// 获取总条数
		        $count = $UserM->getIntegrallogAllCount($map); 	
		        $allpage = intval(ceil($count / $limits));   //计算总页面    
		        $lists = $UserM->getIntegrallogByWhere($map, $Nowpage, $limits);
		        foreach($lists as $k=>$v){
		            $lists[$k]['create_time']=date("Y-m-d H:i:s",$lists[$k]['create_time']);            
		        }
		        
		        $memberintegral =Db::query("SELECT integral FROM think_member where id=:memberid",["memberid"=>session('useraccount.id')]);
					       
	        }
	        
	        
	        $this->assign('memberintegral', $memberintegral[0]['integral']); 	
	        $this->assign('count', $count); 
	        $this->assign('Nowpage', $Nowpage); //当前页
	        $this->assign('allpage', $allpage); //总页数 
	        $this->assign('val', $key);
	        
	       if(input('get.page'))
	        {
	            return json(array_values($lists));
	        }
	        return $this->fetch('/user/usintegrallog'); 

	    }
	    
        $this->assign('former_url', '');
        return $this->fetch('/user/index'); 
    	
    }
    /*
     * 用户修改密码
     */ 
     public function usUpdatePassWord(){
     	if(session('useraccount.id')){	      
	        if(!request()->isPost()){
	        	return $this->fetch('/user/usupdatepass');
	        }
	        $param = input('post.');
	        $hasUser = Db::name('member')->where('id',session('useraccount.id'))->find();
	        $param['password']=md5(md5($param['password']) . config('auth_key'));
	        if($param['password']!= $hasUser['password']){
	            return json(['code' => -2, 'url' => '', 'msg' => '原密码不正确']);
	        }
		    $param['newpassword']=md5(md5($param['newpassword']) . config('auth_key'));
		    $hasUpdateUser = Db::name('member')->where('id',session('useraccount.id'))->update(['password'=>$param['newpassword']]);    
	        if($hasUpdateUser===false){
	        	return json(['code' => -1, 'url' => '', 'msg' => '密码修改失败']);
	        }
	        return json(['code' => 1, 'url' => '', 'msg' => '密码修改成功']);
	    }	    
        $this->assign('former_url', '');
        return $this->fetch('/user/index'); 
     	
     }
    /*
     * 余额支付
     */ 
    public function Paybalance(){
    	if(session('useraccount.id')){
    		$param=inputself();
	        
	    	
	    	//开启事务
	    	Db::startTrans();
	    	try
	    	{
	    		
				//$hasUser =Db::name('member')->where('id', session('useraccount.id'))->find();
				$sql="select * from think_member where id=:id for update";
				$hasUser=Db::query($sql,['id'=>session('useraccount.id')]);
				$ordermcard =Db::name('info')->where('mcard', $param['p2_Order'])->find();
		    	if($hasUser[0]['money']<abs($ordermcard['mamount'])){
		    		// 回滚事务
	    			Db::rollback();	
		    		$errormsg='余额不足';
		      		$this->assign('errormsg', $errormsg);
		     		return $this->fetch('index/error');
		    	}
	    		if($ordermcard==false){
	    			// 回滚事务
	    			Db::rollback();	    			
	    			$errormsg='订单号不存在';
	      			$this->assign('errormsg', $errormsg);
	     			return $this->fetch('index/error'); 
	    		}
	    		//更新用户余额
	    		$sql="update think_member set money=money-:money where id=:id and money>=:money1";
	    		$bool = Db::execute($sql,['money'=>abs($ordermcard['mamount']),'money1'=>abs($ordermcard['mamount']),'id'=>session('useraccount.id')]);
	    		if($bool==false)
	    		{
	    			// 回滚事务
	    			Db::rollback();	    			
	    			$errormsg='更新用户金额失败';
	      			$this->assign('errormsg', $errormsg);
	     			return $this->fetch('index/error');  			                  
	      		}
	      		writemoneylog(session('useraccount.id'),"购买商品订单号".$param['p2_Order'],1,abs($ordermcard['mamount']));
	      		//更新订单状态
	    		$sql="update think_info set mstatus=0,maddtype=5 where mcard=:mcard and mstatus=2";
	    		$bool = Db::execute($sql,['mcard'=>$param['p2_Order']]);
	    		//echo Db::table('think_info')->getLastSql();
	    		
	    		if($bool==false)
	    		{
	    			// 回滚事务
	    			Db::rollback();
	    			$errormsg='更新订单状态失败';
	      			$this->assign('errormsg', $errormsg);
	     			return $this->fetch('index/error');   			                  
	      		}
	      		
	    	}
	    	catch(\Exception $e){
					// 回滚事务
	                Db::rollback();
	                $errormsg=$e->getMessage();
	                $errormsg=str_replace('\'','' ,$errormsg);
	                $errormsg=str_replace('\"','' ,$errormsg);
	                $this->assign('errormsg', $errormsg);
	     			return $this->fetch('index/error');
	    	}
	    	Db::commit();
	    	if(isMobilePc()){
				return $this->redirect(url('@mobile/Getmail/index',['mpid'=>$param['p5_Pid'],'number'=>$param['p2_Order']]));
			}else{
		        return $this->redirect(url('@jingdian/Getmail/index',['mpid'=>$param['p5_Pid'],'number'=>$param['p2_Order']]));
			}
		}else{
			if(isMobilePc()){
				return $this->redirect(url('@mobile/user/index'),302);
			}else{
				return $this->redirect(url('@jingdian/user/index'),302);
			}
			
		}	
    }
    
    
    /*
     * 分销页面
     */ 
    public function usfxcenter(){
    	if(!session('useraccount.id')){
    		$this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}
    	$result=Db::name('amount_total_log')->where('memberid',session('useraccount.id'))->find();
    	
    	$hasUser =Db::name('member')->where('id',session('useraccount.id'))->find();
    	//佣金数量
    	$yjcount=Db::name('tgmoney_log')->where('memberid',session('useraccount.id'))->count();
    	//提现数量
    	$txcount=Db::name('member_tixian')->where('memberid',session('useraccount.id'))->count();
    	//下线数量
    	$map=[];
    	$map['pid1|pid2|pid3']=session('useraccount.id');
    	$xxcount=Db::name('member')->where($map)->count();
    	return $this->fetch('',['host'=>$_SERVER['HTTP_HOST'],
    							'total'=>$result,
    							'hasUser'=>$hasUser,
    							'yjcount'=>$yjcount,
    							'txcount'=>$txcount,
    							'xxcount'=>$xxcount]);
    }
    
 
   
    public function uslowermember(){
    	if(session('useraccount.id')){
    		$count=0;
	    	$Nowpage=1;
	        $allpage=0;
	        $type=input('get.type') ? input('get.type'):1;
	        $Nowpage = input('get.page') ? input('get.page'):1;
		    $limits = input('get.limit') ? input('get.limit'):config('list_rows');// 获取总条数    	
		    
		    if($type==1){
				$result=Db::name('member')->field('account,create_time')->where('pid'.$type,session('useraccount.id'))->page($Nowpage, $limits)->select();  	
		    }elseif($type==2){
		    	$result=Db::query('SELECT a.account,a.create_time,b.account as shangji  from  think_member as a LEFT JOIN think_member b  on a.pid1=b.id   where a.pid2=:pid2',['pid2'=>session('useraccount.id')]);   		
		    }elseif($type==3){
		    	$result=Db::query('SELECT a.account,a.create_time,b.account as shangji  from  think_member as a LEFT JOIN think_member b  on a.pid1=b.id   where a.pid3=:pid2',['pid2'=>session('useraccount.id')]);
		    }
		    $count=Db::name('member')->where('pid'.$type,session('useraccount.id'))->count();
		    
		    foreach($result as $k=>$v){
			    $result[$k]['create_time']=date("Y-m-d H:i:s",$result[$k]['create_time']);            
			}
		    
		    $allpage = intval(ceil($count / $limits));   //计算总页面       	
	        $this->assign('count', $count); 
	        $this->assign('Nowpage', $Nowpage); //当前页
	        $this->assign('allpage', $allpage); //总页数 
	        $this->assign('type', $type);
	        
	       if(input('get.page'))
	        {
	            
	            return json(array_values($result));
	        }
	        $countPid1=Db::name('member')->where('pid1',session('useraccount.id'))->count();
	    	$countPid2=Db::name('member')->where('pid2',session('useraccount.id'))->count();
	    	$countPid3=Db::name('member')->where('pid3',session('useraccount.id'))->count();
	    	return $this->fetch('',['countPid1'=>$countPid1,'countPid2'=>$countPid2,'countPid3'=>$countPid3]); 
 	
    	}
    	$this->assign('former_url', '');
        return $this->fetch('/user/index'); 
        
    	
    }
    
    /*
     * 佣金明细
     */ 
    public function ustgmoneylog(){
    	if(session('useraccount.id')){
	        $key = trim(session('useraccount.id'));
	        $map = [];
	        $count=0;
	        $Nowpage=1;
	        $allpage=0;
	        $NowIp=getIP();
	        $lists=[];
	        if($key&&$key!==""&&$key!=="undefined"&&$key!=="null"&&!empty($key))
	        {
				$map['memberid'] = $key;        
		       	$UserM = new UserModel();  
		        $Nowpage = input('get.page') ? input('get.page'):1;
		        $limits = config('list_rows');// 获取总条数
		        $count = $UserM->getTgmoneylogAllCount($map); 	
		        $allpage = intval(ceil($count / $limits));   //计算总页面    
		        $lists = $UserM->getTgmoneylogByWhere($map, $Nowpage, $limits);
		        foreach($lists as $k=>$v){
		            $lists[$k]['create_time']=date("Y-m-d H:i:s",$lists[$k]['create_time']);            
		        }
		      
		        $tgmoney =Db::query("SELECT sum(money) as tgmoney from think_tgmoney_log where memberid=:memberid",["memberid"=>session('useraccount.id')]);
				
	        }
	        
	        
	        $this->assign('tgmoney', $tgmoney[0]['tgmoney']);  	
	        $this->assign('count', $count); 
	        $this->assign('Nowpage', $Nowpage); //当前页
	        $this->assign('allpage', $allpage); //总页数 
	        $this->assign('val', $key);
	        
	       if(input('get.page'))
	        {
	            return json(array_values($lists));
	        }
	        return $this->fetch(''); 

	    }
	    
        $this->assign('former_url', '');
        return $this->fetch('/user/index'); 
    	
    }
    
     /*
     * 用户提现
     */
    public function tixian(){
    	if(!request()->isPost()){
    		if(session('useraccount.id')){		        
		        return $this->fetch('/user/ustixian'); 
		    }
		    $this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}	
    }
    
     /*
     * 用户提现Do
     */
    public function tixianDo(){
    	if(!request()->isPost()){	        
		        return $this->fetch('/user/ustixian');     		  
    	}
    	if(session('useraccount.id')){
	    	$hasUser =Db::name('member')->where('id', session('useraccount.id'))->find();
	    	if($hasUser['alipayname']=="" || $hasUser['alipayno']==""){
	    		$errormsg="请先设置提现支付宝信息";
		        return json(['code' => 1, 'url' => url('user/usinfo'), 'msg' =>$errormsg]);
	    	}
	    	
	    	$param=inputself();
	    	//提现金额大于0
	    	if($param['money']<=0){
	    		$errormsg="提现金额不能小于0元";
		        return json(['code' => -1, 'url' => url('user/tixian'), 'msg' =>$errormsg]);
	    	}
	    	//单次最低提现金额
	    	if($param['money']<config('fx_txmoney')){
	    		$errormsg="单次最低提现".config('fx_txmoney')."元";
		        return json(['code' => -1, 'url' => url('user/tixian'), 'msg' =>$errormsg]);
	    	}
	    	//单日提现几次
	    	$beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
	    	$where['create_time']=['egt',$beginToday];
	    	$where['memberid']=session('useraccount.id');					
	    	$count=Db::name('member_tixian')->where($where)->count();
	    	if($count>config('fx_txcount')){
	    		$errormsg="单日最多提现".config('fx_txcount')."次";
		        return json(['code' => -1, 'url' => url('user/tixian'), 'msg' =>$errormsg]);
	    	}
	    	//提现金额是否大于佣金金额
	    	if($param['money']>session('useraccount.tg_money')){
	    		$errormsg="佣金金额不足";
		        return json(['code' => -1, 'url' => url('user/tixian'), 'msg' =>$errormsg]);
	    	}
	    	Db::startTrans();
	    	try{
	    		//更新用户金额
	    		
	    		$result=Db::execute('update think_member set tg_money=tg_money-:tixianmoney where id=:id and tg_money>=:tg_money',['tixianmoney'=>$param['money'],'tg_money'=>$param['money'],'id'=>session('useraccount.id')]);  	
	    		if($result==false){
		        	// 回滚事务
		            Db::rollback();
		            $errormsg="提现失败，更新用户余额出错";
		            return json(['code' => -1, 'url' => url('user/tixian'), 'msg' =>$errormsg]);
		        }
		        //记录提现日志		      	
				$orderno=createOrder();
				$data['memberid'] =session('useraccount.id');
				$data['money'] = $param['money'];
				$feemoney=0;
				if(config('fx_sxf')>0){
					if(config('fx_sxftype')==0){
						$feemoney=config('fx_sxf');
					}elseif(config('fx_sxftype')==1){
						$feemoney=$param['money']*config('fx_sxf')/100;
					}
				}
				$data['paymoney'] = $param['money']-$feemoney;
				$data['feemoney'] = $feemoney;
				
				$data['make'] = '';
				$data['status'] = 0;
				$data['userip'] = getIP();
				$data['orderno'] = $orderno;
				$data['create_time'] = time();
				$log = Db::name('member_tixian')->insert($data);
				if($log==false)
				{
					// 回滚事务
					Db::rollback();	    			
					$errormsg='添加提现日志失败';
	            	return json(['code' => -1, 'url' => url('user/tixian'), 'msg' =>$errormsg]);			                  
				}
				$data=[];
				//记录金额日志		      	
				$data['memberid'] = session('useraccount.id');
				$data['money'] = $param['money'];
				$data['make'] = '提现订单:'.$orderno.' 金额：'.$param['money'];
				$data['type'] = 1;
				$data['ip'] = getIP();
				$data['create_time'] = time();
				$log = Db::name('member_money_log')->insert($data);
			
				if($log==false)
				{
					// 回滚事务
				    Db::rollback();	    			
				    $errormsg='更新用户金额日志失败';
				    $code=-1;
	            	return TyReturn($errormsg,$code);			                  
				}
				
	    	}catch(\Exception $e){
	    		// 回滚事务
	          Db::rollback();
	          $errormsg=$e->getMessage();
	          return json(['code' => -1, 'url' => url('user/usTixianlog'), 'msg' =>$errormsg]);
	    	}
	    	Db::commit();
	    	//提现通知
	        
	        $mobile = config('mainmobile');     //手机号
	        $tplCode = config('alimoban_tixian');   //模板ID
	        $paramTixian['name']=session('useraccount.account');
	        $paramTixian['money']=$param['money'];
	        $msgStatus = sendMsg($mobile,$tplCode,$paramTixian);         	
	          
	        return json(['code' => 1, 'url' => url('user/usTixianlog'), 'msg' => '提现成功']);
       }
    		
    	
    	
    }
    
    
    /*
     *基本资料 
     */
     public function usinfo(){
     	if(!session('useraccount.id')){
     		$this->assign('former_url', '');
        	return $this->fetch('/user/index');
     	}
     	if(!request()->isPost()){
     		$hasUser =Db::name('member')->where('id', session('useraccount.id'))->find();
     		$this->assign('hasUser', $hasUser);
     		return $this->fetch(); 
     	}
     	$param=inputself();
     	$sql="update think_member set alipayno=:alipayno,alipayname=:alipayname where id=:id";
	    $bool = Db::execute($sql,['alipayno'=>$param['alipayno'],'alipayname'=>$param['alipayname'],'id'=>session('useraccount.id')]);
     	
     	if($bool===false){
     		 return json(['code' => -1, 'url' => url('user/usinfo'), 'msg' => '修改失败']);
     	}else{
     		 return json(['code' => 1, 'url' => url('user/uscenter'), 'msg' => '修改成功']);
     	}
     }
    
     /*
     * 提现明细
     */ 
    public function usTixianlog(){
    	if(session('useraccount.id')){
	        $key = trim(session('useraccount.id'));
	        $map = [];
	        $count=0;
	        $Nowpage=1;
	        $allpage=0;
	        $NowIp=getIP();
	        $lists=[];
	        if($key&&$key!==""&&$key!=="undefined"&&$key!=="null"&&!empty($key))
	        {
				$map['memberid'] = $key;        
		       	$UserM = new UserModel();  
		        $Nowpage = input('get.page') ? input('get.page'):1;
		        $limits = config('list_rows');// 获取总条数
		        $count = $UserM->getTixianlogAllCount($map); 	
		        $allpage = intval(ceil($count / $limits));   //计算总页面    
		        $lists = $UserM->getTixianlogByWhere($map, $Nowpage, $limits);
		        foreach($lists as $k=>$v){
		            $lists[$k]['create_time']=date("Y-m-d H:i:s",$lists[$k]['create_time']); 
		            $lists[$k]['money']=number_format($lists[$k]['money'],2,".",",");               
		        }
		        
		        $jiesuan =Db::query("SELECT sum(money) as jiesuan from think_member_tixian where memberid=:memberid and status=1",["memberid"=>session('useraccount.id')]);
				$weijiesuan =Db::query("SELECT sum(money) as weijiesuan from think_member_tixian where memberid=:memberid and status=0",["memberid"=>session('useraccount.id')]);	       
				
	        }
	        
	        if($jiesuan[0]['jiesuan']==''){
	        	$jiesuan[0]['jiesuan']=0;
	        }
	        if($weijiesuan[0]['weijiesuan']==''){
	        	$weijiesuan[0]['weijiesuan']=0;
	        }
	        
	        $this->assign('jiesuan', $jiesuan[0]['jiesuan']); 
	        $this->assign('weijiesuan', $weijiesuan[0]['weijiesuan']); 	
	        $this->assign('count', $count); 
	        $this->assign('Nowpage', $Nowpage); //当前页
	        $this->assign('allpage', $allpage); //总页数 
	        $this->assign('val', $key);
	        
	       if(input('get.page'))
	        {
	            return json(array_values($lists));
	        }
	        return $this->fetch('/user/ustixianlog'); 

	    }
	    
        $this->assign('former_url', '');
        return $this->fetch('/user/index'); 
    	
    }
    
    /*
     * 重置密码
     */ 
    public function resetpwd(){
    	if(!request()->isPost()){  		
       		return $this->fetch();     
       	}
    	$param = input('post.');
    	$smscode=$param['smscode'];
        $account=$param['mobile'];
        if (!$smscode) {
        	return json(['code' => -4, 'url' => '', 'msg' => '请输入手机验证码']);
            
        }
        if ($smscode!=session('regcode')) {
           	return json(['code' => -4, 'url' => '', 'msg' => '手机验验证码错误']);
        }
        
        if (!$account) {
        	return json(['code' => -4, 'url' => '', 'msg' => '请输入手机号']);
            
        }
        if ($account!=session('regmobile')) {
           	return json(['code' => -4, 'url' => '', 'msg' => '手机号和验证码不匹配']);
        }    
        $param['password'] = md5(md5($param['password']) . config('auth_key'));
        $result=Db::name('member')->where('mobile',$account)->update(['password'=>$param['password']]);      
        if($result===false){
        	return json(['code' => -1, 'url' => url('mobile/user/index'), 'msg' => '密码修改失败']);
        }else{
        	session('regcode',null);
        	session('regmoblie',null);
        	return json(['code' => 1, 'url' => url('mobile/user/index'), 'msg' => '密码修改成功']);
        }
        
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
    
}
