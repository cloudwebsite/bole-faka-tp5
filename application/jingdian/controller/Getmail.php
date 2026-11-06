<?php
namespace app\jingdian\controller;
use app\jingdian\model\CommonModel;
use app\jingdian\model\OrderModel;
use think\Config;
use think\Loader;
use think\Db;
use com\GetmailAuth;

class Getmail extends Base
{
 	
    /**
     * 提取卡密
     */ 
    public function index()
 	{	
 		$number=trim(input('param.number'));
 		$mpid=trim(input('param.mpid'));
		if(isMobilePc()){		
				return $this->redirect(url('@mobile/Getmail/index',['mpid'=>$mpid,'number'=>$number]),302);
			}
 		if(SuperIsEmpty($number)){
	 		$errormsg='订单号异常';
	 		$this->assign('errormsg', $errormsg);
	 		return $this->fetch('index/error');
 		}
 		if(SuperIsEmpty($mpid)){
	      $errormsg='商品异常';
	      $this->assign('errormsg', $errormsg);
	      return $this->fetch('index/error');
 		}
 		$GoodsList=new OrderModel();
    	$result=$GoodsList->getShopnum($mpid,$number);
    	//订单详细信息
        $this->assign('Order', $result);
       
        if($result['code']==1)
        {
            $card = isset($result['data']['data_card']) ? $result['data']['data_card'] : [];
            $auth = GetmailAuth::assertCanPickup(is_array($card) ? $card : []);
            if (!$auth['ok']) {
                $this->assign('errormsg', $auth['msg']);
                return $this->fetch('index/error');
            }
        	$historyData['goodname']=strip_tags(htmlspecialchars_decode($result['data']['mname']));
        	$historyData['price']=$result['data']['price'];
        	$historyData['money']=$result['data']['jine'];
        	$historyData['buynum']=$result['data']['buynum'];
        	$historyData['goodtype']=$result['data']['type'];
        	$historyData['mobile']=$result['data']['data_card']['lianxi'];
        	$historyData['email']=$result['data']['data_card']['email'];
        	if(session('useraccount.id')){
        		$historyData['memberid']=session('useraccount.id');
        	}
        	
        	$historyData['userip']=$result['data']['data_card']['userip'];
        	$historyData['maddtype']=$result['data']['data_card']['maddtype'];
        	$historyData['orderno']=$result['data']['data_card']['mcard'];
        	$historyData['outorderno']=$result['data']['data_card']['morder'];
        	$historyData['create_time']=time();
        	$historyData['imgurl']=$result['data']['imgurl'];
        	writeinfohistory($historyData);
        	//消费总金额
	    	if(session('useraccount.id')){
	    		writeamounttotal(session('useraccount.id'),$result['data']['jine'],'xfmoney');
	    	}
	    	
        	//手动发货和自动发货
  
        	if($result['data']['type']==0){
        		//自动发货
        		$GetMailM=new CommonModel();
	 			$html=$GetMailM->getMailByNum($mpid,$number,$result);
	 			   if($html['code']==1){
					  	
						$zhengwen = $html['data'];
						if((preg_match('/[0-9]{8}+\//',$zhengwen) !='0'&&preg_match('/\.jpg|\.png|\.gif$/is', $zhengwen)!='0')&&strpos($zhengwen,'http')===false){	
							$zhengwen=str_replace("<hehe id='zhengwen'>",'',$zhengwen);		
							$zhengwen=str_replace("</hehe>",'',$zhengwen);		
							$zhengwen=str_replace("</prewei>",'',$zhengwen);						
							$zhengwen = explode("<br/>",$zhengwen);
							array_pop($zhengwen);
							array_filter($zhengwen);
						   foreach($zhengwen as $value){
								$zhanshitext .= '<img src="/uploads/images/'.$value.'" style="width:50%;float: left;"></br>';
							 }
						}else{
							$zhanshitext=$html['data'];
							
						}
						
						$len= mb_strlen($zhanshitext,'utf8');
						if($len>3000){
							$zhanshitext="<hehe id='zhengwen'>卡密字数过大请直接下载</hehe>";
						}
	 			     $this->assign('html', $zhanshitext);
		 			     if(config('moban_index')==1){//经典模版
					        	return $this->fetch('jingdian@index/maillist');  
					        }else if(config('moban_index')==2){
					        	return $this->fetch('jingdian@index/maillist');  
					        }else if(config('moban_index')==3){
					        	 return $this->fetch('jingdian@index/maillist');
					        }else if(config('moban_index')==4){
					        	 return $this->fetch('jingdian@imgpc/maillist');
					        }else if(config('moban_index')==5){
					        	 return $this->fetch('jingdian@imgpc2/maillist');
					        }else{
					        	return $this->fetch('jingdian@index/maillist');
					        	  
					        }  	
	 			   }
	 			   if($html['code']==-1){
	 			     $this->assign('errormsg', $html['msg']);
	 			     return $this->fetch('index/error');
	 			   }
        	}else if($result['data']['type']==1){
        		//手动发货      		
        		$GetMailM=new CommonModel();
	 			$html=$GetMailM->changeOrderStatus($mpid,$number,$result);
	 			   if($html['code']==1){
	 			     $this->assign('html', $html['data']);
		 			     if(config('moban_index')==1){//经典模版
					        	return $this->fetch('jingdian@index/manual');  
					        }else if(config('moban_index')==2){
					        	return $this->fetch('jingdian@index/manual');  
					        }else if(config('moban_index')==3){
					        	 return $this->fetch('jingdian@index/manual');
					        }else if(config('moban_index')==4){
					        	 return $this->fetch('jingdian@index/manual');
					        }else{
					        	return $this->fetch('jingdian@index/manual');
					        	  
					        }  	
	 			   }
	 			   if($html['code']==-1){
	 			     $this->assign('errormsg', $html['msg']);
	 			     return $this->fetch('index/error');
	 			   }
        	}			
        }
        if($result['code']==-1)
        {
        	 $msg = isset($result['msg']) ? (string)$result['msg'] : '';
        	 // 支付刚完成、结算尚未落库时自动重试，避免用户看到「未结算」后还要手动刷新
        	 if (strpos($msg, '未结算') !== false || strpos($msg, '请刷新') !== false || strpos($msg, '稍等') !== false) {
        	 	$this->assign('mpid', $mpid);
        	 	$this->assign('number', $number);
        	 	return $this->fetch('index/waitsettle');
        	 }
              return $this->fetch('index/errormsg');
            
        	
        }
        if($result['code']==-2)
        {
        	
              return $this->redirect(url("Index/selectorder",array('key'=>$number)));
            
        	
        }
         if($result['code']==-3)
        {       	
               return $this->fetch('index/errormsg');	
        }
        if($result['code']==-4)
        {      	
               return $this->redirect(url('@jingdian/Index/fenxiang',['order'=>$number]));
        }				
 	}
  

}
