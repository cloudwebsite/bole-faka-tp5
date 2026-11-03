<?php

namespace app\jingdian\model;
use think\Model;
use think\Db;

class DetailModel extends Model
{

    /**
     * [getAllArticle 获取文章详情]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getDetail($id)
    {
        return Db::name('article')->where('id',$id)->find();       
    }
    
     public function getAllDetail()
    {
        return Db::name('article')->where('cate_id','2')->order('id desc')->select();       
    }

}