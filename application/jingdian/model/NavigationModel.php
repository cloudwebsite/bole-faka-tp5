<?php
namespace app\jingdian\model;
use think\Model;
use think\Db;

class NavigationModel extends Model
{
	protected $name = 'child_navigation';  
   	protected $autoWriteTimestamp = true;   // 开启自动写入时间戳
   	/*
   	 * 
   	 */
	public function getNavigationByWhere($map, $Nowpage, $limits,$childid){
		
		$result=$this->where($map)->where('think_child_navigation.memberid',$childid)->page($Nowpage, $limits)->order('think_child_navigation.sort asc')->select();           
        return $result;
	}
	
	/**
     * 根据搜索条件获取所有的用户数量
     * @param $where
     */
    public function getAllCount($map,$childid)
    {      
        return $this->where($map)->where('status',1)->where('think_child_navigation.memberid',$childid)->order('think_child_navigation.sort asc')->count();     
    }
    
     /**
     * 插入信息
     */
    public function insertNavigation($param)
    {
        try{
            $result =$this->allowField(true)->save($param);

            
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
     * 编辑信息
     */
    public function editNavigation($param)
    {
        try{
            $result =  $this->allowField(true)->save($param, ['id' => $param['id'],'memberid'=>$param['memberid']]);
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
    public function getOneNavigation($id,$childid)
    {
        return $this->where('id', $id)->where('memberid', $childid)->find();
    }


    /**
     * 删除信息
     */
    public function delNavigation($id,$childid)
    {
        try{
            $this->where('id', $id)->where('memberid', $childid)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
	
	 
    	  
}