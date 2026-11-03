<?php
namespace app\admin\controller;
use app\admin\model\IntegralmallModel;
use app\admin\model\IntegralmallindexModel;
use app\admin\model\CateGoryModel;
use app\admin\model\AttachGroupModel;
use app\admin\model\IntegralOrderModel;
use think\Db;

class Integralmall extends Base
{
    
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
        $group = new IntegralmallModel(); 
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
            $group = new IntegralmallModel();
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
        $group = new IntegralmallModel();
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
        $group = new IntegralmallModel();
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
        $status = Db::name('integralmall_group')->where(array('id'=>$id))->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('integralmall_group')->where(array('id'=>$id))->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        }
        else
        {
            $flag = Db::name('integralmall_group')->where(array('id'=>$id))->setField(['status'=>1]);
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
            $auth_rule = Db::name('integralmall_group');
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
    	$sql="select * from think_integralmall_group where imgurl<>'' and yunimgurl=''";
    	$result=Db::name('integralmall_group')->where("imgurl<>'' and yunimgurl=''")->select();
    	foreach ($result as $v){
			$filePath=UPLOAD_PATH . DS . "uploads\\face\\".$v['imgurl'];
			
			
			$returnData=action('upload/QiniuuploadAction',$filePath);			
			if($returnData['code']==1){
				$data=['imgurl'=>'','yunimgurl'=>$returnData['src']];
				$bool=Db::name('integralmall_group')->where("id",$v['id'])->update($data);
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
    	$sql="select * from think_integralmall_index where imgurl<>'' and yunimgurl=''";
    	$result=Db::name('integralmall_index')->where("imgurl<>'' and yunimgurl=''")->select();
    	foreach ($result as $v){
			$filePath=UPLOAD_PATH . DS . "uploads\\face\\".$v['imgurl'];
			
			
			$returnData=action('upload/QiniuuploadAction',$filePath);			
			if($returnData['code']==1){
				$data=['imgurl'=>'','yunimgurl'=>$returnData['src']];
				$bool=Db::name('integralmall_index')->where("id",$v['id'])->update($data);
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
            $a=new IntegralmallindexModel();
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
            
            foreach($data as $v=>$val){           	         	
            	$tempdata[]=$val;
            }   
            $a->saveAll($tempdata);
          	
            $msg="更新成功";          
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
        $arr=Db::name("integralmall_group")->column("id,name"); //获取分组列表
        $member = new IntegralmallindexModel(); 
        $group = new IntegralmallModel();     
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $member->getAllCount($map,$status,$type);//计算总页面
        $allpage = intval(ceil($count / $limits));      
        $lists = $member->getMemberByWhere($map, $Nowpage, $limits,$status,$type);
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
            $param['mnamebie']=strip_tags(htmlspecialchars_decode($param['mname']));
         	$param['marketprice']=$param['marketprice']*100;
         	if($param['type']==1){
         		$param['decrypt']=0;
         	}
            $member = new IntegralmallindexModel();
            $flag = $member->insertMember($param);                     
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $group = new IntegralmallModel();
        $this->assign('group',$group->getGroup());
        $attach_group = new AttachGroupModel();
        $this->assign('attach_group',$attach_group->getGroup());
        $fl=new CateGoryModel();
        $this->assign('fl',$fl->getAllfl());
        return $this->fetch();
    }


    /**
     * 编辑商品
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function edit_member()
    {
        $param = inputself();
        $member = new IntegralmallindexModel();
        $group = new IntegralmallModel();
        $fl=new CateGoryModel();
        $id =$param['id'];
        $onemember=$member->getOneMember($id);      
        if(request()->isAjax()){
         $param['mprice']=$param['mprice']*100;       
         $param['marketprice']=$param['marketprice']*100;
         $param['mnamebie']=strip_tags(htmlspecialchars_decode($param['mname']));
         if(config('uploadtype')==0){
         	$param['yunimgurl']='';
         }
         if(config('uploadtype')==1){
         	$param['imgurl']='';
         }             
         $flag = $member->editCategory($param);
         return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $this->assign([
            'member' =>$onemember,
            'group' => $group->getGroup(),
            'fl' =>$fl->getAllfl()
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
        $member = new IntegralmallindexModel();
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
        $status = Db::name('integralmall_index')->where('id',$id)->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('integralmall_index')->where('id',$id)->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁用']);
        }
        else
        {
            $flag = Db::name('integralmall_index')->where('id',$id)->setField(['status'=>1]);
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
        $flag = Db::name('integralmall_index')->where('id',$id)->setField(['status'=>0]);
        return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁用']);
    }
    
    /**
     * 商品上架
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function member_status_shelf()
    {
        $id = input('param.id');
        $flag = Db::name('integralmall_index')->where('id',$id)->setField(['status'=>1]);
        return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已上架']);
    }
    
    /**
     * 商品推荐
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function member_tuijian()
    {
        $id = input('param.id');
        $tuijian = Db::name('integralmall_index')->where('id',$id)->value('tuijian');//判断当前状态情况
        if($tuijian==1)
        {
            $flag = Db::name('integralmall_index')->where('id',$id)->setField(['tuijian'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '取消推荐']);
        }
        else
        {
            $flag = Db::name('integralmall_index')->where('id',$id)->setField(['tuijian'=>1]);
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
        $tuijian = Db::name('integralmall_index')->where('id',$id)->value('hot');//判断当前状态情况
        if($tuijian==1)
        {
            $flag = Db::name('integralmall_index')->where('id',$id)->setField(['hot'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '非爆']);
        }
        else
        {
            $flag = Db::name('integralmall_index')->where('id',$id)->setField(['hot'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '爆款']);
        }
    
    }
    
    
    /**
     * 积分兑换记录
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */   
    public function order(){

        $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['orderno|lianxi|think_integralmall.email'] = ['like',"%" . $key . "%"];          
        }
        $integralorder = new IntegralOrderModel();       
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $integralorder->getAllCount();//计算总页面
        $allpage = intval(ceil($count / $limits));       
        $lists = $integralorder->getOrderByWhere($map, $Nowpage, $limits);  
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign('val', $key);
        $this->assign('count', $count);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }
    /*
     * 处理订单
     */
	public function editorder(){
		$id=input('param.id');
		if(!request()->isAjax()){
			$integralorder=Db::name('integralmall_order')->where('id',$id)->find();
			$integralindex=Db::name('integralmall_index')->where('id',$integralorder['mflid'])->find();
			$integralattach=Db::name('orderattach')
							->join('think_attach','think_attach.id = think_orderattach.attachid','LEFT')
							->where('think_orderattach.orderno',$integralorder['orderno'])->select();
						
			$this->assign('integralorder', $integralorder); //总页数 
	        $this->assign('integralindex', $integralindex);
	        $this->assign('integralattach', $integralattach);
	        return $this->fetch('edit_order');
		}
		$param=inputself();
		$result=Db::name('integralmall_order')->strict(false)->where('id',$id)->update($param);
		if($result===false){
			return json(['code' => 0, 'data' => '', 'msg' => '更新失败']);
		}else{
			return json(['code' => 1, 'data' => '', 'msg' => '更新成功']);
		}
		
	}
}