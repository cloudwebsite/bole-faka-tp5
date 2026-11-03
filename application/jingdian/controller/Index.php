<?php
namespace app\jingdian\controller;
use app\jingdian\model\IndexModel;
use app\jingdian\model\GoodsListModel;
use think\Config;
use think\Loader;
use think\Db;
use com\GetmailAuth;
use com\IpLocationqq;
use com\AccessEncryption;

class Index extends Base
{
 	protected $autoWriteTimestamp = true;   // 开启自动写入时间戳
   
   
  public function index(){
		if(config('accessencryption')==1){
			$visitors = new AccessEncryption(); // 实例化访客密码访问
			$visitors->BLEncrypt(config('access_pwd'));
		}
        //初始化
        $param=self::init();
		//判断手机还是电脑
		if($param['isMobilePc']==1){
			$m_status=config('m_status');
			//判断是否直接进入手机版
			if($m_status==1){
				return $this->redirect(config('is_https').'://'.$_SERVER['HTTP_HOST'].'/mobile',302);	    		
			}
		}
    	if(config('moban_index')==2){//917模版
    		if(config('web_reg_type')==1){
		    	//强制注册
		    	if(!session('useraccount')){    		               
			        if(isMobilePc()){
			        	return $this->redirect(url('@mobile/user/index'),302); 		        	
			        }else{
			        	 return $this->redirect(url('@jingdian/user/index'),302); 
			        }		                      
			    }		    
		    }
		    //会员信息
			if(session('useraccount.id')){	
		        $hasUser =Db::name('member')->where('id', session('useraccount.id'))->find();
		        $param['useraccount']=$hasUser;	        
		   	}  
    	}
        
        
        //初始化商品类
        $GoodList=new GoodsListModel();
        //获取商品列表
        $data_fl=$GoodList->getGoods();
        //获取商品分类
        $data_flAll=$GoodList->getAllGoods($data_fl);
        //获取所有优惠信息
        $data_yh=$GoodList->shopAllyh();
        //获取所有附加选项信息
        $data_attach=$GoodList->shopAllattach();
        
        
        $IndexModel=new IndexModel();
        //公告信息
        $GongGao=$IndexModel->getGongGao();
        //免责声明
        $MianZei=$IndexModel->getMianZei();
        //常见问题
        $ChangJian=$IndexModel->getChangJian();
        //历史订单信息
        $LiShi=$IndexModel->getLiShi();
		
		//商品列表详细信息
        $this->assign('GoodsList', $data_fl); 
		//商品列表详细信息ALL
        $this->assign('GoodsListAll', $data_flAll); 
        //所有优惠信息
        $this->assign('shopAllyh', $data_yh);
        //所有附加选项信息
        $this->assign('shopAllattach', $data_attach);
        //公告信息
        $this->assign('GongGao', $GongGao);
        //免责声明
        $this->assign('MianZei', $MianZei);
        //常见问题
        $this->assign('ChangJian', $ChangJian);
        //历史订单信息
		$this->assign('LiShi', $LiShi);
		
		$this->assign('param', $param);
     

        if(config('moban_index')==1){//经典模版
		        return $this->fetch();
		    }else if(config('moban_index')==2){//917模版
		        return $this->fetch('/917/index');
		    }else if(config('moban_index')==3){//简单模版
		        return $this->fetch('/hengtiao/index');
	    	}else if(config('moban_index')==4){
	    		return $this->fetch('/imgpc/index');
	    	}else if(config('moban_index')==5){
	    		return $this->fetch('/imgpc2/index');
	    	}else if(config('moban_index')==6){//优云宝
	    		return $this->fetch('/youyunbao/index');
	    	}
        
        
        
    }


