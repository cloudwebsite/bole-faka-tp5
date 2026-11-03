<?php
namespace app\admin\model;
use think\Model;
use think\Db;

class AttachModel extends Model
{
    protected $name = 'attach';  
    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳

    
	/**
     * 获取所有导航信息
     */ 
    public function getAllAttach()
    {
        return $this->select();
    }
    
    
    
    /**
     * 根据搜索条件获取所有的用户数量
     * @param $where
     */
    public function getAllCount()
    {
        return $this->count();
    }

 	/**
     * 根据条件获取全部数据
     */
    public function getAll($map, $Nowpage, $limits)
    {
        return $this->where($map)->page($Nowpage,$limits)->order('sort asc')->select();     
    }

    /**
     * 插入信息
     */
    public function insertAttach($param)
    {
        try{

            $result =$this->allowField(true)->insert($param);
            if(false === $result){
            	$ErrorMsg=$this->getError();
            	writelog(session('uid'),session('username'),'添加选项【'.$param['title'].'】失败     错误信息：'.$ErrorMsg,1);
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                writelog(session('uid'),session('username'),'添加选项【'.$param['title'].'】成功',1);
                return ['code' => 1, 'data' => '', 'msg' => '添加成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑信息
     */
    public function editAttach($param)
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
    public function getOneAttach($id)
    {
        $result=$this->where('attachgroupid', $id)->select();

        return $result;
    }


    /**
     * 删除信息
     */
    public function delAttach($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    
    /**
     * 删除信息
     */
    public function delAttachByAttachId($id)
    {
        try{
            $this->where('attachgroupid', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


}