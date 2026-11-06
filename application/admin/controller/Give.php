<?php
namespace app\admin\controller;
use app\admin\model\GiveModel;
use think\Db;
use com\IpLocationqq;
 
class Give extends Base
{

    /**
     * [index 充值赠送列表]
     * @return [type] [description]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function index()
    {

        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('pay_give')->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = Db::name('pay_give')->page($Nowpage, $limits)->order('paymoney asc')->select();
        foreach($lists as $k=>$v){
            $lists[$k]['create_time']=date('Y-m-d H:i:s',$v['create_time']);
           
        }        
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign('count', $count);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }


     /**
     * 添加充值配置
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function add_give()
    {
         $give = new GiveModel();
        if(request()->isAjax()){

            $param = input('post.');
            
           
            $flag = $give->insertGive($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

    
        return $this->fetch();
    }


    /**
     * 编辑充值
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function edit_give()
    {
        $give = new GiveModel();
        if(request()->isAjax()){
            $param = input('post.');           
            $flag = $give->editGive($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $id = input('param.id');
        $this->assign([
            'give' => $give->getOneGive($id)
        ]);
        return $this->fetch();
    }


    /**
     * 删除充值
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function del_give()
    {
        $id = input('param.id');
        $give = new GiveModel();
        $flag = $give->delGive($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
    
    
  
 
}