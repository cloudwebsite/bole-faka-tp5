<?php
namespace app\jingdian\controller;
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
 		$param=inputself();
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
    	
    	$sql="SELECT * FROM think_attach where attachgroupid in(SELECT attachgroupid from think_fl where id=:id)";
			$resultattach = Db::query($sql,['id'=>trim(input('param.mpid'))]);
			if(count($resultattach)>0){
				$param['isattach']=1;
			}else{
				$param['isattach']=0;
			}
			$this->assign('attach', $resultattach); 
			$this->assign('param', $param);               
    	//订单详细信息
        $this->assign('Order', $result);
        if($result['code']==1)
        {
        	return $this->fetch('Index/order');	
        }
        if($result['code']==-1)
        {
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
    	
 	}
}