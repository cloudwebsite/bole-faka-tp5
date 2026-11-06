<?php
namespace app\mobile\controller;
use app\jingdian\model\IndexModel;
use app\jingdian\model\GoodsListModel;
use think\Config;
use think\Loader;
use think\Db;
use com\IpLocationqq;
use com\AccessEncryption;
use com\GetmailAuth;

class Index extends Base
{

		public function index(){
				if(config('accessencryption')==1){
					$visitors = new AccessEncryption(); // 实例化访客密码访问
					$visitors->BLEncrypt(config('access_pwd'));
				}
				// 首页可直接下单的模板：强制会员时未登录不可进入
				if (in_array((string)config('wapmoban_index'), ['1', '2'], true)) {
					$loginUrl = force_member_login_url();
					if ($loginUrl !== null) {
						return $this->redirect($loginUrl, 302);
					}
				}
		        //初始化
		        $param=self::init();
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
			
			if(config('wapmoban_index')==1 || config('wapmoban_index')==2){//经典模版
	        	//会员信息
				if(session('useraccount.id')){			
			        $hasUser =Db::name('member')->where('id', session('useraccount.id'))->find();
			        $param['useraccount']=$hasUser;	        
			   	} 
			}   	
			$this->assign('param',$param);
			
	     
	        
	        if(config('wapmoban_index')==1){//经典模版
	        	//会员信息
				if(session('useraccount.id')){			
			        $hasUser =Db::name('member')->where('id', session('useraccount.id'))->find();
			        $param2['useraccount']=$hasUser;	        
			   	}    
	        	return $this->fetch();
	        }else if(config('wapmoban_index')==2){
	        	return $this->fetch("/gzh/index");
	        }else if(config('wapmoban_index')==3){
	        	return $this->fetch("/imgmobile/index");
	        }else if(config('wapmoban_index')==4){//优云宝
	        	return $this->fetch("/youyunbao/index");
	        }else{
	        	return $this->fetch();
	        }
	        
	        
	        
	}
	    
