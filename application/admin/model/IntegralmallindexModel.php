<?php
namespace app\admin\model;
use think\Model;
use think\Db;

class IntegralmallindexModel extends Model
{
   protected $name = 'integralmall_index';  
   protected $autoWriteTimestamp = true;   // 开启自动写入时间戳

    /**
     * 根据搜索条件获取用户列表信息
     */
    public function getMemberByWhere($map, $Nowpage, $limits,$status,$type)
    {
        if($status==999 && $type!=999){
        	$result=$this->field('think_integralmall_index.*,name')->join('think_integralmall_group','think_integralmall_group.id = think_integralmall_index.mlm','LEFT')
            ->where($map)->where('think_integralmall_index.type',$type)->page($Nowpage, $limits)->order('sort asc')->select();           
            foreach ($result as &$v) {          
	            $v=replaceImgurl($v);               
	        }
	        return $result;
         
           
        }else if($type==999 && $status!=999){
        	$result=$this->field('think_integralmall_index.*,name')->join('think_integralmall_group','think_integralmall_group.id = think_integralmall_index.mlm','LEFT')
            ->where($map)->where('think_integralmall_index.status',$status)->page($Nowpage, $limits)->order('sort asc')->select();
            
            foreach ($result as &$v) {          
	            $v=replaceImgurl($v);               
	        }
	        return $result;
        }else if($status==999 && $type==999){
        	$result=$this->field('think_integralmall_index.*,name')->join('think_integralmall_group','think_integralmall_group.id = think_integralmall_index.mlm','LEFT')
            ->where($map)->page($Nowpage, $limits)->order('sort asc')->select();
            
            foreach ($result as &$v) {          
	            $v=replaceImgurl($v);               
	        }
	        
	        return $result;
        }else{
        	$result=$this->field('think_integralmall_index.*,name')->join('think_integralmall_group','think_integralmall_group.id = think_integralmall_index.mlm','LEFT')
            ->where($map)->where('think_integralmall_index.status',$status)->where('think_integralmall_index.type',$type)->page($Nowpage, $limits)->order('sort asc')->select();
            foreach ($result as &$v) {          
	            $v=replaceImgurl($v);               
	        }
	        return $result;
        }
        
    }

    /**
     * 根据搜索条件获取所有的用户数量
     * @param $where
     */
    public function getAllCount($map,$status,$type)
    {
        if($status==999 && $type!=999){
        	return $this->where($map)->where('type',$type)->count();

        }else if($type==999 && $status!=999){
        	return $this->where($map)->where('status',$status)->count();
        }else if($status==999 && $type==999){
        	return $this->where($map)->count();
        }else{
        	return $this->where($map)->where('status',$status)->where('type',$type)->count();
        }
        
    }


    /**
     * 插入信息
     */
    public function insertMember($param)
    {
        try{
            $result = $this->allowField(true)->save($param);
            if(false === $result){            
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
            	
                return ['code' => 1, 'data' => '', 'msg' => '添加成功','id'=>$this->id];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑信息
     * @param $param
     */
    public function editCategory($param)
    {
        try{
            $result = $this->allowField(true)->save($param, ['id' => $param['id']]);
            if(false === $result){            
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
            	//echo $this->getlastsql();
                return ['code' => 1, 'data' => '', 'msg' => '编辑成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
	
	
	/**
     * 根据商品id获取商品信息
     * @param $id
     */
    public function getOneFl($id)
    {
        return $this->where('id', $id)->find();
    }

    /**
     * 根据管理员id获取角色信息
     * @param $id
     */
    public function getOneMember($id)
    {
        $result=$this->where('id', $id)->find();
        $result=replaceImgurl($result);        
        return $result;
    }


    /**
     * 删除管理员
     * @param $id
     */
    public function delUser($id)
    {
        try{

            $this->where('id', $id)->delete();
            Db::name('auth_group_access')->where('uid', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


    public function delMember($id)
    {
        try{                   
            $this->where('id',$id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


}