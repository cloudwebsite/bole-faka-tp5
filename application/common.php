<?php
use think\Db;
use Aliyun\Core\Config;  
use Aliyun\Core\Profile\DefaultProfile;  
use Aliyun\Core\DefaultAcsClient;  
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;


/**
 * 字符串截取，支持中文和其他编码
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true) {
	if (function_exists("mb_substr"))
		$slice = mb_substr($str, $start, $length, $charset);
	elseif (function_exists('iconv_substr')) {
		$slice = iconv_substr($str, $start, $length, $charset);
		if (false === $slice) {
			$slice = '';
		}
	} else {
		$re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
		$re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
		$re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
		$re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
		preg_match_all($re[$charset], $str, $match);
		$slice = join("", array_slice($match[0], $start, $length));
	}
	return $suffix ? $slice . '...' : $slice;
}
// 
/**
 * 文件非法检测，针对图片马
 */
/**
 * 上传文件内容扫描：分块通读，拒绝含脚本特征的文件
 */
function feifa_file($file)
{
        $filename = UPLOAD_PATH . $file;
        if (!is_file($filename) || !is_readable($filename)) {
            exit(json_encode(['code' => -1, 'msg' => '上传失败,文件不存在！'], JSON_UNESCAPED_UNICODE));
        }
        $needles = [
            '<?php', '<?=', '<%', '<script', '</script',
            'phpinfo(', 'eval(', 'assert(', 'base64_decode(',
            'shell_exec(', 'passthru(', 'proc_open(', 'popen(',
            'create_function(', '$_GET', '$_POST', '$_REQUEST',
            '.htaccess',
        ];
        $source = fopen($filename, 'rb');
        if (!$source) {
            @unlink($filename);
            exit(json_encode(['code' => -1, 'msg' => '上传失败,文件非法！'], JSON_UNESCAPED_UNICODE));
        }
        $prev = '';
        $chunkSize = 8192;
        $bad = false;
        while (!feof($source)) {
            $chunk = fread($source, $chunkSize);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $hay = $prev . $chunk;
            $hayLower = strtolower($hay);
            foreach ($needles as $n) {
                if (stripos($hayLower, strtolower($n)) !== false) {
                    $bad = true;
                    break 2;
                }
            }
            // 保留尾部重叠，避免特征跨块
            $prev = substr($chunk, -64);
        }
        if (is_resource($source)) {
            fclose($source);
        }
        if ($bad) {
            @unlink($filename);
            exit(json_encode(['code' => -1, 'msg' => '上传失败,文件非法！'], JSON_UNESCAPED_UNICODE));
        }
}

/**
 * 读取配置
 * @return array 
 */
function load_config(){
    $list = Db::name('config')->select();
    $config = [];
    foreach ($list as $k => $v) {
        $config[trim($v['name'])]=$v['value'];
    }

    return $config;
}

/**
 * 读取分站配置
 * @return array 
 */
function load_config_child($memberid){
    $list = Db::name('child_config')->where('memberid',$memberid)->select();
    $config = [];
    foreach ($list as $k => $v) {
        $config[trim($v['name'])]=$v['value'];
    }
    return $config;
}

/**
 * 用数据库配置重建主站配置缓存（不要把运行时整包配置写进缓存）
 */
function rebuild_db_config_cache(){
    $config = load_config();
    cache('db_config_data', $config);
    return $config;
}

/**
 * 仅用 load_config_child() 结果重建分站配置缓存
 */
function rebuild_child_config_cache($memberid){
    $config = load_config_child($memberid);
    cache('config_child_' . $memberid, $config);
    return $config;
}

/**
 * 校验表单提交令牌（表单字段 __token__ 或请求头 X-CSRF-TOKEN）
 * @param bool $consume true=校验通过后作废（整页提交）；false=同页多次 AJAX 可复用
 */
function check_form_token($consume = true){
    $token = input('post.__token__/s', '');
    if ($token === '') {
        $token = input('param.__token__/s', '');
    }
    if ($token === '' && function_exists('request')) {
        $token = (string)request()->header('X-Csrf-Token', '');
    }
    if ($token === '' || !\think\Session::has('__token__')) {
        return false;
    }
    if (\think\Session::get('__token__') !== $token) {
        if ($consume) {
            \think\Session::delete('__token__');
        }
        return false;
    }
    if ($consume) {
        \think\Session::delete('__token__');
    }
    return true;
}


/**
* 验证手机号是否正确
* @author honfei
* @param number $mobile
*/
function isMobile($mobile) {
    if (!is_numeric($mobile)) {
        return false;
    }
    return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
}


/** 
 * 发送短息 
 * @param string $mobile    接收手机号 
 * @param string $tplCode   短信模板
 * @param array  $tplParam  短信内容数组
 * @return array 
 */  
