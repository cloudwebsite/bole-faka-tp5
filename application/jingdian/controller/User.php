<?php
namespace app\jingdian\controller;
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
    
    /**
     * 会员登录中间页：自动提交一次性凭证后进入会员中心
     */
    public function ticketLogin()
    {
        $ticket = trim((string)input('ticket'));
        $this->assign('ticket', $ticket);
        $this->assign('consume_url', url('user/ticketConsume'));
        return $this->fetch('user/ticket_login');
    }

    /**
     * 校验一次性登录凭证并建立会话，然后跳转会员中心
     */
    public function ticketConsume()
    {
        if (!request()->isPost()) {
            $this->error('非法请求', url('user/index'));
        }
        $ticket = trim((string)input('post.ticket'));
        $payload = \com\AdminLoginTicket::consume($ticket);
        if (!$payload) {
            $this->error('登录凭证无效或已使用', url('user/index'));
        }
        $hasUser = Db::name('member')->where('id', (int)$payload['memberid'])->find();
        if (!$hasUser || (int)$hasUser['status'] === 0) {
            $this->error('账户不可用', url('user/index'));
        }
        $host = $_SERVER['HTTP_HOST'];
        $token = md5(md5($hasUser['account'] . $hasUser['password']) . md5(date("Y-m-d")) . config('auth_key') . config('token') . $host);
        Db::name('member')->where('id', $hasUser['id'])->update(['token' => $token]);
        session('useraccount', $hasUser);
        cookie('usertoken', $token);
        return $this->redirect(url('@jingdian/user/uscenter'), 302);
    }

    public function index(){
       
       if(!request()->isPost()){
       	$referer=$_SERVER['HTTP_REFERER'];
       	if(empty($referer)){
       		$referer=input("param.former_url");
       	}
       	if(empty($referer)){
       		$referer=url('user/usCenter');
       	}
       		if(session('useraccount.id')||session('useraccount.account')){
	            return $this->redirect(url('@jingdian/index/index'),302);
	        }
	        $this->assign('former_url', $referer);
       		return $this->fetch();   
       }
       	
       
       	
       	$Postparam=inputself();
       	$account = input("param.account");
       	$password = input("param.password");
		$referer=input("param.former_url");
        
        
        $result = $this->validate(compact('account', 'password'), 'MemberloginValidate');
        if(true !== $result){
            return json(['code' => -5, 'url' => '', 'msg' => $result]);
        }
        $captchaFail = member_captcha_check();
        if ($captchaFail !== null) {
            return json($captchaFail);
        }

        // IP/账号登录频率限制（防撞库、脚本刷登录）
        $loginGuard = \com\MemberLoginGuard::check($account);
        if (!$loginGuard['ok']) {
            $guardMemberId = Db::name('member')->where('account', $account)->value('id');
            writeloginlog($guardMemberId ? $guardMemberId : 0, $loginGuard['msg']);
            return json(['code' => $loginGuard['code'], 'url' => '', 'msg' => $loginGuard['msg']]);
        }
        \com\MemberLoginGuard::hit($account);

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
        //更新用户状态
        $param = [
            'login_num' => $hasUser['login_num'] + 1,
            'last_login_ip' => getIP(),
            'last_login_time' => time(),
            'token' => $token,
            'login_error' => 0
        ];
        
        Db::name('member')->where('id', $hasUser['id'])->update($param);                
       	writeloginlog($hasUser['id'],"登录成功");
        return json(['code' => 1, 'url' => $referer, 'msg' => '登录成功！']);
            
    }
    /*
     * 普通帐号注册
     */
    public function reg(){
       if(config('web_reg_xingshi')==1){
       		//仅手机号
       		return $this->redirect(url('@jingdian/user/regmobile'),302);
       }
       if(!request()->isPost()){     		   		
       		if(session('useraccount.account')){
	            return $this->redirect(url('@jingdian/index/index'),302);
	        }
       		return $this->fetch('/user/reg');     
       }
       	$param = inputself();
        $captchaFail = member_captcha_check();
        if ($captchaFail !== null) {
            return json($captchaFail);
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
        		return json(['code' => 1, 'url' => url('jingdian/index/index'), 'msg' => '注册成功']);
        	}else{
        		return json(['code' => 1, 'url' => url('jingdian/index/index'), 'msg' => '注册成功,请联系客服开通']);
        	}
        	
        	
        		$referer=url('index/index');
        	
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
       		return $this->redirect(url('@jingdian/user/reg'),302);
       }
    	
    	if(!request()->isPost()){  		
       		if(session('useraccount.account')){
	            return $this->redirect(url('@jingdian/index/index'),302);
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
        		'email'=>$param['email'],
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
        		return json(['code' => 1, 'url' => url('jingdian/index/index'), 'msg' => '注册成功']);
        	}else{
        		return json(['code' => 1, 'url' => url('jingdian/index/index'), 'msg' => '注册成功,请联系客服开通']);
        	}
        	
        	
        	$referer=url('index/index');
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
        session('useraccount',null);
        cookie('usertoken',null);
        $this->redirect('@jingdian/index/index');
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
		       			$map['mcard|morder']=$param['mcard'];
		       		}
		       		
		       	}
		       	if(isset($param['mstatus'])){
		       		if($param['mstatus']==999){
		       			
		       		}elseif($param['mstatus']==888){
		       			$map['mstatus'] = ['not in','1,2,4,5'];
		       		}else{
		       			$map['mstatus'] = $param['mstatus']; 
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
		       		$mcard=$param['mcard'];		       		       	
		    }
		    if(isset($param['mstatus'])){
       			$mstatus=$param['mstatus'];
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
     * 用户积分兑换记录
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
		       			$map['orderno']=$param['orderno'];
		       		}
		       		
		       	}
		       	if(isset($param['mstatus'])){
		       		if($param['mstatus']==999){
		       			
		       		}elseif($param['mstatus']==888){
		       			$map['mstatus'] = ['not in','1,2,4,5'];
		       		}else{
		       			$map['mstatus'] = $param['mstatus']; 
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
		       		$mcard=$param['orderno'];		       		       	
		    }
		    if(isset($param['mstatus'])){
       			$mstatus=$param['mstatus'];
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
		
		return $this->fetch('/user/uspay',['givelist'=>$givelist]); 
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
      *分销推广 
      */
    public function usmarket(){
    	return $this->fetch('',['host'=>$_SERVER['HTTP_HOST']]); 
    }
    
    /*
    *我的下级
    */
    public function uslowermember(){
    	if(session('useraccount.id')){
    		$countPid1=Db::name('member')->where('pid1',session('useraccount.id'))->count();
	    	$countPid2=Db::name('member')->where('pid2',session('useraccount.id'))->count();
	    	$countPid3=Db::name('member')->where('pid3',session('useraccount.id'))->count();
	    	return $this->fetch('',['countPid1'=>$countPid1,'countPid2'=>$countPid2,'countPid3'=>$countPid3]); 
    	}
    	$this->assign('former_url', '');
        return $this->fetch('/user/index'); 
    }
    public function uslowermemberDo(){
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
	    	
	    	$data=[
	    	'code'=>0,
	    	'count'=>$count,
	    	'msg'=>'',
	    	'data'=>$result
	    	];
	    	
	    	return json($data);
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
     		 return json(['code' => 1, 'url' => url('user/usinfo'), 'msg' => '修改成功']);
     	}
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
		    		if($ordermcard==false){
		    			// 回滚事务
		    			Db::rollback();	    			
		    			$errormsg='订单号不存在';
		      			$this->assign('errormsg', $errormsg);
		     			return $this->fetch('index/error'); 
		    		}
				// 必须以库内订单金额为准，禁止客户端 mamount；且金额必须 >0
				$payAmount = abs(floatval($ordermcard['mamount']));
				if ($payAmount <= 0) {
					Db::rollback();
					$errormsg='订单金额异常';
					$this->assign('errormsg', $errormsg);
					return $this->fetch('index/error');
				}
		    	if($hasUser[0]['money']<$payAmount){
		    		// 回滚事务
	    			Db::rollback();	
		    		$errormsg='余额不足';
		      		$this->assign('errormsg', $errormsg);
		     		return $this->fetch('index/error');
		    	}
		    		//更新用户余额
		    		$sql="update think_member set money=money-:money where id=:id and money>=:money1";
		    		$bool = Db::execute($sql,['money'=>$payAmount,'money1'=>$payAmount,'id'=>session('useraccount.id')]);
		    		if($bool==false)
		    		{
		    			// 回滚事务
		    			Db::rollback();	    			
		    			$errormsg='更新用户金额失败';
		      			$this->assign('errormsg', $errormsg);
		     			return $this->fetch('index/error');  			                  
		      		}
					
					writemoneylog(session('useraccount.id'),"购买商品订单号".$param['p2_Order'],1,$payAmount);
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
				return $this->redirect(url('@jingdian/user/index'),302);
			}	
	    }
    
    /*
     * 发送注册短信验证码
     */
    public function sendMsgDo(){
    	$param=inputself();       
        $captchaFail = member_captcha_check($param);
        if ($captchaFail !== null) {
            return json($captchaFail);
        }
        $mobile = $param['account'];     //手机号
        $sql="select * from think_member where mobile=:mobile or account=:account";
    	$hasUser = Db::query($sql,['mobile'=>$mobile,'account'=>$mobile]);
        if($hasUser){
        	return json(['code' => -4, 'url' => '', 'msg' => '手机号已经存在']);
        }
        
    	$userip=getIP();
    	
    	$sql="select * from think_sendsms_log where (mobile=:mobile or ip=:ip) and type=0 order by id desc limit 1";
    	$hasSms = Db::query($sql,['mobile'=>$mobile,'ip'=>$userip]);
    
    	if($hasSms){
    		if($hasSms[0]['endtime']<time()){
    			$tplCode = config('alimoban_reg');   //模板ID
		        $data['code']=generate_code();
		        session('regcode',$data['code']);
		        $msgStatus = sendMsg($mobile,$tplCode,$data);
		        if($msgStatus['Code']=="OK"){
		        	$sendData=[
		        	'mobile'=>$mobile,
		        	'code'=>$data['code'],
		        	'type'=>0,
		        	'ip'=>$userip,
		        	'starttime'=>time(),
		        	'endtime'=>time()+60
		        	];
		        	writesendsmslog($sendData);
		        	session('regcode',$data['code']);
		        	session('regmobile',$mobile);
		        }
		        return json(['code' => $msgStatus['Code'], 'msg' => $msgStatus['Message']]);
    		}else{
    			return json(['code' => 'fail', 'msg' => '发送频繁，请稍后再试']);
    		}
    	}else{
    		$tplCode = config('alimoban_reg');   //模板ID
		    $data['code']=generate_code();
		    $msgStatus = sendMsg($mobile,$tplCode,$data);
		     if($msgStatus['Code']=="OK"){
		        	$sendData=[
		        	'mobile'=>$mobile,
		        	'code'=>$data['code'],
		        	'type'=>0,
		        	'ip'=>$userip,
		        	'starttime'=>time(),
		        	'endtime'=>time()+60
		        	];
		        	writesendsmslog($sendData);
		        	session('regcode',$data['code']);
		        	session('regmobile',$mobile);
		        }
		    return json(['code' => $msgStatus['Code'], 'msg' => $msgStatus['Message']]);
    	}
        
        
    }
    
    /*
     * 发送重置密码短信验证码
     */
    public function sendMsgResetPwdDo(){
    	$param=inputself();       
        $captchaFail = member_captcha_check($param);
        if ($captchaFail !== null) {
            return json($captchaFail);
        }
        $mobile = $param['mobile'];     //手机号
        $sql="select * from think_member where mobile=:mobile";
    	$hasUser = Db::query($sql,['mobile'=>$mobile]);
        if(!$hasUser){
        	return json(['code' => -4, 'url' => '', 'msg' => '手机号不存在']);
        }
        
        
        
    	$userip=getIP();
    	
    	$sql="select * from think_sendsms_log where (mobile=:mobile or ip=:ip) and type=1 order by id desc limit 1";
    	$hasSms = Db::query($sql,['mobile'=>$mobile,'ip'=>$userip]);
    	if($hasSms){
    		if($hasSms[0]['endtime']>=time()){
    			return json(['code' => 'fail', 'msg' => '发送频繁，请稍后再试']);
    		}
    	}
    		
    	$tplCode = config('alimoban_resetpwd');   //模板ID
		$data['code']=generate_code();
		$msgStatus = sendMsg($mobile,$tplCode,$data);
		if($msgStatus['Code']=="OK"){
		   	$sendData=[
		   	'mobile'=>$mobile,
			'code'=>$data['code'],
			'type'=>1,
			'ip'=>$userip,
			'starttime'=>time(),
			'endtime'=>time()+60
			];
			writesendsmslog($sendData);
			session('regcode',$data['code']);
			session('regmobile',$mobile);
		}
		return json(['code' => $msgStatus['Code'], 'msg' => $msgStatus['Message']]);
           
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
        	return json(['code' => -1, 'url' => url('jingdian/user/index'), 'msg' => '密码修改失败']);
        }else{
        	session('regcode',null);
        	session('regmoblie',null);
        	return json(['code' => 1, 'url' => url('jingdian/user/index'), 'msg' => '密码修改成功']);
        }
        
    }
    
    
	 /*
     *我的分站
     */
     public function ussubdetail(){
		 if(!session('useraccount.id')){
				$this->assign('former_url', '');
				return $this->fetch('/user/index');
			}
	        $hasUser =Db::name('member')->where('id', session('useraccount.id'))->find();
			$fzAuth=Db::name('fz_auth')->where('memberid',session('useraccount.id'))->find();
			$ztai = "正常";
			if(($fzAuth['starttime'] + $fzAuth['endtime'] * 24 * 60 * 60 - time()) <= 0){
			$ztai = "过期";
			}
			if($hasUser['fzstatus']== 0){
			$ztai = "关闭";
			}
			$daoqi=$fzAuth['starttime'] + $fzAuth['endtime'] * 24 * 60 * 60;
            // 分站管理入口使用一次性登录凭证
            $ticket = \com\AdminLoginTicket::issue($hasUser['id'], ['fzhost' => $hasUser['fzhost']]);
			$famanger='http://'.$hasUser['fzhost'].url('@jingdian/user/ticketLogin',['ticket'=>$ticket]);
     		$this->assign('hasUser', $hasUser);
     		$this->assign('famanger', $famanger);
     		$this->assign('ztai', $ztai);
     		$this->assign('daoqi', $daoqi);
     		$this->assign('fzAuth', $fzAuth);
     		return $this->fetch(); 
	 }
	
	
	
	 /*
     *购买升级分站
     */
     public function ussub(){
     	if(!session('useraccount.id')){
     		$this->assign('former_url', '');
        	return $this->fetch('/user/index');
     	}
     	if(!request()->isPost()){
			$subhosts = explode(',', config('sub_webhost'));
     		$hasUser =Db::name('member')->where('id', session('useraccount.id'))->find();
     		$this->assign('hasUser', $hasUser);
     		$this->assign('subhosts', $subhosts);
     		return $this->fetch(); 
     	}
     	$param=inputself();
		$memberId = intval(session('useraccount.id'));
		// 仅允许套餐 0=年付、1=永久；禁止伪造 subsetmealid 使 config 取空变成 0 元
		$mealId = isset($param['subsetmealid']) ? intval($param['subsetmealid']) : -1;
		if (!in_array($mealId, [0, 1], true)) {
			return json(['code' => -1, 'url' => url('user/ussub'), 'msg' => '套餐无效']);
		}
		$money = floatval(config('sub_'.$mealId));
		if ($money <= 0) {
			writesystemlog(['make'=>'开通分站拒绝：套餐价无效 meal='.$mealId.' money='.$money,'level'=>'0']);
			return json(['code' => -1, 'url' => url('user/ussub'), 'msg' => '套餐价格未配置或无效']);
		}
		$hostprefix = isset($param['hostprefix']) ? trim((string)$param['hostprefix']) : '';
		$merchanthost = isset($param['merchanthost']) ? trim((string)$param['merchanthost']) : '';
		if ($hostprefix === '' || $merchanthost === '') {
			return json(['code' => -1, 'url' => url('user/ussub'), 'msg' => '域名参数不完整']);
		}
		$url = $hostprefix.$merchanthost;
		$fzConfig=Db::name('child_config')->where('value',$url)->find();
		$Mainhost = explode(',', config('main_webhost'));
		if($fzConfig||in_array($url, $Mainhost)){
     		 return json(['code' => -1, 'url' => url('user/ussub'), 'msg' => '域名已被占用，请重新选择']);
		}

		$mealName = ($mealId === 0) ? '年付' : '永久';
		$endtime = ($mealId === 0) ? 365 : 0;

		Db::startTrans();
		try {
			$rows = Db::query('SELECT * FROM think_member WHERE id=:id FOR UPDATE', ['id' => $memberId]);
			if (empty($rows)) {
				Db::rollback();
				return json(['code' => -1, 'url' => url('user/ussub'), 'msg' => '用户不存在']);
			}
			$member = $rows[0];
			$fzAuth = Db::name('fz_auth')->where('memberid', $memberId)->find();
			if ($fzAuth && intval($fzAuth['endtime']) === 0) {
				Db::rollback();
				return json(['code' => -1, 'url' => url('user/ussub'), 'msg' => '你已经是永久分站']);
			}
			if (floatval($member['money']) < $money) {
				Db::rollback();
				return json(['code' => -1, 'url' => url('user/ussub'), 'msg' => '余额不足先充值']);
			}
			$deduct = Db::execute(
				'UPDATE think_member SET money=money-:pay WHERE id=:id AND money>=:need',
				['pay' => $money, 'need' => $money, 'id' => $memberId]
			);
			if ($deduct === false || $deduct < 1) {
				Db::rollback();
				return json(['code' => -1, 'url' => url('user/ussub'), 'msg' => '扣款失败']);
			}
			writemoneylog($memberId, '开通/升级分站'.$mealName, 1, $money);

			$map = ['name' => 'web_host', 'memberid' => $memberId];
			$exist = Db::name('child_config')->where($map)->find();
			if (!$exist) {
				Db::name('child_config')->insert($map);
			}
			Db::name('child_config')->where($map)->setField('value', $url);
			$bool = Db::name('member')->where('id', $memberId)->update(['fzhost' => $url, 'fzstatus' => '1']);
			if ($bool === false) {
				Db::rollback();
				return json(['code' => -1, 'url' => url('user/ussub'), 'msg' => '更新分站信息失败']);
			}

			if ($fzAuth) {
				if ($endtime == 365) {
					$result = Db::execute(
						'UPDATE think_fz_auth SET endtime=endtime+:endtime WHERE memberid=:memberid',
						['endtime' => $endtime, 'memberid' => $memberId]
					);
				} else {
					$result = Db::name('fz_auth')->where('memberid', $memberId)->update(['endtime' => $endtime]);
				}
			} else {
				Db::name('child_article')->insert(['memberid'=>$memberId,'title'=>'我是首页公告标题，可以修改','cate_id'=>1,'content'=>'我是首页公告内容，可以修改']);
				Db::name('child_article')->insert(['memberid'=>$memberId,'title'=>'我是免责声明，可以修改','cate_id'=>3,'content'=>'我是免责声明，可以修改']);
				$result = Db::name('fz_auth')->insert([
					'memberid' => $memberId,
					'starttime' => time(),
					'endtime' => $endtime,
					'goodsname' => 1,
					'goodsimgurl' => 1,
					'goodsxqnotice' => 1,
					'goodsprice' => 1,
					'ordersauth' => 0,
				]);
			}
			if ($result === false) {
				Db::rollback();
				return json(['code' => -1, 'url' => url('user/ussub'), 'msg' => '购买失败']);
			}
			Db::commit();
			return json(['code' => 1, 'url' => url('user/ussub'), 'msg' => '购买成功']);
		} catch (\Exception $e) {
			Db::rollback();
			return json(['code' => -1, 'url' => url('user/ussub'), 'msg' => '购买失败']);
		}
     }
	
    /*
     *购买升级用户组
     */
     public function uslevel(){
     	if(!session('useraccount.id')){
     		$this->assign('former_url', '');
        	return $this->fetch('/user/index');
     	}
     	if(!request()->isPost()){
     		$hasUser =Db::name('member')->where('id', session('useraccount.id'))->find();
			$groupUser =Db::name('member_group')->where('id', $hasUser['group_id'])->find();
			$groupUsers =Db::name('member_group')->where('is_default',0)->where('status',1)->where('price','>','0')->order('sort desc')->select();//sort
	        if($groupUser==false){
	        	$groupUser['group_name']="注册会员";
	        }
     		$this->assign('hasUser', $hasUser);
     		$this->assign('groupUser', $groupUser);
     		$this->assign('groupUsers', $groupUsers);
     		return $this->fetch(); 
     	}
     	$param=inputself();
		$memberId = intval(session('useraccount.id'));
		$groupId = isset($param['groupid']) ? intval($param['groupid']) : 0;
		if ($groupId < 1) {
			return json(['code' => -1, 'url' => url('user/uslevel'), 'msg' => '用户组无效']);
		}
		// 与列表一致：启用、非默认、价格>0；禁止伪造 groupid 走 0 元组或失效组
		$group = Db::name('member_group')
			->where('id', $groupId)
			->where('is_default', 0)
			->where('status', 1)
			->where('price', '>', 0)
			->find();
		if (!$group) {
			writesystemlog(['make'=>'升级用户组拒绝：非法groupid='.$groupId,'level'=>'0']);
			return json(['code' => -1, 'url' => url('user/uslevel'), 'msg' => '用户组不存在或不可购买']);
		}
		$price = floatval($group['price']);
		if ($price <= 0) {
			return json(['code' => -1, 'url' => url('user/uslevel'), 'msg' => '用户组价格无效']);
		}

		Db::startTrans();
		try {
			$rows = Db::query('SELECT * FROM think_member WHERE id=:id FOR UPDATE', ['id' => $memberId]);
			if (empty($rows)) {
				Db::rollback();
				return json(['code' => -1, 'url' => url('user/uslevel'), 'msg' => '用户不存在']);
			}
			$member = $rows[0];
			if (intval($member['group_id']) === $groupId) {
				Db::rollback();
				return json(['code' => -1, 'url' => url('user/uslevel'), 'msg' => '不能和原有用户组相同']);
			}
			if (floatval($member['money']) < $price) {
				Db::rollback();
				return json(['code' => -1, 'url' => url('user/uslevel'), 'msg' => '余额不足先充值']);
			}
			$deduct = Db::execute(
				'UPDATE think_member SET money=money-:pay WHERE id=:id AND money>=:need',
				['pay' => $price, 'need' => $price, 'id' => $memberId]
			);
			if ($deduct === false || $deduct < 1) {
				Db::rollback();
				return json(['code' => -1, 'url' => url('user/uslevel'), 'msg' => '扣款失败']);
			}
			writemoneylog($memberId, '升级用户组'.$group['group_name'], 1, $price);
			$bool = Db::name('member')->where('id', $memberId)->update(['group_id' => $groupId]);
			if ($bool === false) {
				Db::rollback();
				return json(['code' => -1, 'url' => url('user/uslevel'), 'msg' => '升级失败']);
			}
			Db::commit();
			return json(['code' => 1, 'url' => url('user/uslevel'), 'msg' => '升级成功']);
		} catch (\Exception $e) {
			Db::rollback();
			return json(['code' => -1, 'url' => url('user/uslevel'), 'msg' => '升级失败']);
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
