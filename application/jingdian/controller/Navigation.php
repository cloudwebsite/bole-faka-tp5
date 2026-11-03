<?php
namespace app\jingdian\controller;
use app\jingdian\model\NavigationModel;
use app\jingdian\model\CommonModel;
use think\Db;

class Navigation extends Base
{
	
	/*
    *商品列表Do
    */
    public function usnavigation(){
    	if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	$this->error('未知错误',url('user/uscenter'));
        }
    	if(session('useraccount.id')){  
    		$key = input('key');
    		$count=0;
	    	$Nowpage=1;
	        $allpage=0;
	        $type=input('get.type') ? input('get.type'):1;
	        $Nowpage = input('get.page') ? input('get.page'):1;
		    $limits = input('get.limit') ? input('get.limit'):config('list_rows');// 获取总条数
	    	$map = [];
	        if($key&&$key!=="")
	        {
	            $map['think_child_navigation.name'] = ['like',"%" . $key . "%"];          
	        }
	    	 //初始化商品类
        	$NavigationM=new NavigationModel();
        	 //获取商品列表
        	$lists=$NavigationM->getNavigationByWhere($map, $Nowpage, $limits,session('useraccount.id'));	    		    	
	    	$count=$NavigationM->getAllCount($map,session('useraccount.id'));    	    	
	    	
	    	$this->assign('count', $count); 
        	$this->assign('Nowpage', $Nowpage); //当前页
        	$this->assign('allpage', $allpage); //总页数 
        	$this->assign('val', $key);
	    	if(input('get.page'))
	        {
	            return json($lists);
	        }	    		
	    	return $this->fetch('/goodsset/navigation'); 
    	}
    	$this->assign('former_url', '');
        return $this->fetch('/user/index'); 
               
    }
	 /**
     * 添加导航
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function add_navigation()
    {
        if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	$this->error('未知错误',url('user/uscenter'));
        }
    	if(!session('useraccount.id')){
    		$this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}
        $navigation = new NavigationModel();
        if(request()->isAjax()){

            $param = inputself();
            $param['memberid']=session('useraccount.id');
           	
            $flag = $navigation->insertNavigation($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }  
        return $this->fetch();
    }


    /**
     * 编辑导航
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function edit_navigation()
    {
        if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	$this->error('未知错误',url('user/uscenter'));
        }
    	if(!session('useraccount.id')){
    		$this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}
        $navigation = new NavigationModel();
        if(request()->isPost()){
            $param = input('post.');
            $param['memberid']=session('useraccount.id');
            $flag = $navigation->editNavigation($param);
            return $this->success($flag['msg']);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $id = input('param.id');
        $this->assign([
            'navigation' => $navigation->getOneNavigation($id,session('useraccount.id'))
        ]);
        return $this->fetch('Goodsset/editNavigation');
    }


    /**
     * 删除导航
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function del_navigation()
    {
       if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	$this->error('未知错误',url('user/uscenter'));
        }
    	if(!session('useraccount.id')){
    		$this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}
       $id = input('param.id');
       $navigation = new NavigationModel();
        $flag = $navigation->delNavigation($id,session('useraccount.id'));
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

}