<?php
namespace app\pay\controller;

use app\jingdian\model\OrderModel;
use app\jingdian\model\UserModel;
use think\Config;
use think\Loader;
use org\Lanpay;
use epay\EpayNotify;
use org\WxpayService;
use think\Db;
use com\PayFulfill;
require ROOT_PATH.'/vendor/riverslei/payment/autoload.php';
use Payment\Common\PayException;
use Payment\Notify\PayNotifyInterface;
use Payment\Client\Notify;
use Payment\Configv;	

/**
 * 支付宝
 */
class AlipayNotify implements PayNotifyInterface
{
    public function notifyProcess(array $data)
    {
        $channel = $data['channel'];
        if ($channel === Configv::ALI_CHARGE) {// 支付宝支付
        } elseif ($channel === Configv::WX_CHARGE) {// 微信支付
        } elseif ($channel === Configv::CMB_CHARGE) {// 招商支付
        } elseif ($channel === Configv::CMB_BIND) {// 招商签约
        } else {
            // 其它类型的通知
        }
        // 执行业务逻辑，成功后返回true
        return true;
    }
}

/*
* 支付返回方法
*/
class Callback extends Base
{
	//支付宝返回通知
      public function callbackalipay()
    {
		if(strlen(config('app_id'))<5||strlen(config('ali_public_key'))<5||strlen(config('rsa_private_key'))<5){
		   exit('fail'); 
		}
        $param = inputself();
		$config = [
            'use_sandbox' => false,
            'app_id'                    => config('app_id'),
            'sign_type'                 => 'RSA2',
            'ali_public_key'            => config('ali_public_key'),
            'rsa_private_key'           => config('rsa_private_key'),
            'return_raw' => true
		];
		$callback = new AlipayNotify();
		try {
			$ret = Notify::run('ali_charge', $config, $callback);// 处理回调，内部进行了签名检查
		} catch (PayException $e) {
			echo $e->errorMessage();
			exit;
		}
      if($param['trade_status']=="TRADE_SUCCESS"&&$param['app_id']==config('app_id')){
        	$ok = PayFulfill::fulfill(
                $param['out_trade_no'],
                $param['total_amount'],
                isset($param['trade_no']) ? $param['trade_no'] : '',
                ['maddtype' => '2']
            );
            if (!$ok['ok']) {
                exit('fail');
            }
      }
			exit($ret); //状态
		

    }
	
	//微信返回通知
    public function callbackweixin()
    {	
		if(strlen(config('wx_apiKey'))<5||strlen(config('wx_appid'))<3||strlen(config('mch_id'))<3){
		   exit('fail'); 
		}
		$mchid = config('mch_id');    	
		$appid = config('wx_appid');
		$apiKey = config('wx_apiKey');   
		$wxPay = new WxpayService($mchid,$appid,$apiKey);
		$result = $wxPay->notify();
		if($result){
            // 优先 total_fee（订单金额），兼容 cash_fee
            $feeFen = isset($result['total_fee']) ? $result['total_fee'] : $result['cash_fee'];
            $amount = ((float)$feeFen) / 100;
            $ok = PayFulfill::fulfill(
                $result['out_trade_no'],
                $amount,
                isset($result['transaction_id']) ? $result['transaction_id'] : ''
            );
            if (!$ok['ok']) {
                echo 'fail';
                return;
            }
		}else{
			echo 'fail';
		}

    }
	
