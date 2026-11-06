<?php

namespace app\admin\controller;
use app\admin\model\AttachModel;
use app\admin\model\AttachGroupModel;
use think\Db;

class Attach extends Base
{
	/**
     * 附加选项分类列表
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function index(){
    	 $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['name'] = ['like',"%" . $key . "%"];          
        }
        $attach_group = new AttachGroupModel();       
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $attach_group->getAllCount($map);//计算总页面
        $allpage = intval(ceil($count / $limits));       
        $lists = $attach_group->getAll($map, $Nowpage, $limits);  
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign('val', $key);
        $this->assign('count', $count);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch("group");
    }
    
    /**
     * [del_group 删除类目组]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function del_group()
    {
        $id = input('param.id');
        $group = new AttachGroupModel();
        $flag = $group->delGroup($id);
        $attach = new AttachModel();
        $attach->delAttachByAttachId($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
	
    /**
     * [group_status 类目状态]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function group_status()
    {
        $id=input('param.id');
        $status = Db::name('attach_group')->where(array('id'=>$id))->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('attach_group')->where(array('id'=>$id))->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        }
        else
        {
            $flag = Db::name('attach_group')->where(array('id'=>$id))->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }   
    } 

	    /**
	     * 添加选项
	     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
	     */
	    public function add_group()
	    {
	        if(request()->isAjax()){
	
	            $param = input('post.');
	            $attach_group = new AttachGroupModel();
	            $attach = new AttachModel();
	            $flag = $attach_group->insertGroup($param);
	            if(!empty($param['title'])){
	
	       			 $id = $flag['id'];
	                 $title = $param['title'];
	                 $tip = $param['tip'];
	
	                 
						 foreach($title as $k=>$v){
	                            $data1 = array();
	                            $data1['title'] = $v;
	                            $data1['tip'] = $tip[$k];
	                            $data1['attachgroupid'] = $id;
	                            
	
	                          $flag = $attach->insertAttach($data1);
	                           
	                            
	                      
	                        }
	     	
	          }
	            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
	        }
	
	        return $this->fetch();
	    }
	
	
	    /**
	     * 编辑商品
	     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
	     */
	    public function edit_group()
	    {
	        $attach = new AttachModel();
	        $attach_group = new AttachGroupModel();
	       
	        if(request()->isAjax()){
	         $param = input('post.');
	         $flag = $attach_group->editGroup($param);

	         if(!empty($param['attach'])){
	
	         	foreach($param['attach'] as $k=>$v){   
	                        $data = array();
	                        $data['id'] = $v['id'];
	                        $data['title'] = $v['title'];
	                        $data['tip'] = $v['tip'];
	                       
	                         $flag =$attach->editAttach($data);
							
	                    }
	         	
	          }
	       if(!empty($param['title1'])){
	
	       			 $id = $param['id'];
	                 $title1 = $param['title1'];
	                 $tip1 = $param['tip1'];
						 foreach($title1 as $k=>$v){
	                            $data1 = array();
	                            $data1['title'] = $v;
	                            $data1['tip'] = $tip1[$k];
	                            $data1['attachgroupid'] = $id;

	
	                          $flag = $attach->insertAttach($data1);
	                           
	                            
	                      
	                        }
	     	
	          }
	
	         return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
	        }
	
	        $id = input('param.id');
	       
	        $this->assign([
	            'group' => $attach_group->getOne($id),
	            'attach' =>$attach->getOneAttach($id)
	        ]);
	      
	        return $this->fetch();
	    }
	    
		
	
	
}