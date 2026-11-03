<?php
namespace app\jingdian\controller;
use app\jingdian\model\GoodsListModel;
use think\Config;
use think\Loader;
use think\Db;
use com\IpLocationqq;
use com\IpLocation;

class Api extends Base
{
 	/*
 	 * 获取IP物理地址
 	 */
 	public function GetIpAddr(){
 		$userip=trim(input('param.userip'));
 		$Ip = new IpLocationqq('qqwry.dat'); // 实例化类 参数表示IP地址库文件
 		$IpLoca=$Ip->getlocation($userip);
 		return json($IpLoca);
 	}
 	public function GetIpAddr2(){
 		$userip=trim(input('param.userip'));
 		$Ip = new IpLocation('UTFWry2.dat'); // 实例化类 参数表示IP地址库文件
 		$IpLoca=$Ip->getlocation($userip);
 		return json($IpLoca);
 	}

	/*
 	 * 获取商品和分类信息
 	 */
 	public function GetGoodList(){
 		//获取商品信息
        $GoodList=new GoodsListModel();
        $data_fl=$GoodList->getGoods();
        $data_flAll=$GoodList->getAllGoods($data_fl);
        foreach ($data_flAll as $key=>$val) {
          $data_flAll[$key]['loucengname']=strip_tags(str_replace('&nbsp;','',htmlspecialchars_decode($val['loucengname'])));
          foreach ($val['hehe'] as $key2=>$val2) {
          	$data_flAll[$key]['hehe'][$key2]['xqnotice']='';
           $data_flAll[$key]['hehe'][$key2]['mname']=strip_tags(str_replace('&nbsp;','',htmlspecialchars_decode($val2['mname'])));
          }
           
        }
        
        return json($data_flAll);
      
 	}
 	
 	/*
 	 * 添加订单附加选项
 	 */
 	public function AddOrderAttach(){
 		$param=inputself();
 		$param['text']=filterEmoji($param['text']);
 		$sql="INSERT ignore into think_orderattach set orderno=:orderno,attachid=:attachid,text=:text";
 		$attachText=str_replace('\r\n','<br>' ,$param['text']);
 		$bool = Db::execute($sql,['orderno'=>$param['orderno'],'attachid'=>$param['attachid'],'text'=>$attachText]);
 		if($bool){
 			return json(['code'=>1,'msg'=>"添加成功"]);
 		}else{
 			return json(['code'=>-1,'msg'=>"添加失败"]);
 		}
 	}
 	
 	
 	/*
 	 * 根据商品获取附加选项
 	 */
 	public function GetAttachByShopId(){
 		$param=inputself();
 		$sql="SELECT * FROM think_attach where attachgroupid in(SELECT attachgroupid from think_fl where id=:id)";
		$result = Db::query($sql,['id'=>trim(input('param.mpid'))]);

 		if($result){
 			return json(['code'=>1,'msg'=>"成功",'data'=>$result]);
 		}else{
 			return json(['code'=>-1,'msg'=>"失败"]);
 		}
 	}
 	
 	
 	
 	/*
 	 * 添加用户充值订单号
 	 */
 	public function AddPayOrder(){
 		if (config('recharge_kami_only') == 1 || config('recharge_kami_only') === '1') {
 			return json(['code'=>-1,'msg'=>"当前仅支持卡密充值"]);
 		}
 		$param=inputself();
 		$param['order']=isset($param['order'])?$param['order']:'';
 		$param['orderno']=$param['order'];
 		$param['create_time']=time();
 		$param['ip']=getIP();
 		$param['memberid']=empty(session('useraccount.id'))?'0':session('useraccount.id');
 		$param['paytype']=isset($param['paytype'])?$param['paytype']:'0';
 		$param['money']=isset($param['money'])?$param['money']:'0';
 		$sql="insert ignore into think_member_payorder set orderno=:order,outorderno=:orderno,create_time=:create_time,ip=:ip,paytype=:paytype,money=:money,memberid=:memberid";
 		$bool = Db::execute($sql,[
 								'order'=>$param['order'],
 								'orderno'=>$param['orderno'],
 								'create_time'=>$param['create_time'],
 								'ip'=>$param['ip'],			
 								'paytype'=>$param['paytype'],
 								'money'=>$param['money'],
 								'memberid'=>$param['memberid']
 								]);
 		if($bool){
 			return json(['code'=>1,'msg'=>"添加成功"]);
 		}else{
 			return json(['code'=>-1,'msg'=>"添加失败"]);
 		}
 	}
 	
