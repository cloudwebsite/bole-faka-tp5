<?php
namespace app\jingdian\controller;
use app\jingdian\model\ArticleModel;
use app\jingdian\model\CommonModel;
use think\Db;

class Article extends Base
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
	            $map['think_child_article.title'] = ['like',"%" . $key . "%"];          
	        }
	    	 //初始化商品类
        	$Article=new ArticleModel();
        	 //获取商品列表
        	$lists=$Article->getArticleByWhere($map, $Nowpage, $limits,session('useraccount.id'));	    		    	
	    	$count=$Article->getAllCount($map,session('useraccount.id'));    	    	
        	$cates=Db::name('article_cate')->order('id asc')->select();
        	
        	$this->assign('cate',$cates);
	    	$this->assign('count', $count); 
        	$this->assign('Nowpage', $Nowpage); //当前页
        	$this->assign('allpage', $allpage); //总页数 
        	$this->assign('val', $key);
	    	if(input('get.page'))
	        {
	            return json($lists);
	        }	    		
	    	return $this->fetch('/goodsset/article'); 
    	}
    	$this->assign('former_url', '');
        return $this->fetch('/user/index'); 
               
    }
	 /**
     * 添加导航
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function add_article()
    {
        if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	return $this->error('未知错误',url('user/uscenter'));
        }
    	if(!session('useraccount.id')){
    		$this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}
        if(request()->isPost()){          
            $param = inputself();
            $param['memberid']=session('useraccount.id');
            $article = new ArticleModel();
            $flag = $article->insertArticle($param);
            if($flag===false){
            	$this->error('添加失败',url('article/index'));
    			
	    	}else{
	    		
	    		$this->success('添加成功',url('article/index'));
	    	}
            
        }  
        return $this->fetch('/goodsset/article');
    }


    /**
     * 编辑导航
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function edit_article()
    {
        if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	return $this->error('未知错误',url('user/uscenter'));
        }
    	if(!session('useraccount.id')){
    		$this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}
        $article = new ArticleModel();
        if(request()->isPost()){
            $param = input('post.');
            $flag = $article->updateArticle($param);
            return $this->success($flag['msg']);
           
        }
        
        $id = input('param.id');
        $cates=Db::name('article_cate')->order('id asc')->select();
        $this->assign('cate',$cates);
        $this->assign('article',$article->getOneArticle($id));  
        return $this->fetch('goodsset/editarticle');
    }


    /**
     * 删除导航
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function del_article()
    {
       if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	return $this->error('未知错误',url('user/uscenter'));
        }
    	if(!session('useraccount.id')){
    		$this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}
       $id = input('param.id');
       $article = new ArticleModel();
        $flag = $article->delArticle($id,session('useraccount.id'));
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

}