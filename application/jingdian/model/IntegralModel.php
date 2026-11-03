<?php
namespace app\jingdian\model;
use think\Model;
use think\Db;

class IntegralModel extends Model
{

	protected $name = 'integralmall_order';  
    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳
    /**
     * 获取所有商品信息带分组信息
     */
	public function getAllIntegral($map=[]){		
		$integralindex=Db::name('integralmall_index')->where(['status'=>1])->order('sort')->select();
		$integralgroup=Db::name('integralmall_group')->where(['status'=>1])->where($map)->order('sort')->select();
		$newlist = array();
        foreach ($integralgroup as $key => $val) {
            $newlist[] = $val;
        }
        foreach ($newlist as $key => $vv) {
            foreach ($integralindex as $k => &$vo) {
            	 
                if ($vv['id'] == $vo['mlm']) {
                    $vo=replaceImgurl($vo);
                    $newlist[$key]['goods'][] = $vo ?: '无';                       
                }
            }
            if(empty($newlist[$key]['goods'])){
            	$newlist[$key]['goods']=[];
            }          
        }
        foreach ($newlist as &$v) {          
            $v=replaceImgurl($v);               
        }
        return $newlist;
		
	}
	
	
	/**
     * 根据商品标题模糊查询
     */
	public function goodsByName($map){
		$integralindex=Db::name('integralmall_index')->where($map)->where(['status'=>1])->order('sort')->select();		
        foreach ($integralindex as &$v) {          
            $v=replaceImgurl($v);               
        }
        return $integralindex;		
	}
	
	/*
	 * 获取指定商品信息
	 */
    public function getIntegralByid($id){
    	$integralindex=Db::name('integralmall_index')->where(['id'=>$id])->find();
    	if(!$integralindex){
    		$code=-1;
    		$msg='该商品已经删除';
    	}elseif($integralindex['status']==0){
    		$code=-1;
    		$msg='该商品已下架';
    	}else{
    		$code=1;
    		$msg='查询成功';
    		if($integralindex['type']==1){
	            $count = mt_rand(100,999);   	
	        }else{
	            $count=Db::name('mail')->where(['mpid'=>$integralindex['mflid'],'mis_use'=>0])->count();
	        }	        	
    		$integralindex['count']=$count;  		
    		$integralindex=replaceImgurl($integralindex); 
    	}
        
                             
    	$data=['code'=>$code,'msg'=>$msg,'data'=>$integralindex];
    	return $data;
    }
    
    /*
     * 获取所有商品信息不带分组信息，销量排序
     */
    public function getAllIntegralOrdersellercount(){
		$integralindex=Db::name('integralmall_index')->where(['status'=>1])->order('sellercount desc')->select();		
        foreach ($integralindex as &$v) {          
            $v=replaceImgurl($v);               
        }
        return $integralindex;		
	}
	
