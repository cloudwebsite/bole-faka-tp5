<?php

namespace app\admin\controller;
use app\admin\model\CateGoryGroupModel;
use app\admin\model\CateGoryModel;
use app\admin\model\CateGoryYhModel;
use app\admin\model\AttachGroupModel;
use think\Db;

class Category extends Base
{
    //*********************************************会员组*********************************************//
    /**
     * [group 类目组]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function group(){

        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['name'] = ['like',"%" . $key . "%"];          
        }      
        $group = new CateGoryGroupModel(); 
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');
        $count = $group->getAllCount($map);         //获取总条数
        $allpage = intval(ceil($count / $limits));  //计算总页面      
        $lists = $group->getAll($map, $Nowpage, $limits);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign('val', $key);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }

     /**
     * [add_group 添加类目]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function add_group()
    {
        if(request()->isAjax()){
            $param = input('post.');
            $group = new CateGoryGroupModel();
            $flag = $group->insertGroup($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }


    /**
     * [edit_group 编辑类目]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function edit_group()
    {
        $group = new CateGoryGroupModel();
        if(request()->isPost()){           
            $param = input('post.');
            if(config('uploadtype')==0){
	         	$param['yunimgurl']='';
	        }
	        if(config('uploadtype')==1){
	         	$param['imgurl']='';
	        }
            $flag = $group->editGroup($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $this->assign('group',$group->getOne($id));
        return $this->fetch();
    }


    /**
     * [del_group 删除类目组]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function del_group()
    {
        $id = input('param.id');
        $group = new CateGoryGroupModel();
        $flag = $group->delGroup($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    /**
     * [group_status 类目状态]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function group_status()
    {
        $id=input('param.id');
        $status = Db::name('category_group')->where(array('id'=>$id))->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('category_group')->where(array('id'=>$id))->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        }
        else
        {
            $flag = Db::name('category_group')->where(array('id'=>$id))->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }   
    } 

	 /**
     * [ruleorder 排序]
     * @return [type] [description]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function ruleorder()
    {
        if (request()->isAjax()){
            $param = input('post.');     
            $auth_rule = Db::name('category_group');
            foreach ($param as $id => $sort){
                $auth_rule->where(array('id' => $id ))->setField('sort' , $sort);
            }
            return json(['code' => 1, 'msg' => '排序更新成功']);
        }
    }
    
    /*
     * 一键所有类目图标上传到七牛云
     */
    public function yjUploadCategoryico(){
    	$successCount=0;
    	$failCount=0;
    	$sql="select * from think_category_group where imgurl<>'' and yunimgurl=''";
    	$result=Db::name('category_group')->where("imgurl<>'' and yunimgurl=''")->select();
    	foreach ($result as $v){
			$filePath=UPLOAD_PATH . DS . "uploads\\face\\".$v['imgurl'];
			
			
			$returnData=action('upload/QiniuuploadAction',$filePath);			
			if($returnData['code']==1){
				$data=['imgurl'=>'','yunimgurl'=>$returnData['src']];
				$bool=Db::name('category_group')->where("id",$v['id'])->update($data);
				if($bool){
					$successCount=$successCount+1;
				}else{
					$failCount=$failCount+1;
				}
			}
					
        }
        $this->success('操作完成,成功操作'.$successCount.'个,'.'失败操作'.$failCount.'个',url('group'));
        
    
    }
    
    
    /*
     * 一键所有商品图标上传到七牛云
     */
    public function yjUploadMemberico(){
    	$successCount=0;
    	$failCount=0;
    	$sql="select * from think_fl where imgurl<>'' and yunimgurl=''";
    	$result=Db::name('fl')->where("imgurl<>'' and yunimgurl=''")->select();
    	foreach ($result as $v){
			$filePath=UPLOAD_PATH . DS . "uploads\\face\\".$v['imgurl'];
			
			
			$returnData=action('upload/QiniuuploadAction',$filePath);			
			if($returnData['code']==1){
				$data=['imgurl'=>'','yunimgurl'=>$returnData['src']];
				$bool=Db::name('fl')->where("id",$v['id'])->update($data);
				if($bool){
					$successCount=$successCount+1;
				}else{
					$failCount=$failCount+1;
				}
			}
					
        }
        $this->success('操作完成,成功操作'.$successCount.'个,'.'失败操作'.$failCount.'个',url('group'));
        
    
    }
    
    
    
