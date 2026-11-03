<?php
namespace app\jingdian\controller;
use app\jingdian\model\GoodsListModel;
use app\jingdian\model\OrderModel;
use app\jingdian\model\UserModel;
use think\Config;
use think\Loader;
use think\Db;
use com\IpLocationqq;
use com\IpLocation;
use com\PayFulfill;

class Mqapi extends Base
{
    public function getReturn($code = 1, $msg = "成功", $data = null)
    {
        return array("code" => $code, "msg" => $msg, "data" => $data);
    }
   public function pay(){
		return $this->fetch();
    }
	//创建订单
    public function createOrder()
    {
        $this->closeEndOrder();
        $payId = input("payId");
        if (!$payId || $payId == "") {
            return json($this->getReturn(-1, "请传入商户订单号"));
        }
        $type = input("type");
        if (!$type || $type == "") {
            return json($this->getReturn(-1, "请传入支付方式"));
        }
        if ($type != 3 && $type != 2&& $type != 1) {
            return json($this->getReturn(-1, "支付方式错误"));
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

        $sign = input("sign");
        if (!$sign || $sign == "") {
            return json($this->getReturn(-1, "请传入签名"));
        }

        $isHtml = input("isHtml");
        if (!$isHtml || $isHtml == "") {
            $isHtml = 0;
        }
        $param = input("param");
        if (!$param) {
            $param = "";
        }

        $res = Db::name("setting")->where("key", "key")->find();
        $key = $res['value'];
    

        $_sign = md5($payId . $param . $type . $price . $key);
        if ($sign != $_sign) {
            return json($this->getReturn(-1, "签名错误"));
        }

        $jkstate = Db::name("setting")->where("key", "jkstate")->find();
        $jkstate = $jkstate['value'];
        if ($jkstate!="1"){
            return json($this->getReturn(-1, "监控端状态异常，请检查"));

        }



        $reallyPrice = bcmul($price ,100);

        $payQf = Db::name("setting")->where("key", "payQf")->find();
        $payQf = $payQf['value'];


        $orderId = date("YmdHms") . rand(1, 9) . rand(1, 9) . rand(1, 9) . rand(1, 9);

        $ok = false;
        for ($i = 0; $i < 300; $i++) {
            $tmpPrice = $reallyPrice . "-" . $type;
            $row = Db::execute(
                "INSERT IGNORE INTO think_tmp_price (price,oid,create_date) VALUES (?,?,?)",
                [$tmpPrice, $orderId, time()]
            );
			
            if ($row) {
                $ok = true;
                break;
            }
            if ($payQf == 1) {
                $reallyPrice++;
            } else if ($payQf == 2) {
                $reallyPrice--;
            }
        }

        if (!$ok) {
            return json($this->getReturn(-1, "订单超出负荷，请稍后重试"));
        }
        //echo $reallyPrice;

        $reallyPrice = bcdiv($reallyPrice, 100,2);

        if ($type == 3) {
            $payUrl = Db::name("setting")->where("key", "wxpay")->find();
            $payUrl = $payUrl['value'];

        } else if ($type == 2) {
            $payUrl = Db::name("setting")->where("key", "jhpay")->find();
            $payUrl = $payUrl['value'];
        } else if ($type == 1) {
            $alipayid = Db::name("setting")->where("key", "alipay")->find();
            $payUrl = $alipayid['value'];
        }

        if ($payUrl == "") {
            return json($this->getReturn(-1, "请您先进入后台配置程序"));
        }
        $isAuto = 1;
        $_payUrl = Db::name("pay_qrcode")
            ->where("price", $reallyPrice)
            ->where("type", $type)
            ->find();
        if ($_payUrl) {
            $payUrl = $_payUrl['pay_url'];
            $isAuto = 0;
        }


        $res = Db::name("pay_order")->where("pay_id", $payId)->find();
        if ($res) {
            return json($this->getReturn(-1, "商户订单号已存在"));
        }




        $createDate = time();
        $data = array(
            "close_date" => 0,
            "create_date" => $createDate,
            "is_auto" => $isAuto,
            "order_id" => $orderId,
            "param" => $param,
            "pay_date" => 0,
            "pay_id" => $payId,
            "pay_url" => $payUrl,
            "price" => $price,
            "really_price" => $reallyPrice,
            "state" => 0,
            "type" => $type,
        );
        Db::name("pay_order")->insert($data);
		
		return $this->redirect(url('@jingdian/Mqapi/pay')."?orderId=".$orderId);

        


    }
    //获取订单信息
    public function getOrder()
    {

        $this->closeEndOrder();
        $res = Db::name("pay_order")->where("order_id", input("orderId"))->find();
        if ($res){
            $time = Db::name("setting")->where("key", "close")->find();

            $data = array(
                "payId" => $res['pay_id'],
                "orderId" => $res['order_id'],
                "payType" => $res['type'],
                "price" => $res['price'],
                "reallyPrice" => $res['really_price'],
                "payUrl" => $res['pay_url'],
                "isAuto" => $res['is_auto'],
                "state" => $res['state'],
                "timeOut" => $time['value'],
                "param" => $res['param'],
                "date" => $res['create_date']
            );
            return json($this->getReturn(1, "成功", $data));
        }else{
            return json($this->getReturn(-1, "云端订单编号不存在"));
        }
    }
    //支付宝
    public function alipay()
    {

        $this->closeEndOrder();
		 if( strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') == false ) {
			return $this->redirect(config('is_https') . '://' . $_SERVER['HTTP_HOST']);
		}
        $res = Db::name("pay_order")->where("order_id", input("orderId"))->find();
        if ($res){
            $time = Db::name("setting")->where("key", "close")->find();

            $data = array(
                "payId" => $res['pay_id'],
                "orderId" => $res['order_id'],
                "payType" => $res['type'],
                "price" => $res['price'],
                "reallyPrice" => $res['really_price'],
                "payUrl" => $res['pay_url'],
                "isAuto" => $res['is_auto'],
                "state" => $res['state'],
                "timeOut" => $time['value'],
                "param" => $res['param'],
                "date" => $res['create_date']
            );
			
			$this->assign('Order', $data);           
        }else{
           return $this->fetch('Index/order');	
        }
		
		return $this->fetch();
    }
    //查询订单状态
    public function checkOrder()
    {
        $this->closeEndOrder();
        $res = Db::name("pay_order")->where("order_id", input("orderId"))->find();
        if ($res){
            if ($res['state']==0){
                return json($this->getReturn(-1, "订单未支付"));
            }
            if ($res['state']==-1){
                return json($this->getReturn(-1, "订单已过期"));
            }

            $res2 = Db::name("setting")->where("key","key")->find();
            $key = $res2['value'];

            $res['price'] = number_format($res['price'],2,".","");
            $res['really_price'] = number_format($res['really_price'],2,".","");



            return json($this->getReturn(1, "成功"));
        }else{
            return json($this->getReturn(-1, "云端订单编号不存在"));
        }

    }
    //关闭订单
    public function closeOrder(){
        $res2 = Db::name("setting")->where("key","key")->find();
        $key = $res2['value'];
        $orderId = input("orderId");

        $_sign = $orderId.$key;

        if (md5($_sign)!=input("sign")){
            return json($this->getReturn(-1, "签名校验不通过"));
        }

        $res = Db::name("pay_order")->where("order_id",$orderId)->find();

        if ($res){
            if ($res['state']!=0){
                return json($this->getReturn(-1, "订单状态不允许关闭"));
            }
            Db::name("pay_order")->where("order_id",$orderId)->update(array("state"=>-1,"close_date"=>time()));
            Db::name("tmp_price")
                ->where("oid",$res['order_id'])
                ->delete();
            return json($this->getReturn(1, "成功"));
        }else{
            return json($this->getReturn(-1, "云端订单编号不存在"));

        }

    }
    //获取监控端状态
    public function getState(){
      
        $this->closeEndOrder();
        $res2 = Db::name("setting")->where("key","key")->find();
        $key = $res2['value'];
        $t = input("t");

        $_sign = $t.$key;

        if (md5($_sign)!=input("sign")){
            return json($this->getReturn(-1, "签名校验不通过"));
        }

        $res = Db::name("setting")->where("key","lastheart")->find();
        $lastheart = $res['value'];
        $res = Db::name("setting")->where("key","lastpay")->find();
        $lastpay = $res['value'];
        $res = Db::name("setting")->where("key","jkstate")->find();
        $jkstate = $res['value'];

        return json($this->getReturn(1, "成功",array("lastheart"=>$lastheart,"lastpay"=>$lastpay,"jkstate"=>$jkstate)));

    }

    //App心跳接口
    public function appHeart(){
        $this->closeEndOrder();

        $res2 = Db::name("setting")->where("key","key")->find();
        $key = $res2['value'];
        $keyCheck = PayFulfill::assertMqKey($key);
        if (!$keyCheck['ok']) {
            return json($this->getReturn(-1, $keyCheck['msg']));
        }
        $t = input("t");
        $_sign = $t.$key;

        if (md5($_sign)!=input("sign")){
            return json($this->getReturn(-1, "签名校验不通过"));
        }

        $jg = time()*1000 - $t;
        if ($jg>50000 || $jg<-50000){
            return json($this->getReturn(-1, "客户端时间错误"));
        }

        Db::name("setting")->where("key","lastheart")->update(array("value"=>time()));
        Db::name("setting")->where("key","jkstate")->update(array("value"=>1));
        return json($this->getReturn());
    }
    //App推送付款数据接口（签名保持 App 原协议：md5(type+price+t+key)）
    public function appPush(){
        $this->closeEndOrder();

        $res2 = Db::name("setting")->where("key","key")->find();
        $key = $res2['value'];
        $keyCheck = PayFulfill::assertMqKey($key);
        if (!$keyCheck['ok']) {
            return json($this->getReturn(-1, $keyCheck['msg']));
        }
        $t = input("t");
        $type = input("type");
        $price = input("price");

        $_sign = $type . $price . $t . $key;
        if (md5($_sign) != input("sign")) {
            return json($this->getReturn(-1, "签名校验不通过"));
        }

        $jg = time()*1000 - $t;
        if ($jg>50000 || $jg<-50000){
            return json($this->getReturn(-1, "客户端时间错误"));
        }

        Db::name("setting")
            ->where("key","lastpay")
            ->update(array("value"=>time()));

        // 按金额+支付方式匹配（App 协议）；同价多单时拒绝履约，避免错单
        $pending = Db::name("pay_order")
            ->where("really_price", $price)
            ->where("state", 0)
            ->where("type", $type)
            ->select();
        if (!$pending || count($pending) === 0) {
            $data = array(
                "close_date" => 0,
                "create_date" => time(),
                "is_auto" => 0,
                "order_id" => "无订单转账",
                "param" => "无订单转账",
                "pay_date" => 0,
                "pay_id" => "无订单转账",
                "pay_url" => "",
                "price" => $price,
                "really_price" => $price,
                "state" => 1,
                "type" => $type
            );
            Db::name("pay_order")->insert($data);
            return json($this->getReturn());
        }
        if (count($pending) > 1) {
            return json($this->getReturn(-1, "存在多笔同金额待支付订单，请错峰下单后重试"));
        }
        $res = $pending[0];

        $fulfilled = PayFulfill::fulfill($res['pay_id'], $res['price'], $res['order_id']);
        if (!$fulfilled['ok']) {
            return json($this->getReturn(-1, $fulfilled['msg']));
        }

        Db::name("tmp_price")->where("oid", $res['order_id'])->delete();
        Db::name("pay_order")->where("id", $res['id'])->update([
            "state" => 1,
            "pay_date" => time(),
            "close_date" => time(),
        ]);
        return json($this->getReturn(1, "成功"));
    }


    //关闭过期订单接口(请用定时器至少1分钟调用一次)
    public function closeEndOrder(){

        $res = Db::name("setting")->where("key","lastheart")->find();
        $lastheart = $res['value'];
        if ((time()-$lastheart)>60){
            Db::name("setting")->where("key","jkstate")->update(array("value"=>0));
        }



        $time = Db::name("setting")->where("key", "close")->find();

        $closeTime = time()-60*$time['value'];
        $close_date = time();

        $res = Db::name("pay_order")
            ->where("create_date <=".$closeTime)
            ->where("state",0)
            ->update(array("state"=>-1,"close_date"=>$close_date));
		$tmp = Db::name("tmp_price")
                    ->where("create_date <=".$closeTime)
                    ->delete();
        if ($res){
            return json($this->getReturn(1,"成功清理".$res."条订单"));
        }else{
            return json($this->getReturn(1,"没有等待清理的订单"));
        }



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
        $klsf[] = 'User-Agent:Mozilla/5.0 (iPhone; CPU iPhone OS 11_2_1 like Mac OS X) AppleWebKit/604.4.7 (KHTML, like Gecko) Mobile/15C153 MicroMessenger/6.6.1 NetType/WIFI Language/zh_CN';
        $klsf[] = 'Referer:https://servicewechat.com/wx7c8d593b2c3a7703/5/page-frame.html';
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