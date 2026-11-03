<?php
namespace app\jingdian\model;
use think\Model;
use think\Db;

class GoodsSetModel extends Model
{
	protected $name = 'child_fl';  
   	//protected $autoWriteTimestamp = true;   // 开启自动写入时间戳
   	/*
   	 * 
   	 */
	public function getGoodsByWhere($map, $Nowpage, $limits,$childid,$type){
		$varchar='b.*,b.mname as z_mname,b.mprice_bz as z_mprice_bz,b.mprice as z_mprice,b.mnotice as z_mnotice
		,b.imgurl as z_imgurl,b.yunimgurl as z_yunimgurl,b.marketprice as z_marketprice,b.xqnotice as z_xqnotice,b.status as z_status
		,b.sort as z_sort,b.msgboxtip as z_msgboxtip,b.tuijian as z_tuijian,b.hot as z_hot,b.ykongge as z_ykongge
		,b.zkongge as z_zkongge,b.color as z_color,b.kamitou as z_kamitou,b.kamiwei as z_kamiwei';
		if($type!=3){
			$result=$this->field($varchar.',think_child_fl.*')->join('think_fl b','b.id = think_child_fl.goodid','LEFT')
            	->where($map)->where('b.status',1)->where('think_child_fl.memberid',$childid)->page($Nowpage, $limits)->order('think_child_fl.sort asc')->select();
		}else{
			$seachkey='';
			foreach($map as $v=>$val){			
				if(is_array($val)){					
					if(empty($seachkey)){
						
						$seachkey="think_fl.mnamebie like '" .$val[1]."'";
					}else{
						
						$seachkey.=' and '."think_fl.mnamebie like '" .$val[1]."'";
					}
					
				}else{
					if(empty($seachkey)){
						$seachkey=$v.'='.$val;
					}else{
						$seachkey.=' and '.$v.'='.$val;
					}
					
				}
				
				
			}
			
			if(empty($seachkey)){
				$result=Db::query("SELECT *,id as goodid from think_fl where status=1 and id not in(SELECT goodid from think_child_fl where memberid=:memberid) limit ".($Nowpage-1)*$limits.",".$limits,['memberid'=>$childid]);	
			}else{
				$result=Db::query("SELECT *,id as goodid from think_fl  where status=1 and {$seachkey} and id not in(SELECT goodid from think_child_fl where memberid=:memberid) limit ".($Nowpage-1)*$limits.",".$limits,['memberid'=>$childid]);
			}
			
		      foreach($result as &$v){
		        $v=replaceImgurl($v);
		      }
		}
		
       
    	//获取商品原价
    	$shopxq=Db::query("select * from think_fl");
    	//获取会员私密价格       
        $memberprice=Db::query("select * from think_member_price where memberid=:memberid",['memberid'=>$childid]);
        //获取会员分组
        $member=Db::query("select * from think_member where id=:memberid",['memberid'=>$childid]);	           		
	    //获取会员分组价格
	    if(!empty($member)){
	        $membergroupprice=Db::query("select * from think_member_group_price where membergroupid=:membergroupid",['membergroupid'=>$member[0]['group_id']]);
	        //获取分组折扣
			$membergroupdiscount=Db::query("select * from think_member_group where id=:membergroupid",['membergroupid'=>$member[0]['group_id']]);      		        			
	    }
	    //获取批发价格
	    $shopyh=db('yh')->select();
      	if(!empty($shopyh)){
      		$pifaPrice=$shopyh[0]['mdj']/100;
      	}
      	
      	
      	foreach ($result as &$v) {
      		//获取最低价格
      		$ArrayPrice=[];
      		$zhuPrice=0;    //初始化商品零售价格
	        $simiPrice=0; 	//初始化私密价格
	    	$groupPrice=0;	//初始化分组价格
	    	$pifaPrice=0;   //初始化批发价格
	    	$memberdiscount=0; //初始化分组折扣
	    	if(!empty($membergroupdiscount)){
		          $memberdiscount=$membergroupdiscount[0]['discount'];	           					         					           					
		    }
      		if(!empty($memberprice)){
      			foreach ($memberprice as &$vmpirce) {
      				if($v['goodid']==$vmpirce['goodid']){
      					$simiPrice=$vmpirce['price']/100;
      				}
      			}       		
        	}
        	
        	if(!empty($membergroupprice)){
        		foreach ($membergroupprice as &$vmgpirce) {
      				if($v['goodid']==$vmgpirce['goodid']){
      					$groupPrice=$vmgpirce['price']/100;
      				}
      			}     
	        }
	        
	        if(!empty($shopyh)){
        		foreach ($shopyh as &$vshopyh) {
      				if($v['goodid']==$vshopyh['mpid']){
      					if($pifaPrice==0){
      						$pifaPrice=$vshopyh['mdj']/100;
      					}else{
      						$pifaPrice=min($pifaPrice,$vshopyh['mdj']/100);
      					}
      					
      				}
      			}     
	        }
	        
	        if(!empty($shopxq)){
        		foreach ($shopxq as &$vshopxq) {
      				if($v['goodid']==$vshopxq['id']){
      					$zhuPrice=$vshopxq['mprice']/100;     					
      				}
      			}     
	        }
	        if($zhuPrice>0){
	      		$ArrayPrice[]=['price'=>$zhuPrice,'type'=>1];
	      	}
	        if($simiPrice>0){
	      		$ArrayPrice[]=['price'=>$simiPrice,'type'=>2];
	      	}
	      	if($groupPrice>0){
	      		$ArrayPrice[]=['price'=>$groupPrice,'type'=>3];
	      	}
	      	if($pifaPrice>0){
	      		$ArrayPrice[]=['price'=>$pifaPrice,'type'=>4];
	      	}
	      	$minPriceArray=min($ArrayPrice);
	      	$minPrice=$minPriceArray['price'];
	      	
	      	/*
		    * 折扣开始
		    */
		   
			if($memberdiscount>0 && $memberdiscount<100){
			    $somemberdisount=bcdiv($memberdiscount,100,2);
			    $minPrice=bcmul($minPrice,$somemberdisount,4);
			} 
		    /*
		     * 折扣结束
		    */
		   
		   if($minPrice<=0){
		   		$minPrice=9999;
		   }
		   

		   $v['childminPrice']=$minPrice;
		   $v['childminPricetype']=$minPriceArray['type'];
		   $v=self::replaceFzImgurl($v); 
      	
      	}
      	
      	
        return $result;
	}
	