   	/**
     *首页初始化
   	*/
   	public function init(){
   		$tokenid=cookie("tokenid");
   		
   		$count=0;
   		$param=[];
   		$device=input('param.device');
   		if($tokenid&&$tokenid!==""&&$tokenid!=="undefined"&&$tokenid!=="null"&&!empty($tokenid))
        {
	   		if(config('select_cookie')==1){
	   			$map['cookie'] = $tokenid;
		   		$IndexM = new IndexModel();        
		        $count = $IndexM->getCookieByWhere($map);	
	   		}
	   				
        }else if(strlen($tokenid)!=64){
        	cookie("tokenid",null);   	
        }
        if(isMobilePc() && $device!='pc' && config('m_status')!=2)
		{
        	$isMobilePc=1;     	
        }else{
        	$isMobilePc=0;
        }
        if(is_weixin()){
        	$isweixin=1;
        }else{
        	$isweixin=0;
        }
        $param['count']=$count;
        $param['isweixin']=$isweixin;
        $param['isMobilePc']=$isMobilePc;
        return $param;  
   	
   	}
  
   	public function zywlwy(){
	    $param=inputself();
		 //初始化商品类
        $GoodList=new GoodsListModel();
        //获取指定商品优惠信息
        $data_yh=$GoodList->shopyh($param);    
		$param['data_yh']=$data_yh;
		if(isset($param['lianxi'])&&isset($param['email'])&&isset($param['buynum'])){
			$param['pa_MP']=$param['lianxi'].'|'.$param['email'].'|'.$param['buynum'];
		}
       $shoptype=$param['data_yh']['data_fl'][0]['type'];
       $mpid=$param['data_yh']['data_fl'][0]['id'];
       $data_attach=[];
       if($shoptype==1){
       		$data_attach=$GoodList->getShopattachByShopId($mpid);
       		
       		$this->assign('attach', $data_attach);
       }
       $this->assign('attach', $data_attach);
	 	$this->assign('param', $param);
	 	 if(config('moban_index')==1){//经典模版
	        	return $this->fetch('zywlwy');
	        }else if(config('moban_index')==2){//917模版
	        	return $this->fetch('/917/order');
	        }else if(config('moban_index')==3){//简单模版
	        	return $this->fetch('/hengtiao/order');
	        }
 	}
	
	 
	 public function dc()
	 {
		off_spider();
		$order=input('param.order');
		$uf=kami_order_txt_path($order);
		$ufx='order_'.$order.'.txt';
		if ($uf === '' || !is_file($uf)) {
			return '文件已删除';
		}
		clearstatcache(true, $uf);
		$contents=file_get_contents($uf);
		if((preg_match('/[0-9]{8}+\//',$contents) !='0'&&preg_match('/\.jpg|\.png|\.gif$/is', $contents)!='0')&&strpos($contents,'http')===false){					
        	return $this->redirect(url('@jingdian/Downimg/index',['order'=>$order]),302);
		}
		if(file_exists($uf)){			
			$tou=getSubstr($contents,"<pretou>","</pretou>");
			$wei=getSubstr($contents,"<prewei>","</prewei>");
			$zhengwen=str_replace($tou,'',$contents);
			$zhengwen=str_replace($wei,'',$zhengwen);
			$zhengwen=str_replace("<pretou>",'',$zhengwen);
			$zhengwen=str_replace("</pretou>",'',$zhengwen);
			$zhengwen=str_replace("<prewei>",'',$zhengwen);
			$zhengwen=str_replace("</prewei>",'',$zhengwen);
			//$zhengwen=$zhengwen."\r";
			$arrayzw = explode("||||||",str_replace(array("\r\n", "\r", "\n"),'||||||',$zhengwen));
			$array=array_filter($arrayzw);
			   foreach($array as $value){
					$zhanshitext .= $value;
					$zhanshitext .= "\r\n";
				 }
			header("Cache-Control: max-age=0");
			header("Content-Description: File Transfer");
			header("Content-Transfer-Encoding: binary"); // 告诉浏览器，这是二进制文件
			header("Content-Type:application/force-download");
			header("Content-Disposition:attachment;filename=".basename($ufx));
			return trim($zhanshitext, "\r");
		}else{
			return '文件已删除';
		}
		

	 }
	 
	 
	public function fenxiang(){
		off_spider();
		$order=input('param.order');
		$contents="";
		$uf=kami_order_txt_path($order);
		clearstatcache(true, $uf);
		if($uf !== '' && is_file($uf)){
		  $contents=file_get_contents($uf);
		}else{
			$contents= '文件已删除';
		}
		$checkBom = ($uf !== '' && is_file($uf)) ? checkBOM($uf) : false;
		if ($checkBom) 
		{
			$contents=substr($contents,3);
		}
		$tou=getSubstr($contents,"<pretou>","</pretou>");
		$wei=getSubstr($contents,"<prewei>","</prewei>");
		$zhengwen=str_replace($tou,'',$contents);
		$zhengwen=str_replace($wei,'',$zhengwen);
		$zhengwen=str_replace("<pretou>",'',$zhengwen);
		$zhengwen=str_replace("</pretou>",'',$zhengwen);
		$zhengwen=str_replace("<prewei>",'',$zhengwen);
		$zhengwen=str_replace("</prewei>",'',$zhengwen);
		$zhengwen=htmlspecialchars($zhengwen,ENT_QUOTES,"UTF-8");
		if((preg_match('/[0-9]{8}+\//',$zhengwen) !='0'&&preg_match('/\.jpg|\.png|\.gif$/is', $zhengwen)!='0')&&strpos($zhengwen,'http')===false){	
				$zhengwen = explode("\r",$zhengwen);
				array_pop($zhengwen);
			   foreach($zhengwen as $value){
					$zhanshitext .= '<img src="/uploads/images/'.$value.'" style="width:50%;float: left;"></br>';
				 }
				 $jpg=1;
		}else{			
			$zhanshitext=$tou."<hehe id='zhengwen'>".$zhengwen."</hehe>".$wei;
			$zhanshitext=str_replace("\r","<br>",$zhanshitext);	
			 $jpg=0;		
		}
		$len= mb_strlen($zhengwen,'utf8');
		if($len>3000){
			$zhanshitext=$tou."<hehe id='zhengwen'>卡密字数过大请直接下载</hehe>".$wei;
		}
		
			
		$this->assign('myfile', $zhanshitext);
		$this->assign('jpg', $jpg);
		$this->assign('zhengwen', $zhengwen);
		$this->assign('order', $order);
	  return $this->fetch('cardlist');
	 }
		 
 

    
  

