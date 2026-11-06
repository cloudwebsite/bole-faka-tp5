<?php
namespace app\admin\model;
use think\Model;
use think\Db;

class IntegralOrderModel extends Model
{
    protected $name = 'integralmall_order';  
    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳

    /**
     * 根据搜索条件获取用户列表信息
     */
    public function getOrderByWhere($map, $Nowpage, $limits)
    {
        
        $result=$this->field('think_integralmall_order.*,think_integralmall_index.mnamebie as mnamebie,think_integralmall_index.mprice as mprice,think_integralmall_index.imgurl as imgurl,think_integralmall_index.yunimgurl as yunimgurl,think_integralmall_index.type as type,a.account as account')
        			 ->join('think_integralmall_index','think_integralmall_index.id = think_integralmall_order.mflid','LEFT')
        			 ->join('think_member a','a.id = think_integralmall_order.memberid','LEFT')
            		 ->where($map)->where('maddtype<>88')->page($Nowpage, $limits)->order('think_integralmall_order.id desc')->select();
        foreach ($result as &$v) {
        	$v=replaceImgurl($v);
        }
        return $result; 
            
    }

    /**
     * 根据搜索条件获取所有的用户数量
     * @param $where
     */
    public function getAllCount()
    {
        return $this->where('maddtype<>88')->count();
    }

 /**
     * 根据条件获取全部数据
     */
    public function getAll($map, $Nowpage, $limits)
    {
        return $this->where($map)->page($Nowpage,$limits)->order('id asc')->select();     
    }

    /**
     * 插入信息
     */
    public function insertMember($param)
    {
        try{
            $result = $this->validate('MemberValidate')->allowField(true)->save($param);
            if(false === $result){            
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '添加成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑信息
     * @param $param
     */
    public function editMember($param)
    {
        try{
            $result =  $this->validate('MemberValidate')->allowField(true)->save($param, ['id' => $param['id']]);
            if(false === $result){            
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '编辑成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


    /**
     * 根据管理员id获取角色信息
     * @param $id
     */
    public function getOneMember($id)
    {
        return $this->where('id', $id)->find();
    }

    public function delMember($id)
    {
        try{
            $map['closed']=1;
            $this->save($map, ['id' => $id]);
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


}