function sendMsg($mobile,$tplCode,$tplParam){  
    if( empty($mobile) || empty($tplCode) ) return array('Message'=>'缺少参数','Code'=>'Error');  
    if(!isMobile($mobile)) return array('Message'=>'无效的手机号','Code'=>'Error');  
      
    $accessKeyId = config('alisms_appkey');  
    $accessKeySecret = config('alisms_appsecret');  
	$signName = config('alisms_signname'); 
    if( empty($accessKeyId) || empty($accessKeySecret) ) return array('Message'=>'请先在后台配置短信差数','Code'=>'Error'); 	
	
	 
    $templateParam = $tplParam; 
    $templateCode = $tplCode; 	
	if(is_array($templateParam)){
		foreach ($templateParam as $k => $v) {
			$templateCode = str_replace('${' . $k . '}', $v, $templateCode);
		}
	}
	
    $url='http://xw.wyisp.com/api/send_sms';
    $post_data = array();
    $post_data['id'] = $accessKeyId;
    $post_data['docking'] = $accessKeySecret;
    $post_data['content'] = "【{$signName}】".$templateCode; 
    $post_data['mobile'] = $mobile;
    $o='';
    foreach ($post_data as $k=>$v)
    {
       $o.="$k=".urlencode($v).'&';
    }
    $data=substr($o,0,-1);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //如果需要将结果直接返回到变量里，那加上这句。
    $content = curl_exec($ch);
    if(curl_errno($ch)){	
    	echo "erro";
    }
    curl_close($ch); //关闭curl链接
    
    $smscode = json_decode($content,true);
	if ($smscode['code'] == '1'){
    	$result['Code'] =  "OK";
    	$result['Message'] =  "发送成功";
    }else{
    	$result['Code'] =  "0";
    	$result['Message'] =  "发送失败";
    }
    return $result;
}




//生成网址的二维码 返回图片地址
function Qrcode($token, $url, $size = 8){ 
    $md5 = md5($token);
    $dir = date('Ymd'). '/' . substr($md5, 0, 10) . '/';
    $patch = 'qrcode/' . $dir;
    if (!file_exists($patch)){
        mkdir($patch, 0755, true);
    }
    $file = 'qrcode/' . $dir . $md5 . '.png';
    $fileName =  $file;
    if (!file_exists($fileName)) {

        $level = 'L';
        $data = $url;
        QRcode::png($data, $fileName, $level, $size, 2, true);
    }
    return $file;
}



/**
 * 循环删除目录和文件
 * @param string $dir_name
 * @return bool
 */
function delete_dir_file($dir_name) {
    $result = false;
    if(is_dir($dir_name)){
        if ($handle = opendir($dir_name)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if (is_dir($dir_name . DS . $item)) {
                        delete_dir_file($dir_name . DS . $item);
                    } else {
                        unlink($dir_name . DS . $item);
                    }
                }
            }
            closedir($handle);
            if (rmdir($dir_name)) {
                $result = true;
            }
        }
    }

    return $result;
}



//时间格式化1
function formatTime($time) {
    $now_time = time();
    $t = $now_time - $time;
    $mon = (int) ($t / (86400 * 30));
    if ($mon >= 1) {
        return '一个月前';
    }
    $day = (int) ($t / 86400);
    if ($day >= 1) {
        return $day . '天前';
    }
    $h = (int) ($t / 3600);
    if ($h >= 1) {
        return $h . '小时前';
    }
    $min = (int) ($t / 60);
    if ($min >= 1) {
        return $min . '分钟前';
    }
    return '刚刚';
}


//时间格式化2
function pincheTime($time) {
     $today  =  strtotime(date('Y-m-d')); //今天零点
      $here   =  (int)(($time - $today)/86400) ; 
      if($here==1){
          return '明天';  
      }
      if($here==2) {
          return '后天';  
      }
      if($here>=3 && $here<7){
          return $here.'天后';  
      }
      if($here>=7 && $here<30){
          return '一周后';  
      }
      if($here>=30 && $here<365){
          return '一个月后';  
      }
      if($here>=365){
          $r = (int)($here/365).'年后'; 
          return   $r;
      }
     return '今天';
}


function getRandomString($len, $chars=null){
    if (is_null($chars)){
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    }  
    mt_srand(10000000*(double)microtime());
    for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++){
        $str .= $chars[mt_rand(0, $lc)];  
    }
    return $str;
}


function random_str($length){
    //生成一个包含 大写英文字母, 小写英文字母, 数字 的数组
    $arr = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
 
    $str = '';
    $arr_len = count($arr);
    for ($i = 0; $i < $length; $i++)
    {
        $rand = mt_rand(0, $arr_len-1);
        $str.=$arr[$rand];
    }
 
    return $str;
}


