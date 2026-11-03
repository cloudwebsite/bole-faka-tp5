<?php
namespace app\jingdian\model;
use think\Model;
use think\Db;

class CommonModel extends Model
{

    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳
    
    
    
    /**
     * 提取邮箱
     */
    public function getMailByNum($mpid,$number,$result)
    {
    	//验证订单号
    	$number=trim($number);
 		$mpid=trim($mpid);
 		
        	$canbuynum=$result['data']['canbuynum'];
        	$sendbeishu=$result['data']['sendbeishu'];
        	$jifen=$result['data']['integral'];
        	$jifen=bcmul($jifen,$canbuynum);
        	$order=$result['data']['data_card']['mcard'];
        	$decrypt=$result['data']['decrypt'];
        	$childid=$result['data']['data_card']['childid'];
        	if($result['data']['data_card']['memberid']!=0){//判断是否会员登录
        		$memberData=Db::name('member')->where('id',$result['data']['data_card']['memberid'])->find();
        		$email=$memberData['email'];
        	}else{
        		$email=$result['data']['data_card']['email'];
        	}
        	
        	$errormsg='';
        	$code=1;
        	
        	//开启事务
    		Db::startTrans();
    		try
    		{
    			//更新订单信息
    			$sql="select * from think_info where (mcard=:mcard or morder=:morder) and mstatus=0 for update";
    			$bool=Db::query($sql,['mcard'=>$number,
    								  'morder'=>$number]);
    			
    			if($bool==false){
    				// 回滚事务
                    Db::rollback();                
                    $errormsg='订单查询失败001';
                    $code=-1;
                    return TyReturn($errormsg,$code);
    			}
    			
    			if($bool[0]['mstatus']!=0){
    				// 回滚事务
                    Db::rollback();                
                    $errormsg='订单查询失败002';
                    $code=-1;
                    return TyReturn($errormsg,$code);
    			}
    							  
    			if(session('useraccount.id') && session('useraccount.account')){//判断是否会员登录
    				$sql = "update think_info set mflid=:mflid,mstatus=1,memberid=:memberid,update_time=:update_time where (mcard=:mcard or morder=:morder) and mstatus=0";
    				$bool = Db::execute($sql,['mflid'=>$mpid,
    										'memberid'=>session('useraccount.id'),
    										'update_time'=>time(),
    										'mcard'=>$number,
    										'morder'=>$number
    										]);
    			}else{
    				$sql = "update think_info set mflid=:mflid,mstatus=1,update_time=:update_time where (mcard=:mcard or morder=:morder) and mstatus=0";
    				$bool = Db::execute($sql,['mflid'=>$mpid,										
    										'update_time'=>time(),
    										'mcard'=>$number,
    										'morder'=>$number
    										]);	
    			}
    			

    			
    			         		          
    			if($bool==false){
    				// 回滚事务
    				 Db::rollback();
    				$errormsg='更新订单状态失败';
    				$code=-1;
    				 return TyReturn($errormsg,$code);			                                    
    			}
    			$sql="SELECT * from think_mail where mpid=:mpid and mis_use=0 ORDER BY id asc LIMIT :beishu for update";
				$maildata =Db::query($sql,['mpid'=>$mpid,
										'beishu'=>$canbuynum*$sendbeishu
									 ]);
								 
    			if($maildata==false)
    			{
    				// 回滚事务
                    Db::rollback();
                 
                    $errormsg='提取邮箱出错001';
                    $code=-1;
                    return TyReturn($errormsg,$code);
    			}
    			$file="";
                $html="";
                if($decrypt==0){
                	foreach ($maildata as $v) 
	                {
	                	$mails[] = $v['musernm'];
	                    $ids[] = $v['id'];
	                    $file .= $v['musernm'];
	                    $file .= "\r";
	                    $html .=htmlspecialchars($v['musernm'],ENT_QUOTES,"UTF-8");
	                    $html .= '<br/>';
	                }
                }elseif($decrypt==1){
                	foreach ($maildata as $v) 
	                {
	                	$mails[] =passport_decrypt($v['mpasswd'],DECRYPT_KEY);
	                    $ids[] = $v['id'];
	                    $file .= passport_decrypt($v['mpasswd'],DECRYPT_KEY);
	                    $file .= "\r";
	                    $html .=htmlspecialchars(passport_decrypt($v['mpasswd'],DECRYPT_KEY),ENT_QUOTES,"UTF-8");
	                    $html .= '<br/>';
	                }
                }
                
               
                
            if(count($ids)!=$canbuynum*$sendbeishu){
              	// 回滚事务
                    Db::rollback();
                 
                    $errormsg='请检查商品库存,异常代码(001)';
                    
                    $code=-1;
                    return TyReturn($errormsg,$code);
            }    
    			$arr_string = join(',', $ids);
                $sql = "update think_mail set mis_use=1,update_time=:update_time,syddhao=:order where id in(".$arr_string.")";
                $updatemail = Db::execute($sql,['update_time'=>time(),
                								'order'=>$order
                								]);
    			if($updatemail==false)
    			{
    				// 回滚事务
                    Db::rollback();
                    
                    $errormsg='更新邮箱状态出错001';
                    $code=-1;
                    return TyReturn($errormsg,$code);

    			}
    			
    			//更新销量
    			$sql = "update think_fl set sellercount=sellercount+:sellerc where id=:mpid";
           		$updatseller = Db::execute($sql,['sellerc'=>$canbuynum*$sendbeishu,
           										'mpid'=>$mpid
           										]);
    			if($updatseller==false)
    			{
    				// 回滚事务
                    Db::rollback();
                    
                    $errormsg='更新邮箱销量出错001';
                    $code=-1;
                    return TyReturn($errormsg,$code);

    			}
    			if(session('useraccount.id') && session('useraccount.account')){//判断是否会员登录
    				//更新积分
	    			$sql = "update think_member set integral=integral+:jifen where id=:userid";
	           		$updatseller = Db::execute($sql,['jifen'=>$jifen,
	           										'userid'=>session('useraccount.id')
	           										]);
	           		
	    			if($updatseller===false)
	    			{
	    				// 回滚事务
	                    Db::rollback();
	                    
	                    $errormsg='更新用户积分出错';
	                    $code=-1;
	                    return TyReturn($errormsg,$code);
	
	    			}
	    			//记录积分log
	    			writeintegrallog(session('useraccount.id'),"购物赠送：".$order,0,$jifen);
	    			//记录金额log
	    			writemoneylog(session('useraccount.id'),"购物消费：".$order,1,$result['data']['jine']);
	    			
    				
    			}
    			
    			
    			$html="<hehe id='zhengwen'>".$html."</hehe>";
	    		$fileemail=$file;
	            $toutext=!empty($result['data']['data_fl']['kamitou'])?$result['data']['data_fl']['kamitou']:config('kami_tou');
	            $weitext=!empty($result['data']['data_fl']['kamiwei'])?$result['data']['data_fl']['kamiwei']:config('kami_wei');
	            if(!empty($toutext))
	            {
	                $file="<pretou>".$toutext."\r"."</pretou>".$file;
	                $html=$toutext."<br/>".$html;
	            }
	            if(!empty($weitext))
	            {
	                $file.="<prewei>"."\r".$weitext."</prewei>";
	                $html.="<br/>".$weitext;
	            }
				$checkBom = checkBOM($file);
				
						// 有bom的情况下"\xEF\xBB\xBF"第一次写入这段字符不可缺少
				if ($checkBom == FALSE) 
				{
					//$file = "\xEF\xBB\xBF" . $file;
					//$html = "\xEF\xBB\xBF" . $html;
				}
				$txtPath = kami_order_txt_path($order);
				if ($txtPath === '') {
					Db::rollback();
					$errormsg='异常错误003';
					$code=-1;
					return TyReturn($errormsg,$code);
				}
				clearstatcache(true, $txtPath);
				if (is_file($txtPath)) {
					// 回滚事务
                    Db::rollback();                  
                    $errormsg='异常的错误002';
                    $code=-1;
                    return TyReturn($errormsg,$code);
				}
	            // 原子写入：先写临时文件再 rename，避免读到半截 txt
	            $tmpPath = $txtPath . '.tmp.' . getmypid();
	            $fwbool = @file_put_contents($tmpPath, $file, LOCK_EX);
	            if ($fwbool === false || !is_file($tmpPath)) {
	            	@unlink($tmpPath);
	            	Db::rollback();
	            	$errormsg='异常错误003';
	            	$code=-1;
	            	return TyReturn($errormsg,$code);
	            }
	            if (!@rename($tmpPath, $txtPath)) {
	            	// Windows 偶发 rename 失败时回退 copy
	            	$copied = @copy($tmpPath, $txtPath);
	            	@unlink($tmpPath);
	            	if (!$copied || !is_file($txtPath)) {
	            		Db::rollback();
	            		$errormsg='异常错误003';
	            		$code=-1;
	            		return TyReturn($errormsg,$code);
	            	}
	            }
	            clearstatcache(true, $txtPath);
	            
    			
    			
    		}catch(\Exception $e){
					// 回滚事务
                    Db::rollback();
                    $errormsg=$e->getMessage();
                    $code=-1;
                    return TyReturn($errormsg,$code);
    		}
    		Db::commit();

    		
			 
			//发送邮件
			
            if(!empty($email))
            {
                $mail_host=config('mail_host');
                $mail_port=config('mail_port');
                $mail_username=config('mail_username');
                $mail_password=config('mail_password');
                if(!empty($mail_host) and !empty($mail_port) and !empty($mail_username) and !empty($mail_password))
                {
                    $json=SendMail($email,'订单:'.$order.'卡密信息',$fileemail, kami_order_txt_path($order));
	                if($json['code']==1)
	                {
	                	$sql = "update think_info set sendtype=1 where mcard=:mcard";
	                }
	                else
	                {
	                    $sql = "update think_info set sendtype=2 where mcard=:mcard";
	                }
                }
                else
                {
                    $sql = "update think_info set sendtype=3 where mcard=:mcard";
                }
                    	
                $bjdd = Db::execute($sql,['mcard'=>$order]);
            }
            //分销佣金begin
	        if(config('fx_cengji')>0){
	        	$map = [];
	        	$map['mcard|morder'] = $number;
	        	self::Fx_money($map,$canbuynum);        
	        }   
	        //分销佣金end
	        
	        //分站佣金begin
	        if($childid>0){
	        	self::childFxmoney($childid,$mpid,$canbuynum,$result['data']['jine'],$order,$result['data']['data_fl']['mnamebie'],$result['data']['data_card']['memberid']);	        	
	        }	
	        //分站佣金end
	           
    	return TyReturn($errormsg,$code,$html);
                                         
    }
    
