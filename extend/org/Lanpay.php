<?php
/*
 * 支付核心类库
 * Author：来利云
 * Date:2019/10/20
 */
namespace org;
class Lanpay
{
    private $id;
    private $key;

    public function __construct($id = null, $key = null)
    {
        $this->id = $id;
        $this->key = $key;
    }

    public function mp_pay($trade_no, $name, $money, $notify_url)
    {
        $url = 'https://xw.wyisp.com/api/mp_pay.html';
        $data = [
            'id' => $this->id,
            'trade_no' => $trade_no,
            'name' => $name,
            'money' => $money,
            'notify_url' => $notify_url,
        ];
        $sign = $this->getbSign($data);
        $data['sign'] = $sign;
        $data['sign_type'] = 'MD5';
        $url = $url . '?' . http_build_query($data);
        return $url;
    }

    public function f2f_pay($trade_no, $name, $money, $notify_url)
    {
        $url = 'https://xw.wyisp.com/api/f2f_pay.html';
        $data = [
            'id' => $this->id,
            'trade_no' => $trade_no,
            'name' => $name,
            'money' => $money,
            'notify_url' => $notify_url,
        ];
        $sign = $this->getbSign($data);
        $data['sign'] = $sign;
        $data['sign_type'] = 'MD5';
        $url = $url . '?' . http_build_query($data);
        $res = $this->curl_get($url);
        if (!$res) echo'发起支付失败';
        $res = json_decode($res, 1);
        if ($res['code'] == 1) {
            return $res['code_url'];
        } else {
        	echo$res['msg'];
        }
    }
    
    public function scan_pay($trade_no, $name, $money, $notify_url)
    {
        $url = 'https://xw.wyisp.com/api/scan_pay.html';
        $data = [
            'id' => $this->id,
            'trade_no' => $trade_no,
            'name' => $name,
            'money' => $money,
            'notify_url' => $notify_url,
        ];
        $sign = $this->getbSign($data);
        $data['sign'] = $sign;
        $data['sign_type'] = 'MD5';
        $url = $url . '?' . http_build_query($data);
        $res = $this->curl_get($url);
        if (!$res) echo'发起支付失败';
        $res = json_decode($res, 1);
        if ($res['code'] == 1) {
            return $res['code_url'];
        } else {
        	echo  $res['msg'];
        }
    }

    /**
     * @Note   验证签名
     * @param $data  待验证参数
     * @return bool
     */
    public function verify($data)
    {
        if (!isset($data['sign']) || !$data['sign']) {
            return false;
        }
        $sign = $data['sign'];
        unset($data['sign']);
        unset($data['sign_type']);
        $sign2 = $this->getbSign($data);
        if ($sign != $sign2) {
            return false;
        }
        return true;
    }

    /**
     * @Note  生成签名
     * @param $data   参与签名的参数
     * @return string
     */
    public function getbSign($data)
    {
        $data = array_filter($data);
        if (get_magic_quotes_gpc()) {
            $data = stripslashes($data);
        }
        ksort($data);
        $str1 = '';
        foreach ($data as $k => $v) {
            $str1 .= '&' . $k . "=" . $v;
        }
        $str = $str1 . $this->key;
        $str = trim($str, '&');
        $sign = md5($str);
        return $sign;
    }
    
    /*
	 *发送CURL get请求
	 */
	function curl_get($url) {
	    $curl = curl_init();    //初始化一个cURL会话。
	    curl_setopt($curl, CURLOPT_TIMEOUT, 100);  //设置cURL允许执行的最长秒数
	    curl_setopt($curl, CURLOPT_URL, $url);  //URL地址
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,FALSE);  //禁用后cURL将终止从服务端进行验证
	    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);  //不验证证书是否存在
	    curl_setopt($curl, CURLOPT_HEADER, FALSE);    //禁止后使用CURL_TIMECOND_IFUNMODSINCE，默认值为CURL_TIMECOND_IFUNMODSINCE
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);  //将curl_exec()获取的信息以文件流的形式返回，而不是直接输出
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION,1); //是否抓取跳转后的页面
	    $res = curl_exec($curl);  //执行一个cURL会话
	    curl_close($curl);  //关闭一个cURL会话
	    return $res;
	}

}