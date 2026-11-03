<?php

namespace app\admin\model;
use think\Model;
use think\Db;
use app\jingdian\model\GoodsListModel;

class CateGoryGroupModel extends Model
{
    protected $name = 'category_group';   
    protected $autoWriteTimestamp = true;   // 开启自动写入时间戳

    /**
     * 根据条件获取全部数据
     */
    public function getAll($map, $Nowpage, $limits)
    {
        //初始化商品类
        $GoodList=new GoodsListModel();
        $result=$this->field("think_category_group.*,count(distinct think_fl.id)as count")
                    ->join('think_fl','think_category_group.id=think_fl.mlm','LEFT')
        			->where($map)->page($Nowpage,$limits)
        			->group('think_category_group.id')
        			->order('think_category_group.id')->select();
        
        foreach ($result as &$v) {          
	        $v=replaceImgurl($v);               
	    }
	    return $result;			     
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
            $result =  $this->validate('CateGoryGroupValidate')->allowField(true)->save($param);
            if(false === $result){
            	$ErrorMsg=$this->getError();
            	writelog(session('uid'),session('username'),'添加类目【'.$param['name'].'】失败     错误信息：'.$ErrorMsg,1);
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                writelog(session('uid'),session('username'),'添加类目【'.$param['name'].'】成功',1);
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
            $result =  $this->validate('CateGoryGroupValidate')->allowField(true)->save($param, ['id' => $param['id']]);
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
        //初始化商品类
        $GoodList=new GoodsListModel();
        $result=$this->where('id', $id)->find();
        $result=replaceImgurl($result);     
        return $result;
    }


    /**
     * 删除信息
     */
    public function delGroup($id)
    {
        try{
            $fl=Db::name('fl')->where('mlm',$id)->count();
            if($fl){
            	return ['code' => 0, 'data' => '', 'msg' => '此类目下存在'.$fl.'个商品，请先删除商品'];
            }
            
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    
}