    public function getBuyCount(){
    		$jine = trim(input('param.money'));
            $mpid= trim(input('param.mpid'));
            $memberid=trim(input('param.memberid'));
            $data_mail = Db::query("SELECT COUNT(mis_use) as count from think_mail where mis_use=0 and mpid=:mpid",['mpid'=>$mpid]);
          	$data_fl = Db::query("select * from think_fl where id=:mpid",['mpid'=>$mpid]);
          	$data_yh = Db::query("SELECT * from think_yh where mpid=:mpid ORDER BY mdy desc",['mpid'=>$mpid]);
            //计算单价
            if(!empty($data_yh)){
                        foreach ($data_yh as $v) {
                            if($v['mdy'] <= $jine){
                                $mdj = $v['mdj'];
                                break;
                            }
                        }
                   }
            $sfyh=1;       
            if(!isset($mdj)){
                       $mdj = $data_fl[0]['mprice'];
                       $sfyh=0;
                    }
            //计算单价
           
             //计算购买数量
            $canbuynum = floor($jine*100/$mdj);
            //库存数量
            if($data_fl[0]['type']==1){
            	$allnum=mt_rand(100,999); 
            }else{
            	$allnum=$data_mail[0]['count']; 
            }
            
         
    		if ($allnum-$canbuynum < 0) {
            	$code=-1;
                $msg = '库存不足,总库存：'.$allnum." 需要数量：".$canbuynum;
            } elseif ($canbuynum == 0) {
            	$code=-1;
                $msg = "金额不足！";
            } elseif ($canbuynum < $data_fl[0]['mmin'] || $canbuynum > $data_fl[0]['mmax']) {
            	$code=-1;
                $msg = '最小提取数量：' . $data_fl[0]['mmin'] . '&nbsp;&nbsp;最大提取数量：' . $data_fl[0]['mmax'];
            }else{
            	  $code=1;
                $msg = 'OK'; 
            }
           
          return json(['code'=>$code,'msg'=>$msg,'count'=>$canbuynum,'mdj'=>$mdj]);    
    }
    
    
    
    
     public function getBuyMoney(){
    		$buynum = trim(input('param.buynum'));
            $mpid= trim(input('param.mpid'));
            $GoodList=new GoodsListModel();
    		$param=$GoodList->getBuyMoneyBybuynum($buynum,$mpid); 
    		return json($param);
    }
    