     /**
     * [ruleorder 排序]
     * @return [type] [description]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function rulemember()
    {
        if (request()->isAjax()){
            $a=new CateGoryModel();
            $param = input('post.');        
            
            foreach($param as $v=>$val){
            	if($v=='checkname'){
            		continue;
            	}
            	foreach($val as $id=>$val2){
            		$data[$id]['id']=$id; 
					
            		if($v=='mprice' || $v=='fx_money'){
            			$data[$id][$v]=$val2*100;
            		}elseif($v=='group'){
            			$data[$id]['mlm']=$val2;
            		}else{
            			$data[$id][$v]=$val2;
            		}          		
            	}         	          	            	
            }
            $fenzuerror=0;
            $fenzumsg='';
            $simierror=0;
            $simimsg='';
            foreach($data as $v=>$val){
					$onegoods=$a->getOneMember($v);//分站价格同步  
				 if($onegoods['mprice']<>$val['mprice']){
					$mprice=$val['mprice']-$onegoods['mprice'];
					Db::name('child_fl')->where('goodid', $v)->where('mprice<>-1')->inc('mprice',$mprice)->inc('marketprice',$mprice)->inc('mprice_bz',$mprice)->update();	
				 }
            	//判断分组价格
		         $member_group_price=Db::name('member_group_price')->where(['goodid'=>$v])->limit(1)->order('price')->select();
		         if($member_group_price){
		         	if($member_group_price[0]['price']>$val['mprice']){
		         		$fenzumsg.="商品ID:".$v."\r\n";
		         		$fenzuerror++;
		         		continue;
		         	}
		         }
		         //判断私密价格
		         $member_price=Db::name('member_price')->where(['goodid'=>$v])->limit(1)->order('price')->select();
		         if($member_price){
		         	if($member_price[0]['price']>$val['mprice']){
		         		$simimsg.="商品ID:".$v."\r\n";
		         		$simierror++;
		         		continue;
		         	}
		         }
            	$tempdata[]=$val;
            }
            $msg='分组价格错误数量：'.$fenzuerror."\r\n".$fenzumsg."\r\n".'私密价格错误数量：'.$simierror."\r\n".$simimsg;
            
            $a->saveAll($tempdata);
          	
                      
            return json(['code' => 1, 'msg' => $msg]);
        }
    }
    
    
    //*********************************************会员列表*********************************************//
    /**
     * 商品列表
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function index(){

        $key = input('key');
        $status=input('status');
        if($status==''){
        	$status=999;
        }
        
        $type=input('type');
        if($type==''){
        	$type=999;
        }
        $map = [];
        if($key&&$key!=="")
        {
            $map['mname|mnotice'] = ['like',"%" . $key . "%"];          
        }
        
        $mlm=input('mlm');
        if($mlm!='999' && $mlm!==null){
          $map['think_fl.mlm']=$mlm;
        }
        $arr=Db::name("category_group")->column("id,name"); //获取分组列表
        $member = new CateGoryModel(); 
        $group = new CateGoryGroupModel();     
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $member->getAllCount($map,$status,$type);//计算总页面
        $allpage = intval(ceil($count / $limits));      
        $lists = $member->getMemberByWhere($map, $Nowpage, $limits,$status,$type);
        if($lists){       	
        	$sql="SELECT count(id) as count,goodid FROM `think_child_fl` GROUP BY goodid";
        	$result=Db::name('child_fl')->field('count(id) as count,goodid')->group('goodid')->select();
        	$countfz=Db::name('fz_auth')->count();
        	foreach($lists as $k=>$v){            
	            $goodid=$v['id'];
	            $lists[$k]['kamicount']=Db::name('mail')->where(['mpid'=>$v['id'],'mis_use'=>'0'])->count();
	            $lists[$k]['fznotgoodscount']=$countfz;
	            foreach($result as $k2=>$v2){ 
	                if($v2['goodid']==$goodid){
	                	$lists[$k]['fznotgoodscount']=$countfz-$v2['count'];
	                }	                
	            }   	                       
	        }     	
        }
        $dataCount['all']=$member->getAllCount([],999,999);
        $dataCount['allsale']=$member->getAllCount([],0,999);
        $dataCount['allzidong']=$member->getAllCount([],999,0);
        $dataCount['zidongsell']=$member->getAllCount([],1,0);
        $dataCount['zidongsale']=$member->getAllCount([],0,0);
        $dataCount['allshoudong']=$member->getAllCount([],999,1);
        $dataCount['shoudongsell']=$member->getAllCount([],1,1);
        $dataCount['shoudongsale']=$member->getAllCount([],0,1);
        
        if($mlm===null){
          $mlm=999;
        }
        $this->assign('dataCount', $dataCount); 
        $this->assign('count', $count); 
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign('val', $key);
        $this->assign('group', $group->getGroup());
        $this->assign('status', $status);
        $this->assign('type', $type);
        $this->assign("search_user",$arr);
        $this->assign("mlm",$mlm);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * 添加商品
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function add_member()
    {
        if(request()->isAjax()){

            $param = input('post.');
            $param['mprice']=$param['mprice']*100;
            $param['fx_money']=$param['fx_money']*100;
            $param['mnamebie']=strip_tags(htmlspecialchars_decode($param['mname']));
         	$param['marketprice']=$param['marketprice']*100;
         	if($param['type']==1){
         		$param['decrypt']=0;
         	}
            $member = new CateGoryModel();
            $yh = new CateGoryYhModel();
            $flag = $member->insertMember($param);
            
            if(!empty($param['mdy1'])){

       			 $id = $flag['id'];
                 $mdy1 = $param['mdy1'];
                 $mdj1 = $param['mdj1'];

                 
					 foreach($mdy1 as $k=>$v){
                            $data1 = array();
                            $data1['mdy'] = $v;
                            $data1['mdj'] = $mdj1[$k]*100;
                            $data1['mpid'] = $id;
                            $data1['id'] = "";

                          $flag = $yh->insertGroup($data1);
                           
                            
                      
                        }
     	
          }
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $group = new CateGoryGroupModel();
        $this->assign('group',$group->getGroup());
        $attach_group = new AttachGroupModel();
        $this->assign('attach_group',$attach_group->getGroup());
        return $this->fetch();
    }


    /**
     * 编辑商品
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function edit_member()
    {
        $param = inputself();
        $member = new CateGoryModel();
        $group = new CateGoryGroupModel();
        $yh = new CateGoryYhModel();
        $id =$param['id'];
        $onemember=$member->getOneMember($id);      
        if(request()->isAjax()){
         $param['mprice']=$param['mprice']*100;
         $param['fx_money']=$param['fx_money']*100;
		 if($onemember['mprice']!=$param['mprice']){//分站价格同步
			$mprice=$param['mprice']-$onemember['mprice'];
			Db::name('child_fl')->where('goodid', $id)->where('mprice','>',0)->inc('mprice',$mprice)->inc('marketprice',$mprice)->inc('mprice_bz',$mprice)->update();	
		 }
         //判断分组价格
         $member_group_price=Db::name('member_group_price')->where(['goodid'=>$param['id']])->limit(1)->order('price')->select();
         if($member_group_price){
         	if($member_group_price[0]['price']>$param['mprice']){
         		return json(['code' => -1, 'data' => '', 'msg' => '商品价格 小于 分组价格,请先设置分组价格或者删除分组价格']);
         	}
         }
         //判断私密价格
         $member_price=Db::name('member_price')->where(['goodid'=>$param['id']])->limit(1)->order('price')->select();
         if($member_price){
         	if($member_price[0]['price']>$param['mprice']){
         		return json(['code' => -1, 'data' => '', 'msg' => '商品价格 小于 会员私密价格,请先设置会员私密价格或者删除会员私密价格']);
         	}
         }
         $param['marketprice']=$param['marketprice']*100;
         $param['mnamebie']=strip_tags(htmlspecialchars_decode($param['mname']));
         if(config('uploadtype')==0){
         	$param['yunimgurl']='';
         }
         if(config('uploadtype')==1){
         	$param['imgurl']='';
         }
       
         
         $flag = $member->editCategory($param);
         

         	
         	if(!empty($param['mdy'])){

         		foreach($param['mdy'] as $k=>$v){   
                        $data = array();
                        $data['id'] = $v['id'];
                        $data['mdj'] = $v['mdj']*100;
                        $data['mdy'] = $v['mdy'];
                      
                         $flag =$yh->editGroup($data);
						
                }
         	
          	}
       		if(!empty($param['mdy1'])){

       			 $id = $param['id'];
                 $mdy1 = $param['mdy1'];
                 $mdj1 = $param['mdj1'];
					 foreach($mdy1 as $k=>$v){
                            $data1 = array();
                            $data1['mdy'] = $v;
                            $data1['mdj'] = $mdj1[$k]*100;
                            $data1['mpid'] = $id;
                            $data1['id'] = "";

                          $flag = $yh->insertGroup($data1);
                           
                            
                      
                        }
     	
          	}

         return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $this->assign([
            'member' =>$onemember,
            'group' => $group->getGroup(),
            'yh' =>$yh->getOne($id)
        ]);
        $attach_group = new AttachGroupModel();
        $this->assign('attach_group',$attach_group->getGroup());
        return $this->fetch();
    }
    
  

    /**
     * 删除商品
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function del_member()
    {
        $id = input('param.id');
        $member = new CateGoryModel();
        $flag = $member->delMember($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

	 public function del_yh()
    {
        $id = input('param.id');
        $member = new CateGoryYhModel();
        $flag = $member->delYh($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }


    /**
     * 商品状态
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function member_status()
    {
        $id = input('param.id');
        $status = Db::name('fl')->where('id',$id)->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('fl')->where('id',$id)->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁用']);
        }
        else
        {
            $flag = Db::name('fl')->where('id',$id)->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }
    
    }
    
    /**
     * 商品下架
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function member_status_sale()
    {
        $id = input('param.id');
        $flag = Db::name('fl')->where('id',$id)->setField(['status'=>0]);
        return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁用']);
    }
    
    /**
     * 商品上架
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function member_status_shelf()
    {
        $id = input('param.id');
        $flag = Db::name('fl')->where('id',$id)->setField(['status'=>1]);
        return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已上架']);
    }
    
    /**
     * 商品推荐
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function member_tuijian()
    {
        $id = input('param.id');
        $tuijian = Db::name('fl')->where('id',$id)->value('tuijian');//判断当前状态情况
        if($tuijian==1)
        {
            $flag = Db::name('fl')->where('id',$id)->setField(['tuijian'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '取消推荐']);
        }
        else
        {
            $flag = Db::name('fl')->where('id',$id)->setField(['tuijian'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已推荐']);
        }
    
    }
    /**
     * 爆款促销
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function member_hot()
    {
        $id = input('param.id');
        $tuijian = Db::name('fl')->where('id',$id)->value('hot');//判断当前状态情况
        if($tuijian==1)
        {
            $flag = Db::name('fl')->where('id',$id)->setField(['hot'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '非爆']);
        }
        else
        {
            $flag = Db::name('fl')->where('id',$id)->setField(['hot'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '爆款']);
        }
    
    }
    
    public function yjAddfzgoods(){
    	$param=inputself();
    	$goodid=$param['id'];
    	$sql="SELECT memberid from think_fz_auth where memberid not in(SELECT memberid from think_child_fl where goodid=:goodid)";
    	$bool=Db::query($sql,['goodid'=>$goodid]);
    	if($bool){
    		$Tempdata='';
	    	foreach ($bool as $v) {
	    		$imgurl=-1;
	    		$kamitou=-1;
	    		$kamiwei=-1;
	    		$xqnotice=-1;
	    		$sort=0;    		    		
	    		$Tempdata.="(".$goodid.",".$v['memberid'].",'".$imgurl."','".$kamitou."','".$kamiwei."','".$xqnotice."','".$sort."'),";
	    	}
	    	$Tempdata=substr($Tempdata,0,strlen($Tempdata)-1);
	    	$result=Db::execute("INSERT ignore into think_child_fl (goodid,memberid,imgurl,kamitou,kamiwei,xqnotice,sort) values ".$Tempdata);          	
	    	
	    	if($result===false){
	    		return json(['code'=>-1,'msg'=>'添加失败','sucessCount'=>0]);
	    	}else{
	    		return json(['code'=>1,'msg'=>'成功为'.$result."个分站添加此商品",'successCount'=>$result]);
	    	}
    	}else{
    		return json(['code'=>-1,'msg'=>'无分站需要添加','sucessCount'=>0]);
    	}
    }

}