<?php
namespace app\mobile\controller;
use app\jingdian\model\OrderModel;
use app\jingdian\model\GoodsListModel;
use think\Config;
use think\Loader;
use think\Db;

class Order extends Base
{
 	protected $autoWriteTimestamp = true;   // 开启自动写入时间戳
    
    /**
     * 获取订单号信息
     */
    public function getOrder()
 	{
		off_spider();
 		$number=trim(input('param.number'));
 		if(SuperIsEmpty($number)){
 		$errormsg='订单号异常';
 		$this->assign('errormsg', $errormsg);
 		return $this->fetch('index/error');
 		}
 		
 		$OrderM=new OrderModel();
 		$order=$OrderM->getOrder($number);

 		return $order;
 		
 	}
 	
 		/**
     * 查询订单号详情
     */
    public function getOrderDetailByOrder()
 	{
		off_spider();
 		$number=trim(input('param.number'));
 		if(SuperIsEmpty($number)){
 		$errormsg='订单号异常';
 		$this->assign('errormsg', $errormsg);
 		return $this->fetch('index/error');
 		}
 		//订单详情
 		$OrderM=new OrderModel();
 		$order=$OrderM->getOrder($number);
 
 		if($order['code']==-1){
 		$errormsg=$order['msg'];
 		$this->assign('errormsg', $errormsg);
 		return $this->fetch('index/error');
 		}
 		//商品详情
 		$GoodList=new GoodsListModel();
 		$data['mpid']=$order['data']['mflid'];
 		$param=$GoodList->shopyh($data);
        
		//订单附加信息详情
		$orderattach=$OrderM->getOrderAttach($number);
	
 		$this->assign('param', $param);
 		$this->assign('orderattach', $orderattach);
        $this->assign('orderdetail', $order);
 		return $this->fetch('imgmobile/orderdetailtwo');		
 	}
 	
  

}
