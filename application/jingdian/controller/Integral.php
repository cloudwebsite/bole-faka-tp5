<?php
namespace app\jingdian\controller;
use app\jingdian\model\IntegralModel;
use app\jingdian\model\BaseModel;
use think\Config;
use think\Loader;
use org\Verify;
use think\Db;

class Integral extends Base
{ 
    
    public function _initialize(){
        parent::_initialize();
        $integral=new IntegralModel();
    	$result=$integral->getnavigation();  	
        //积分商城导航
        $this->assign('jfnav', $result);

        $basemodel = new BaseModel();
        $result= $basemodel->getJFpcbanner();
        //积分商城导航
        $this->assign('jfpcbanner', $result);
    }

    
    public function index(){
    	$integral=new IntegralModel();
    	$result=$integral->getAllIntegral();
    	//商品列表详细信息
        $this->assign('GoodsListAll', $result); 
        return $this->fetch();            
    }
    
    /*
     * 商品详情
     */
    public function goodsdetail(){
    	$id=input('param.id');
    	$integral=new IntegralModel();
    	$result=$integral->getIntegralByid($id);
    	if($result['code']!=1){
        	$this->assign('errormsg', $result['msg']);
	 		return $this->fetch('error');
        }
        $ordersellcount=$integral->getAllIntegralOrdersellercount();
        $this->assign('param', $result['data']);
        $this->assign('ordersellcount', $ordersellcount);
    	return $this->fetch(); 
    }
    
    /*
     * 订单
     */
    public function order(){
    	$param=inputself();
    	if(!session('useraccount')){
    		$this->error('请先登录',url('jingdian/user/index'));
    	}
    	$order=Db::name('integralmall_order')->where('orderno',$param['orderno'])->find();
    	if(!$order){
    		$this->error('异常错误');  		
    	}
    	if($order['mstatus']==2){
    		$integral=Db::name('integralmall_index')->where('id',$order['mflid'])->find();
    		$attach=[];
    		if($integral['type']==1){
    			 //附加选项
		        $sql="SELECT * FROM think_attach where attachgroupid=:attachgroupid";
				$attach = Db::query($sql,['attachgroupid'=>trim($integral['attachgroupid'])]);
					
    		}else{
    			$count=Db::name('mail')->where(['mpid'=>$integral['mflid'],'mis_use'=>0])->count();
    			if($count<=0){
    				$this->error('暂无库存');
    			}
    		}
    		$integral=replaceImgurl($integral);
    		$this->assign('attach', $attach);
    		$this->assign('order', $order);
        	$this->assign('integral', $integral);
        	return $this->fetch();    		
    	}else{
    		return $this->redirect(url('@jingdian/integral/fenxiang',['order'=>$param['orderno']]),302);
    	}  	
    }
    
    /*
     * 积分支付
     */ 
    public function Payintegral(){
    	$param=inputself();
    	if(!session('useraccount.id')){
    		return $this->redirect(url('@jingdian/user/index'),302);
    	}
    	
    	$integral=new IntegralModel();
    	$result=$integral->Payintegral($param);
    	dump($result);
    	if($result['code']==1){
    		return $this->redirect(url('@jingdian/integral/getgift',['orderno'=>$param['orderno']]));
    	}else{
    		$this->assign('errormsg', $result['msg']);
	 		return $this->fetch('error');
    	}   		
    }
    
    /*
     * 创建订单
     */
    public function createorder(){
    	$param=inputself();
    	$integral=new IntegralModel();
    	$result=$integral->createorder($param);
    	return json(['code'=>$result['code'],'msg'=>$result['msg'],'url'=>$result['url']]);   	   	
    }
    
    /*
     * 提取兑换商品
     */
    public function getgift(){
		if(!session('useraccount.id')){
    		return $this->redirect(url('@jingdian/user/index'),302);
    	}
    	$param=inputself();	        	    	
	    $integral=new IntegralModel();
	    $result=$integral->getGift($param);
	    if($result['code']==1){
        	$this->assign('html', $result['data']);
        	return $this->fetch('cardlist'); 
	    }elseif($result['code']==-2){
        	return $this->redirect(url('jingdian/integral/fenxiang',['order'=>$param['orderno']]),302);
	    }else{
	    	$this->assign('errormsg', $result['msg']);
	 		return $this->fetch('error');
	    }
	    	
    }
    
    /*
     * 查看缓存卡密
     */
    public function fenxiang(){
		$order=input('param.order');
		$contents="";
		$uf='uploadintegral/'.$order.'.txt';
		if(file_exists($uf)){
		  $contents=file_get_contents($uf);
		}else{
			$integralorder=Db::name('integralmall_order')->where('orderno',$order)->find();
			if($integralorder){
				$integralindex=Db::name('integralmall_index')->where('id',$integralorder['mflid'])->find();
				if($integralindex){
					if($integralindex['type']==1){
						$this->assign('integralorder', $integralorder);
						$this->assign('integralindex', $integralindex);
	 					return $this->fetch('cardlist');
					}
				}
			}
			$contents= '文件已删除';			
		}
		$checkBom =checkBOM($uf);
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
		$zhanshitext=$tou."<hehe id='zhengwen'>".$zhengwen."</hehe>".$wei;
		$zhanshitext=str_replace("\r","<br>",$zhanshitext);
		$this->assign('html', $zhanshitext);
		$this->assign('zhengwen', $zhengwen);
		$this->assign('order', $order);
	  	return $this->fetch('cardlist');
	}
	
	
	
	/*
     * 模糊查找商品
    */
	public function goodsByName(){
	    $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['mname|mnamebie'] = ['like',"%" . $key . "%"];          
        }      
        $integral=new IntegralModel();
	    $result=$integral->goodsByName($map); 	     
        $this->assign('GoodsListAll', $result);
	    $this->assign('val', $key); 
      return $this->fetch('goodsbyname');
	}
}
