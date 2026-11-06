<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class MemberGroupPriceModel extends Model
{
    protected $name = 'member_group_price';   
    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳

    /**
     * 根据条件获取全部数据
     */
    public function getAll($map, $Nowpage, $limits)
    {
        return $this->field('think_member_group_price.id as id,think_member_group_price.price as price,think_fl.mname as goodname,think_member_group.group_name as groupname')->join('think_member_group','think_member_group.id = think_member_group_price.membergroupid','LEFT')
        				->join('think_fl','think_fl.id = think_member_group_price.goodid','LEFT')
            			->where($map)->where('think_fl.status=1')->page($Nowpage,$limits)->order('think_member_group_price.id asc')->select();     
    }


    /**
     * 根据条件获取所有数量
     */
    public function getAllCount($map)
    {
        $result=$this->field('think_member_group_price.id as id,think_member_group_price.price as price,think_fl.mname as goodname,think_member_group.group_name as groupname')
        				->join('think_member_group','think_member_group.id = think_member_group_price.membergroupid','LEFT')
        				->join('think_fl','think_fl.id = think_member_group_price.goodid','LEFT')
            			->where($map)->where('think_fl.status=1')->count();
        
        
     
        return $result;
    }

    /**
     * 获取所有的会员组信息
     */ 
    public function getGroup()
    {
        return $this->select();
    }


    /**
     * 插入会员分组价格
     */
    public function insertGrouppirce($param)
    {
        try{                                       
            $result =$this->allowField(true)->save($param);
            if(false === $result){
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '添加成功'];
            }
        }catch(\Exception $e){
        	$errormsg=$e->getMessage();
        	if(strstr($errormsg,"SQLSTATE[23000]:")==false){
        		return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];	
        	}else{
        		return ['code' => -2, 'data' => '', 'msg' => '此商品 该分组已经设置过价格'];
        	}
            
        }
    }

    /**
     * 编辑信息
     */
    public function editGroup($param)
    {
        try{
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
     * 删除信息
     */
    public function delGroup($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        }catch(\Exception $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

}