<?php

namespace app\admin\controller;
use think\Controller;
use think\File;
use think\Request;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager; 

class Upload extends Base
{
	//图片上传
    public function upload(){
       $file = request()->file('file');
       $info = $file->validate(['ext'=>'jpg,png,gif'])->move(UPLOAD_PATH . DS . 'uploads/images');
       if($info){
		   //非法检测
            $file_path = 'uploads/images/' . $info->getSaveName();
			feifa_file($file_path);
			//非法检测
            echo $info->getSaveName();
        }else{
            echo $file->getError();
        }
    }

    //会员头像上传
    public function uploadface(){
       $file = request()->file('file');
       $info = $file->validate(['ext'=>'jpg,png,gif'])->move(UPLOAD_PATH . DS . 'uploads/face');
       if($info){
		   //非法检测
            $file_path = 'uploads/face/' . $info->getSaveName();
			feifa_file($file_path);
			//非法检测
            echo $info->getSaveName();
        }else{
            echo $file->getError();
        }
    }
    
    //网站图标上传
    public function uploadico(){
       $file = request()->file('file');
       $info = $file->validate(['ext'=>'jpg,png,gif,ico'])->move(UPLOAD_PATH . DS . 'uploads/images');
       if($info){
		   //非法检测
            $file_path = 'uploads/images/' . $info->getSaveName();
			feifa_file($file_path);
			//非法检测
         $name=$info->getSaveName();       	
         echo $name;
           
        }else{
            echo $file->getError();
        }
    }
    
    /*
     * 七牛云
     */
    public function Qiniuupload(){
		$file = request()->file('file');            		            		           
		// 要上传图片的本地路径
		$filePath = $file->getRealPath();            
		$ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);  //后缀
		// 上传到七牛后保存的文件名
		$key =substr(md5($file->getRealPath()) , 0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;
		require_once '../extend/qiniu/autoload.php';
		// 需要填写你的 Access Key 和 Secret Key
		$accessKey = config('qiniu_ak');
		$secretKey = config('qiniu_sk');
		// 构建鉴权对象
		$auth = new Auth($accessKey, $secretKey);
		// 要上传的空间
		$bucket =config('qiniu_bucket');
		$domain = config('qiniu_domain');
		$token = $auth->uploadToken($bucket);
		// 初始化 UploadManager 对象并进行文件的上传
		$uploadMgr = new UploadManager();
		// 调用 UploadManager 的 putFile 方法进行文件的上传
		list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
		if ($err !== null) {
		    echo $err;		     
		} else {
			echo $ret['key'];		   
		}
	}
	
	/*
     * 七牛云
     */
    public function QiniuuploadAction($filePath){           		            		           
		          
		// 上传到七牛后保存的文件名
		$key =substr($filePath ,strripos($filePath,'\\')+1);
	
		require_once '../extend/qiniu/autoload.php';
		// 需要填写你的 Access Key 和 Secret Key
		$accessKey = config('qiniu_ak');
		$secretKey = config('qiniu_sk');
		// 构建鉴权对象
		$auth = new Auth($accessKey, $secretKey);
		// 要上传的空间
		$bucket =config('qiniu_bucket');
		$domain = config('qiniu_domain');
		$token = $auth->uploadToken($bucket);
		// 初始化 UploadManager 对象并进行文件的上传
		$uploadMgr = new UploadManager();
		// 调用 UploadManager 的 putFile 方法进行文件的上传
		$filePath=str_replace('\\','/' ,$filePath);
		list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
		if ($err !== null) {
		    return ['code'=>-1,'msg'=>$err];		     
		} else {
			return ['code'=>1,'msg'=>'','src'=>$ret['key']];	
				   
		}
	}  
    
 
    


}