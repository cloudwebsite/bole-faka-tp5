<?php
namespace app\jingdian\controller;
use app\jingdian\model\GoodsListModel;
use app\jingdian\model\OrderModel;
use app\jingdian\model\UserModel;
use think\Config;
use think\Loader;
use think\Db;
use com\PayFulfill;
class Trc20 extends Base
{
    public function getReturn($code = 1, $msg = "成功", $data = null)
    {
        return array("code" => $code, "msg" => $msg, "data" => $data);
    }
	
	public function pay(){
        $orderId = input("orderId");
        $order = Db::name("usdt_order")->where("order_id", $orderId)->find();
        $checkToken = '';
        if ($order) {
            $ttl = (int)config('usdt_expiration_time');
            if ($ttl < 1) {
                $ttl = 30;
            }
            $checkToken = PayFulfill::makeTrc20CheckToken($order['order_id'], $ttl * 60 + 600);
        }
		$this->assign('order', $order);
        $this->assign('check_token', $checkToken);
		return $this->fetch();
    }
	//创建订单
    public function createOrder()
    {
        $this->closeEndOrder();
        $payId = input("payId");
        $pid = input("pid");
		
        if (!$payId || $payId == "") {
            return json($this->getReturn(-1, "请传入订单号"));
        }
		$ordermoney =Db::name('info')->where('mcard', $payId)->find();		
		$userresult=Db::name('member_payorder')->where('orderno', $payId)->find();	
		if(!empty($userresult['memberid'])){
			$price = $userresult['money'];
		}else{
			$price = $ordermoney['mamount'];
		}//重新从数据库读取订单金额
		
        if (!$price || $price == "") {
            return json($this->getReturn(-1, "请传入订单金额"));
        }
        if ($price <= 0) {
            return json($this->getReturn(-1, "订单金额必须大于0"));
        }
       if(config('usdt_rate')){
		   $reallyPrice = ceil($price/config('usdt_rate')*100);
	   }else{
		   $reallyPrice = ceil($price/6*100);
	   }
        

        $orderId = date("YmdHms") . rand(1, 9) . rand(1, 9) . rand(1, 9) . rand(1, 9);
        $ok = false;
        for ($i = 0; $i < 300; $i++) {
            $tmpPrice = $reallyPrice;
            $row = Db::execute(
                "INSERT IGNORE INTO think_tmp_uprice (price,oid,create_date) VALUES (?,?,?)",
                [$tmpPrice, $orderId, time()]
            );
            if ($row) {
                $ok = true;
                break;
            }
            $reallyPrice++;
        }

        if (!$ok) {
            return json($this->getReturn(-1, "订单超出负荷，请稍后重试"));
        }
        $reallyPrice = bcdiv($reallyPrice, 100,2);
        if (strlen(config('usdt_wallet'))<10) {
            return json($this->getReturn(-1, "请您先进入后台配置钱包地址"));
        }
        $payUrl = config('usdt_wallet');

        $res = Db::name("usdt_order")->where("pay_id", $payId)->find();
        if ($res) {
            return json($this->getReturn(-1, "商户订单号已存在"));
        }
        $createDate = time();
        $data = array(
            "close_date" => 0,
            "create_date" => time(),
            "order_id" => $orderId,
            "timeout" => time()+(60*config('usdt_expiration_time')),
            "pay_date" => 0,
            "pay_id" => $payId,
            "pid" => $pid,
            "pay_url" => $payUrl,
            "price" => $price,
            "really_price" => $reallyPrice,
            "state" => 0,
        );
        Db::name("usdt_order")->insert($data);
		return $this->redirect(url('@jingdian/Trc20/pay')."?orderId=".$orderId);
    }
	//查询订单状态（需支付页 check_token；链上金额 + 业务金额双校验）
    public function checkOrder()
    {
        $this->closeEndOrder();
        $orderId = trim((string)input("orderId"));
        $checkToken = (string)input("check_token");
        if ($orderId === '' || !PayFulfill::verifyTrc20CheckToken($orderId, $checkToken)) {
            return json($this->getReturn(-1, "校验失败"));
        }
        if (!PayFulfill::softRateLimit('trc20_check:' . $orderId, 40, 60)) {
            return json($this->getReturn(-1, "请求过于频繁"));
        }

        $order = Db::name("usdt_order")->where("order_id", $orderId)->find();
        if (!$order) {
            return json($this->getReturn(-1, "订单不存在"));
        }
        if ((int)$order['state'] === 1) {
            $resolved = PayFulfill::resolveExpectedAmount($order['pay_id']);
            $type = (!empty($resolved['member']['memberid'])) ? 1 : 2;
            return json($this->getReturn(1, "ok", $type));
        }
        if ((int)$order['state'] !== 0) {
            return json($this->getReturn(-1, "订单已关闭"));
        }

		$end    = time() * 1000;
		$start  = (time()-60*config('usdt_expiration_time')) * 1000;
		$params = [
			'limit'           => 50,
			'start'           => 0,
			'direction'       => 'in',
			'relatedAddress'  => config('usdt_wallet'),
			'start_timestamp' => $start,
			'end_timestamp'   => $end,
		];
		$url 	= config('usdt_proxy')?config('usdt_proxy'):"https://apilist.tronscan.org";
		$api    = $url."/api/token_trc20/transfers?" . http_build_query($params);
		$resp   = $this->getCurl($api);
		$data   = json_decode($resp, true);

		if (empty($data) || empty($data['token_transfers'])) {
			return json($this->getReturn(-1, "为找到记录"));
		}
		foreach ($data['token_transfers'] as $transfer) {
			if ($transfer['to_address'] == config('usdt_wallet') && $transfer['finalResult'] == 'SUCCESS') {
                $chainAmount = ((float)$transfer['quant']) / 1000000;
				if($transfer['block_ts'] / 1000 > $order['create_date']
                    && $transfer['block_ts'] / 1000 < $order['timeout']
                    && PayFulfill::amountsEqual($chainAmount, $order['really_price'])
                    && $transfer['tokenInfo']['tokenAbbr']=='USDT'
                    && $transfer['tokenInfo']['tokenCanShow']=='1'){
				    $trade = Db::name("usdt_order")->where("trade_id", $transfer['transaction_id'])->find();
                    if(!$trade){
                        $resolved = PayFulfill::resolveExpectedAmount($order['pay_id']);
                        $type = (!empty($resolved['ok']) && !empty($resolved['member']['memberid'])) ? 1 : 2;
                        $fulfilled = PayFulfill::fulfill($order['pay_id'], $order['price'], $order['order_id']);
                        if (!$fulfilled['ok']) {
                            return json($this->getReturn(-1, $fulfilled['msg']));
                        }
						Db::name("tmp_uprice")
							->where("oid",$order['order_id'])
							->delete();
						Db::name("usdt_order")->where("id",$order['id'])->update(array("state"=>1,"pay_date"=>time(),"trade_id"=>$transfer['transaction_id'],"close_date"=>time()));
					   return json($this->getReturn(1, "ok",$type));
                    }
				}
			}
		}
	    return json($this->getReturn(-1, "next"));
	
	}

    
    public function closeEndOrder(){
        $closeTime = time()-60*config('usdt_expiration_time');
        $close_date = time();
        $res = Db::name("usdt_order")
            ->where("create_date <=".$closeTime)
            ->where("state",0)
            ->update(array("state"=>-1,"close_date"=>$close_date));
		$tmp = Db::name("tmp_uprice")
                    ->where("create_date <=".$closeTime)
                    ->delete();
        if ($res){
            return json($this->getReturn(1,"成功清理".$res."条订单"));
        }else{
            return json($this->getReturn(1,"没有等待清理的订单"));
        }
    }

	public function test(){
		$url 	= config('usdt_proxy')?config('usdt_proxy'):"https://apilist.tronscan.org";
        $apizt = $this->check_url($url.'/api/listdonators');
        if($apizt){
            exit("OK 不用代理");
        }else{
            exit("NO 需要反向代理或更换海外外服务器");
        }
    }

    /**
     * 检测网址连接是否可用
     **/
    function check_url($url){
    	if (@fopen($url, 'r')==false) {
    			return false;
    	}
    	return true;
    }
    //发送Http请求
    function getCurl($url, $post = 0, $cookie = 0, $header = 0, $nobaody = 0)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $klsf[] = 'Accept:*/*';
        $klsf[] = 'Accept-Language:zh-cn';
        //$klsf[] = 'Content-Type:application/json';
        $klsf[] = 'User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $klsf);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        if ($header) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }
        if ($cookie) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        if ($nobaody) {
            curl_setopt($ch, CURLOPT_NOBODY, 1);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT,60);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($ch);
        curl_close($ch);
        return $ret;
    }

}