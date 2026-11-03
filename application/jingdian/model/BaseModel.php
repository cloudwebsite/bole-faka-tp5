<?php
use app\jingdian\model\GoodsListModel;
namespace app\jingdian\model;
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
        if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){
        	
        	return Db::name('child_ad')->where(array('ad_position_id' =>22,"status"=>1,"memberid"=>session('child_useraccount.id')))->select();
        }else{
        	return Db::name('ad')->where(array('ad_position_id' =>22,"status"=>1))->select();
        }
               
    }
    
     /**
     * [getAllCate 获取PC bannner]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getPCbanner()
    {
        if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){
        	return Db::name('child_ad')->where(array('ad_position_id' =>23,"status"=>1,"memberid"=>session('child_useraccount.id')))->order('orderby')->select(); 
        	
        }else{
        	return Db::name('ad')->where(array('ad_position_id' =>23,"status"=>1))->order('orderby')->select(); 
        }
              
    }
    
    
    
     /**
     * [getAllNavigation 获取导航连接]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getAllNavigation()
    {
        if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){
        	return Db::name('child_navigation')->where(array("status"=>1,'groupid'=>0,"memberid"=>session('child_useraccount.id')))->order("sort asc")->limit(10)->select();
        }else{
        	return Db::name('navigation')->where(array("status"=>1,'groupid'=>0))->order("sort asc")->limit(10)->select();
        }
               
    }
    
     /**
     * [getAllHot 获取爆款促销]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getAllHot()
    {
        //初始化商品类
        $GoodList=new GoodsListModel();
        if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){
        	$resultfl=Db::name('fl')->where(array("status"=>1))->order("sort")->select();
        	$resultchildfl=Db::name('child_fl')->where("status<>0 and hot<>0")->where('memberid',session('child_useraccount.id'))->order("sort")->select();
        	if(count($resultchildfl)==0){
        		return [];
        	}else{
        		$temp_child_flp=[];
        		//初始化商品类
        		
        		$GoodList=new GoodsListModel();
        		foreach ($resultchildfl as $key=>$val) {
	        	     foreach ($resultfl as &$vfl) {
	        	     		if($vfl['id']==$val['goodid']){ 
	        	     			if(($val['hot']==-1 && $vfl['hot']==1) || $val['hot']==1){
	        	     				
	        	     				if($val['mname']!=-1){
			        	     			$vfl['mname']=$val['mname'];
			        	     		}
			        	     		//商品价格替换
			        	     		if($val['mprice']>0){
			        	     			$vfl['mprice']=$val['mprice'];
			        	     		}
			        	     		//商品提示替换
			        	     		if($val['marketprice']!=-1){
			        	     			$vfl['marketprice']=$val['marketprice'];
			        	     		}
			        	     		//商品提示替换
			        	     		if($val['tuijian']!=-1){
			        	     			$vfl['tuijian']=$val['tuijian'];
			        	     		}
			        	     		//商品图片地址
			        	     		if($val['imgurl']!=-1){			        	     			
			        	     				$vfl['imgurl']=$val['imgurl'];
			        	     				$vfl['yunimgurl']=$val['yunimgurl'];			        	     			
			        	     		}
			        	     		$temp_child_flp[]=$vfl;
	        	     			}
	        	     			
	        	     		}
	        	     }
	        	}
	        	
	        	foreach ($temp_child_flp as &$v) {                     
	            $v=replaceImgurl($v);       
	        	}
	        	
	        	return $temp_child_flp;
        	}
        	
        }else{
        	
        	$result=Db::name('fl')->where(array("status"=>1,'hot'=>1))->order("sort")->select();       	
        	foreach ($result as &$v) {                     
            $v=replaceImgurl($v);       
        	}
        	return $result;  
        }
             
    }
     /**
     * [getAllCate 获取wap bannner]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getWAPbanner()
    {
        if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){
        	return Db::name('child_ad')->where(array('ad_position_id' =>24,"status"=>1,"memberid"=>session('child_useraccount.id')))->select();
        }else{
        	return Db::name('ad')->where(array('ad_position_id' =>24,"status"=>1))->order('orderby')->select();
        }       
    }
    
     /**
     * [getAllCate 获取卡密底部 bannner]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getKmbanner()
    {
        if(session('child_useraccount.id') && session('child_useraccount.fzstatus')==1){	
        	return Db::name('child_ad')->where(array('ad_position_id' =>25,"status"=>1,"memberid"=>session('child_useraccount.id')))->select();
        }else{
        	return Db::name('ad')->where(array('ad_position_id' =>25,"status"=>1))->order('orderby')->select();  
        }     
    }
    
     /**
     * [PC积分 bannner]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getJFpcbanner()
    {
       return Db::name('ad')->where(array('ad_position_id' =>26,"status"=>1))->order('orderby')->select();             
    }
    
    /**
     * [WAP  bannner]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getMJFpcbanner()
    {
       return Db::name('ad')->where(array('ad_position_id' =>27,"status"=>1))->order('orderby')->select();             
    }      
    
}