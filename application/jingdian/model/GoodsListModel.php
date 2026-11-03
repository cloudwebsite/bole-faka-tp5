<?php
namespace app\jingdian\model;
use think\Model;
use think\Db;

class GoodsListModel extends Model
{


    /**
     * 获取商品信息（不加分类）
     */
    public function getGoods()
    {
		$data_mail = Db::query("SELECT count(mis_use) as count,mpid from think_mail where mis_use=0 GROUP BY mpid");
		$data_yiyong = Db::query("SELECT count(mis_use) as yiyong,mpid from think_mail where mis_use=1 GROUP BY mpid");
		$data_fl = Db::query("SELECT * from think_fl where `status`=1 ORDER BY sort");
        $result = array();
        foreach ($data_mail as $val) {
            $result[$val['mpid']] = $val['count'];
        }
        
        $used = array();
        foreach ($data_yiyong as $val) {
            $used[$val['mpid']] = $val['yiyong'];
        }
        
        if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){
        	$data_child_fl=Db::query("SELECT * from think_child_fl where `status`<>0 and memberid=:memberid ORDER BY sort",['memberid'=>session('child_useraccount.id')]);
	        if(count($data_child_fl)==0){
	        	$data_fl=[];
	        }else{       	
	        	$temp_child_fl=[];
	        	foreach ($data_child_fl as $key=>$val) {
	        	     foreach ($data_fl as &$vfl) {
	        	     		     	
	        	     	if($vfl['id']==$val['goodid']){ 
	        	     		         	     		
	        	     		$tempval=replaceChild($val,$vfl);	        	     		
	        	     		$temp_child_flp[]=$tempval;
	        	     	}
	        	     	
	        	     }	
	        	}
	        	$data_fl=$temp_child_flp;
	        	
	        }
        }
        
        
        foreach ($data_fl as &$v) {
            if(!empty($result[$v['id']])){
            	$v['count'] = $result[$v['id']];
            }else{
            	if($v['type']==1){
	            	$v['count'] = mt_rand(100,999);   	
	            }else{
	            	$v['count'] = 0; 
	            }
            }
            
            
             if(!empty($used[$v['id']])){
            	$v['yiyong'] = $used[$v['id']];            	
            }else{
            	$v['yiyong'] = 0;
            }
            
            $v=replaceImgurl($v);                    
        }
		