    //根据用户名和商品ID获取商品价格
    public function getPriceType(){
    	
    	$typeprice=4;//批发价格
			if(session('useraccount.id') && session('useraccount.account')){//判断是否会员登录
           		$mpid=trim(input('param.mpid'));
           		//获取会员私密价格
           		$memberprice=Db::query("select * from think_member_price where memberid=:memberid and goodid=:goodid",['memberid'=>session('useraccount.id'),'goodid'=>$mpid]);
           		if(empty($memberprice)){
           			//获取会员分组
           			$member=Db::query("select * from think_member where id=:memberid",['memberid'=>session('useraccount.id')]);	           		
	           		//获取会员分组价格
	           		if(!empty($member)){
	           			$membergroupprice=Db::query("select * from think_member_group_price where membergroupid=:membergroupid and goodid=:goodid",['membergroupid'=>$member[0]['group_id'],'goodid'=>$mpid]);
	           			if(!empty($membergroupprice)){
	           				$mdj=$membergroupprice[0]['price'];
	           				$typeprice=2;//分组价格
	           			}else{
	           				//获取分组折扣
	           				$membergroupdiscount=Db::query("select * from think_member_group where id=:membergroupid",['membergroupid'=>$member[0]['group_id']]);
	           				if(!empty($membergroupdiscount)){
	           					$memberdiscount=$membergroupdiscount[0]['discount'];
	           					$typeprice=3;//分组折扣	           					         					           					
	           				}
	           			}	
	           		}
	           			           		
           		}else{
           			$mdj=$memberprice[0]['price'];
           			$typeprice=1;//会员私密价格
           		}
     		
           	}
           	$param['usertypeprice']=$typeprice;
           	$param['userprice']=99999;
           	$param['discount']=0;
           	if(isset($mdj)){
           		//存在私密价格
           		$param['userprice']=$mdj;
           	}else if(isset($memberdiscount)){
           		//存在分组折扣
           		$param['discount']=$memberdiscount;
           	}
           	return json(['code'=>1,'param'=>$param]);
    }
    
    
     
    public function shopxq()
 	{
		$data=inputself();
		$GoodList=new GoodsListModel();
     $param=$GoodList->shopyh($data);	
		$this->assign('param', $param); //当前页
    return $this->fetch('shopxq');      
    }
     
    
    /*
    2018年3月13日 17:15:50 
    */
     