	/*
	 * 全部分类
	 */
	public function getAllcategory(){
		$integralgroup=Db::name('integralmall_group')->where(['status'=>1])->order('sort desc')->select();
		foreach ($integralgroup as &$v) {          
            $v=replaceImgurl($v);               
        }
		return $integralgroup;
	}
	
    
    /*
     * 创建订单
     */
    public function createorder($param){
    	if(!session('useraccount')){
    		$code=-1;
    		$msg="请先登录,是否去登录？";
    		if(isMobilePc()){
    			$url=url("@mobile/user/index")."?former_url=".url('mobile/integral/goodsdetail',['id'=>$param['mpid']]);
    		}else{
    			$url=url("@jingdian/user/index")."?former_url=".url('jingdian/integral/goodsdetail',['id'=>$param['mpid']]);
    		}
    		
    		return ['code'=>$code,'msg'=>$msg,'url'=>$url];
    	}
    	$user=Db::name('member')->where('id',session('useraccount.id'))->find();
    	if(!$user){
    		$code=-2;
    		if(isMobilePc()){
    			$url=url("@mobile/user/index")."?former_url=".url('mobile/integral/goodsdetail',['id'=>$param['mpid']]);
    		}else{
    			$url=url("@jingdian/user/index")."?former_url=".url('jingdian/integral/goodsdetail',['id'=>$param['mpid']]);
    		}
    		return ['code'=>$code,'msg'=>$msg,'url'=>$url];
    	}	    	
    	$integralindex=Db::name('integralmall_index')->where('id',$param['mpid'])->find();   	
    	if(!$integralindex){
    		$code=-3;
    		$msg="商品异常错误";
    		
    		if(isMobilePc()){
    			$url=url("@mobile/integral/index");
    		}else{
    			$url=url("@jingdian/integral/index");
    		}
    		return ['code'=>$code,'msg'=>$msg,'url'=>$url];
    	}
    	if($user['integral']<$param['buynum']*$integralindex['mprice']/100){
    		$code=-4;
    		$msg="积分不足，当前积分".$user['integral']."需要积分".$param['buynum']*$integralindex['mprice']/100;
    		$url='';
    		return ['code'=>$code,'msg'=>$msg,'url'=>$url];	
    	}
    	
    	$orderno=createOrder();
    	$data=['orderno'=>$orderno,
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
    	if(!$result){
    		$code=-5;
    		$msg="创建订单失败";
    		$url='';
    		return ['code'=>$code,'msg'=>$msg,'url'=>$url];
    	}
    	$code=1;
    	$msg="";
    	if(isMobilePc()){
    		$url=url('mobile/integral/order',['orderno'=>$orderno]);
    	}else{
    		$url=url('jingdian/integral/order',['orderno'=>$orderno]);
    	}
    	
    	return ['code'=>$code,'msg'=>$msg,'url'=>$url];
    }
    
      /*
     * 积分支付
     */ 
    public function Payintegral($param){
    	if(session('useraccount.id')){
    		     	    	
	    	//开启事务
	    	Db::startTrans();
	    	try
	    	{	    						
				//获取订单信息
				$sql="select * from think_integralmall_order where orderno=:orderno and mstatus=2 for update";
				$ordermcard=Db::query($sql,['orderno'=>$param['orderno']]);
				if($ordermcard==false){
	    			// 回滚事务
	    			Db::rollback();	    			
	    			$msg='订单号不存在';
	    			$code=-1;
	      			return ['code'=>$code,'msg'=>$msg];
	    		}
	    		
				$sql="select * from think_member where id=:id for update";
				$hasUser=Db::query($sql,['id'=>session('useraccount.id')]);
		    	if($hasUser[0]['integral']<$ordermcard[0]['money']){
		    		// 回滚事务
	    			Db::rollback();	
		    		$msg='积分不足';
		      		$code=-2;
	      			return ['code'=>$code,'msg'=>$msg];
		    	}
		    	
		    	//更新订单状态
	    		$sql="update think_integralmall_order set mstatus=0 where orderno=:orderno and mstatus=2";
	    		$bool = Db::execute($sql,['orderno'=>$param['orderno']]);    		
	    		
	    		if($bool==false)
	    		{
	    			// 回滚事务
	    			Db::rollback();
	    			$msg='更新订单状态失败';
	      			$code=-3;
	      			return ['code'=>$code,'msg'=>$msg];  			                  
	      		}
	      		
	      		//判断数量
	      		$sql="select * from think_integralmall_index where id=:id";
	      		$integralindex=Db::query($sql,['id'=>$ordermcard[0]['mflid']]);
				$paymoney=$ordermcard[0]['money'];
				$jisuanmoney=$integralindex[0]['mprice']*$ordermcard[0]['buynum'];
				if($paymoney-$jisuanmoney>0.01){
					// 回滚事务
	    			Db::rollback();
	    			$msg='订单被篡改';
	      			$code=-3;
	      			return ['code'=>$code,'msg'=>$msg];
				}
	    		//更新用户余额
	    		$sql="update think_member set integral=integral-:integral where id=:id";
	    		$bool = Db::execute($sql,['integral'=>$ordermcard[0]['money'],'id'=>session('useraccount.id')]);
	    		if($bool==false)
	    		{
	    			// 回滚事务
	    			Db::rollback();	    			
	    			$msg='更新用户积分失败';
	      			$code=-4;
	      			return ['code'=>$code,'msg'=>$msg];			                  
	      		}
	      		//记录积分log
	    		writeintegrallog(session('useraccount.id'),"积分兑换：".$param['orderno'],1,$ordermcard[0]['money']);
	      		
	      		
	    	}
	    	catch(\Exception $e){
					// 回滚事务
	                Db::rollback();
	                $msg=$e->getMessage();
	                $msg=str_replace('\'','' ,$msg);
	                $msg=str_replace('\"','' ,$msg);
	                $code=-1;
	      			return ['code'=>$code,'msg'=>$msg];
	    	}
	    	Db::commit();
	    	return ['code'=>1,'msg'=>'success'];	
		}else{
			$msg='请先登录';
			$code=-1;
	      	return ['code'=>$code,'msg'=>$msg];
		}	
    }
    
    
    /*
     * 提取兑换商品
     */
    public function getGift($param){
    	if(session('useraccount.id')){       	    	
	    	$msg='';
        	$code=1;
	    	//开启事务
	    	Db::startTrans();
	    	try
	    	{	    						
				
				//获取订单信息
				$sql="select * from think_integralmall_order where orderno=:orderno and mstatus=0 for update";
				$ordermcard=Db::query($sql,['orderno'=>$param['orderno']]);
				if($ordermcard==false){
	    			// 回滚事务
	    			Db::rollback();
	    			$code=-2;	    			
	    			$msg='订单号不存在或已使用';
	      			return ['code'=>$code,'msg'=>$msg];
	    		}
	
		    	//更新订单状态
	    		$sql="update think_integralmall_order set mstatus=1 where orderno=:orderno and mstatus=0";
	    		$bool = Db::execute($sql,['orderno'=>$param['orderno']]);    			    		
	    		if($bool==false)
	    		{
	    			// 回滚事务
	    			Db::rollback();
	    			$msg='更新订单状态失败';
	      			$code=-1;	    			
	      			return ['code'=>$code,'msg'=>$msg];  			                  
	      		}
	      		//出货
	      		$sql="select * from think_integralmall_index where id=:id";
	      		$integralindex=Db::query($sql,['id'=>$ordermcard[0]['mflid']]);
	      		if($integralindex==false){
	    			// 回滚事务
	    			Db::rollback();	    			
	    			$msg='商品查询失败';
	    			$code=-1;
	      			return ['code'=>$code,'msg'=>$msg];
	    		}
	    		//更新销量
	    		$sql = "update think_integralmall_index set sellercount=sellercount+:sellerc where id=:id";
	           	$updatseller = Db::execute($sql,['sellerc'=>$integralindex[0]['sendbeishu']*$ordermcard[0]['buynum'],
	           									'id'=>$ordermcard[0]['mflid']
	           									]);
	    		if($updatseller==false)
	    		{
	    			// 回滚事务
	                   Db::rollback();	                    
	                   $msg='更新商品销量出错001';
	                   $code=-1;
	      		       return ['code'=>$code,'msg'=>$msg];
	   			}
	    		if($integralindex[0]['type']==0){
	    			//自动发货
	    			$sql="select * from think_mail where mpid=:mpid and mis_use=0 order by id asc limit :beishu for update";
					$maildata =Db::query($sql,['mpid'=>$integralindex[0]['mflid'],
											'beishu'=>$integralindex[0]['sendbeishu']*$ordermcard[0]['buynum']
										 ]);
									 
	    			if($maildata==false)
	    			{
	    				// 回滚事务
	                    Db::rollback();	                 
	                    $msg='提取卡密出错01';
	      				$code=-1;
	      				return ['code'=>$code,'msg'=>$msg];
	    			}
	    			
	    			$file="";
	                $html="";
	                $decrypt=$integralindex[0]['decrypt'];
	                if($decrypt==0){
	                	foreach ($maildata as $v) 
		                {
		                	$mails[] = $v['musernm'];
		                    $ids[] = $v['id'];
		                    $file .= $v['musernm'];
		                    $file .= "\r\n";
		                    $html .=htmlspecialchars($v['musernm'],ENT_QUOTES,"UTF-8");
		                    $html .= '<br/>';
		                }
	                }elseif($decrypt==1){
	                	foreach ($maildata as $v) 
		                {
		                	$mails[] =passport_decrypt($v['mpasswd'],DECRYPT_KEY);
		                    $ids[] = $v['id'];
		                    $file .= passport_decrypt($v['mpasswd'],DECRYPT_KEY);
		                    $file .= "\r\n";
		                    $html .=htmlspecialchars(passport_decrypt($v['mpasswd'],DECRYPT_KEY),ENT_QUOTES,"UTF-8");
		                    $html .= '<br/>';
		                }
	                }
	                
	               
	                
		            if(count($ids)!=$integralindex[0]['sendbeishu']*$ordermcard[0]['buynum']){
		              	// 回滚事务
		                Db::rollback();		                 
		                $msg='请检查商品库存,异常代码(001)';	
		                $code=-1;
	      				return ['code'=>$code,'msg'=>$msg];	                    		                    
		            }    
	    			$arr_string = join(',', $ids);
	                $sql = "update think_mail set mis_use=1,update_time=:update_time,syddhao=:order where id in(".$arr_string.")";
	                $updatemail = Db::execute($sql,['update_time'=>time(),
	                								'order'=>$param['orderno']
	                								]);
	    			if($updatemail==false)
	    			{
	    				// 回滚事务
	                    Db::rollback();	                    
	                    $msg='更新邮箱状态出错001';
						$code=-1;
	      				return ['code'=>$code,'msg'=>$msg];
	
	    			}
	    			
	    			
	    			$html="<hehe id='zhengwen'>".$html."</hehe>";
		    		$fileemail=$file;
		            $toutext=!empty($integralindex[0]['kamitou'])?$integralindex[0]['kamitou']:config('kami_tou');
		            $weitext=!empty($integralindex[0]['kamiwei'])?$integralindex[0]['kamiwei']:config('kami_wei');
		            if(!empty($toutext))
		            {
		                $file="<pretou>".$toutext."\r\n"."</pretou>".$file;
		                $html=$toutext."<br/>".$html;
		            }
		            if(!empty($weitext))
		            {
		                $file.="<prewei>"."\r\n".$weitext."</prewei>";
		                $html.="<br/>".$weitext;
		            }
					
					$bool=file_exists('uploadintegral/'.$param['orderno'].'.txt');
					
					if($bool){
						// 回滚事务
	                    Db::rollback();                  
	                    $msg='异常的错误002';
	                    $code=-1;
	                    return TyReturn($msg,$code);
					}
		            $sor = fopen('uploadintegral/'.$param['orderno'].'.txt',"x");
	
		            if(!$sor){
		            	// 回滚事务
	                    Db::rollback();                  
	                    $msg='异常错误003';
	                    $code=-1;
	                    return TyReturn($msg,$code);
		            }
		            $fwbool=fwrite($sor,$file);
		            fclose($sor);
		            
				
	    		}else{
	    			//手动发货
	    			$html="<span class='pull-left pt26 f-success-icon' style='margin-top:0px'></span><span class='pull-left pl20'><p></p><h3>兑换成功系统将尽快为您发货！</h3><p></p><p>商品名称：".$integralindex[0]['mnamebie']."</p><p>订单编号：".$ordermcard[0]['orderno']."</p></span>";
	    			
	    		}
	      		
	      		
	      		
	    		
	      		
	      		
	    	}
	    	catch(\Exception $e){
					// 回滚事务
	                Db::rollback();
	                $msg=$e->getMessage();
	                $msg=str_replace('\'','' ,$msg);
	                $msg=str_replace('\"','' ,$msg);
	                $code=-1;
	      			return ['code'=>$code,'msg'=>$msg];
	    	}
	    	Db::commit();
	    	return TyReturn($msg,$code,$html);
		}
    }
    
    /*
     * 获取导航信息
     */
    public function getnavigation(){
    	return Db::name('navigation')->where(["status"=>1,'groupid'=>1])->order("sort asc")->limit(10)->select();
    }
    
    
    /**
     * [getAllArticle 根据订单号或联系方式分页查询]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
     public function getOrderByWhere($map, $Nowpage, $limits)
    {
  		$result=$this->field("think_integralmall_order.*,IFNULL(think_integralmall_index.mname,'未知') as name,IFNULL(think_integralmall_index.imgurl,'') as imgurl,IFNULL(think_integralmall_index.yunimgurl,'') as yunimgurl,think_integralmall_index.type as type")
  					->join('think_integralmall_index','think_integralmall_index.id = think_integralmall_order.mflid','LEFT')
                   	->where($map)->page($Nowpage, $limits)
                   	->order('think_integralmall_order.id desc')
                   	->select();
        
        foreach ($result as &$v) {          
            $v=replaceImgurl($v);               
        }
        return $result;           	
          
    }
    
    public function getAllCount($map)
    {
        return $this->where($map)->count();
    }
}