	/**
     *首页初始化
   	*/
   	public function init(){
   		$tokenid=cookie("tokenid");
   		$count=0;
   		$param=[];
   		
   		if($tokenid&&$tokenid!==""&&$tokenid!=="undefined"&&$tokenid!=="null"&&!empty($tokenid))
        {
	   		if(config('select_cookie')==1){
	   			$map['cookie'] = $tokenid;
		   		$IndexM = new IndexModel();        	        
		        $count = $IndexM->getCookieByWhere($map);	
	   		}
	   			
        }
        if(isMobilePc())
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
   	
   		
   		
   	public function order(){

            $order=input('param.number');
            //初始化商品类
            $GoodList=new GoodsListModel();
            //获取商品列表
            $data_fl=$GoodList->getGoods();
            //获取商品分类
            $data_flAll=$GoodList->getAllGoods($data_fl);
            //获取所有优惠信息
            $data_yh=$GoodList->shopAllyh();
            
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
            //公告信息
            $this->assign('GongGao', $GongGao);
            //免责声明
            $this->assign('MianZei', $MianZei);
            //常见问题
            $this->assign('ChangJian', $ChangJian);
            //历史订单信息
          $this->assign('LiShi', $LiShi);
          	$this->assign('order',$order);
          return $this->fetch('order');
      
    }    

 	/*2018年3月13日 17:15:50 
    */
     
   public function selectorder()
 	{
		//初始化
		$param=self::init();
			
		$key = trim(input('key'));
		$key=strtolower($key);
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
        if(empty($key))
        {
        	$key=cookie('tokenid');
        	if(strlen($key)<10){
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
				$callkey.='cookie|';
			}
			if(config('select_openid')==1){
				$callkey.='openid';
			}
			if($callkey==''){
				 $this->assign('errormsg', '管理员没开启自助查询');
	 			 return $this->fetch('mobile@index/error');
			}
			if(substr($callkey, -1)=='|'){
				$callkey=substr($callkey,0,strlen($callkey)-1);
			}
			
			
			$map[$callkey] = $key;  
			
			       
	       	$IndexM = new IndexModel();  
	        $Nowpage = input('get.page') ? input('get.page'):1;
	        $limits = config('list_rows')+40;// 获取总条数
	        $count = $IndexM->getAllCount($map);//计算总页面
	        if($count<=0){
	        	$uf=kami_order_txt_path($key);
		    	if ($uf !== '' && is_file($uf)) {
		        	return $this->redirect(url('@mobile/Index/fenxiang',['order'=>$key]),302);
		        }
	        }
	        $allpage = intval(ceil($count / $limits));       
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
        
        if(config('wapmoban_index')==1){//经典模版
	        	return $this->fetch('query');
	        }else if(config('wapmoban_index')==2){
	        	return $this->fetch('query');
	        }else if(config('wapmoban_index')==3){
	        	return $this->fetch("/imgmobile/selectorder");
	        }else if(config('wapmoban_index')==4){//优云宝调用img模板
	        	return $this->fetch("/imgmobile/selectorder");
	        }else{
					return $this->fetch();
				}
    }

    /**
     * 订单号查单后补充校验下单联系方式
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
				$loginUrl = force_member_login_url();
				if ($loginUrl !== null) {
					return $this->redirect($loginUrl, 302);
				}
				$param=inputself();
				$mpid=trim($param['p5_Pid']);
				$data_yh=db('yh')->where('mpid',trim(input('param.p5_Pid')))->order('mdy asc')->select();
				$param['data_yh']=$data_yh;
	            if(empty($data_yh))
	            {
	            	$param['isyh']=0;
	            }
	            else
	            {          	
	            	$param['isyh']=1;
	            }
	            if(session('useraccount.account')){
	              $param['lianxi']='';
	            } 
	            $this->assign('param', $param);
	            return $this->fetch('zywlpay');
	            
	       
	    }


   

 
  public function dc()
	 {
		off_spider();
		$order=input('param.order');
		$uf=kami_order_txt_path($order);
		if ($uf === '' || !is_file($uf)) {
			return '文件已删除';
		}
		clearstatcache(true, $uf);
		$contents=file_get_contents($uf);
		if((preg_match('/[0-9]{8}+\//',$contents) !='0'&&preg_match('/\.jpg|\.png|\.gif$/is', $contents)!='0')&&strpos($contents,'http')===false){					
        	return $this->redirect(url('@jingdian/Downimg/index',['order'=>$order]),302);
		}
		header("Content-Type:application/force-download");
		header("Content-Disposition:attachment;filename=".basename($uf));
		if(file_exists($uf)){
			$contents=file_get_contents($uf);
			$tou=getSubstr($contents,"<pretou>","</pretou>");
			$wei=getSubstr($contents,"<prewei>","</prewei>");
			$zhengwen=str_replace($tou,'',$contents);
			$zhengwen=str_replace($wei,'',$zhengwen);
			$zhengwen=str_replace("<pretou>",'',$zhengwen);
			$zhengwen=str_replace("</pretou>",'',$zhengwen);
			$zhengwen=str_replace("<prewei>",'',$zhengwen);
			$zhengwen=str_replace("</prewei>",'',$zhengwen);
			$arrayzw = explode("||||||",str_replace(array("\r\n", "\r", "\n"),'||||||',$zhengwen));
			$array=array_filter($arrayzw);
			   foreach($array as $value){
					$zhanshitext .= $value;
					$zhanshitext .= "\r\n";
				 }
			return trim($zhengwen, "\r");
			//readfile($uf);
		}else{
			return '文件已删除';
		}
		

	 }
	 
	 
	  public function fenxiang()
	 {
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
				$zhengwen = explode(PHP_EOL,$zhengwen);
				array_pop($zhengwen);
			   foreach($zhengwen as $value){
					$zhanshitext .= '<img src="/uploads/images/'.$value.'" style="width:50%;float: left;"></br>';
				 }
		}else{			
			$zhanshitext=$tou."<hehe id='zhengwen'>".$zhengwen."</hehe>".$wei;
			$zhanshitext=str_replace(PHP_EOL,"<br>",$zhanshitext);	
		}
		
		
		$this->assign('myfile', $zhanshitext);
		$this->assign('zhengwen', $zhanshitext);
		$this->assign('tou', $tou);
		$this->assign('wei', $wei);
		$this->assign('order', $order);
	  return $this->fetch('imgmobile/cardlist');
	 }
    
    
    
    
    
    
    

     

    
    
     
    
      public function shopxq()
 	{
		$data=inputself();
		 $data['p5_Pid']=$data['mpid'];
		$GoodList=new GoodsListModel();
        $param=$GoodList->shopyh($data);
        $param['p5_Pid']=$param['mpid'];
      	return $param;      
    }
    
    
    /*
     * imgmobile全部分类
     */
	public function category(){
		//初始化商品类
	    $GoodList=new GoodsListModel();
	    $data_flAll=$GoodList->getAlllms();
	    
	    //商品列表详细信息ALL
	    $this->assign('GoodsListAll', $data_flAll); 
		if(config('wapmoban_index')==4){//优云宝
			return $this->fetch("/youyunbao/category");
		}else{
			
			return $this->fetch('imgmobile/category');
		}
	}
	
	/*
     * imgmobile指定类目商品信息
     */
	public function categorybyid(){
      //初始化
	    $param=self::init();
      $lmid=trim(input('param.lmid'));
		//初始化商品类
	    $GoodList=new GoodsListModel();
	    $data_fl=$GoodList->getGoods();
	        //获取商品分类
	    $data_flAll=$GoodList->getAllGoodsByid($data_fl,$lmid);
	   	$IndexModel=new IndexModel();
	        //公告信息
	    $GongGao=$IndexModel->getGongGao();
	        
	    //公告信息
	    $this->assign('GongGao', $GongGao);
	    //商品列表详细信息ALL
	    $this->assign('GoodsListAll', $data_flAll); 
	    $this->assign('param', $param);
		if(config('wapmoban_index')==4){//优云宝
			return $this->fetch("/youyunbao/categorybyid");
		}else{
			return $this->fetch('imgmobile/categorybyid');
		}
	}
	
	/*
     * imgmobile商品详情
     */
		public function goodsdetail(){
			$loginUrl = force_member_login_url();
			if ($loginUrl !== null) {
				return $this->redirect($loginUrl, 302);
			}
			//初始化
			$param=self::init();
			$data=inputself();
			$data['p5_Pid']=$data['mpid'];
			//判断是不是电脑
			if(isMobilePc()==false){
				return $this->redirect(url("jingdian/Index/goodsdetail",['mpid'=>$data['p5_Pid']]),302);
			}
		$GoodList=new GoodsListModel();
        $param2=$GoodList->shopyh($data);
        if($param2['code']!=1){
	 		$this->assign('errormsg', $param2['msg']);
	        return $this->fetch('mobile@index/error');
        }
        $param2['p5_Pid']=$param2['mpid'];
        $goodscount=$GoodList->goodscount($data);    
      	$param2['isweixin']=$param['isweixin'];
        $param2['isattach']=0;
        $result=[];
        if($param2['data_fl'][0]['type']==1){
        	 //附加选项
	        $sql="SELECT * FROM think_attach where attachgroupid in(SELECT attachgroupid from think_fl where id=:id)";
			$result = Db::query($sql,['id'=>trim(input('param.mpid'))]);
			if(count($result)>0){
				$param2['isattach']=1;
			}else{
				$param2['isattach']=0;
			}
        }
        
		$this->assign('attach', $result);  
		
		
      	$this->assign('param', $param2); 
      	$this->assign('goodscount', $goodscount); 
		if(config('wapmoban_index')==3){
			return $this->fetch("/imgmobile/goodsdetail");
		}else if(config('wapmoban_index')==4){//优云宝
			return $this->fetch("/youyunbao/goodsdetail");
		}else{
			return $this->fetch();
		}
	}
	
	/*
     * imgmobile商品详情
     */
		public function orderpay(){ 
			$loginUrl = force_member_login_url();
			if ($loginUrl !== null) {
				return $this->redirect($loginUrl, 302);
			}
			//初始化
			$param=self::init();
		$param2=inputself();
		$param2['isweixin']=$param['isweixin'];
		if(session('useraccount.account')){
			$param2['lianxi']='';
		}
		//会员信息
		if(session('useraccount.id')){			
	        $hasUser =Db::name('member')->where('id', session('useraccount.id'))->find();
	        $param2['useraccount']=$hasUser;	        
	   	}    
		

		$this->assign('param', $param2);
		return $this->fetch('imgmobile/orderpay');
	}
	/*
     * imgmobile订单查询
     */
	public function imgselectorder(){
		return $this->fetch('imgmobile/selectorder');
	}
	
	/*
     * imgmobile历史订单
     */
	public function imgorderhistory(){
		$order=input('param.order');
		$map['orderno|outorderno']=$order;
		$result=Db::name('info_history')->where($map)->find();

		return $this->fetch('imgmobile/orderhistory',['Order'=>$result]);
		
	}
	/*
     * imgmobile订单详情
     */
	public function orderdetail(){
		return $this->fetch('imgmobile/orderdetail');
	}
	/*
     * imgmobile客服中心
     */
	public function kefu(){
		return $this->fetch('imgmobile/kefu');
	}
	
	public function goodsseach(){
		$key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['mname'] = ['like',"%" . $key . "%"];          
        }      
        //初始化商品类
	    $GoodList=new GoodsListModel();
	    $data_fl=$GoodList->getGoodsByName($map); 

	    foreach($data_fl as &$v){
	    	$v['name']='';
	    }
	     

        $this->assign('GoodsListAll', $data_fl);
	    $this->assign('val', $key); 
		if(config('wapmoban_index')==4){//优云宝
			return $this->fetch("/youyunbao/goodsseach");
		}else{			
			return $this->fetch('imgmobile/goodsseach');
		}
	}
	
	/*
	 * URL跳转
	 */
	public function callbackurl(){
		$callbackurl=input('callbackurl');
		$param=inputself();
		$callbackurl=str_replace(" ","+",$callbackurl);
		if(is_weixinorqq()){
        	$isweixin=1;
        }else{
        	$isweixin=0;
        }
        $this->assign('isweixin', $isweixin);
        $this->assign('param', $param);
		$this->assign('callbackurl', base64_decode($callbackurl));
		return $this->fetch('imgmobile/callbackurl'); 
	}
}
