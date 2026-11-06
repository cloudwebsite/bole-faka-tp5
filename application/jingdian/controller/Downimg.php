<?php
namespace app\jingdian\controller;
use app\jingdian\model\IndexModel;
use app\jingdian\model\GoodsListModel;
use think\Config;
use think\Loader;
use think\Db;
use com\IpLocationqq;

class Downimg extends Base
{

 public function index(){

			$order = $this->safeOrderId(input('param.order'));
			if ($order === '') {
				exit('参数错误');
			}
			$contents="";
			$uf = $this->safeUploadPath($order . '.txt');
			if ($uf === '' || !is_file($uf)) {
				$contents = '文件已删除';
			} else {
			  $contents=file_get_contents($uf);
			}
			$checkBom = ($uf !== '' && is_file($uf)) ? checkBOM($uf) : false;
			if ($checkBom) 
			{
				$contents=substr($contents,3);
			}
			$tou=getSubstr($contents,"<pretou>","</pretou>");
			$wei=getSubstr($contents,"<prewei>","</prewei>");
			$zhengwen=str_replace($tou,'',$contents);
			$zhengwen=str_replace($wei,'',$zhengwen);
			$zhengwen=str_replace("<pretou>",'',$zhengwen);
			$zhengwen=str_replace("</pretou>",'',$zhengwen);
			$zhengwen=str_replace("<prewei>",'',$zhengwen);
			$zhengwen=str_replace("</prewei>",'',$zhengwen);
			$zhengwen=htmlspecialchars($zhengwen,ENT_QUOTES,"UTF-8");
			$zhanshitext = [];
			if((preg_match('/[0-9]{8}+\//',$zhengwen) !='0'&&preg_match('/\.jpg|\.png|\.gif$/is', $zhengwen)!='0')&&strpos($zhengwen,'http')===false){
					$zhengwen = explode("||||||",str_replace(array("\r\n", "\r", "\n"),'||||||',$zhengwen));
					$zhanshitext = array_filter($zhengwen);
				  
			}
			$this->picDownload($zhanshitext, $order);

 }




	//批量下载图片
 function picDownload($images, $order = null){	 
			if ($order === null) {
				$order = $this->safeOrderId(input('param.order'));
			}
			if ($order === '' || !is_array($images)) {
				exit('参数错误');
			}
            $uploadRoot = realpath(UPLOAD_PATH . '/upload');
            if ($uploadRoot === false) {
                @mkdir(UPLOAD_PATH . '/upload', 0755, true);
                $uploadRoot = realpath(UPLOAD_PATH . '/upload');
            }
            $filename = $uploadRoot . DIRECTORY_SEPARATOR . $order . '.zip';
            // 再次确认落在 upload 目录内
            if (strpos(realpath(dirname($filename)) ?: '', $uploadRoot) !== 0) {
                exit('路径非法');
            }

			if(!file_exists($filename)){		
            // 生成文件
            $zip = new \ZipArchive ();
            // 使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释
            if ($zip->open ($filename ,\ZipArchive::OVERWRITE) !== true) {
                //OVERWRITE 参数会覆写压缩包的文件 文件必须已经存在
                if($zip->open ($filename ,\ZipArchive::CREATE) !== true){
                    // 文件不存在则生成一个新的文件 用CREATE打开文件会追加内容至zip
                    exit ( '无法打开文件，或者文件创建失败' );
                }
            }
            $imgRoot = realpath(UPLOAD_PATH . '/uploads/images');
            foreach($images as $key => $v){
                $base = $this->get_basename($v);
                if ($base === '' || preg_match('/\.\.|[\\\\\\/]/', $base)) {
                    continue;
                }
                if (!preg_match('/\.(jpe?g|png|gif|webp)$/i', $base)) {
                    continue;
                }
                $urlfile = UPLOAD_PATH . '/uploads/images/' . $base;
                $real = realpath($urlfile);
                if ($real === false || $imgRoot === false || strpos($real, $imgRoot) !== 0) {
                    continue;
                }
                if(file_exists($real)){
                    $zip->addFile($real, iconv('utf-8','gb2312',$base));
                }
            }
        // 关闭
        $zip->close ();
		}
		
        //下面是输出下载;
		
        header ( "Cache-Control: max-age=0" );
        header ( "Content-Description: File Transfer" );
        header ( 'Content-disposition: attachment; filename=' . basename ( $filename ) ); // 文件名
        header ( "Content-Type: application/zip" ); // zip格式的
        header ( "Content-Transfer-Encoding: binary" ); // 告诉浏览器，这是二进制文件
        header ( 'Content-Length: ' . filesize ( $filename ) ); // 告诉浏览器，文件大小
        @readfile ( $filename );//输出文件;
        @unlink($filename);
            exit;
      }
	  
	  
	   function get_basename($filename){
		 return preg_replace('/^.+[\\\\\\/]/', '', $filename);
		}

    /**
     * 校验订单号格式（字母、数字、下划线、短横线）
     */
    protected function safeOrderId($order)
    {
        $order = (string)$order;
        if ($order === '' || !preg_match('/^[A-Za-z0-9_\-]+$/', $order)) {
            return '';
        }
        if (strlen($order) > 128) {
            return '';
        }
        return $order;
    }

    /**
     * 解析 upload/ 下文件，realpath 必须落在 upload 目录
     */
    protected function safeUploadPath($relative)
    {
        $relative = ltrim(str_replace(['\\', '..'], ['/', ''], (string)$relative), '/');
        if ($relative === '' || strpos($relative, '/') !== false) {
            // 仅允许单层文件名
            if (strpos($relative, '/') !== false || strpos($relative, '\\') !== false) {
                return '';
            }
        }
        $base = realpath(UPLOAD_PATH . '/upload');
        if ($base === false) {
            return '';
        }
        $full = $base . DIRECTORY_SEPARATOR . $relative;
        $realDir = realpath(dirname($full));
        if ($realDir === false || strpos($realDir, $base) !== 0) {
            return '';
        }
        return $full;
    }
		
		
}
