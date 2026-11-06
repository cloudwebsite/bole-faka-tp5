<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class MemberGroupModel extends Model
{
    protected $name = 'member_group';   
    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳

    /**
     * 根据条件获取全部数据
     */
    public function getAll($map, $Nowpage, $limits)
    {
        return $this->field("think_member_group.*,count(distinct think_member.id)as count")
                    ->join('think_member','think_member.group_id=think_member_group.id','LEFT')
                    ->where($map)->page($Nowpage,$limits)
                    ->group('think_member_group.id')
                    ->order('think_member_group.discount desc')->select();     
    }


    /**
     * 根据条件获取所有数量
     */
    public function getAllCount($map)
    {
        return $this->where($map)->count();
    }

    /**
     * 获取所有的会员组信息
     */ 
    public function getGroup()
    {
        return $this->select();
    }


    /**
     * 插入信息
     */
    public function insertGroup($param)
    {
        try{
            $res=self::getdefault();
            if($res && $param['is_default']==1 && $res['id']!=$param['id']){
            	 return ['code' => -1, 'data' => '', 'msg' => '默认等级已存在'];
            }
            
            $result =  $this->validate('MemberGroupValidate')->save($param);
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
     */
    public function editGroup($param)
    {
        try{
            $res=self::getdefault();
            if($res && $param['is_default']==1 && $res['id']!=$param['id']){
            	 return ['code' => -1, 'data' => '', 'msg' => '默认等级已存在'];
            }
            $result =  $this->validate('MemberGroupValidate')->save($param, ['id' => $param['id']]);
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
     * 根据id获取一条信息
     */
    public function getOne($id)
    {
        return $this->where('id', $id)->find();
    }
    
    /**
     * 获取默认等级
     */
    public function getdefault()
    {
        return $this->where('is_default', '1')->find();
    }
    
    /**
     * 获取积分为0的等级
     */
    public function getpoint0()
    {
        return $this->where('point', 0)->find();
    }


    /**
     * 删除信息
     */
    public function delGroup($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

}