	/**
     * 根据搜索条件获取所有的用户数量
     * @param $where
     */
    public function getAllCount($map,$childid,$type)
    {      
        if($type!=3){
          return $this->join('think_fl b','b.id = think_child_fl.goodid','LEFT')
            	->where($map)->where('b.status',1)->where('think_child_fl.memberid',$childid)->count();
        }else{
          $seachkey='';
			foreach($map as $v=>$val){				
				if(is_array($val)){					
					if(empty($seachkey)){
						
						$seachkey="think_fl.mnamebie like '" .$val[1]."'";
					}else{
						
						$seachkey.=' and '."think_fl.mnamebie like '" .$val[1]."'";
					}
					
				}else{
					if(empty($seachkey)){
						$seachkey=$v.'='.$val;
					}else{
						$seachkey.=' and '.$v.'='.$val;
					}
					
				}
				
				
			}
			
			if(empty($seachkey)){
				$result=Db::query("SELECT *,id as goodid from think_fl where status=1 and id not in(SELECT goodid from think_child_fl where memberid=:memberid)",['memberid'=>$childid]);	
			}else{
				$result=Db::query("SELECT *,id as goodid from think_fl where status=1 and {$seachkey} and id not in(SELECT goodid from think_child_fl where memberid=:memberid)",['memberid'=>$childid]);
			}
          
          return count($result);
        }
        
            	     
    }
    
    
     /*
     * 子站处理图片
     */
   	public function replaceFzImgurl($v){
 		if($v['imgurl']==-1){
 			//继承
 			$v['z_yunimgurl']=str_replace('\\','/' ,$v['z_yunimgurl']);
   			$v['z_imgurl']=str_replace('\\','/' ,$v['z_imgurl']);
	        if(!empty($v['z_yunimgurl'])){
	        	$domain=config('qiniu_domain');
	        	$v['imgurltemp']=$domain.'/'.$v['z_yunimgurl'];
	        	$v['imgurltempnodomain']=$v['z_yunimgurl'];
	            $v['webimgurl']=$v['imgurltemp'];
	            return $v;
	        }
	        if(!empty($v['z_imgurl'])){
	            $domain='/uploads/face/';
	            $v['imgurltemp']=$domain.$v['z_imgurl'];
	            $v['imgurltempnodomain']=$v['z_imgurl'];
	            $v['webimgurl']=$v['imgurltemp'];
	            return $v;
	        }
	        $v['imgurltemp']='/static/admin/images/head_default.gif';
	        $v['imgurltempnodomain']=$v['imgurltemp'];
	        $v['webimgurl']=$v['imgurltemp'];
	        return $v;
 		}else{ 			
	        $v['yunimgurl']=str_replace('\\','/' ,$v['yunimgurl']);
   			$v['imgurl']=str_replace('\\','/' ,$v['imgurl']);
	        if(!empty($v['yunimgurl'])){
	        	$domain=config('qiniu_domain');
	        	$v['imgurltemp']=$domain.'/'.$v['yunimgurl'];
	        	$v['imgurltempnodomain']=$v['yunimgurl'];
	            $v['webimgurl']=$v['imgurltemp'];
	            return $v;
	        }
	        if(!empty($v['imgurl'])){
	            $domain='/uploads/face/';
	            $v['imgurltemp']=$domain.$v['imgurl'];
	            $v['imgurltempnodomain']=$v['imgurl'];
	            $v['webimgurl']=$v['imgurltemp'];
	            return $v;
	        }
	        $v['imgurltemp']='/static/admin/images/head_default.gif';
	         $v['imgurltempnodomain']=$v['imgurltemp'];
	        $v['webimgurl']=$v['imgurltemp'];
	        return $v;
 		}
        
        
        
   	}
	
	 
    	  
}