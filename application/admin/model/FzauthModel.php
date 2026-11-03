<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class FzauthModel extends Model
{
    protected $name = 'fz_auth';  
    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳

    /**
     * 根据搜索条件获取用户列表信息
     */
    public function getMemberByWhere($map, $Nowpage, $limits)
    {
        return $this->field("think_member.*,IFNULL(think_member_group.group_name,'注册会员') as group_name")
                    ->join('think_member_group', 'think_member.group_id = think_member_group.id','LEFT')
                    ->where($map)->page($Nowpage, $limits)->order('think_member.id desc')->select();
    }

    /**
     * 根据搜索条件获取所有的用户数量
     * @param $where
     */
    public function getAllCount($map)
    {
        return $this->where($map)->count();
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
    public function getOneFzauth($id)
    {
        return $this->where('memberid', $id)->find();
    }
    
    /**
     * 根据用户名获取id
     * @param $id
     */
    public function getOneMemberByaccount($account)
    {
        return $this->where('account', $account)->find();
    }



    /**
     * 删除信息
     * @param $id
     */
    public function delMember($id)
    {
        try{
             $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


}