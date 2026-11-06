<?php
namespace app\admin\controller;
use com\PayFulfill;
use think\Db;

class Trc20 extends Base
{
	public function orderlist(){
		return $this->fetch();
    }

    public function getReturn($code = 1,$msg = "成功",$data = null){
        return array("code"=>$code,"msg"=>$msg,"data"=>$data);
    }
    public function getMain(){
        $today = strtotime(date("Y-m-d"),time());
        $todayOrder = Db::name("usdt_order")
            ->where("create_date", ">=", $today)
            ->where("create_date", "<=", ($today+86400))
            ->count();
        $todaySuccessOrder = Db::name("usdt_order")
            ->where("state", ">=", 1)
            ->where("create_date", ">=", $today)
            ->where("create_date", "<=", ($today+86400))
            ->count();
        $todayCloseOrder = Db::name("usdt_order")
            ->where("state",-1)
            ->where("create_date", ">=", $today)
            ->where("create_date", "<=", ($today+86400))
            ->count();
        $todayMoney = Db::name("usdt_order")
            ->where("state", ">=", 1)
            ->where("create_date", ">=", $today)
            ->where("create_date", "<=", ($today+86400))
            ->sum("price");
        $countOrder = Db::name("usdt_order")
            ->count();
        $countMoney = Db::name("usdt_order")
            ->where("state", ">=", 1)
            ->sum("price");
        
        return json($this->getReturn(1,"成功",array(
            "todayOrder"=>$todayOrder,
            "todaySuccessOrder"=>$todaySuccessOrder,
            "todayCloseOrder"=>$todayCloseOrder,
            "todayMoney"=>round($todayMoney,2),
            "countOrder"=>$countOrder,
            "countMoney"=>round($countMoney),
        )));

    }

    
    public function getOrders(){
        $page = max(1, (int)input("page"));
        $size = max(1, min(100, (int)input("limit")));

        $state = input("state");
        $countQuery = Db::name('usdt_order');
        $listQuery = Db::name('usdt_order');
        if ($state !== null && $state !== '') {
            $countQuery = $countQuery->where("state", $state);
            $listQuery = $listQuery->where("state", $state);
        }
        $count = $countQuery->count();
        $array = $listQuery->order("id","desc")->page($page, $size)->select();
        return json(array(
            "code"=>0,
            "msg"=>"获取成功",
            "data"=>$array,
            "count"=> $count
        ));
    }
    public function delOrder(){
        $id = (int)input("id");
        $res = Db::name("usdt_order")->where("id", $id)->find();
        if (!$res) {
            return json($this->getReturn(-1, "订单不存在"));
        }

        Db::name("usdt_order")->where("id", $id)->delete();
        if ((int)$res['state'] === 0) {
            Db::name("tmp_uprice")
                ->where("oid", $res['order_id'])
                ->delete();
        }
        return json($this->getReturn());

    }

    /**
     * 补单：通道回调丢失时，按业务订单金额完成发卡/充值
     */
    public function setBd(){
        $res = Db::name("usdt_order")->where("id", (int)input("id"))->find();
        if (!$res) {
            return json($this->getReturn(-1, "订单不存在"));
        }

        $ret = PayFulfill::adminReissue(
            $res['pay_id'],
            $res['price'],
            $res['order_id'],
            isset($res['state']) ? $res['state'] : 0
        );
        PayFulfill::logAdminReissue(
            (int)session('uid'),
            $res['pay_id'],
            $res['price'],
            $ret['msg']
        );

        if (!$ret['ok']) {
            return json($this->getReturn(-1, $ret['msg']));
        }

        // 履约成功或业务已处理：同步通道为完成并清临时占价
        if (in_array((int)$res['state'], [0, -1], true)) {
            Db::name("tmp_uprice")->where("oid", $res['order_id'])->delete();
        }
        Db::name("usdt_order")->where("id", $res['id'])->update([
            "state" => 1,
            "pay_date" => time(),
        ]);

        return json($this->getReturn(1, $ret['msg']));
    }

    public function delGqOrder(){
        Db::name("usdt_order")->where("state","-1")->delete();
        return json($this->getReturn());
    }
    public function delLastOrder(){
        Db::name("usdt_order")->where("create_date", "<", (time()-604800))->delete();
        return json($this->getReturn());
    }


    //获取客户IP
    public function ip() {

        return $_SERVER['REMOTE_ADDR'];
    }
}