function SendMail($address,$title,$message,$attachment)
{
            $mail=new \phpmailer\phpmailer;
            $mail->isSMTP();// 使用SMTP服务  
            $mail->CharSet = "utf8";// 编码格式为utf8，不设置编码的话，中文会出现乱码  
            $mail->Host = config("mail_host");// 发送方的SMTP服务器地址  
            $mail->SMTPAuth = true;// 是否使用身份验证  
            $mail->Username =config("mail_username");// 发送方的163邮箱用户名，就是你申请163的SMTP服务使用的163邮箱</span><span style="color:#333333;">  
            $mail->Password =config("mail_password");// 发送方的邮箱密码，注意用163邮箱这里填写的是“客户端授权密码”而不是邮箱的登录密码！</span><span style="color:#333333;">  
            $mail->Port =config("mail_port");// 163邮箱的ssl协议方式端口号是465/994  
            $mail->setFrom(config("mail_username"),config("mail_senduser"));// 设置发件人信息，如邮件格式说明中的发件人，这里会显示为Mailer(xxxx@163.com），Mailer是当做名字显示  
            $mail->addAddress($address,"");// 设置收件人信息，如邮件格式说明中的收件人，这里会显示为Liang(yyyy@163.com)  
            $mail->SMTPSecure = "ssl";
            $mail->addReplyTo(config("mail_username"),"Reply");// 设置回复人信息，指的是收件人收到邮件后，如果要回复，回复邮件将发送到的邮箱地址  
            if(!empty($attachment)){
            	$mail->addAttachment($attachment);// 添加附件  
            }
            
            $mail->Subject ="=?utf-8?B?".base64_encode($title)."?=";// 邮件标题 
            $mail->Body =$message;// 邮件正文  
            if(!$mail->send()){// 发送邮件  
                
                //echo "Mailer Error: ".$mail->ErrorInfo;// 输出错误信息  
                $code=0;
                $msg=$mail->ErrorInfo;
                
            }else{  
                //echo '发送成功'; 
                $code=1; 
                $msg='发送成功';
            }  
            return array('code'=>$code,'msg'=>$msg);
            

  
}

function getIP() 
{
if (getenv("HTTP_CLIENT_IP")) 
$ip = getenv("HTTP_CLIENT_IP"); 
else if(getenv("HTTP_X_FORWARDED_FOR")) 
$ip = getenv("HTTP_X_FORWARDED_FOR"); 
else if(getenv("REMOTE_ADDR")) 
$ip = getenv("REMOTE_ADDR"); 
else 
$ip = "Unknow"; 
return $ip; 
} 

function get_client_ip($type = 0) {
    $type       =  $type ? 1 : 0;
    static $ip  =   NULL;
    if ($ip !== NULL) return $ip[$type];
    if(isset($_SERVER['HTTP_X_REAL_IP'])){//nginx 代理模式下，获取客户端真实IP
        $ip=$_SERVER['HTTP_X_REAL_IP'];
    }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {//客户端的ip
        $ip     =   $_SERVER['HTTP_CLIENT_IP'];
    }elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {//浏览当前页面的用户计算机的网关
        $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos    =   array_search('unknown',$arr);
        if(false !== $pos) unset($arr[$pos]);
        $ip     =   trim($arr[0]);
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip     =   $_SERVER['REMOTE_ADDR'];//浏览当前页面的用户计算机的ip地址
    }else{
        $ip=$_SERVER['REMOTE_ADDR'];
    }
    $ip = trim((string)$ip);
    // IPv6 zone id
    if (strpos($ip, '%') !== false) {
        $ip = str_replace('%25', '%', $ip);
        $ip = strstr($ip, '%', true);
    }
    // 合法 IPv4 / IPv6（ip2long 不支持 IPv6，旧逻辑会把 IPv6 打成 0.0.0.0）
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $bin = @inet_pton($ip);
        $norm = ($bin !== false) ? @inet_ntop($bin) : $ip;
        $ip = array($norm ?: $ip, 0);
        return $ip[$type];
    }
    $long = sprintf("%u", ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

function isMobilePc()
	{ 
	    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
	    if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
	    {
	        return true;
	    } 
	    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
	    if (isset ($_SERVER['HTTP_VIA']))
	    { 
	        // 找不到为flase,否则为true
	        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
	    } 
	    // 脑残法，判断手机发送的客户端标志,兼容性有待提高
	    if (isset ($_SERVER['HTTP_USER_AGENT']))
	    {
	        $clientkeywords = array ('nokia',
	            'sony',
	            'ericsson',
	            'mot',
	            'samsung',
	            'htc',
	            'sgh',
	            'lg',
	            'sharp',
	            'sie-',
	            'philips',
	            'panasonic',
	            'alcatel',
	            'lenovo',
	            'iphone',
	            'ipod',
	            'blackberry',
	            'meizu',
	            'android',
	            'netfront',
	            'symbian',
	            'ucweb',
	            'windowsce',
	            'palm',
	            'operamini',
	            'operamobi',
	            'openwave',
	            'nexusone',
	            'cldc',
	            'midp',
	            'wap',
	            'mobile'
	            ); 
	        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
	        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
	        {
	            return true;
	        } 
	    } 
	    // 协议法，因为有可能不准确，放到最后判断
	    if (isset ($_SERVER['HTTP_ACCEPT']))
	    { 
	        // 如果只支持wml并且不支持html那一定是移动设备
	        // 如果支持wml和html但是wml在html之前则是移动设备
	        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
	        {
	            return true;
	        } 
	    } 
	    return false;
	} 
	
function is_weixin() { 
	    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) { 
	        return true; 
	    } return false; 
}
function is_qq() {
  if (strpos($_SERVER['HTTP_USER_AGENT'], 'QQ/') !== false) { 
	        return true; 
	    } return false; 
}
function is_weixinorqq(){
  if(is_weixin() || is_qq()){
    return true;
  }return false;
}

/**
 * 订单卡密 txt 绝对路径（public/upload/{订单号}.txt）
 * 避免相对路径受工作目录影响导致「刚提卡文件却读不到」
 */
