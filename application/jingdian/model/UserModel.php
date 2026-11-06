<?php
namespace app\jingdian\model;
use think\Model;
use think\Db;

class UserModel extends Model
{

	protected $name = 'member';   
    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳
   
    
    /**
     * 充值用户处理逻辑
     */
    public function payMember($order,$param)
    {
      //echo '<pre>';
      //print_r($param);exit;
    	$map = [];
        if($order&&$order!=="")
        {
        	$map['mcard|morder'] = $order;	
        }else{
        	return false; 
        }	
    	//开启事务
    	Db::startTrans();
    	try
    	{
    		//更新订单记录
    		
    		$sql = "update think_member_payorder set status=1,orderno=:r2order,outorderno=:r6order where (orderno=:orderno or outorderno=:outorderno) and status=0";
	        $updatepayorder = Db::execute($sql,["orderno"=>$order,"outorderno"=>$order,"r2order"=>$param['r2'],"r6order"=>$param['r6']]);
	        if($updatepayorder==false)
	    	{
	    		// 回滚事务
	            Db::rollback();
	            $errormsg='更新用户充值记录出错';
	            $code=-1;
	            return TyReturn($errormsg,$code);	
	    	}
	    	
	    	//查询充值赠送
	    	$addmoney=$param['money'];
	    	$givemoney=0;
	    	$givelist=Db::name('pay_give')->where('paymoney','elt',$param['money'])->order('paymoney desc')->limit(1)->find();
	    	if($givelist){
	    		if($givelist['paytype']==0){
	    			$givemoney=$givelist['givemoney'];
	    			$addmoney=$addmoney+$givemoney;
	    			//记录金额log
	    			writemoneylog($param['memberid'],"在线充值：".$order."单笔赠送（".$givemoney."）元",0,$param['money'],$param['userip']);
	    			//充值总金额
	    			writeamounttotal($param['memberid'],$param['money'],'czmoney');
	    			//赠送总金额
	    			writeamounttotal($param['memberid'],$givemoney,'zsmoney');
	    		}else{
	    			
	    			$bili=bcdiv($givelist['givemoney'],100,2);
	    			$givemoney=bcmul($addmoney,$bili,4);	    			
	    			$addmoney=$addmoney+$givemoney;
	    			//记录金额log
	    			writemoneylog($param['memberid'],"在线充值：".$order."比例（".$givelist['givemoney']."%）赠送（".$givemoney."）元",0,$param['money'],$param['userip']);
	    			//充值总金额
	    			writeamounttotal($param['memberid'],$param['money'],'czmoney');
	    			//赠送总金额
	    			writeamounttotal($param['memberid'],$givemoney,'zsmoney');
	    		}
	    	
	    	}else{
	    		writemoneylog($param['memberid'],"在线充值：".$order,0,$param['money'],$param['userip']);
	    		//充值总金额
	    		writeamounttotal($param['memberid'],$param['money'],'czmoney');
	    	}
	    	//增加用户金额
	    	$sql = "update think_member set money=money+:addmoney where id=:memberid";
	        $updatemoney = Db::execute($sql,["addmoney"=>$addmoney,"memberid"=>$param['memberid']]);
	        if($updatemoney==false)
	    	{
	    		// 回滚事务
	            Db::rollback();
	            $errormsg='更新用户金额出错';
	            $code=-1;
	            return TyReturn($errormsg,$code);	
	    	}
    	}catch(\Exception $e){
					// 回滚事务
                    Db::rollback();
                    $errormsg=$e->getMessage();
                    $code=-1;
                    return TyReturn($errormsg,$code);
    	}
    	Db::commit();
    	return TyReturn("充值成功","1");
    }
    
    /**
     * [查询用户财务明细]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
     public function getPaylogByWhere($map, $Nowpage, $limits)
    {
  		return Db::name('member_money_log')
                   	->where($map)->page($Nowpage, $limits)
                   	->order('create_time desc')
                   	->select();
          
    }
    public function getPaylogAllCount($map)
    {
        return Db::name('member_money_log')->where($map)->count();
    }
    
    
    /**
     * [查询用户佣金明细]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
     public function getTgmoneylogByWhere($map, $Nowpage, $limits)
    {
  		return Db::name('tgmoney_log')
  					->alias('a')
  					->field("a.tgtype,a.money,a.shopname,a.create_time,a.relation,IFNULL(b.account,'未知') as childaccount,IFNULL(c.account,'非会员') as buyaccount")
  					->join('think_member b','a.childid = b.id','LEFT')
  					->join('think_member c','a.buyid = c.id','LEFT')
                   	->where($map)->page($Nowpage, $limits)
                   	->order('create_time desc')
                   	->select();
          
    }
    public function getTgmoneylogAllCount($map)
    {
        return Db::name('tgmoney_log')->where($map)->count();
    }
    
     /**
     * [查询用户积分明细]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
     public function getIntegrallogByWhere($map, $Nowpage, $limits)
    {
  		return Db::name('member_integral_log')
                   	->where($map)->page($Nowpage, $limits)
                   	->order('create_time desc')
                   	->select();
          
    }
    public function getIntegrallogAllCount($map)
    {
        return Db::name('member_integral_log')->where($map)->count();
    }
    
       /**
     * [查询用户提现明细]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
     public function getTixianlogByWhere($map, $Nowpage, $limits)
    {
  		return Db::name('member_tixian')
                   	->where($map)->page($Nowpage, $limits)
                   	->order('create_time desc')
                   	->select();
          
    }
    
    public function getTixianlogAllCount($map)
    {
        return Db::name('member_tixian')->where($map)->count();
    }


	
	
}