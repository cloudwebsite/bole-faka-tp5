<?php

function http_request2($url, $param=array()){
        if(!is_array($param)){
                throw new Exception("参数必须为array");
            }
        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL,$url);//指定post网页地址
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且返回输出
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
}
//参数1：访问的URL，参数2：post数据(不填则为GET)，参数3：提交的$cookies,参数4：是否返回$cookies
 function curl_request($url,$post='',$cookie='', $returnCookie=0){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_REFERER, "http://XXX");
        if($post) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        if($cookie) {
            curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        }
        curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        if (curl_errno($curl)) {
            return curl_error($curl);
        }
        curl_close($curl);
        if($returnCookie){
            list($header, $body) = explode("\r\n\r\n", $data, 2);
            preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
            $info['cookie']  = substr($matches[1][0], 1);
            $info['content'] = $body;
            return $info;
        }else{
            return $data;
        }
}

function getReqHmacString($p0_Cmd = "Buy",$p1_MerId,$p2_Order,$p3_Amt,$p4_Cur='CNY',$p5_Pid,$p6_Pcat,$p7_Pdesc,$p8_Url,$p9_SAF = "0",$pa_MP,$pd_FrpId,$pr_NeedResponse="1",$merchantKey)
{
  
		
	#进行签名处理，一定按照文档中标明的签名顺序进行
  $sbOld = "";
  #加入业务类型
  $sbOld = $sbOld.$p0_Cmd;
  #加入商户编号
  $sbOld = $sbOld.$p1_MerId;
  #加入商户订单号
  $sbOld = $sbOld.$p2_Order;     
  #加入支付金额
  $sbOld = $sbOld.$p3_Amt;
  #加入交易币种
  $sbOld = $sbOld.$p4_Cur;
  #加入商品名称
  $sbOld = $sbOld.$p5_Pid;
  #加入商品分类
  $sbOld = $sbOld.$p6_Pcat;
  #加入商品描述
  $sbOld = $sbOld.$p7_Pdesc;
  #加入商户接收支付成功数据的地址
  $sbOld = $sbOld.$p8_Url;
  #加入送货地址标识
  $sbOld = $sbOld.$p9_SAF;
  #加入商户扩展信息
  $sbOld = $sbOld.$pa_MP;
  #加入支付通道编码
  $sbOld = $sbOld.$pd_FrpId;
  #加入是否需要应答机制
  $sbOld = $sbOld.$pr_NeedResponse;

  return HmacMd5($sbOld,$merchantKey);
  
}

function getReqHmacStringSuper($array,$merchantKey){
	$sbOld=implode("",$array);
	return HmacMd5($sbOld,$merchantKey);	
}
function annulCard($array,$merchantKey,$reqURL_SNDApro)
{
	
 	$hmac=getReqHmacStringSuper($array,$merchantKey);
 	$params=$array;
 	$params['hmac']=$hmac;
	//print_r($params);
	$pageContents	= http_request($reqURL_SNDApro, $params);
	
	$pageContents=iconv("GBK","UTF-8//IGNORE",$pageContents);
	//echo "pageContents:".$pageContents;
	$result 				= explode("\n",$pageContents);
	//print_r($result);
	$r0_Cmd				=	"";							#业务类型
	$r1_Code			=	"";							#支付结果
	$r2_TrxId			=	"";							#API支付交易流水号
	$r6_Order			=	"";							#商户订单号
	$rq_ReturnMsg	=	"";							#返回信息
	$hmac					=	"";					 	  #签名数据
 	 $unkonw				= "";							#未知错误  	


	for($index=0;$index<count($result);$index++){		//数组循环
		$result[$index] = trim($result[$index]);
		if (strlen($result[$index]) == 0) {
			continue;
		}
		$aryReturn		= explode("=",$result[$index]);

		$sKey					= $aryReturn[0];
		$sValue				= $aryReturn[1];
		if($sKey			=="r0_Cmd"){				#取得业务类型  
			$r0_Cmd				= $sValue;
		}elseif($sKey == "r1_Code"){			        #取得支付结果
			$r1_Code			= $sValue;
		}elseif($sKey == "r2_TrxId"){			        #取得API支付交易流水号
			$r2_TrxId			= $sValue;
		}elseif($sKey == "r6_Order"){			        #取得商户订单号
			$r6_Order			= $sValue;
		}elseif($sKey == "rq_ReturnMsg"){				#取得交易结果返回信息
			$rq_ReturnMsg	= $sValue;
		}elseif($sKey == "hmac"){						#取得签名数据
			$hmac 				= $sValue;	      
		} else{
			return $result[$index];
		}
	}
	

	#进行校验码检查 取得加密前的字符串
	$sbOld="";
	#加入业务类型
	$sbOld = $sbOld.$r0_Cmd;                
	#加入支付结果
	$sbOld = $sbOld.$r1_Code;
	#加入API支付交易流水号
	#$sbOld = $sbOld.$r2_TrxId;                
	#加入商户订单号
	$sbOld = $sbOld.$r6_Order;                
	#加入交易结果返回信息
	$sbOld = $sbOld.$rq_ReturnMsg;                   
	$sNewString = HmacMd5($sbOld,$merchantKey);      	
	return $r1_Code;
	
	
}
function HmacMd5($data,$key)
{
// RFC 2104 HMAC implementation for php.
// Creates an md5 HMAC.
// Eliminates the need to install mhash to compute a HMAC
// Hacked by Lance Rushing(NOTE: Hacked means written)

//需要配置环境支持iconv，否则中文参数不能正常处理
$key = iconv("GB2312","UTF-8//IGNORE",$key);
$data = iconv("GB2312","UTF-8//IGNORE",$data);

$b = 64; // byte length for md5
if (strlen($key) > $b) {
$key = pack("H*",md5($key));
}
$key = str_pad($key, $b, chr(0x00));
$ipad = str_pad('', $b, chr(0x36));
$opad = str_pad('', $b, chr(0x5c));
$k_ipad = $key ^ $ipad ;
$k_opad = $key ^ $opad;

return md5($k_opad . pack("H*",md5($k_ipad . $data)));
}

function CheckHmac($r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r7_Uid,$r8_MP,$r9_BType,$hmac)
{
	if($hmac==getCallbackHmacString($r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r7_Uid,$r8_MP,$r9_BType))
		return true;
	else
		return false;
}


function getCallbackHmacString($r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r7_Uid,$r8_MP,$r9_BType)
{
  

  $p1_MerId=config('zywl_id');
  $merchantKey= config('zywl_key');
	#取得加密前的字符串
	$sbOld = "";
	#加入商家ID
	$sbOld = $sbOld.$p1_MerId;
	#加入消息类型
	$sbOld = $sbOld.$r0_Cmd;
	#加入业务返回码
	$sbOld = $sbOld.$r1_Code;
	#加入交易ID
	$sbOld = $sbOld.$r2_TrxId;
	#加入交易金额
	$sbOld = $sbOld.$r3_Amt;
	#加入货币单位
	$sbOld = $sbOld.$r4_Cur;
	#加入产品Id
	$sbOld = $sbOld.$r5_Pid;
	#加入订单ID
	$sbOld = $sbOld.$r6_Order;
	#加入用户ID
	$sbOld = $sbOld.$r7_Uid;
	#加入商家扩展信息
	$sbOld = $sbOld.$r8_MP;
	#加入交易结果返回类型
	$sbOld = $sbOld.$r9_BType;

	return HmacMd5($sbOld,$merchantKey);

}