function kami_order_txt_path($order)
{
	$order = preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$order);
	if ($order === '') {
		return '';
	}
	$dir = rtrim(defined('UPLOAD_PATH') ? UPLOAD_PATH : (ROOT_PATH . 'public' . DS), '/\\') . DIRECTORY_SEPARATOR . 'upload';
	if (!is_dir($dir)) {
		@mkdir($dir, 0755, true);
	}
	return $dir . DIRECTORY_SEPARATOR . $order . '.txt';
}

/**
* 统一返回格式
*/
function TyReturn($msg,$code = -1,$data = []){
	$rs = ['code'=>$code,'msg'=>$msg];
	if(!empty($data))$rs['data'] = $data;
	return $rs;
}

/**
* 为空判断
*/
function SuperIsEmpty($str){
	if(empty($str)){
	return true;
	}return false;
}
/*
 * 过滤所有空格
 */
function myTrim($str)
{
 $search = array(" ","　","\n","\r","\t");
 $replace = array("","","","","");
 return str_replace($search, $replace, $str);
}
/**
 * 记录会员金额日志
 * @param  [type] $uid         [用户id]
 * @param  [type] $username    [用户名]
 * @param  [type] $description [描述]
 * @param  [type] $status      [状态]
 * @return [type]              [description]
 */
function writemoneylog($memberid,$make,$type,$money,$userip='')
{
    if($userip==''){
      $userip=getIP();
    }
    $data['memberid'] = $memberid;
    $data['money'] = $money;
    $data['make'] = $make;
    $data['type'] = $type;
    $data['ip'] = $userip;
    $data['create_time'] = time();
    $log = Db::name('member_money_log')->insert($data);

}
/**
 * 记录会员登录日志
 * @param  [type] $uid         [用户id]
 * @param  [type] $username    [用户名]
 * @param  [type] $description [描述]
 * @param  [type] $status      [状态]
 * @return [type]              [description]
 */
function writeloginlog($memberid,$make)
{
    $data['memberid'] = $memberid;
    $data['description'] = $make;
    $data['ip'] = getIP();
    $data['create_time'] = time();
    $log = Db::name('member_login_log')->insert($data);
}

/**
 * 记录会员积分日志
 * @param  [type] $uid         [用户id]
 * @param  [type] $username    [用户名]
 * @param  [type] $description [描述]
 * @param  [type] $status      [状态]
 * @return [type]              [description]
 */
function writeintegrallog($memberid,$make,$type,$integral)
{

    $data['memberid'] = $memberid;
    $data['integral'] = $integral;
    $data['make'] = $make;
    $data['type'] = $type;
    $data['ip'] = getIP();
    $data['create_time'] = time();
    $log = Db::name('member_integral_log')->insert($data);

}

/**
 * 记录日志
 * @param  [type] $uid         [用户id]
 * @param  [type] $username    [用户名]
 * @param  [type] $description [描述]
 * @param  [type] $status      [状态]
 * @return [type]              [description]
 */
function writelog($uid,$username,$description,$status)
{

    $data['admin_id'] = $uid;
    $data['admin_name'] = $username;
    $data['description'] = $description;
    $data['status'] = $status;
    $data['ip'] = getIP();
    $data['add_time'] = time();
    $log = Db::name('Log')->insert($data);

}

/**
 * 记录订单历史
 * @param  [type] $uid         [用户id]
 * @param  [type] $username    [用户名]
 * @param  [type] $description [描述]
 * @param  [type] $status      [状态]
 * @return [type]              [description]
 */
function writeinfohistory($param)
{
    $log = Db::name('info_history')->insert($param);
}

/**
 * 记录分销明细
 * @param  [type] $uid         [用户id]
 * @param  [type] $username    [用户名]
 * @param  [type] $description [描述]
 * @param  [type] $status      [状态]
 * @return [type]              [description]
 */
function writetgmoneylog($param)
{
    $log = Db::name('tgmoney_log')->insert($param);
    
}

/**
 * 记录发送日志
 * @param  [type] $uid         [用户id]
 * @param  [type] $username    [用户名]
 * @param  [type] $description [描述]
 * @param  [type] $status      [状态]
 * @return [type]              [description]
 */
function writesendsmslog($param)
{
    $log = Db::name('sendsms_log')->insert($param);
}

/**
 * 记录系统日志
 * @param  [type] $uid         [用户id]
 * @param  [type] $username    [用户名]
 * @param  [type] $description [描述]
 * @param  [type] $status      [状态]
 * @return [type]              [description]
 */
function writesystemlog($param)
{
    $header=request()->header();
    $referer=isset($header['referer'])?$header['referer']:''; 
    $systemLogData=['url'=>strtolower($header['host']).'/'.strtolower(request()->module()).'/'.strtolower(request()->controller()).'/'.strtolower(request()->action()),
					'referer'=>$referer,
					'userid'=>session('useraccount.id')?session('useraccount.id'):0,
					'ip'=>getIP(),
					'make'=>$param['make'],
					'level'=>$param['level'],
					'create_time'=>time()
					];
    $log = Db::name('system_log')->insert($systemLogData);
}

/*
 * 金额累计
 */
