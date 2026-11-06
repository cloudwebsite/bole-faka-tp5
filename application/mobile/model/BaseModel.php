<?php

namespace app\mobile\model;
use think\Model;
use think\Db;

class BaseModel extends Model
{
  
    /**
     * [getAllCate 获取文章分类]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getAllCate()
    {
        return Db::name('article_cate')->field('id,name,status')->select();       
    }
    
     /**
     * [getAllCate 获取友情连接]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getYqHref()
    {
        return Db::name('ad')->where(array('ad_position_id' =>22,"status"=>1))->select();       
    } 
    
     /**
     * [getAllCate 获取wap bannner]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getWAPbanner()
    {
        return Db::name('ad')->where(array('ad_position_id' =>24,"status"=>1))->order('orderby')->select();       
    }
    
     /**
     * [getAllCate 获取卡密底部 bannner]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getKmbanner()
    {
        return Db::name('ad')->where(array('ad_position_id' =>25,"status"=>1))->order('orderby')->select();       
    }
    
     /**
     * [getAllHot 获取爆款促销]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getAllHot()
    {
        return Db::name('fl')->where(array("status"=>1,'hot'=>1))->order("sort")->select();       
    } 
    
}