<?php
namespace app\jingdian\model;
use app\jingdian\model\CommonModel;
use app\jingdian\model\GoodsListModel;
use think\Model;
use think\Db;

class OrderModel extends Model
{

	protected $name = 'info';   
    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳
   
    
    /**
     * 更新订单状态
     */
    public function updateOrderStatus($order,$param)
    {
    	$map = [];
        if($order&&$order!=="")
        {
        	$map['mcard|morder'] = $order;	
        }else{
        	return false; 
        }
       
    	$result=db('info')->where($map)->where('mstatus',2)->update($param);
    	return $result;
    }
   
    /**
     * 获取订单号信息
     */
    public function getOrder($order)
    {
    	if(SuperIsEmpty($order)){
        return TyReturn('订单号输入不正确001',-1); 
    	}
    	
    	$map = [];
    	$msg='';
    	$code=1;
    	$uf = kami_order_txt_path($order);
    	clearstatcache(true, $uf);
    	if ($uf !== '' && is_file($uf) && filesize($uf) > 0) {
        	$code=-4;
            $msg = "发现缓存";
            return TyReturn($msg,$code); 
        }
    	
        if($order&&$order!=="")
        {
        	$map['mcard|morder'] = $order;	
        }else{
        	return TyReturn('订单号输入不正确',-1); 
        }	
		$card=db('info')->where($map)->find();	
        if (empty($card)) {
        	$code=-1;
            $msg = '卡号不存在!（转账之后稍等30秒再提取）' . '<br>' . '紧急联系电话：' . config('WEB_MOBILE') . '<br>' . '联系QQ：' . config('WEB_QQ');
        } elseif ($card['mstatus'] == 1) {
        	$code=-2;
            $msg = "卡号已使用";
        }elseif ($card['mstatus'] == 0 && $card['mflid']!=0) {
        	$code=-3;
            $msg = "已付款,未提取";
        } elseif ($card['mstatus'] == 2) {
        	$code=-1;
            $msg = "卡号未结算(请刷新页面)";
        }elseif ($card['mstatus'] == 3) {
        	$code=-2;
            $msg = "订单进行中";
        }elseif ($card['mstatus'] == 4) {
        	$code=-2;
            $msg = "订单已撤回";
        }elseif ($card['mstatus'] == 5) {
        	$code=-2;
            $msg = "订单已完成";
        }elseif (file_exists($uf)) {
        	$code=-1;
            $msg = "未知信息";
        }
        return TyReturn($msg,$code,$card);         
    }
     /**
     * 根据订单号获取购买数量
     */
    public function getShopnum($mpid,$order)
    {   	
    	if(SuperIsEmpty($mpid)){
        return TyReturn('异常错误goodid',-1); 
    	}
    	if(SuperIsEmpty($order)){
        return TyReturn('订单号输入不正确002',-1); 
    	}
    	$result=self::getOrder($order);
    	$code=$result['code'];
    	$msg=$result['msg'];
    	$data=$result;
    	$checkcode=false;
    	if($code==1){
    	$checkcode=true;
    	}elseif($code==-3 && $mpid==$result['data']['mflid']){
    	$code=1;
    	$checkcode=true;
    	}
    	if($checkcode)
    	{
    		$jine=$result['data']['mamount'];
    		$buynum=$result['data']['buynum'];
    		$childid=$result['data']['childid'];
    		$orderattach=[];
    		$data_fl = self::getShopxq($mpid); 
    		if(empty($data_fl))
    		{
          		$code=-1;
          		$msg='获取商品信息出错';
          		return TyReturn($msg,$code); 
            }
            $sendbeishu=$data_fl[0]['sendbeishu'];
    		if($sendbeishu<1){
    			$sendbeishu=1;
    		}
    			
    		$shoptype=$data_fl[0]['type'];
    		$sfyh=0;
    		$data_yh=[];
    		if($childid>0){
    			$CommonM=new CommonModel();
	 			$childminmoney=$CommonM->GetChildMinMoneyBygoodid($childid,$buynum,$mpid);	 			
	 			$childFxmoney=bcsub($jine, $childminmoney, 4);
		      	if($childFxmoney<0){
		      		$code=-1;
          			$msg='价格设置错误，请联系管理员修改价格'.$childminmoney.' '.$jine.' '.$childFxmoney;
          			return TyReturn($msg,$code); 
		      	}
			 	$data_child_fl=db('child_fl')->where(['goodid'=>trim($mpid),'memberid'=>$childid])->find();
			 	if($data_child_fl){
			 		if($data_child_fl['mprice']>0){
			 			$child_fl_mpirce=$data_child_fl['mprice'];
			 			$mdj =$child_fl_mpirce;
			 		}
			 	}
			}
			
			
				$data_yh = self::getShopyh($mpid);
				
				/*
				 * 计算分组私密价格开头
				 */
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
		           				}
		           			}	
		           		}
		           			           		
	           		}else{
	           			$mdj=$memberprice[0]['price'];
	           			$typeprice=1;//会员私密价格
	           		}
	           		
	           		
	           	}
	           	/*
	           	 * 计算分组私密价格结尾
	           	 */
	           	
	           	
				if($typeprice!=1 && $typeprice!=2){
					 //判断是转账提取还是在线购买
		            if(empty($buynum) || $buynum==0)
		            {//转账购买
		            	 //计算单价
			            if(!empty($data_yh))
			            {
			                foreach ($data_yh as $v) 
			                {
			                    $tempnum=floor($jine/$v['mdj']*100);                                              	
			                    if($v['mdy'] <= $tempnum)
			                    {
			                        $mdj = $v['mdj'];
			                        break;
			                    }
			                }
			            }
			                  
			            if(!isset($mdj))
			            {
			              $mdj = $data_fl[0]['mprice'];	                
			            }else{
			              $sfyh=1; 
			            }
			            //更新使用者IP
			            Db::execute("update think_info set userip=:userip where mcard=:mcard or morder=:morder",['userip'=>getIP(),'mcard'=>$order,'morder'=>$order]);
		            }else{//在线购买
		            	//验证购买数量是否正确
			            if(!empty($data_yh))
			            {
			                foreach ($data_yh as $v) 
			                {
			                    if($v['mdy'] <= $buynum)
			                    {
			                        $mdj = $v['mdj'];
			                        break;
			                     }
			                }
			            }
			                  
			            if(!isset($mdj))
			            {
			                $mdj = $data_fl[0]['mprice'];
			                $sfyh=0;
			            }else{
			            	$sfyh=1; 
			            }
		            }
	           
					
				}
				
				 /*
	             * 折扣开始
	             */
	            if(isset($memberdiscount)){
	            	 if($memberdiscount>0 && $memberdiscount<100){
		            	$somemberdisount=bcdiv($memberdiscount,100,2);
		            	$mdj=bcmul($mdj,$somemberdisount,4);
		            	$typeprice=5;//分组折扣
		            }
	            }
	           
	            /*
	             * 折扣结束
	             */
                  
            //计算购买数量 实际能购买的数量
            $somdj=bcdiv($mdj, 100, 4);
            $canbuynum=bcdiv($jine,$somdj);          
            if(!empty($buynum) && $buynum!=0 && $buynum*$mdj/100-$jine<0.01){//在线购买
            	$canbuynum=$buynum;//赋值
            }
            
            
            
            //自动发货 手动发货判断
            if($shoptype==0){
            	//自动发货
            	 //获取库存数量
	            $data_mail=self::getShopCount($mpid);
	            if(empty($data_mail))
	    		{
	          		$code=-1;
	          		$msg='获取商品库存出错';
	          		return TyReturn($msg,$code); 
	            }
	         
	            //库存数量
	            $allnum=$data_mail[0]['count'];
            }else if($shoptype==1){
            	//手动发货
            	$orderattach=self::getOrderAttach($result['data']['morder']);
            	$allnum=mt_rand(100,999);
            }
           
            if ($allnum-($canbuynum*$sendbeishu) < 0) 
            {
            	$code=-1;
                $msg = '库存不足，请联系客服补货!' . '<br>' . '联系电话：' . config('WEB_MOBILE') . '<br>' . '联系QQ：' . config('WEB_QQ');
                return TyReturn($msg,$code); 
            }
            elseif (!empty($buynum) && $buynum!=0 && $buynum*$mdj/100-$jine>0.01) 
            {
            	
            	$code=-1;
                $msg = "交易信息被篡改". '<br>' . '联系电话：' . config('WEB_MOBILE') . '<br>' . '联系QQ：' . config('WEB_QQ');
            }
            elseif ($canbuynum == 0) 
            {
            	$code=-1;
                $msg = "金额不足！";
            } 
            elseif ($canbuynum < $data_fl[0]['mmin'] || $canbuynum > $data_fl[0]['mmax']) 
            {
            	$code=-1;
                $msg = '最小提取数量：' . $data_fl[0]['mmin'] . '&nbsp;&nbsp;最大提取数量：' . $data_fl[0]['mmax'];
            }
            //初始化商品类
        	$GoodList=new GoodsListModel();           
            $data_fl[0]=replaceImgurl($data_fl[0]);
            $data=[
            'canbuynum'=>$canbuynum,
            'buynum'=>$buynum,
            'mname'=>strip_tags($data_fl[0]['mname']),
            'imgurl'=>$data_fl[0]['imgurl'],
            'sendbeishu'=>$data_fl[0]['sendbeishu'],
            'integral'=>$data_fl[0]['integral'],
            'price'=>$mdj/100,
            'type'=>$data_fl[0]['type'],
            'decrypt'=>$data_fl[0]['decrypt'],
            'sfyh'=>$sfyh,
            'order'=>$order,
            'mpid'=>$mpid,
            'jine'=>$jine,
            'data_yh'=>$data_yh,
            'data_card'=>$result['data'],
            'allnum'=>$allnum,
            'attach'=>$orderattach,
            'data_fl'=>$data_fl[0]
            ];      
    	}    	
    	return TyReturn($msg,$code,$data);
    	
	}
	
	
	 /**
     * 根据金额获取购买数量 适用于充值卡类型
     */
    public function getShopnumByCard($mpid,$money)
    {   	
    	if(SuperIsEmpty($mpid)){
        return TyReturn('异常错误goodid',-1); 
    	}
    	if(SuperIsEmpty($money)){
        return TyReturn('金额不正确002',-1); 
    	}
            $msg='';
            $code='1';
            $data=[];
    		$data_fl = self::getShopxq($mpid); 
    		if(empty($data_fl))
    		{
          		$code=-1;
          		$msg='获取商品信息出错';
          		return TyReturn($msg,$code); 
            }
            $sendbeishu=$data_fl[0]['sendbeishu'];
    		if($sendbeishu<1){
    			$sendbeishu=1;
    		}
          	$data_yh = self::getShopyh($mpid);
          	$shoptype=$data_fl[0]['type'];
             //计算单价
	            if(!empty($data_yh))
	            {
	                foreach ($data_yh as $v) 
	                {
	                    $tempnum=floor($money/$v['mdj']*100);                                              	
	                    if($v['mdy'] <= $tempnum)
	                    {
	                        $mdj = $v['mdj'];
	                        break;
	                    }
	                }
	            }
	            $sfyh=1;       
	            if(!isset($mdj))
	            {
	                $mdj = $data_fl[0]['mprice'];
	                $sfyh=0;
	            }
            
           
                    
            //计算购买数量
            $canbuynum = floor($money*100/$mdj);

            

            
            
            //自动发货 手动发货判断
            if($shoptype==0){
            	//自动发货
            	 //获取库存数量
	            //获取库存数量
	            $data_mail=self::getShopCount($mpid);
	            if(empty($data_mail))
	    		{
	          		$code=-1;
	          		$msg='获取商品库存出错';
	          		return TyReturn($msg,$code); 
	            }
		         
	            //库存数量
	            $allnum=$data_mail[0]['count'];
            }else if($shoptype==1){
            	//手动发货
            	
            	$allnum=mt_rand(100,999);
            }
            
            
            
            
            if ($allnum-($canbuynum*$sendbeishu) < 0) 
            {
            	$code=-1;
                $msg = '库存不足，请联系客服补货!' . '<br>' . '联系电话：' . config('WEB_MOBILE') . '<br>' . '联系QQ：' . config('WEB_QQ');
                return TyReturn($msg,$code); 
            }
            elseif ($canbuynum == 0) 
            {
            	$code=-1;
                $msg = "金额不足！";
            } 
            elseif ($canbuynum < $data_fl[0]['mmin'] || $canbuynum > $data_fl[0]['mmax']) 
            {
            	$code=-1;
                $msg = '最小提取数量：' . $data_fl[0]['mmin'] . '&nbsp;&nbsp;最大提取数量：' . $data_fl[0]['mmax'];
            }           
            $data=[
            'canbuynum'=>$canbuynum,
            'mname'=>$data_fl[0]['mname'],
            'sendbeishu'=>$data_fl[0]['sendbeishu'],
            'price'=>$mdj/100,
            'sfyh'=>$sfyh,
            'mpid'=>$mpid,
            'jine'=>$money,
            'data_yh'=>$data_yh,
            'allnum'=>$allnum
            ];      
    	    	
    	return TyReturn($msg,$code,$data);
    	
	}
	
	 /**
     * 获取单个商品信息
     */
    public function getShopxq($mpid)
    {   	
    	if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){
    		//初始化商品类
        	$GoodList=new GoodsListModel();
    		$shopFLxq=Db::query("select * from think_fl where id=:mpid",['mpid'=>$mpid]);
    		$shopChildFlxq=Db::query("select * from think_child_fl where goodid=:mpid and memberid=:memberid",['mpid'=>$mpid,'memberid'=>session('child_useraccount.id')]);    		
    		$shopxq[0]=replaceChild($shopChildFlxq[0],$shopFLxq[0]);
    			
    	}else{
    		$shopxq=Db::query("select * from think_fl where id=:mpid",['mpid'=>$mpid]);
    	}
    	
      	return $shopxq;
	}
	
	 /**
     * 获取单个商品优惠信息
     */
    public function getShopyh($mpid)
    { 
      	
    	$shopyh=db('yh')->where('mpid',trim($mpid))->order('mdy desc')->select();
      	return $shopyh;
	}
	
	 /**
     * 获取指定订单附件选项信息
     */
    public function getOrderAttach($order)
    { 
      	
    	$map['mcard|morder'] = $order;	
		$card=db('info')->where($map)->find();
		
    	$sql="SELECT think_orderattach.text,think_attach.title FROM think_orderattach LEFT JOIN think_attach on think_orderattach.attachid=think_attach.id where think_orderattach.orderno=:orderno or think_orderattach.orderno=:orderno2";
    	$orderattach=Db::query($sql,['orderno'=>$card['mcard'],'orderno2'=>$card['morder']]);
    	
    	
    	//$orderattach=db('orderattach')->where('orderno',trim($order))->order('attachid desc')->select();
      	return $orderattach;
	}
	 /**
     * 获取单个商品可用库存
     */
    public function getShopCount($mpid)
    { 
      	
    	$shopCount = Db::query("SELECT COUNT(mis_use) as count from think_mail where mis_use=0 and mpid=:mpid",['mpid'=>$mpid]);
      	return $shopCount;
	}
	
	
}