    public function selectorder()
 	{
		$key = trim(input('key'));
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
   		   
        }else{
        	$key=cookie('tokenid');
        	if(strlen($key)!=64){
        		
        		$key='';
        		cookie('tokenid',null);
        	}
        }
        
        
        if($key&&$key!==""&&$key!=="undefined"&&$key!=="null"&&!empty($key))
        {
			$callkey='';
			if(config('select_mcard')==1){
				$callkey.='mcard|morder|';
			}
			if(config('select_mobile')==1){
				$callkey.='lianxi|';
			}
			if(config('select_cookie')==1){
				$callkey.='cookie';
			}
			if($callkey==''){
				 $this->assign('errormsg', '管理员没开启自助查询');
	 			 return $this->fetch('index/error');
			}
			if(substr($callkey, -1)=='|'){
				$callkey=substr($callkey,0,strlen($callkey)-1);
			}
			
			
			$map[$callkey] = $key;        
	       	$IndexM = new IndexModel();  
	        $Nowpage = input('get.page') ? input('get.page'):1;
	        $limits = config('list_rows');// 获取总条数
	        $count = $IndexM->getAllCount($map);
	        if($count<=0){
	        	$uf=kami_order_txt_path($key);
		    	if ($uf !== '' && is_file($uf)) {
		        	return $this->redirect(url('@jingdian/Index/fenxiang',['order'=>$key]),302);
		        }
	        }
	        	
	        $allpage = intval(ceil($count / $limits));   //计算总页面    
	        $lists = $IndexM->getOrderByWhere($map, $Nowpage, $limits);
	        $Ip = new IpLocationqq('qqwry.dat'); // 实例化类 参数表示IP地址库文件
	        foreach($lists as $k=>$v){
	            $userip=$lists[$k]['userip'];
	            
	            if(!empty($userip)){
	            	$lists[$k]['ipaddr'] = $Ip->getlocation($userip);
	            }else{
	            	$lists[$k]['ipaddr']=['country'=>'未知','area'=>'地区'];    	
	            }
	            //判断是否为手机查询
	            if(strlen($key)==11){
	            	$UserCountry=$Ip->getlocation($userip);            	
	            	$NowCountry=$Ip->getlocation($NowIp);
	            	if($NowCountry['country']!==$UserCountry['country']){
	            		unset($lists[$k]);
	            		//array_splice($lists,$k,1);
	            		$count =$count-1;
	        			  $allpage = intval(ceil($count / $limits));   //计算总页面
	
	            		
	            		
	            	}	            	
	            }    
	            
	        }
            // 联系方式或浏览记录命中可取卡；仅订单号命中需再填联系方式
            $lists = array_values($lists);
            GetmailAuth::afterSelectOrder($lists, $key);
	       
        }
        
        if(strlen($key)==64){
        	$key="";
        }
        	
        $this->assign('count', $count); 
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign('val', $key);
        