    //手动发货
    public function changeOrderStatus($mpid,$number,$result)
    {
    	//验证订单号
    		$number=trim($number);
 		  	$mpid=trim($mpid);
 		  	
 		  	
 		
        	$canbuynum=$result['data']['canbuynum'];
        	$sendbeishu=$result['data']['sendbeishu'];
        	$jifen=$result['data']['integral'];
        	$jifen=bcmul($jifen,$canbuynum);
        	$order=$result['data']['data_card']['mcard'];
        	if($result['data']['data_card']['memberid']!=0){//判断是否会员登录
        		$memberData=Db::name('member')->where('id',$result['data']['data_card']['memberid'])->find();
        		$email=$memberData['email'];
        	}else{
        		$email=$result['data']['data_card']['email'];
        	}
        	$errormsg='';
        	$code=1;
        	
        	//开启事务
    		Db::startTrans();
    		try
    		{
    			//更新订单信息
    			$sql="select * from think_info where (mcard=:mcard or morder=:morder) and mstatus=0 for update";
    			$bool=Db::query($sql,['mcard'=>$number,
    								  'morder'=>$number]);
    			
    			if($bool==false){
    				// 回滚事务
                    Db::rollback();                
                    $errormsg='订单查询失败001';
                    $code=-1;
                    return TyReturn($errormsg,$code);
    			}
    			
    			if($bool[0]['mstatus']!=0){
    				// 回滚事务
                    Db::rollback();                
                    $errormsg='订单查询失败002';
                    $code=-1;
                    return TyReturn($errormsg,$code);
    			}
    			
    			   	if(session('useraccount.id') && session('useraccount.account')){//判断是否会员登录
	    				$sql = "update think_info set mflid=:mflid,mstatus=1,memberid=:memberid,update_time=:update_time where (mcard=:mcard or morder=:morder) and mstatus=0";
	    				$bool = Db::execute($sql,['mflid'=>$mpid,
	    										'memberid'=>session('useraccount.id'),
	    										'update_time'=>time(),
	    										'mcard'=>$number,
	    										'morder'=>$number
	    										]);
	    			}else{
	    				$sql = "update think_info set mflid=:mflid,mstatus=1,update_time=:update_time where (mcard=:mcard or morder=:morder) and mstatus=0";
	    				$bool = Db::execute($sql,['mflid'=>$mpid,										
	    										'update_time'=>time(),
	    										'mcard'=>$number,
	    										'morder'=>$number
	    										]);	
	    			}
	           
	    			if($bool==false)
	    			{
	    				// 回滚事务
	    				 Db::rollback();
	    				$errormsg='更新订单状态失败';
	    				$code=-1;
	    				 return TyReturn($errormsg,$code);
	
	    			                  
	                    
	    			}
		
	    			//更新销量
	    			$sql = "update think_fl set sellercount=sellercount+:sellerc where id=:mpid";
           			$updatseller = Db::execute($sql,['sellerc'=>$canbuynum*$sendbeishu,
           										'mpid'=>$mpid
           										]);
	    			if($updatseller==false)
	    			{
	    				// 回滚事务
	                    Db::rollback();
	                    
	                    $errormsg='更新邮箱销量出错001';
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
	    		
	    		if(session('useraccount.id') && session('useraccount.account')){//判断是否会员登录
    				//更新积分
	    			$sql = "update think_member set integral=integral+:jifen where id=:userid";
	           		$updatseller = Db::execute($sql,['jifen'=>$jifen,
	           										'userid'=>session('useraccount.id')
	           										]);
	           		
	    			if($updatseller===false)
	    			{
	    				// 回滚事务
	                    Db::rollback();
	                    
	                    $errormsg='更新用户积分出错';
	                    $code=-1;
	                    return TyReturn($errormsg,$code);
	
	    			}
	    			//记录积分log
	    			writeintegrallog(session('useraccount.id'),"购物赠送：".$order,0,$jifen);
	    			//记录金额log
	    			writemoneylog(session('useraccount.id'),"购物消费：".$order,1,$result['data']['jine']);
	    			
    				
    			}
    			
	    		
    		
            
			 
			//发送邮件
			
            if(!empty($email))
            {
                $mail_host=config('mail_host');
                $mail_port=config('mail_port');
                $mail_username=config('mail_username');
                $mail_password=config('mail_password');
                if(!empty($mail_host) and !empty($mail_port) and !empty($mail_username) and !empty($mail_password))
                {
                    $json=SendMail($email,'订单编号:'.$order,'尊敬的用户，您的订单号是'.$order."\r\n已成功下单","");
	                if($json['code']==1)
	                {
	                	$sql = "update think_info set sendtype=1 where mcard=:mcard";
	                }
	                else
	                {
	                    $sql = "update think_info set sendtype=2 where mcard=:mcard";
	                }
                }
                else
                {
                    $sql = "update think_info set sendtype=3 where mcard=:mcard";
                }
                    	
                $bjdd = Db::execute($sql,['mcard'=>$order]);
            }
         //新订单提醒
		if(!empty(config('web_qq'))){
               SendMail(config('web_qq').'@qq.com','订单编号:'.$order,'\r\n亲，有新订单啦！请尽快登录网站发货，商品名称：'.$result['data']['mname']."\r\n已成功下单",""); 
            }
          if(!empty(config('web_mobile'))){
          	$mobile = config('web_mobile');     //手机号
          	$tplCode = config('alimoban_id');   //模板ID
          	$result['data']['mname']=str_replace("【","",$result['data']['mname']);
          	$result['data']['mname']=str_replace("】","",$result['data']['mname']);
          	$param['shopname']=mb_substr($result['data']['mname'],0,20,'utf-8');
          	$msgStatus = sendMsg($mobile,$tplCode,$param);         	
          }
        $html="支付成功";   
    	return TyReturn($errormsg,$code,$html);
                                         
    }
    
 
	
	 /*
     * 分销分配
     */
    public function Fx_money($map,$buynum){
    	//开启分销
        	$resultInfo= db('info')->where($map)->find();
	        if($resultInfo==false){
	        	return false; 
	        }
	        //是否分销订单
	        if($resultInfo['memberid']!=0 || $resultInfo['pid1']!=0){
	        	//有可能是分销订单
	        	$resultFl= db('fl')->where('id',$resultInfo['mflid'])->find();
	        	if($resultFl){
	        		$fx_money=$resultFl['fx_money']/100*$buynum;
	        		if($fx_money>=$resultInfo['mamount']){        			
	        			writesystemlog(['make'=>'商品分销佣金大于订单金额【商品ID:'.$resultInfo['mflid'].' 分销佣金：'.$fx_money.' 订单金额：'.$resultInfo['mamount'].'】','level'=>'0']);
	        			return false; 
	        		}
	        		if($fx_money>0){
	        			
	        			//商品是分销商品
	        			$pid1=0;
	        			$pid2=0;
	        			$pid3=0;
	        			$childid=0;
	        			if($resultInfo['memberid']!=0){
	        				$resultMember=db('member')->where('id',$resultInfo['memberid'])->find();
				        	if($resultMember){
				        		$pid1=$resultMember['pid1'];
				        		$pid2=$resultMember['pid2'];
				        		$pid3=$resultMember['pid3'];
				        		$childid=$pid1;
				        	}
	        			}elseif($resultInfo['pid1']!=0){
	        				$resultMember=db('member')->where('id',$resultInfo['pid1'])->find();
				        	if($resultMember){
				        		$pid1=$resultInfo['pid1'];
				        		$pid2=$resultMember['pid1'];
				        		$pid3=$resultMember['pid2'];
				        		$childid=$pid1;
				        	}
	        			}
	        			

	        			if(config('fx_neigou')==1){
		        			//内购
		        			$pid3=$pid2;
		        			$pid2=$pid1;	        				
		        			$pid1=$resultInfo['memberid'];        			
		        		}	        						        	
				        if(config('fx_cengji')==1 && $pid1!=0){				        		
				        	$sql="update think_member set tg_money=tg_money+:fx_money where id=:id";
				        	Db::execute($sql,['fx_money'=>$fx_money,'id'=>$pid1]);
				        	if(config('fx_neigou')==1){
				        		$relation=1;//分成者->推广者  关系->推荐人
				        	}else{
				        		$relation=2;//分成者->推广者  关系->自己
				        	}
				        	$tgmoneyData=['memberid'=>$pid1,
										  'money'=>$fx_money,
										  'orderno'=>$resultInfo['mcard'],
										  'buyid'=>$resultInfo['memberid'],
										  'childid'=>$childid,
										  'relation'=>$relation,
										  'shopname'=>$resultFl['mnamebie'],
										  'create_time'=>time()
										  ];
							writetgmoneylog($tgmoneyData);
							//佣金总金额
							writeamounttotal($pid1,$fx_money,'yjmoney');
							//分销总金额
							writeamounttotal($pid1,$fx_money,'fxmoney');	
								
				        }elseif(config('fx_cengji')==2){
				        	$fx_bili=100/(config('fx_pid1')+config('fx_pid2'));
			        		$fx_pid1_bili=config('fx_pid1')*$fx_bili;
			        		$fx_pid2_bili=config('fx_pid2')*$fx_bili;
			        		$fx_pid1_money=($fx_pid1_bili*$fx_money)/100;
			        		$fx_pid1_money=round($fx_pid1_money,4);
			        		$fx_pid2_money=($fx_pid2_bili*$fx_money)/100;
			        		$fx_pid2_money=round($fx_pid2_money,4);
			        		if($pid1!=0){
			        			$sql="update think_member set tg_money=tg_money+:fx_money where id=:id";
				        		Db::execute($sql,['fx_money'=>$fx_pid1_money,'id'=>$pid1]);
				        		if(config('fx_neigou')==1){
					        		$relation=1;//分成者->推广者  关系->推荐人
					        	}else{
					        		$relation=2;//分成者->推广者  关系->自己
					        	}
				        		$tgmoneyData=['memberid'=>$pid1,
											  'money'=>$fx_pid1_money,
											  'orderno'=>$resultInfo['mcard'],
											  'buyid'=>$resultInfo['memberid'],
											  'childid'=>$childid,
											  'relation'=>$relation,
											  'shopname'=>$resultFl['mnamebie'],
											  'create_time'=>time()
											  ];
								writetgmoneylog($tgmoneyData);
								//佣金总金额
								writeamounttotal($pid1,$fx_pid1_money,'yjmoney');
								//分销总金额
								writeamounttotal($pid1,$fx_pid1_money,'fxmoney');	
			        		}
			        		if($pid2!=0){
			        			$sql="update think_member set tg_money=tg_money+:fx_money where id=:id";
				        		Db::execute($sql,['fx_money'=>$fx_pid2_money,'id'=>$pid2]);
				        		if(config('fx_neigou')==1){
					        		$relation=2;//分成者->推广者  关系->自己
					        	}else{
					        		$relation=3;//分成者->推广者  关系->下一级
					        	}
				        		$tgmoneyData=['memberid'=>$pid2,
											  'money'=>$fx_pid2_money,
											  'orderno'=>$resultInfo['mcard'],
											  'buyid'=>$resultInfo['memberid'],
											  'childid'=>$childid,
											  'relation'=>$relation,
											  'shopname'=>$resultFl['mnamebie'],
											  'create_time'=>time()
											  ];
								writetgmoneylog($tgmoneyData);
								//佣金总金额
								writeamounttotal($pid2,$fx_pid2_money,'yjmoney');
								//分销总金额
								writeamounttotal($pid2,$fx_pid2_money,'fxmoney');
			        		}				        						        		
				        }elseif(config('fx_cengji')==3){
				        	$fx_bili=100/(config('fx_pid1')+config('fx_pid2')+config('fx_pid3'));
			        		$fx_pid1_bili=config('fx_pid1')*$fx_bili;
			        		$fx_pid2_bili=config('fx_pid2')*$fx_bili;
			        		$fx_pid3_bili=config('fx_pid3')*$fx_bili;
			        		$fx_pid1_money=($fx_pid1_bili*$fx_money)/100;
			        		$fx_pid1_money=round($fx_pid1_money,4);
			        		$fx_pid2_money=($fx_pid2_bili*$fx_money)/100;
			        		$fx_pid2_money=round($fx_pid2_money,4);
			        		$fx_pid3_money=($fx_pid3_bili*$fx_money)/100;
			        		$fx_pid3_money=round($fx_pid3_money,4);
			        		if($pid1!=0){
			        			$sql="update think_member set tg_money=tg_money+:fx_money where id=:id";
				        		Db::execute($sql,['fx_money'=>$fx_pid1_money,'id'=>$pid1]);
				        		if(config('fx_neigou')==1){
					        		$relation=1;//分成者->推广者  关系->推荐人
					        	}else{
					        		$relation=2;//分成者->推广者  关系->自己
					        	}
				        		$tgmoneyData=['memberid'=>$pid1,
											  'money'=>$fx_pid1_money,
											  'orderno'=>$resultInfo['mcard'],
											  'buyid'=>$resultInfo['memberid'],
											  'childid'=>$childid,
											  'relation'=>$relation,
											  'shopname'=>$resultFl['mnamebie'],
											  'create_time'=>time()
											  ];
								writetgmoneylog($tgmoneyData);
								//佣金总金额
								writeamounttotal($pid1,$fx_pid1_money,'yjmoney');
								//分销总金额
								writeamounttotal($pid1,$fx_pid1_money,'fxmoney');
			        		}
			        		if($pid2!=0){
			        			$sql="update think_member set tg_money=tg_money+:fx_money where id=:id";
				        		Db::execute($sql,['fx_money'=>$fx_pid2_money,'id'=>$pid2]);
				        		if(config('fx_neigou')==1){
					        		$relation=2;//分成者->推广者  关系->自己
					        	}else{
					        		$relation=3;//分成者->推广者  关系->下一级
					        	}
				        		$tgmoneyData=['memberid'=>$pid2,
											  'money'=>$fx_pid2_money,
											  'orderno'=>$resultInfo['mcard'],
											  'buyid'=>$resultInfo['memberid'],
											  'childid'=>$childid,
											  'relation'=>$relation,
											  'shopname'=>$resultFl['mnamebie'],
											  'create_time'=>time()
											  ];
								writetgmoneylog($tgmoneyData);
								//佣金总金额
								writeamounttotal($pid2,$fx_pid2_money,'yjmoney');
								//分销总金额
								writeamounttotal($pid2,$fx_pid2_money,'fxmoney');
			        		}
			        		if($pid3!=0){
			        			$sql="update think_member set tg_money=tg_money+:fx_money where id=:id";
				        		Db::execute($sql,['fx_money'=>$fx_pid3_money,'id'=>$pid3]);
				        		if(config('fx_neigou')==1){
					        		$relation=4;//分成者->推广者  关系->下二级
					        	}else{
					        		$relation=5;//分成者->推广者  关系->下三级
					        	}
				        		$tgmoneyData=['memberid'=>$pid3,
											  'money'=>$fx_pid3_money,
											  'orderno'=>$resultInfo['mcard'],
											  'buyid'=>$resultInfo['memberid'],
											  'childid'=>$childid,
											  'relation'=>$relation,
											  'shopname'=>$resultFl['mnamebie'],
											  'tgtype'=>0,
											  'create_time'=>time()
											  ];
								writetgmoneylog($tgmoneyData);
								//佣金总金额
								writeamounttotal($pid3,$fx_pid3_money,'yjmoney');
								//分销总金额
								writeamounttotal($pid3,$fx_pid3_money,'fxmoney');
			        		}	
				        }		
	        		}
	        	}
	        	
	        }
    }
    
    
    
    /*
     * 分站利润分配
     * $goodid 商品ID
     * $buynum  购买数量
     * $paymoney 实际支付金额
     * $childid 分站ID
     * $order   关联订单号
     * $shopname 商品名字
     * $buymemberid 购买者ID
     */
    public function childFxmoney($childid,$goodid,$buynum,$paymoney,$order,$shopname,$buymemberid){
    	
      	$childminmoney=self::GetChildMinMoneyBygoodid($childid,$buynum,$goodid);     	
      	$childFxmoney=$paymoney-$childminmoney;
      	if($childFxmoney>0){
      		$sql="update think_member set tg_money=tg_money+:fx_money where id=:id";
		    Db::execute($sql,['fx_money'=>$childFxmoney,'id'=>$childid]);
      	}
      	$child_fl=db('child_fl')->where(['goodid'=>trim($goodid),'memberid'=>$childid])->find();
      	
      	$tgmoneyData=['memberid'=>$childid,
					'money'=>$childFxmoney,
					'orderno'=>$order,
					'buyid'=>$buymemberid,
					'childid'=>$childid,
				    'relation'=>0,
				    'shopname'=>$child_fl['mname'].'['.$shopname.']',
				    'tgtype'=>1,
			        'create_time'=>time()
					];
		
				
		writetgmoneylog($tgmoneyData); 
		//佣金总金额
		writeamounttotal($childid,$childFxmoney,'yjmoney');
		//分销总金额
		writeamounttotal($childid,$childFxmoney,'fzmoney');          			           		         
    }
    
    public function GetChildMinMoneyBygoodid($childid,$buynum,$goodid){
    	$zhuPrice=0;    //初始化商品零售价格
    	$simiPrice=0; 	//初始化私密价格
    	$groupPrice=0;	//初始化分组价格
    	$pifaPrice=0;   //初始化批发价格
    	$memberdiscount=0; //初始化分组折扣
    	
    	//获取商品原价
    	$shopxq=Db::query("select * from think_fl where id=:id",['id'=>$goodid]);
    	if(!empty($shopxq)){
        	$zhuPrice=$shopxq[0]['mprice']/100;
        }
    	//获取会员私密价格        
        $memberprice=Db::query("select * from think_member_price where memberid=:memberid and goodid=:goodid",['memberid'=>$childid,'goodid'=>$goodid]);
      	if(!empty($memberprice)){
        	$simiPrice=$memberprice[0]['price']/100;
        }
        
        //获取会员分组
        $member=Db::query("select * from think_member where id=:memberid",['memberid'=>$childid]);	           		
	    //获取会员分组价格
	    if(!empty($member)){
	        $membergroupprice=Db::query("select * from think_member_group_price where membergroupid=:membergroupid and goodid=:goodid",['membergroupid'=>$member[0]['group_id'],'goodid'=>$goodid]);
	        if(!empty($membergroupprice)){
	            $groupPrice=$membergroupprice[0]['price']/100;
	        }
	        
	        //获取分组折扣
			$membergroupdiscount=Db::query("select * from think_member_group where id=:membergroupid",['membergroupid'=>$member[0]['group_id']]);
      		if(!empty($membergroupdiscount)){
       			$memberdiscount=$membergroupdiscount[0]['discount'];	           					         					           					
       		}	           			
	    }
	    //获取批发价格
	    $shopyh=db('yh')->where('mpid',trim($goodid))->order('mdj asc')->limit(1)->select();
      	if(!empty($shopyh)){
      		$pifaPrice=$shopyh[0]['mdj']/100;
      	}
      	
      	//获取最低价格
      	$ArrayPrice=[];
      	
	   
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
      	$childminmoney=$minPrice*$buynum;
      	return $childminmoney;
      	
    }
    	
 
	
	 
}