function writeamounttotal($memberid,$money,$type){
	$result=Db::name('amount_total_log')->where('memberid',$memberid)->find();	
	if($result==false){
		$sql="insert ignore into think_amount_total_log set ".$type."=:addmoney,memberid=:memberid";
		$return=Db::execute($sql,['addmoney'=>$money,'memberid'=>$memberid]);		
	}else{
		$sql="update think_amount_total_log set ".$type."=".$type."+:addmoney where memberid=:memberid";
		$return=Db::execute($sql,['addmoney'=>$money,'memberid'=>$memberid]);		
	}
}

/**
 * 会员卡密兑换余额
 * @return array code 1成功
 */
function redeem_recharge_kami($memberid, $code)
{
    $memberid = intval($memberid);
    $code = trim($code);
    if ($memberid < 1) {
        return ['code' => -1, 'msg' => '请先登录', 'money' => 0];
    }
    if ($code === '') {
        return ['code' => -1, 'msg' => '请输入卡密', 'money' => 0];
    }
    if (config('recharge_kami_open') === '0' || config('recharge_kami_open') === 0) {
        return ['code' => -1, 'msg' => '卡密充值未开启', 'money' => 0];
    }

    Db::startTrans();
    try {
        $rows = Db::query('SELECT * FROM think_recharge_kami WHERE code=:code AND status=0 FOR UPDATE', ['code' => $code]);
        if (empty($rows)) {
            Db::rollback();
            $exists = Db::name('recharge_kami')->where('code', $code)->find();
            if ($exists) {
                return ['code' => -1, 'msg' => '卡密已被使用', 'money' => 0];
            }
            return ['code' => -1, 'msg' => '卡密不存在', 'money' => 0];
        }
        $kami = $rows[0];
        $face = abs(floatval($kami['money']));
        if ($face <= 0) {
            Db::rollback();
            return ['code' => -1, 'msg' => '卡密面额异常', 'money' => 0];
        }

        $bool = Db::execute(
            'UPDATE think_member SET money=money+:addmoney WHERE id=:memberid',
            ['addmoney' => $face, 'memberid' => $memberid]
        );
        if ($bool === false) {
            Db::rollback();
            return ['code' => -1, 'msg' => '更新余额失败', 'money' => 0];
        }

        $now = time();
        $ip = getIP();
        $ok = Db::execute(
            'UPDATE think_recharge_kami SET status=1,memberid=:memberid,use_time=:use_time,userip=:userip,update_time=:update_time WHERE id=:id AND status=0',
            [
                'memberid' => $memberid,
                'use_time' => $now,
                'userip' => $ip,
                'update_time' => $now,
                'id' => $kami['id'],
            ]
        );
        if (!$ok) {
            Db::rollback();
            return ['code' => -1, 'msg' => '卡密状态更新失败', 'money' => 0];
        }

        writemoneylog($memberid, '卡密充值：' . $code, 0, $face, $ip);
        writeamounttotal($memberid, $face, 'czmoney');

        Db::commit();
        $member = Db::name('member')->where('id', $memberid)->find();
        return [
            'code' => 1,
            'msg' => '兑换成功，到账' . $face . '元',
            'money' => isset($member['money']) ? $member['money'] : 0,
            'face' => $face,
        ];
    } catch (\Exception $e) {
        Db::rollback();
        return ['code' => -1, 'msg' => $e->getMessage(), 'money' => 0];
    }
}

/*以下是取中间文本的函数 
  getSubstr=调用名称
  $str=预取全文本 
  $leftStr=左边文本
  $rightStr=右边文本
*/
function getSubstr($str, $leftStr, $rightStr)
{
    $left = strpos($str, $leftStr);
    //echo '左边:'.$left;
    $right = strpos($str, $rightStr,$left);
    //echo '<br>右边:'.$right;
    if($left < 0 or $right <= $left) return '';
    return substr($str, $left + strlen($leftStr), $right-$left-strlen($leftStr));
}
/*
adminpath
*/
 function getadminpath(){
		$routetext=file_get_contents(APP_PATH ."admin.php");
		$admin=getSubstr($routetext,"Route::rule('","','admin/login/index')");
		return $admin;
}

/*
 * 生成随机数字
 */
function generate_code($length = 6) {
    return rand(pow(10,($length-1)), pow(10,$length)-1);
}


//加密函数
function passport_encrypt($txt, $key) {
	srand((double)microtime() * 1000000);
	$encrypt_key = md5(rand(0, 32000));
	$ctr = 0;
	$tmp = '';
	for($i = 0;$i < strlen($txt); $i++) {
	   $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
	   $tmp .= $encrypt_key[$ctr].($txt[$i] ^ $encrypt_key[$ctr++]);
	}
	return base64_encode(passport_key($tmp, $key));
}
//解密函数
function passport_decrypt($txt, $key) {
	$txt = passport_key(base64_decode($txt), $key);
	$tmp = '';
	for($i = 0;$i < strlen($txt); $i++) {
	   $md5 = $txt[$i];
	   $tmp .= $txt[++$i] ^ $md5;
	}
	return $tmp;
}

function passport_key($txt, $encrypt_key) {
	$encrypt_key = md5($encrypt_key);
	$ctr = 0;
	$tmp = '';
	for($i = 0; $i < strlen($txt); $i++) {
	   $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
	   $tmp .= $txt[$i] ^ $encrypt_key[$ctr++];
	}
	return $tmp;
}


