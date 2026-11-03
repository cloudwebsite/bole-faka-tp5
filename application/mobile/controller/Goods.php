<?php
namespace app\mobile\controller;
use app\jingdian\model\OrderModel;
use think\Config;
use think\Loader;
use think\Db;

class Goods extends Base
{
	/**
     * 根据订单号获取
     */
    public function getShopnumByOrder()
 	{
 		
 		$number=trim(input('param.number'));
 		$mpid=trim(input('param.mpid'));
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
        	return $this->fetch('Index/orderok');	
        }
        if($result['code']==-1)
        {
        	return $this->fetch('index/errormsg');
        }
        if($result['code']==-2)
        {
        	return $this->redirect(url("Index/query",array('key'=>$number)));
        }
         if($result['code']==-3)
        {
        	 return $this->fetch('index/errormsg');	
        }
    	
 	}
}