       if(input('get.page'))
        {
            return json(array_values($lists));
        }
        
        
          if(config('moban_index')==1){//经典模版
		       return $this->fetch('select');
		    }else if(config('moban_index')==2){//917模版
		       return $this->fetch('select');
		    }else if(config('moban_index')==3){//简单模版
		       return $this->fetch('select');
	    	}else if(config('moban_index')==4){//简易商城
	    		return $this->fetch('/imgpc/myorder');
	    	}else if(config('moban_index')==5||config('moban_index')==6){//imgpc2 优云宝
	    		return $this->fetch('/imgpc2/myorder');
	    	}
    }

    /**
     * 订单号查单后补充校验下单联系方式，通过后允许取卡
     */
    public function confirmGetmail()
    {
        if (!request()->isPost()) {
            return json(['code' => -1, 'msg' => '非法请求']);
        }
        $number = trim((string)input('number'));
        $lianxi = trim((string)input('lianxi'));
        $r = GetmailAuth::confirmByLianxi($number, $lianxi);
        if (!$r['ok']) {
            return json(['code' => -1, 'msg' => $r['msg']]);
        }
        return json([
            'code' => 1,
            'msg' => '验证成功',
            'data' => ['mpid' => $r['mpid'], 'number' => $r['mcard']],
        ]);
    }
     
     
     
    
    
    
    
    public function zywlpay()
 	{
			if(config('web_reg_type')==1){
		    	//强制注册
		    	if(!session('useraccount')){    		               
			        if(isMobilePc()){
			        	return $this->redirect(url('@mobile/user/index'),302); 		        	
			        }else{
			        	 return $this->redirect(url('@jingdian/user/index'),302); 
			        }		                      
			    }		    
		    }
			if (!request()->isPost()){
				return $this->redirect(url('@jingdian/index/index'),302);
			}
			$param=inputself();		
			$mpid=isset($param['mpid'])?$param['mpid']:$param['p5_Pid'];
			$param['isyh']=0;
			$data_yh=[];
			$typeprice=4;//批发价格
			$param['user']['usertypeprice']=$typeprice;
			$param['user']['userprice']=99999;
			$param['user']['discount']=0;
			$param['data_yh']=$data_yh;
			if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){
			 	$data_child_fl=db('child_fl')->where(['goodid'=>trim($mpid),'memberid'=>session('child_useraccount.id')])->find();
			 	if($data_child_fl){
			 		if($data_child_fl['mprice']>0){
			 			$child_fl_mpirce=$data_child_fl['mprice'];
			 		}
			 	}
			}
			
			
				$data_yh=db('yh')->where('mpid',trim($mpid))->order('mdy asc')->select();
				$param['data_yh']=$data_yh;
	            if(!empty($data_yh))
	            {
	            	$param['isyh']=1;
	            }
	           
	            $typeprice=4;//批发价格
				if(session('useraccount.id') && session('useraccount.account')){//判断是否会员登录
	           		//获取会员私密价格
	           		$memberprice=Db::query("select * from think_member_price where memberid=:memberid and goodid=:goodid",['memberid'=>session('useraccount.id'),'goodid'=>$mpid]);
	           		if(empty($memberprice)){
	           			//获取会员分组
	           			$member=Db::query("select * from think_member where id=:memberid",['memberid'=>session('useraccount.id')]);	           		
		           		//获取会员分组价格
		           		if(!empty($member)){
		           			$membergroupprice=Db::query("select * from think_member_group_price where membergroupid=:membergroupid and goodid=:goodid",['membergroupid'=>$member[0]['group_id'],'goodid'=>$mpid]);
		           			if(!empty($membergroupprice)){
		           				$mdj=$membergroupprice[0]['price'];
		           				$typeprice=2;//分组价格
		           			}else{
		           				//获取分组折扣
		           				$membergroupdiscount=Db::query("select * from think_member_group where id=:membergroupid",['membergroupid'=>$member[0]['group_id']]);
		           				if(!empty($membergroupdiscount)){
		           					$memberdiscount=$membergroupdiscount[0]['discount'];
		           					$typeprice=3;//分组折扣	           					         					           					
		           				}
		           			}	
		           		}
		           			           		
	           		}else{
	           			$mdj=$memberprice[0]['price'];
	           			$typeprice=1;//会员私密价格
	           		}
	     		
	           	}
	           	$param['user']['usertypeprice']=$typeprice;
	           	$param['user']['userprice']=99999;
	           	$param['user']['discount']=0;
	           	if(isset($mdj)){
	           		//存在私密价格
	           		$param['user']['userprice']=$mdj;
	           	}else if(isset($memberdiscount)){
	           		//存在分组折扣
	           		$param['user']['discount']=$memberdiscount;
	           	}
			
			$sql="SELECT * FROM think_attach where attachgroupid in(SELECT attachgroupid from think_fl where id=:id)";
			$result = Db::query($sql,['id'=>trim(input('param.mpid'))]);
			
			if(count($result)>0){
				$param['isattach']=1;
			}else{
				$param['isattach']=0;
			}
			//会员信息
			if(session('useraccount.id')){			
		        $hasUser =Db::name('member')->where('id', session('useraccount.id'))->find();
		        $param['useraccount']=$hasUser;	        
		   	}  
			
			$this->assign('attach', $result);                  
            $this->assign('param', $param);
            return $this->fetch('zywlpayorder');
            
       
    }
    
    
    /*
     * imgpc商品详情
     */
	public function goodsdetail(){	
		
		
		
		$data=inputself();
	
		$data['p5_Pid']=$data['mpid'];
		//判断是不是手机
		if(isMobilePc()){
			return $this->redirect(url("mobile/Index/goodsdetail",['mpid'=>$data['p5_Pid']]),302);
		}
		
		$GoodList=new GoodsListModel();
		//获取指定商品详情
        $param2=$GoodList->shopyh($data);
       
        if($param2['code']!=1){
        	$this->assign('errormsg', $param2['msg']);
	 		return $this->fetch('index/error');
        }
        
        $param2['p5_Pid']=$param2['mpid'];
        //获取指定商品库存信息
        $goodscount=$GoodList->goodscount($data);
        $lmid=$param2['data_fl'][0]['mlm'];
        //初始化商品类
	    $GoodList=new GoodsListModel();
	    $data_fl=$GoodList->getShopBylmid($lmid);
	    
	    $data_flName=$GoodList->getAllGoodsName();
	    $data_lmshop=$GoodList->getAllGoods($data_flName);
	    
	    
	    
	    $IndexModel=new IndexModel();
	    //免责声明
        $MianZei=$IndexModel->getMianZei();
	    //免责声明
      
        $this->assign('MianZei', $MianZei);
        $this->assign('GoodsListAll', $data_fl);
        $this->assign('GoodsLmShop', $data_lmshop);      
      	$this->assign('param', $param2); 
      	$this->assign('goodscount', $goodscount); 
		if(config('moban_index')==4){//imgpc
		    return $this->fetch('imgpc/goods');
		}else if(config('moban_index')==5){//imgpc2
		    return $this->fetch('imgpc2/goods');
		}else if(config('moban_index')==6){//优云宝
		    return $this->fetch('youyunbao/goods');
		}
	}
	
	
	 /*
     * imgpc商品分类
     */
	public function goodscategory(){
		$lmid=trim(input('param.lmid'));
		//初始化商品类
	    $GoodList=new GoodsListModel();
	    $data_fl=$GoodList->getGoods();
	        //获取商品分类
	    $data_flAll=$GoodList->getAllGoodsByid($data_fl,$lmid);

	    //商品列表详细信息ALL
	    $this->assign('GoodsListAll', $data_flAll); 		
		if(config('moban_index')==4){//imgpc
		    return $this->fetch('imgpc/category');
		}else if(config('moban_index')==5){//imgpc2
		    return $this->fetch('imgpc2/category');
		}else if(config('moban_index')==6){// 优云宝
		    return $this->fetch('youyunbao/category');
		}
	}
	
	
	 /*
     * imgpc模糊查找商品
     */
	public function goodscategoryByName(){
	   $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['mname|mnamebie'] = ['like',"%" . $key . "%"];          
        }      
        //初始化商品类
	    $GoodList=new GoodsListModel();
	    $data_fl=$GoodList->getGoodsByName($map); 

	    foreach($data_fl as &$v){
	    	$v['name']='';
	    }
	     

        $this->assign('GoodsListAll', $data_fl);
	     $this->assign('val', $key); 
      
      	if(config('moban_index')==4){//imgpc
		    return $this->fetch('imgpc/categorybykey');
		}else if(config('moban_index')==5||config('moban_index')==6){//imgpc2 优云宝
		    return $this->fetch('imgpc2/categorybykey');
		}
	}
	
	
		 /*
     * hengtiao根据商品名称模糊查找商品
     */
	public function hengGoodByName(){
        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['mname|mnamebie'] = ['like',"%" . $key . "%"];          
        }   
        //初始化
        $param=self::init();
		//判断手机还是电脑
		if($param['isMobilePc']==1){
			$m_status=config('m_status');
			//判断是否直接进入手机版
			if($m_status==1){
				return $this->redirect(config('is_https').'://'.$_SERVER['HTTP_HOST'].'/mobile',302);	    		
			}
		}
        //初始化商品类
	    $GoodList=new GoodsListModel();
	    //获取商品列表
	    $data_fl=$GoodList->getGoodsBykey($key); 
       //获取商品分类
      	$data_flAll=$GoodList->getAllGoods($data_fl);
	    $data_ListAll=$GoodList->getAlllms();
       
        $IndexModel=new IndexModel();
        //公告信息
        $GongGao=$IndexModel->getGongGao();
        //免责声明
        $MianZei=$IndexModel->getMianZei();
        //常见问题
        $ChangJian=$IndexModel->getChangJian();
        //历史订单信息
        $LiShi=$IndexModel->getLiShi();
		
        //商品列表详细信息
        $this->assign('GoodsList', $data_fl); 
        //商品列表详细信息ALL
        $this->assign('GoodsListAll', $data_flAll);        
        $this->assign('ListAll', $data_ListAll); 
        //公告信息
        $this->assign('GongGao', $GongGao);
        //免责声明
        $this->assign('MianZei', $MianZei);
        //常见问题
        $this->assign('ChangJian', $ChangJian);
        //历史订单信息
        $this->assign('LiShi', $LiShi);
        $this->assign('param', $param);

        $this->assign('val', $key); 
         if(config('moban_index')==1){//经典模版
         	return $this->fetch('index/index');
         }else if(config('moban_index')==3){
         	return $this->fetch('hengtiao/goodsbykey'); 
         }
         return $this->fetch('hengtiao/goodsbykey'); 
        
          
	}
	
	
	 /*
     * hengtiao根据商品类目ID查找商品
     */
	public function hengGoodByLmid(){
	    $lmid = input('lmid');    
        //初始化商品类
	    $GoodList=new GoodsListModel();
	    //获取商品列表
      $data_fl=$GoodList->getGoods();
	    //获取商品分类
	    $data_flAll=$GoodList->getAllGoodsByid($data_fl,$lmid);
	    $data_ListAll=$GoodList->getAlllms();
       
        $IndexModel=new IndexModel();
        //公告信息
        $GongGao=$IndexModel->getGongGao();
        //免责声明
        $MianZei=$IndexModel->getMianZei();
        //常见问题
        $ChangJian=$IndexModel->getChangJian();
        //历史订单信息
        $LiShi=$IndexModel->getLiShi();
		
        //商品列表详细信息
        $this->assign('GoodsList', $data_fl); 
        //商品列表详细信息ALL
        $this->assign('GoodsListAll', $data_flAll); 
        //商品列表详细信息ALL
        $this->assign('ListAll', $data_ListAll); 
        //公告信息
        $this->assign('GongGao', $GongGao);
        //免责声明
        $this->assign('MianZei', $MianZei);
        //常见问题
        $this->assign('ChangJian', $ChangJian);

        $this->assign('val', ''); 
        return $this->fetch('hengtiao/goodsbykey');   
	}
	
	
	
	/*
     * imgpc订单详情
     */
	public function orderdetail(){
		if(config('web_reg_type')==1){
	    	//强制注册
	    	if(!session('useraccount')){    		               
		        if(isMobilePc()){
		        	return $this->redirect(url('@mobile/user/index'),302); 		        	
		        }else{
		        	 return $this->redirect(url('@jingdian/user/index'),302); 
		        }		                      
		    }		    
	    }
		$data=inputself();
		$data['p5_Pid']=$data['mpid'];
		$GoodList=new GoodsListModel();
		//获取指定商品详情
        $param2=$GoodList->shopyh($data);		
		//获取指定商品库存
        $goodscount=$GoodList->goodscount($data);
		if(($param2['data_fl'][0]['sendbeishu']*$data['buynum']) > $goodscount['mail']['count'][0]['count'])
		{
		return $this->redirect($_SERVER['HTTP_REFERER'],302); 
		exit;
		}
        $param2['p5_Pid']=$param2['mpid'];
        
        //获取购买价格和单价
        $GoodList=new GoodsListModel();
        $param=$GoodList->getBuyMoneyBybuynum($data['buynum'],$data['mpid']); 
        $param2['data_shop']=$param; 
         //附加选项
        $sql="SELECT * FROM think_attach where attachgroupid in(SELECT attachgroupid from think_fl where id=:id)";
		$result = Db::query($sql,['id'=>trim(input('param.mpid'))]);
		if(count($result)>0){
			$param2['isattach']=1;
		}else{
			$param2['isattach']=0;
		}
		//会员信息
		if(session('useraccount.id')){			
	        $hasUser =Db::name('member')->where('id', session('useraccount.id'))->find();
	        $param2['useraccount']=$hasUser;	        
	   	}    
		
		$this->assign('attach', $result);  
        $this->assign('param', $param2); 
		if(config('moban_index')==4){//imgpc
		    return $this->fetch('imgpc/order');
		}else if(config('moban_index')==5||config('moban_index')==6){//imgpc2和优云宝
		    return $this->fetch('imgpc2/order');
		}
	}
	
	/*
     * imgpc卡密列表
     */
	public function imgpcmaillist(){
		
		if(config('moban_index')==4){//imgpc
		   return $this->fetch('imgpc/maillist');
		}else if(config('moban_index')==5){//imgpc2
		    return $this->fetch('imgpc2/maillist');
		}
	}
	

}
	