/**
 * 强制会员模式（web_reg_type=1）下，未登录禁止购买/创建订单
 * @return array|null 需拦截时返回 ['code','msg','url']，通过返回 null
 */
function force_member_purchase_guard()
{
    if (config('web_reg_type') != 1) {
        return null;
    }
    // 与 Base 登录态判定保持一致：需同时有 id 与 account
    if (session('useraccount.id') && session('useraccount.account')) {
        return null;
    }
    $loginUrl = function_exists('isMobilePc') && isMobilePc()
        ? url('@mobile/user/index')
        : url('@jingdian/user/index');
    return ['code' => -2, 'msg' => '请先登录后再购买', 'url' => $loginUrl];
}

/**
 * 强制会员模式下未登录则跳转登录页
 * @return string|null 需跳转时返回登录 URL，已登录或非强制模式返回 null
 */
function force_member_login_url()
{
    $guard = force_member_purchase_guard();
    return $guard === null ? null : $guard['url'];
}

/**
 * 会员侧人机校验：图片(0) / 极验(1) / Turnstile(2)
 * Turnstile 时可传 captcha_mode=image 降级为图片（需开启 turnstile_fallback_image）
 * @param array|null $param 请求参数；null 则读 input('param.')
 * @return array|null 失败返回 ['code'=>-4,'url'=>'','msg'=>...]，通过返回 null
 */
function member_captcha_check($param = null)
{
    if (!is_array($param)) {
        $param = input('param.');
        if (!is_array($param)) {
            $param = [];
        }
    }
    $codeType = (string)config('CODE_TYPE');
    $mode = isset($param['captcha_mode']) ? strtolower(trim((string)$param['captcha_mode'])) : '';
    $fallback = config('turnstile_fallback_image');
    $allowFallback = ($fallback === null || $fallback === '' || (string)$fallback === '1');

    $useImage = ($codeType === '0')
        || ($codeType === '2' && $mode === 'image' && $allowFallback);

    if ($useImage) {
        $code = '';
        if (isset($param['code']) && $param['code'] !== '') {
            $code = $param['code'];
        } elseif (isset($param['veri']) && $param['veri'] !== '') {
            $code = $param['veri'];
        } else {
            $code = input('param.code', '');
            if ($code === '' || $code === null) {
                $code = input('param.veri', '');
            }
        }
        $verify = new \org\Verify();
        if ($code === '' || $code === null) {
            return ['code' => -4, 'url' => '', 'msg' => '请输入验证码'];
        }
        if (!$verify->check($code)) {
            return ['code' => -4, 'url' => '', 'msg' => '验证码错误'];
        }
        return null;
    }

    if ($codeType === '1') {
        $gtresult = action('jingdian/Geetest/gtcheck');
        if ($gtresult == false) {
            return ['code' => -4, 'url' => '', 'msg' => '验证码错误'];
        }
        return null;
    }

    if ($codeType === '2') {
        $token = '';
        if (!empty($param['cf-turnstile-response'])) {
            $token = $param['cf-turnstile-response'];
        } elseif (!empty($param['cf_turnstile_response'])) {
            $token = $param['cf_turnstile_response'];
        } else {
            $token = input('param.cf-turnstile-response', '');
            if ($token === '' || $token === null) {
                $token = input('param.cf_turnstile_response', '');
            }
        }
        $ip = function_exists('getIP') ? (string)getIP() : '';
        if (!\com\Turnstile::verify($token, $ip)) {
            return ['code' => -4, 'url' => '', 'msg' => '人机验证失败，请重试'];
        }
        return null;
    }

    // 其它值：不校验
    return null;
}

/**
 * 后台/madmin 登录人机校验
 * - verify_type=1：强制图片（兼容旧后台）
 * - verify_type!=1 且 CODE_TYPE=1/2：跟随会员侧极验/Turnstile（可图片降级）
 * - 其它：不校验（保留原本地滑块等前端交互）
 * @param array|null $param
 * @return array|null
 */
function admin_captcha_check($param = null)
{
    if ((string)config('verify_type') === '1') {
        if (!is_array($param)) {
            $param = input('param.');
            if (!is_array($param)) {
                $param = [];
            }
        }
        $code = isset($param['code']) ? $param['code'] : input('param.code', '');
        $verify = new \org\Verify();
        if ($code === '' || $code === null) {
            return ['code' => -4, 'url' => '', 'msg' => '请输入验证码'];
        }
        if (!$verify->check($code)) {
            return ['code' => -4, 'url' => '', 'msg' => '验证码错误'];
        }
        return null;
    }
    $codeType = (string)config('CODE_TYPE');
    if ($codeType === '1' || $codeType === '2') {
        return member_captcha_check($param);
    }
    return null;
}

