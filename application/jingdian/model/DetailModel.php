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
        if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){       	
        	return Db::name('child_article')->where(array('cate_id' =>2,'status' => 1,"memberid"=>session('child_useraccount.id')))->select();    	
        }else{
        	return Db::name('article')->where(array('cate_id' =>2,'status' => 1))->select();
        }      
    }

}