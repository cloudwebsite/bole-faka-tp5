<?php
namespace app\jingdian\model;
use think\Model;
use think\Db;

class ArticleModel extends Model
{
	protected $name = 'child_article';  
   	protected $autoWriteTimestamp = true;   // 开启自动写入时间戳
   	/**
     * 根据搜索条件获取用户列表信息
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getArticleByWhere($map, $Nowpage, $limits,$childid)
    {
        return $this->field('think_child_article.*,think_article_cate.name')->join('think_article_cate', 'think_child_article.cate_id = think_article_cate.id')->where($map)->where('think_child_article.memberid',$childid)->page($Nowpage, $limits)->order('think_child_article.id desc')->select();
    }
    /**
     * 根据搜索条件获取所有的用户数量
     * @param $where
     */
    public function getAllCount($map,$childid)
    {      
        return $this->field('think_child_article.*,think_article_cate.name')->join('think_article_cate', 'think_child_article.cate_id = think_article_cate.id')->where($map)->where('think_child_article.memberid',$childid)->count();     
    }
    
    /**
     * [insertArticle 添加文章]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function insertArticle($param)
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
    public function updateArticle($param)
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
    public function getOneArticle($id)
    {
        return $this->where('id', $id)->find();
    }



    /**
     * [delArticle 删除文章]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function delArticle($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '文章删除成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
	
	 
    	  
}