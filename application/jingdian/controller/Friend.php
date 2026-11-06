<?php
namespace app\jingdian\controller;
use app\jingdian\model\FriendModel;
use app\jingdian\model\CommonModel;
use think\Db;

class Friend extends Base
{
	
	/*
    *商品列表Do
    */
    public function index(){
    	if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	return $this->error('未知错误',url('user/uscenter'));
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
	            $map['think_child_ad.title'] = ['like',"%" . $key . "%"];          
	        }
	    	 //初始化商品类
        	$Friend=new FriendModel();
        	 //获取商品列表
        	$lists=$Friend->getFriendByWhere($map, $Nowpage, $limits,session('useraccount.id'));	    		    	
	    	$count=$Friend->getAllCount($map,session('useraccount.id'));    	    	
        	$position=Db::name('ad_position')->order('id asc')->select();
        	
        	$this->assign('position',$position);
	    	$this->assign('count', $count); 
        	$this->assign('Nowpage', $Nowpage); //当前页
        	$this->assign('allpage', $allpage); //总页数 
        	$this->assign('val', $key);
	    	if(input('get.page'))
	        {
	            return json($lists);
	        }	    		
	    	return $this->fetch('/goodsset/friend'); 
    	}
    	$this->assign('former_url', '');
        return $this->fetch('/user/index'); 
               
    }
	 /**
     * 添加导航
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function add_ad()
    {
        if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	return $this->error('未知错误',url('user/uscenter'));
        }
    	if(!session('useraccount.id')){
    		$this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}
         
            $param = inputself();
            $param['memberid']=session('useraccount.id');
            $Friend=new FriendModel();
            $flag = $Friend->insertAd($param);
            if($flag===false){
            	$this->error('添加失败',url('friend/index'));
    			
	    	}else{
	    		
	    		$this->success('添加成功',url('friend/index'));
	    	}
            
         
        
    }


    /**
     * 编辑导航
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function edit_ad()
    {
        if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	return $this->error('未知错误',url('user/uscenter'));
        }
    	if(!session('useraccount.id')){
    		$this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}
        $Friend=new FriendModel();
        if(request()->isPost()){
            $param = input('post.');
            $flag = $Friend->updateAd($param);
            if($flag===false){
            	$this->error('修改失败',url('friend/index'));
   			
	    	}else{	    		
	    		$this->success('修改成功',url('friend/index'));
	    	}
           
        }
        
        $id = input('param.id');
        $position=Db::name('ad_position')->order('id asc')->select();
        $this->assign('position',$position);
        $this->assign('ad',$Friend->getOneAd($id));  
        return $this->fetch('Goodsset/editfriend');
    }


    /**
     * 删除导航
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function del_ad()
    {
       if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	return $this->error('未知错误',url('user/uscenter'));
        }
    	if(!session('useraccount.id')){
    		$this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}
       $id = input('param.id');
       $Friend=new FriendModel();
        $flag = $Friend->delAd($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

}