 	/*
 	 * 添加订单号
 	 */
 	public function AddOrder(){
 		$param=inputself();
 		$param['mcard']=isset($param['mcard'])?$param['mcard']:'';
 		$param['morder']=$param['mcard'];
 		$param['create_time']=time();
 		$param['userip']=getIP();
 		$param['lianxi']=isset($param['lianxi'])?$param['lianxi']:'';
 		$param['email']=isset($param['email'])?$param['email']:'';
 		$param['mflid']=isset($param['mflid'])?$param['mflid']:'';//商品id
		$data['p5_Pid']=  $param['mflid'];
		$data['mpid'] = $param['mflid'];
 		$param['buynum']=isset($param['buynum'])?$param['buynum']:'';//购买数量
		$GoodList=new GoodsListModel();		
        $param2=$GoodList->shopyh($data);//获取指定商品优惠信息		
        $goodscount=$GoodList->goodscount($data);//获取指定商品库存	
		if(($param2['data_fl'][0]['sendbeishu']*$param['buynum']) > $goodscount['mail']['count'][0]['count'])
		{
 			return json(['code'=>-1,'msg'=>"超过库存！ 请返回商品页面重新下单"]);
			exit;
		}
		$buymoney=$GoodList->getBuyMoneyBybuynum($param['buynum'],$param['mflid']); //重新计算购买总价
 		$param['openid']=empty(session('openid'))?'':session('openid');
 		$param['memberid']=empty(session('useraccount.id'))?'0':session('useraccount.id');
 		$param['childid']=empty(session('child_useraccount.id'))?'0':session('child_useraccount.id');
 		$param['pid1']=empty(session('userpid'))?'0':session('userpid');
 		$param['cookie']=cookie('tokenid');
 		if($param['memberid']!=="0"){
 			$param['cookie']="";
 			$param['openid']="";
 		}
 		$param['maddtype']=isset($param['maddtype'])?$param['maddtype']:'';
 		//$param['mamount']=isset(abs($param['mamount']))?abs($param['mamount']):'0';
 		$sql="insert ignore into think_info set mcard=:mcard,morder=:morder,create_time=:create_time,userip=:userip,lianxi=:lianxi,email=:email,mflid=:mflid,buynum=:buynum,openid=:openid,cookie=:cookie,maddtype=:maddtype,mamount=:mamount,memberid=:memberid,pid1=:pid1,childid=:childid";
 		$bool = Db::execute($sql,[
 								'mcard'=>$param['mcard'],
 								'morder'=>$param['morder'],
 								'create_time'=>$param['create_time'],
 								'userip'=>$param['userip'],
 								'lianxi'=>$param['lianxi'],
 								'email'=>$param['email'],
 								'mflid'=>$param['mflid'],
 								'buynum'=>$param['buynum'],
 								'openid'=>$param['openid'],
 								'cookie'=>$param['cookie'],
 								'maddtype'=>$param['maddtype'],
 								'mamount'=> abs(str_replace(',', '', $buymoney['money'])),
 								'memberid'=>$param['memberid'],
 								'childid'=>$param['childid'],
 								'pid1'=>$param['pid1']
 								]);
 		if($bool){
 			return json(['code'=>1,'msg'=>"添加成功"]);
 		}else{
 			return json(['code'=>-1,'msg'=>"添加失败"]);
 		}
 	}
 	
 	
 	/*
 	 * 添加积分订单号
 	 */
 	public function AddJfOrder(){
 		$param=inputself();
 		
 		$param['morder']=$param['mcard'];
 		$param['create_time']=time();
 		$param['userip']=getIP();
 		$param['lianxi']=isset($param['lianxi'])?$param['lianxi']:'';
 		$param['email']=isset($param['email'])?$param['email']:'';
 		$param['mflid']=isset($param['mflid'])?$param['mflid']:'';
 		$param['buynum']=isset($param['buynum'])?$param['buynum']:'';
 		$param['openid']=empty(session('openid'))?'':session('openid');
 		$param['memberid']=empty(session('useraccount.id'))?'0':session('useraccount.id');
 		$param['childid']=empty(session('child_useraccount.id'))?'0':session('child_useraccount.id');
 		$param['pid1']=empty(session('userpid'))?'0':session('userpid');
 		$param['cookie']=cookie('tokenid');
 		if($param['memberid']!=="0"){
 			$param['cookie']="";
 			$param['openid']="";
 		}
 		$param['maddtype']=isset($param['maddtype'])?$param['maddtype']:'';
 		$param['mamount']=isset($param['mamount'])?$param['mamount']:'0';
 		
 		$data=['orderno'=>$param['orderno'],
    			'money'=>$param['buynum']*$integralindex['mprice']/100,
    			'buynum'=>$param['buynum'],
    			'mflid'=>$param['mpid'],
    			'create_time'=>time(),
    			'mstatus'=>2,
    			'memberid'=>session('useraccount.id'),
    			'userip'=>getIP(),
    			'cookie'=>cookie('tokenid')   			
    			];
    	$result=Db::name('integralmall_order')->insert($data);						
 		if($result){
 			return json(['code'=>1,'msg'=>"添加成功"]);
 		}else{
 			return json(['code'=>-1,'msg'=>"添加失败"]);
 		}
 	}
 	
}