        return $data_fl;


	}
	
	
	 /**
     * 根据商品名称模糊查询商品信息（不加分类）
     */
    public function getGoodsBykey($key)
    {
    		
	      $data_fl = Db::query("SELECT * from think_fl where `status`=1 and (mname like :key or mnamebie like :key2) ORDER BY sort",['key'=>'%'.$key.'%','key2'=>'%'.$key.'%']);
	      $ids = array();
	      foreach ($data_fl as $val) { 
	            $ids[] = $val['id'];
	      }
        if(count($ids)>0){
            $result = array();
            $used = array();
            foreach ($ids as $val) {
                $data_mail = Db::query("SELECT count(mis_use) as count,mpid from think_mail where mis_use=0 and mpid in($val)");
                $data_yiyong = Db::query("SELECT count(mis_use) as yiyong,mpid from think_mail where mis_use=1 and mpid in($val)"); 
                $result[$val] = $data_mail[0]['count'];
                $used[$val] = $data_yiyong[0]['yiyong'];
            }
            foreach ($data_fl as &$v) {
                if(!empty($result[$v['id']])){
                  $v['count'] = $result[$v['id']];
                }elseif($v['type']==1){
                  $v['count'] = mt_rand(100,999);
                }else{
                  $v['count'] = 0;
                }
                
                 if(!empty($used[$v['id']])){
                  $v['yiyong'] = $used[$v['id']];            	
                }else{
                  $v['yiyong'] = 0;
                }
                
                $v=replaceImgurl($v);                            
            } 
        }
        return $data_fl;
	}
	
	
	
	
	 /**
     * 获取商品基本信息（不加分类不加库存）
     */
    public function getAllGoodsName()
    {
    		
	    $data_fl = Db::query("SELECT * from think_fl where `status`=1 ORDER BY sort");        
        if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){
        	$data_child_fl = Db::query("SELECT * from think_child_fl where memberid=:memberid and status<>0 ORDER BY sort",['memberid'=>session('child_useraccount.id')]);
        	if(count($data_child_fl)==0){
        		$data_fl=[];
        	}else{
        		$temp_child_flp=[];
        		foreach ($data_child_fl as $key=>$val) {
	        	     foreach ($data_fl as &$vfl) {
	        	     		if($vfl['id']==$val['goodid']){ 	        	     			
	        	     			$tempval=replaceChild($val,$vfl);	        	     		
	        	     			$temp_child_flp[]=$tempval;
	        	     			        	     			
	        	     		}
	        	     }
	        	}
	        	$data_fl=$temp_child_flp;
	        	
        	}	 
        }
        foreach ($data_fl as &$v) {          
            $v=replaceImgurl($v);               
        }
       
        return $data_fl;


	}
	
	 /**
     * 获取指定商品基本信息（不加分类不加库存）
     */
    public function getGoodsByName($map)
    {
    		
	    $data_fl = db('fl')->where($map)->where(['status'=>1])->order('sort asc')->select();    
        if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){
        	$data_child_fl = Db::query("SELECT * from think_child_fl where memberid=:memberid and status<>0 ORDER BY sort",['memberid'=>session('child_useraccount.id')]);
        	if(count($data_child_fl)==0){
        		$data_fl=[];
        	}else{
        		$temp_child_flp=[];
        		foreach ($data_child_fl as $key=>$val) {
	        	     foreach ($data_fl as &$vfl) {
	        	     		if($vfl['id']==$val['goodid']){ 	        	     			
	        	     			$tempval=replaceChild($val,$vfl);	        	     		
	        	     			$temp_child_flp[]=$tempval;
	        	     			        	     			
	        	     		}
	        	     }
	        	}
	        	$data_fl=$temp_child_flp;
	        	
        	}	 
        }
        
        foreach ($data_fl as &$v) {          
            $v=replaceImgurl($v);  
        }
       
        return $data_fl;


	}
	
	/**
     * 根据分类ID获取所有商品信息
     */
    public function getShopBylmid($lmid)
    {   	 
   		$data_fl = db('fl')->where(array('status' => 1,'mlm'=>$lmid))->order('sort asc')->select();
   		if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){
        	$data_child_fl = Db::query("SELECT * from think_child_fl where memberid=:memberid and status<>0 ORDER BY sort",['memberid'=>session('child_useraccount.id')]);
        	if(count($data_child_fl)==0){
        		$data_fl=[];
        	}else{
        		$temp_child_flp=[];
        		foreach ($data_child_fl as $key=>$val) {
	        	     foreach ($data_fl as &$vfl) {
	        	     		if($vfl['id']==$val['goodid']){ 	        	     			
	        	     			$tempval=replaceChild($val,$vfl);	        	     		
	        	     			$temp_child_flp[]=$tempval;
	        	     			        	     			
	        	     		}
	        	     }
	        	}
	        	$data_fl=$temp_child_flp;
	        	
        	}	 
        }
   		
   		foreach ($data_fl as &$v) {  
             $v=replaceImgurl($v);
        }
        return $data_fl;
	}
	
	
	
	
	 /**
     * 获取全部商品信息
     */
    public function getAllGoods($data_fl)
    {
  		$lmlist = db('category_group')->where(array('status' => 1))->order('sort asc')->select();
        $newlist = array();
        foreach ($lmlist as $key => $val) {
            $newlist[] = $val;
        }
        foreach ($newlist as $key => $vv) {
            foreach ($data_fl as $k => $vo) {
                if ($vv['id'] == $vo['mlm']) {
                    $newlist[$key]['hehe'][] = $vo ?: '无'; 
                       
                }
            }
            	if(empty($newlist[$key]['hehe']))
            	{
            		$newlist[$key]['hehe']=[];
            	}
           
        }
        foreach ($newlist as &$v) {          
            $v=replaceImgurl($v);               
        }

        return $newlist;


	}
	
	

	 /**
     * 获取指定商品信息
     */
    public function getAllGoodsByid($data_fl,$lmid)
    {
  		$lmlist = db('category_group')->where(array('status' => 1,'id'=>$lmid))->order('sort asc')->select();
        $newlist = array();
        foreach ($lmlist as $key => $val) {
            $newlist[] = $val;
        }
        foreach ($newlist as $key => $vv) {
            foreach ($data_fl as $k => $vo) {
                if ($vv['id'] == $vo['mlm']) {
                    $newlist[$key]['hehe'][] = $vo ?: '无'; 
                       
                }
            }
            	if(empty($newlist[$key]['hehe']))
            	{
            		$newlist[$key]['hehe']=[];
            	}
            	
        }
        foreach ($newlist as &$v) {          
            $v=replaceImgurl($v);               
        }

        return $newlist;


	}
	
	 /**
     * 获取全部商品分类信息
     */
    public function getAlllms()
    {
  		$lmlist = db('category_group')->where(array('status' => 1))->order('sort asc')->select();
        foreach ($lmlist as &$v) {          
            $v=replaceImgurl($v);               
        }
        return $lmlist;
	}
	 /**
     * 获取指定商品分类信息
     */
    public function getlmById($lmid)
    {
  		$lmlist = db('category_group')->where(array('status' => 1,'id'=>$lmid))->order('sort asc')->select();
        foreach ($lmlist as &$v) {          
            $v=self::replaceImgurl($v);               
        }
        return $lmlist;
	}
	
	
	 /**
     * 根据商品ID取得类目信息
     */
    public function getlmByMpid($mpid)
    {
  		$lmlist = db('category_group')->where(array('status' => 1,'id'=>$lmid))->order('sort asc')->select();
        foreach ($lmlist as &$v) {          
            $v=replaceImgurl($v);               
        }
        return $lmlist;
	}
	
	
	
	
	
	/**
     * 获取指定商品优惠信息
     */
	public function shopyh($param)
 	{	
        $code=1;
        $msg='获取成功';
        $mpid=isset($param['p5_Pid'])?$param['p5_Pid']:$param['mpid'];
        $data_fl = Db::query("select * from think_fl where id=:id",['id'=>trim($mpid)]);
        $data_yh=[];
        if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){
        	$data_child_fl = Db::query("select * from think_child_fl where goodid=:goodid and memberid=:memberid",['goodid'=>trim($mpid),'memberid'=>session('child_useraccount.id')]);        	
        	if(!$data_child_fl){
        		$data_fl=[];
        	}else{
        		
        		$data_fl[0]=replaceChild($data_child_fl[0],$data_fl[0]);
        		if($data_child_fl[0]['mprice']==-1){
        			$data_yh=db('yh')->where('mpid',trim($mpid))->order('mdy asc')->select();
        		}
        	}  
        }else{
        	$data_yh=db('yh')->where('mpid',trim($mpid))->order('mdy asc')->select();
        }
       
        
          
        
       
        if(count($data_fl)==0){
        	$code=-1;
        	$msg='该商品已删除';
        }else{
        	$status=$data_fl[0]['status'];
        	if($status==0){
        		$code=-2;
        		$msg='该商品已下架';
        	}else{
        		$minprice=$data_fl[0]['mprice'];
		        $mlmid=$data_fl[0]['mlm'];		                
            	$data_fl[0]=replaceImgurl($data_fl[0]);                      
		        
		        $lmlist = db('category_group')->where(array('status' => 1,'id'=>$mlmid))->order('sort asc')->select();
		        if(empty($data_yh))
		        {   
		            $param['isyh']=0;
		        }else
		        {
		            $minprice=db('yh')->where('mpid',trim($mpid))->limit(1)->order('mdy desc')->select();
		            $param['isyh']=1;
		        }
		        $param['data_yh']=$data_yh;
        		$param['data_fl']=$data_fl;
        		$param['data_category']=$lmlist;
        		$param['minprice']=$minprice;
        	}    	
	        
        }
        
        $param['code']=$code;
        $param['msg']=$msg;
        
      	return $param;      
    }
    
    /**
     * 获取指定商品库存信息
     */
	public function goodscount($param)
 	{	
        $mpid=isset($param['p5_Pid'])?$param['p5_Pid']:$param['mpid'];       
        $data_fl = Db::query("select * from think_fl where id=:mpid",['mpid'=>trim($mpid)]);
        if($data_fl[0]['type']==0){
        	$data_mail = Db::query("SELECT count(mis_use) as count,mpid from think_mail where mis_use=0 and mpid=:mpid",['mpid'=>trim($mpid)]); 
        	$param['mail']['count']=$data_mail;
        }else{
			$param['mail']['count'][0]['count']=mt_rand(100,999);
        }
        
        
        //$param['mail']['yiyong']=$data_yiyong;
      	return $param;      
    }
    
    
    /**
     * 获取所有优惠信息
     */
	public function shopAllyh()
 	{
		$data_yh=db('yh')->order('mdy asc')->select(); 
      	return $data_yh;      
   	}
   	
   	
   	/**
     * 获取所有附加选项信息
     */
	public function shopAllattach()
 	{
		$data_attach=db('attach')->order('id asc')->select(); 
      	return $data_attach;      
   	}
   	
   	/**
     * 获取指定IDattach
     */
	public function shopAttachById($id)
 	{
		$data_attach=db('attach')->where('id',$id)->find(); 
      	return $data_attach;      
   	}
   	
   	/**
     * 获取指定商品附加选项信息
     */
	public function getShopattachByShopId($mpid)
 	{
		$sql="SELECT * FROM think_attach where attachgroupid in(SELECT attachgroupid from think_fl where id=:id)";
		$resultattach = Db::query($sql,['id'=>$mpid]);
		
      	return $resultattach;      
   	}
   	
   	/*
   	 * 根据商品ID和购买数量计算单价和总价
   	 */
   	public function getBuyMoneyBybuynum($buynum,$mpid){
           $typeprice=4;//批发价格
           $data_fl = Db::query("select * from think_fl where id=:mpid",['mpid'=>$mpid]);
           $childprice=0;
	        if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){
	        	$data_child_fl = Db::query("select * from think_child_fl where goodid=:goodid and memberid=:memberid",['goodid'=>trim($mpid),'memberid'=>session('child_useraccount.id')]);        	
	        	if(!$data_child_fl){
	        		$data_fl=[];
	        	}else{	        		
	        		$data_fl[0]=replaceChild($data_child_fl[0],$data_fl[0]);
	        		if($data_child_fl[0]['mprice']!=-1){
	        			$childprice=1;
	        			$mdj = $data_fl[0]['mprice'];
	                	$typeprice=3;//原始价格
	                	$sfyh=0;
	        		}
	        	}  
	        }
		        	
	        
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
	          	
	          	
	            $sfyh=1;       
	            if($typeprice!=1 && $typeprice!=2){
		          	$data_yh = Db::query("SELECT * from think_yh where mpid=:mpid ORDER BY mdy desc",['mpid'=>trim($mpid)]);
		            //计算单价
		            if(!empty($data_yh)){
		                        foreach ($data_yh as $v) {
		                        	
		                            if($v['mdy'] <= $buynum){
		                                $mdj = $v['mdj'];
		                                break;
		                            }
		                        }
		            }
	                if(!isset($mdj)){
	                	$mdj = $data_fl[0]['mprice'];
	                	$typeprice=3;//原始价格
	                	$sfyh=0;
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
           
             //计算购买金额
            if($buynum*$mdj<1){
            	 $canMoney = number_format($buynum*$mdj/100,4);
            }else{
            	 $canMoney = number_format($buynum*$mdj/100,2);
            }
           
            //库存数量
            if ($canMoney == 0) {
            	$code=-1;
                $msg = "金额不足！";
            } elseif ($buynum < $data_fl[0]['mmin'] || $buynum > $data_fl[0]['mmax']) {
            	$code=-1;
                $msg = '最小提取数量：' . $data_fl[0]['mmin'] . '&nbsp;&nbsp;最大提取数量：' . $data_fl[0]['mmax'];
            }else{
            	  $code=1;
                $msg = 'OK'; 
            }
           
          return ['code'=>$code,'msg'=>$msg,'money'=>$canMoney,'mdj'=>$mdj,'typeprice'=>$typeprice];    
    }
    
    
    
    
}