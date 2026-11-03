<?php
namespace app\jingdian\model;
use think\Model;
use think\Db;

class FriendModel extends Model
{
	protected $name = 'child_ad';  
   	protected $autoWriteTimestamp = true;   // 开启自动写入时间戳
   	/**
     * 根据搜索条件获取用户列表信息
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getFriendByWhere($map, $Nowpage, $limits,$childid)
    {
        return $this->field('think_child_ad.*,think_ad_position.name')->join('think_ad_position', 'think_child_ad.ad_position_id = think_ad_position.id')->where($map)->where('think_child_ad.memberid',$childid)->page($Nowpage, $limits)->order('think_child_ad.id desc')->select();
    }
    /**
     * 根据搜索条件获取所有的用户数量
     * @param $where
     */
    public function getAllCount($map,$childid)
    {      
        return $this->field('think_child_ad.*,think_ad_position.name')->join('think_ad_position', 'think_child_ad.ad_position_id = think_ad_position.id')->where($map)->where('think_child_ad.memberid',$childid)->count();     
    }
    
    /**
     * [insertArticle 添加文章]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function insertAd($param)
    {
        try{
            $result = $this->allowField(true)->save($param);
            if(false === $result){             
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '文章添加成功'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [updateArticle 编辑文章]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function updateAd($param)
    {
        try{
            $result = $this->allowField(true)->save($param, ['id' => $param['id']]);
            if(false === $result){          
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '文章编辑成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [getOneArticle 根据文章id获取一条信息]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getOneAd($id)
    {
        return $this->where('id', $id)->find();
    }



    /**
     * [delArticle 删除文章]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function delAd($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '广告删除成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
	
	 
    	  
}