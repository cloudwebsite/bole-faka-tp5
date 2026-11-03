<?php
namespace app\admin\model;
use think\Model;

class GiveModel extends Model
{
    protected $name = 'pay_give';
    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳

    /**
     * 插入充值
     */
    public function insertGive($param)
    {
        try{
            $result =  $this->allowField(true)->save($param);
            if(false === $result){
            	$ErrorMsg=$this->getError();     
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                
                return ['code' => 1, 'data' => '', 'msg' => '添加成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑充值
     */
    public function editGive($param)
    {
        try{
            $result =  $this->allowField(true)->save($param, ['id' => $param['id']]);
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
    public function getOneGive($id)
    {
        return $this->where('id', $id)->find();
    }


    /**
     * 删除信息
     */
    public function delGive($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

}