function createOrder(){
		 $order_date = date('Y-m-d');
		 
		//订单号码主体（YYYYMMDDHHIISSNNNNNNNN）
		$order_id_main = date('YmdHis') . rand(10000000,99999999);
		 
		//订单号码主体长度
		 
		$order_id_len = strlen($order_id_main);
		 
		$order_id_sum = 0;
		 
		for($i=0; $i<$order_id_len; $i++){
		 
			$order_id_sum += (int)(substr($order_id_main,$i,1));
		 
		}
		 
		  //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
		 
		$order_id = $order_id_main . str_pad((100 - $order_id_sum % 100) % 100,2,'0',STR_PAD_LEFT);
		return $order_id;
 	}
 function isIE(){
 	if(stripos($_SERVER['HTTP_USER_AGENT'],"Triden")!==false || stripos($_SERVER['HTTP_USER_AGENT'],"MSIE")!==false){
 		return 1;
 	}else{
 		return 0;
 	}
 }
 
 function androidOriphone(){
  $agent = strtolower($_SERVER['HTTP_USER_AGENT']);//得到用户http信息
  if(strpos($agent, 'android')){
    return 1;
  }
  if(strpos($agent, 'iphone') || strpos($agent, 'ipad')){
    return 2;
  }
 }
 
 
 /*
     * 替换主站信息
     */
    function replaceChild($childFlData,$dataFl){
    	//商品名称替换
	        	     		if($childFlData['mname']!=-1){
	        	     			$dataFl['mname']=$childFlData['mname'];
	        	     		}
	        	     		//商品价格提示替换
	        	     		if($childFlData['mprice_bz']!=-1){
	        	     			$dataFl['mprice_bz']=$childFlData['mprice_bz'];
	        	     		}
	        	     		//商品价格替换
	        	     		if($childFlData['mprice']>0){
	        	     			$dataFl['mprice']=$childFlData['mprice'];
	        	     		}
	        	     		//商品提示替换
	        	     		if($childFlData['mnotice']!=-1){
	        	     			$dataFl['mnotice']=$childFlData['mnotice'];
	        	     		}
	        	     		//商品图片地址
	        	     		if($childFlData['imgurl']!=-1){
	        	     			$dataFl['imgurl']=$childFlData['imgurl'];
	        	     			if(!empty($childFlData['yunimgurl'])){
	        	     				$dataFl['yunimgurl']=$childFlData['yunimgurl'];
	        	     			}
	        	     			if(!empty($childFlData['imgurl'])){
	        	     				$dataFl['imgurl']=$childFlData['imgurl'];
	        	     			}	        	     			        	     			        	     			
	        	     		}
	        	     		
	        	     		//商品详情替换
	        	     		if($childFlData['xqnotice']!=-1){
	        	     			$dataFl['xqnotice']=$childFlData['xqnotice'];
	        	     		}
	        	     		
	        	     		//商品排序替换
	        	     		if($childFlData['sort']!=-1){
	        	     			$dataFl['sort']=$childFlData['sort'];
	        	     		}
	        	     		//商品变化提示
	        	     		if($childFlData['msgboxtip']!=-1){
	        	     			$vfl['msgboxtip']=$childFlData['msgboxtip'];
	        	     		}
	        	     		//商品价格替换
	        	     		if($childFlData['marketprice']>0){
	        	     			$dataFl['marketprice']=$childFlData['marketprice'];
	        	     		}
	        	     		//商品推荐替换
	        	     		if($childFlData['tuijian']!=-1){
	        	     			$dataFl['tuijian']=$childFlData['tuijian'];
	        	     		}
	        	     		//商品热门替换
	        	     		if($childFlData['hot']!=-1){
	        	     			$dataFl['hot']=$childFlData['hot'];
	        	     		}
	        	     		//商品热门替换
	        	     		if($childFlData['ykongge']!=-1){
	        	     			$dataFl['ykongge']=$childFlData['ykongge'];
	        	     		}
	        	     		//商品热门替换
	        	     		if($childFlData['zkongge']!=-1){
	        	     			$dataFl['zkongge']=$childFlData['zkongge'];
	        	     		}
	        	     		//商品变化提示
	        	     		if($childFlData['color']!=-1){
	        	     			$dataFl['color']=$childFlData['color'];
	        	     		}
	        	     		//商品变化提示
	        	     		if($childFlData['kamitou']!=-1){
	        	     			$dataFl['kamitou']=$childFlData['kamitou'];
	        	     		}
	        	     		//商品变化提示
	        	     		if($childFlData['kamiwei']!=-1){
	        	     			$dataFl['kamiwei']=$childFlData['kamiwei'];
	        	     		}
	        	     		return $dataFl;
    }
    
    /*
     * 主站处理图片
     */
   	function replaceImgurl($v){
   		
   		$v['yunimgurl']=str_replace('\\','/' ,$v['yunimgurl']);
   		
   		$v['imgurl']=str_replace('\\','/' ,$v['imgurl']);
        $v['webimgurl']=$v['imgurl'];
        if(!empty($v['yunimgurl'])){
        	$domain=config('qiniu_domain');
        	$v['valueimgurl']=$v['yunimgurl'];
        	$v['imgurl']=$domain.'/'.$v['yunimgurl'];
            $v['webimgurl']=$v['imgurl'];
            return $v;
        }
        if(!empty($v['imgurl'])){
            $domain='/uploads/face/';
           $v['valueimgurl']=$v['imgurl'];
            $v['imgurl']=$domain.$v['imgurl'];
            $v['webimgurl']=$v['imgurl'];
            return $v;
        }
        $v['valueimgurl']=$v['imgurl'];
        $v['imgurl']='/static/admin/images/head_default.gif';
        $v['webimgurl']=$v['imgurl'];
        return $v;    
   	}
   	/*
     * 子站处理图片
     */
   	function replacezImgurlfImgurl($v){
   		
   		$v['yunimgurl']=str_replace('\\','/' ,$v['yunimgurl']);
   		$v['imgurl']=str_replace('\\','/' ,$v['imgurl']);
        $v['webimgurl']=$v['imgurl'];
        if(!empty($v['yunimgurl'])){
        	$domain=config('qiniu_domain');
        	$v['imgurl']=$domain.'/'.$v['yunimgurl'];
            $v['webimgurl']=$v['imgurl'];
            return $v;
        }
        if(!empty($v['imgurl'])){
            $domain='/uploads/face/';
            $v['imgurl']=$domain.$v['imgurl'];
            $v['webimgurl']=$v['imgurl'];
            return $v;
        }
        $v['imgurl']='/static/admin/images/head_default.gif';
        $v['webimgurl']=$v['imgurl'];
        return $v;    
   	}
   	
   	// 过滤掉emoji表情
   	function filterEmoji($str){
	    $str = preg_replace_callback(
	            '/./u',
	            function (array $match) {
	                return strlen($match[0]) >= 4 ? '' : $match[0];
	            },
	            $str);
	
	     return $str;
    }
    
    //自己封装input函数
    function inputself(){
     	header("Cache-control:private");
    	$param=input('param.');
    	$data=[];
    	$pathinfo=input('server.PATH_INFO');
    	$pathinfo=str_replace('.'.config('template.view_suffix'),'_'.config('template.view_suffix'),$pathinfo);
    	foreach($param as $key=>$val){		
 			if($key!=$pathinfo){
 				$data[$key]=$val;
 			}
 		}
 		return $data;    	
    }
    
    function checkBOM($filename){
		if (!file_exists($filename)) {
			return FALSE;
		}
		$contents = file_get_contents($filename);
		$charset[1] = substr($contents, 0, 1);
		$charset[2] = substr($contents, 1, 1);
		$charset[3] = substr($contents, 2, 1);
		if (ord($charset[1]) == 239 && ord($charset[2]) == 187 && ord($charset[3]) == 191) {
			return TRUE;
		}
		return FALSE;
	}
	
	
	function http_request($url, $param=array()){
	    if(!is_array($param)){
	        throw new Exception("参数必须为array");
	    }
	    $httph =curl_init($url);
	    curl_setopt($httph, CURLOPT_SSL_VERIFYPEER, 0);
	    curl_setopt($httph, CURLOPT_FOLLOWLOCATION, 2);
	    curl_setopt($httph,CURLOPT_RETURNTRANSFER,2);
	    curl_setopt($httph, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");
	    curl_setopt($httph, CURLOPT_POST, 1);//设置为POST方式 
	    curl_setopt($httph, CURLOPT_POSTFIELDS, $param);
	    curl_setopt($httph, CURLOPT_HEADER,0);
	    $rst=curl_exec($httph);
	    curl_close($httph);
	    return $rst;
	}
	
   	/*
     * 屏蔽部分蜘蛛抓取
     */
   	function off_spider(){
		if (stripos($_SERVER['HTTP_USER_AGENT'],"bot") !== false|| stripos($_SERVER['HTTP_USER_AGENT'],"spider") !== false|| stripos($_SERVER['HTTP_USER_AGENT'],"http") !== false|| stripos($_SERVER['HTTP_USER_AGENT'],"lib") !== false|| stripos($_SERVER['HTTP_USER_AGENT'],"java") !== false) {
			exit(header("status: 404 Not Found"));
		}
	}
	
	
function versionToInteger($ver) {
    $ver = explode(".", $ver);
    $str="";
    foreach ($ver as $k => $v) {
        $str .= str_pad(($v ?$v :0),3,0,STR_PAD_LEFT);
    }
    $str = str_pad($str,9,"0",STR_PAD_RIGHT);
    return (int) "{$str}";
}
function versionToString($ver) {
    if($ver > 999) {
        if($ver > 999999) {
            $ver = $ver . "";
            $str = (int) substr($ver, 0, strlen($ver) - 6).'.'.(int) substr($ver, -6, 3).'.'.(int) substr($ver, -3);
        } else {
            $ver = $ver . "";
            $str = (int) substr($ver, 0, strlen($ver) - 3).'.'.(int) substr($ver, -3);
        }
    } else {
        $str = $ver;
    }
    return "{$str}";
}

/**
 * 确保卡密补货服务可加载（避免部分环境下 extend 自动加载偶发失败）
 */
function ensure_kami_restock_service()
{
    if (class_exists('\\com\\KamiRestockService', false)) {
        return true;
    }
    $file = (defined('EXTEND_PATH') ? EXTEND_PATH : dirname(APP_PATH) . DIRECTORY_SEPARATOR . 'extend' . DIRECTORY_SEPARATOR)
        . 'com' . DIRECTORY_SEPARATOR . 'KamiRestockService.php';
    if (is_file($file)) {
        require_once $file;
        return class_exists('\\com\\KamiRestockService', false);
    }
    return false;
}