	//蓝支付官方异步返回通知
    public function notifyyzf()
    {
		if(strlen(config('pay_yzfkey'))<5){
		   exit('fail'); 
		}
		$param = inputself();
		$pay = new Lanpay(config('pay_yzfid'), config('pay_yzfkey'));
		if ($pay->verify($param)) {
			if ($param['trade_status'] == 'TRADE_SUCCESS') {
                $ok = PayFulfill::fulfill(
                    $param['out_trade_no'],
                    $param['money'],
                    isset($param['trade_no']) ? $param['trade_no'] : ''
                );
                exit($ok['ok'] ? 'success' : 'fail');
			} else {
				exit('fail');
			}
		} else {
			exit('fail');
		}

    }
    
    
	//码支付返回通知
	public function callbackmzf()
    {
		if(strlen(config('pay_mzfkey'))<5){
		   exit('fail'); 
		}
        $param = inputself();
		$sign = md5(config('pay_mzfid') . $param['payId'] . urldecode($param['param']) . $param['type'] . $param['price'] . $param['reallyPrice'] . config('pay_mzfkey'));
		if (!$param['payId'] || $sign != $param['sign']) { //不合法的数据
			exit('fail');  //返回失败 继续补单
		} else { //合法的数据
            // 用订单标价 price 与库内比对（reallyPrice 可能为分位调价，不能单独作为应付）
            $ok = PayFulfill::fulfill(
                $param['payId'],
                $param['price'],
                isset($param['order_id']) ? $param['order_id'] : ''
            );
			exit($ok['ok'] ? 'success' : 'fail');
		}

    }
        
    
    //易支付返回通知（验签逻辑对齐未修改前版本；履约走金额核对）
    public function callbackepay(){
        try {
            if (strlen((string)config('epay_key')) < 5) {
                exit('fail');
            }
            // 仅当完全无 GET 时才用 POST，避免 GET+POST 合并污染参数导致验签失败
            if (empty($_GET) && !empty($_POST)) {
                $_GET = $_POST;
            }

            $alipay_config = [];
            //商户ID
            $alipay_config['partner'] = trim(config('epay_pid'));
            //商户KEY
            $alipay_config['key'] = config('epay_key');
            //签名方式 不需修改
            $alipay_config['sign_type'] = strtoupper('MD5');
            //字符编码格式 目前支持 gbk 或 utf-8
            $alipay_config['input_charset'] = strtolower('utf-8');
            //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
            $alipay_config['transport'] = 'http';
            //支付API地址
            $alipay_config['apiurl'] = config('epay_url');
            $EpayNotify = new EpayNotify($alipay_config);
            $verify_result = $EpayNotify->verifyNotify();

            if ($verify_result) {
                // 与旧版一致：非成功状态仍回 success，不履约
                if (!isset($_GET['trade_status']) || $_GET['trade_status'] != 'TRADE_SUCCESS') {
                    echo 'success';
                    return;
                }
                $sdorderno = $_GET['out_trade_no'];
                $sdpayno = isset($_GET['trade_no']) ? $_GET['trade_no'] : '';
                $total_fee = $_GET['money'];

                // 验签已通过：与业务单金额核对后履约（防低额改高额单）
                $ok = PayFulfill::fulfill($sdorderno, $total_fee, $sdpayno);
                echo $ok['ok'] ? 'success' : 'fail';
            } else {
                echo 'fail';
            }
        } catch (\Throwable $e) {
            echo 'fail';
        } catch (\Exception $e) {
            echo 'fail';
        }
	}

	public function callback()
    {
		$param = inputself();
		$this->assign('param', $param);
		return $this->fetch('/index/callback');
    }
	/*
     * 泰达币返回通知
     */	
	public function callback_usdt()
	{
		if(strlen(config('usdt_apitoken'))<5){
		   exit('fail'); 
		}
		$param = inputself();
		$sign = $param['signature'];
		$signature = $this->paramsign($param, config('usdt_apitoken'));
		if ($sign == $signature) {
			if ($param['status'] == 2) {
                $resolved = PayFulfill::resolveExpectedAmount($param['order_id']);
                $cbAmount = $resolved['ok'] ? $resolved['amount'] : $param['amount'];
                // 若回调带 amount，必须与库内一致
                if (isset($param['amount']) && $param['amount'] !== ''
                    && !PayFulfill::amountsEqual($cbAmount, $param['amount'])) {
                    exit('fail');
                }
                $ok = PayFulfill::fulfill(
                    $param['order_id'],
                    $cbAmount,
                    isset($param['trade_id']) ? $param['trade_id'] : ''
                );
				exit($ok['ok'] ? 'success' : 'fail');
			} else {
				exit('fail');
			}
		} else {
			exit('fail');
		}
	}
	/**
	 * 验签
	 *
	 * @author AnLin
	 * @version V1.0.0
	 * @since 2018/6/4
	 */
	private function sign($dataArr,$signkey)
	{
        ksort($dataArr);
        reset($dataArr);
         $str = "";
        foreach ($dataArr as $key => $val) {
            $str = $str . $key . "=" . $val . "&";
        }
        return strtoupper(md5($str . "key=" . $signkey));
	}

	/**
	 * USDT
	 */
    private function paramsign(array $paramsign, string $signkey)
    {
        unset($paramsign['signature']);//销毁变量
        ksort($paramsign);
        reset($paramsign);
        foreach ($paramsign as $key => $val) {
            if ($val === '') {//过滤空值
                unset($paramsign[$key]);
            }
        }
        $sign = md5(urldecode(http_build_query($paramsign) . $signkey));
        return $sign;
    }

    
}
