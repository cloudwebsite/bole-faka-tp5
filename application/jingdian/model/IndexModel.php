<?php
namespace app\jingdian\model;
use think\Model;
use think\Db;
use app\jingdian\model\GoodsListModel;

class IndexModel extends Model
{

    
     protected $name = 'info';  
     protected $autoWriteTimestamp = true;   // 开启自动写入时间戳

    /**
     * [getAllArticle 根据订单号或联系方式分页查询]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
     public function getOrderByWhere($map, $Nowpage, $limits)
    {
  		//初始化商品类
        $GoodList=new GoodsListModel();
  		$result=$this->field("think_info.*,IFNULL(think_fl.mname,'未知') as name,IFNULL(think_fl.imgurl,'') as imgurl,IFNULL(think_fl.yunimgurl,'') as yunimgurl,think_fl.type as type")
  					->join('think_fl','think_fl.id = think_info.mflid','LEFT')
                   	->where($map)->page($Nowpage, $limits)
                   	->order('think_info.id desc')
                   	->select();
        if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){
        	$childData=Db::name('child_fl')->where('memberid',session('child_useraccount.id'))->select();		           
		        foreach($result as &$vorder){
		          	foreach($childData as $vchild){
		          		if($vorder['mflid']==$vchild['goodid'] && $vchild['imgurl']!=-1){
		           			$vorder['imgurl']=$vchild['imgurl'];
		           			$vorder['yunimgurl']=$vchild['yunimgurl'];
		           		}
		           	}
		        }
        }
        foreach ($result as &$v) {          
            $v=replaceImgurl($v);               
        }
        return $result;           	
          
    }
    
    /**
     * [getAllArticle 根据订单号或联系方式查询全部]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
     public function getCookieByWhere($map)
    {

        return $this->where($map)->where(['mstatus'=>0])->count();
           
    }
    
    public function getAllCount($map)
    {
        return $this->where($map)->count();
    }

    /**
     * [getAllCate 获取公告信息]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getGongGao()
    {
        if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){
        	return Db::name('child_article')->where(array('cate_id' =>1,'status' =>1,"memberid"=>session('child_useraccount.id')))->select();       	
        }else{
        	return Db::name('article')->where(array('cate_id' =>1,'status' =>1))->select();
        }
        
            
    }
    
    /**
     * [getAllCate 获取免责声明]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getMianZei()
    {
        
        if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){
        	return Db::name('child_article')->where(array('cate_id' =>3,'status' =>1,"memberid"=>session('child_useraccount.id')))->select();     	
        }else{
        	return Db::name('article')->where(array('cate_id' =>3,'status' =>1))->select();
        }    
    }
    
    
    /**
     * [getAllCate 获取常见问题]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getChangJian()
    {
        
        if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){       	
        	return Db::name('child_article')->where(array('cate_id' =>2,'status' =>1,"memberid"=>session('child_useraccount.id')))->limit(4)->select();    	
        }else{
        	return Db::name('article')->where(array('cate_id' =>2,'status' =>1))->limit(4)->select();
        } 
            
    }
    
    /**
     * [getAllCate 获取历史订单]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getLiShi()
    {
        return Db::name('info')->where(array('mstatus' =>1))->where('mamount>0')->order('id desc')->limit(20)->select();;
            
    }
    
    
}