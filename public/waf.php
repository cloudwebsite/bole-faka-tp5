<?php
namespace Waf;
/**
* 
*/
class Waf
{
	protected $args;
	protected $input_black;
	protected $input_args;
	protected $input_header;
	protected $input_cookies;
	protected $input_post;
	protected $scan_black;
	protected $cookie;
	protected $user_agent;
	protected $url;
	protected $post;
	function __construct()

	{
		$this->args=file_get_contents(__DIR__."/../waf/args.json");
		$this->scan_black=file_get_contents(__DIR__."/../waf/scan_black.json");
		$this->cookie=file_get_contents(__DIR__."/../waf/cookie.json");
		$this->url=file_get_contents(__DIR__."/../waf/url.json");
		$this->user_agent=file_get_contents(__DIR__."/../waf/user_agent.json");
		$this->post=file_get_contents(__DIR__."/../waf/post.json");
		$this->input_header=$_SERVER["HTTP_USER_AGENT"];
		$this->input_args=$this->filter_invisible(urldecode($this->filter_0x25($_SERVER["REQUEST_URI"])));
		$this->input_cookies=@$_SERVER["HTTP_COOKIE"];
		$this->input_post=$this->arr_to_str($_REQUEST);


	}
	//处理请求头黑名单严重 可封ip
	public function waf_black(){
		$sd=json_decode($this->scan_black,true);
		if (preg_match("#".$sd['header']."#i", $this->input_header)) {
			    header("HTTP/1.1 403 Forbidden");
				echo  $this->wafhtml(); exit();
				//return $value[2];
			    //echo 'ua';exit();

		}    
		if (preg_match("#".$sd['args']."#i", $this->input_args)) {
			    header("HTTP/1.1 403 Forbidden");
				echo  $this->wafhtml(); exit();
				//return $value[2];
                //echo 'QUERY_STRING';exit();

		}   
		if (preg_match("#".$sd['cookie']."#i", $this->input_cookies)) {
			    header("HTTP/1.1 403 Forbidden");
				echo  $this->wafhtml(); exit();
				//return $value[2];
			    //echo 'cookie';exit();

		}

	}
   //处理请求头ua 严重 可封ip
	public function waf_ua(){
		$sd=json_decode($this->user_agent,true);
		foreach ($sd as $key => $value) {
			if (preg_match("#".$value[1]."#i", $this->input_header)) {
			    header("HTTP/1.1 403 Forbidden");
				echo  $this->wafhtml(); exit();
				//return $value[2];
				//echo $value[2].'ua';exit();
				
			}
		}
	}

	public function waf(){
		$sd=json_decode($this->args,true);
		foreach ($sd as $key => $value) {
			if (preg_match("#".$value[1]."#i", $this->input_args)) {
			    header("HTTP/1.1 403 Forbidden");
				echo  $this->wafhtml(); exit();
				//return $value[2];
				//echo $value[2];exit();
				
			}
		}
		$sd=json_decode($this->url,true);
		foreach ($sd as $key => $value) {
			if (preg_match("#".$value[1]."#i", $this->input_args)) {
			    header("HTTP/1.1 403 Forbidden");
				echo  $this->wafhtml(); exit();
				//return $value[2];
				//echo $value[2].'$this->url';exit();
				
			}
		}

	}

	public function waf_post(){
		$sd=json_decode($this->post,true);
		foreach ($sd as $key => $value) {
			if (preg_match("#".$value[1]."#i", $this->input_post)) {
			    header("HTTP/1.1 403 Forbidden");
				echo  $this->wafhtml(); exit();
				//echo $value[2];exit();
				
			}
		}
	}
	public function waf_cookie(){
		$sd=json_decode($this->cookie,true);
		foreach ($sd as $key => $value) {
			if (preg_match("#".$value[1]."#i", $this->input_cookies)) {
			    header("HTTP/1.1 403 Forbidden");
				echo  $this->wafhtml(); exit();
				//return $value[2];
				//echo $value[2];exit();
				
			}
		}


	}



	public function arr_to_str($arr) {
		if (is_array($arr)) {
			//$t ='' ;
		    foreach ($arr as $key => $value) {
        		//  if(is_array($value)){
                   // $this->arr_to_str($value);
        		 // }
        		  
	         if(is_array($value)){
                 foreach($value as $newkey=>$newval){
                     if(is_array($newval)){
                         foreach($newval as $k=>$v){
        		            $t=$t.'&'.$k.'='.$v;
                         }
                         unset($newval);
                        }
		            $t=$t.'&'.$newkey.'='.$newval;
                 }
                 unset($value);
                }
			   $t=$t.'&'.$key.'='.$value;
		     }
		}else{
          $t=$arr;
		}
		
		return $t;
	}
	
	
	
	/*
	检测不可见字符造成的截断和绕过效果，注意网站请求带中文需要简单修改
	*/
	public function filter_invisible($str){
		for($i=0;$i<strlen($str);$i++){
			$ascii = ord($str[$i]);
			if($ascii>126 || $ascii < 32){ //有中文这里要修改
				if(!in_array($ascii, array(9,10,13))){
					//write_attack_log("interrupt");
				}else{
					$str = str_replace($ascii, " ", $str);
				}
			}
		}
		$str = str_replace(array("`","|",";",","), " ", $str);
		return $str;
	}

	/*
	检测网站程序存在二次编码绕过漏洞造成的%25绕过，此处是循环将%25替换成%，直至不存在%25
	*/
	public function filter_0x25($str){
		if(strpos($str,"%25") !== false){
			$str = str_replace("%25", "%", $str);
			return $this->filter_0x25($str);
		}else{
			return $str;
		}
	}
	public function wafhtml(){
		$str1='<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>网站防火墙</title>
<style>
*{margin:0;padding:0;color:#444}
body{font-size:14px;font-family:"宋体"}
.main{width:600px;margin:10% auto;}
.title{background: #20a53a;color: #fff;font-size: 16px;height: 40px;line-height: 40px;padding-left: 20px;}
.content{background-color:#f3f7f9; height:280px;border:1px dashed #c6d9b6;padding:20px}
.t1{border-bottom: 1px dashed #c6d9b6;color: #ff4000;font-weight: bold; margin: 0 0 20px; padding-bottom: 18px;}
.t2{margin-bottom:8px; font-weight:bold}
ol{margin:0 0 20px 22px;padding:0;}
ol li{line-height:30px}
</style>
</head>
<body>
	<div class="main">
		<div class="title">网站防火墙</div>
		<div class="content">
			<p class="t1">您的请求带有不合法参数，已被网站管理员设置拦截！</p>
			<p class="t2">可能原因：</p>
			<ol>
				<li>您提交的内容包含危险的攻击请求</li>
			</ol>
			<p class="t2">如何解决：</p>
			<ol>
				<li>检查提交内容；</li>
				<li>如网站托管，请联系空间提供商；</li>
				<li>普通网站访客，请联系网站管理员；</li>
			</ol>
		</div>
	</div>
</body>
</html>'; 
		return $str1;

	}




}

error_reporting(0);
ini_set('display_errors','off');
$Waf = new \Waf\Waf();
if (@$_GET['wafhtml']){
header("HTTP/1.1 403 Forbidden");
echo  $Waf->wafhtml(); exit();
}
$Waf->waf_black();
$Waf->waf_ua();
$Waf->waf();
$Waf->waf_cookie();
$Waf->waf_post();
?>