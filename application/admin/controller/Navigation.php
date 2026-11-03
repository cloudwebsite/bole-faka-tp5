<?php

namespace app\admin\controller;
use app\admin\model\NavigationModel;
use think\Db;

class Navigation extends Base
{
	/**
     * 导航列表
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function index(){
    	 $key = input('key');
        $map = [];
        if($key&&$key!=="")
        {
            $map['name|text'] = ['like',"%" . $key . "%"];          
        }
        $navigation = new NavigationModel();       
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $navigation->getAllCount();//计算总页面
        $allpage = intval(ceil($count / $limits));       
        $lists = $navigation->getAll($map, $Nowpage, $limits);  
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
    
     /**
     * 添加导航
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function add_navigation()
    {
         $navigation = new NavigationModel();
        if(request()->isAjax()){

            $param = input('post.');
            
           
            $flag = $navigation->insertNavigation($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        
        $this->assign('navigation',$navigation->getAllNavigation());
        return $this->fetch();
    }


    /**
     * 编辑导航
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function edit_navigation()
    {
        $navigation = new NavigationModel();
        if(request()->isAjax()){
            $param = input('post.');
            
            $flag = $navigation->editNavigation($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $id = input('param.id');
        $this->assign([
            'navigation' => $navigation->getOneNavigation($id)
        ]);
        return $this->fetch();
    }


    /**
     * 删除导航
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function del_navigation()
    {
        $id = input('param.id');
       $navigation = new NavigationModel();
        $flag = $navigation->delNavigation($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }



    /**
     * 导航状态
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function navigation_status()
    {
        $id = input('param.id');
        $status = Db::name('navigation')->where('id',$id)->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('navigation')->where('id',$id)->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        }
        else
        {
            $flag = Db::name('navigation')->where('id',$id)->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }
    
    }
    
    
     /**
     * [ruleorder 排序]
     * @return [type] [description]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function rulenavigation()
    {
        if (request()->isAjax()){
            $param = input('post.');     
            $auth_rule = Db::name('navigation');
            foreach ($param as $id => $sort){
                $auth_rule->where(array('id' => $id ))->setField('sort' , $sort);
            }
            return json(['code' => 1, 'msg' => '排序更新成功']);
        }
    }
	
}