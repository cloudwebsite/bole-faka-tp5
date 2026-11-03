<?php
namespace app\madmin\controller;
use app\admin\model\CateGoryModel;
use app\admin\model\OrderModel;
use app\admin\model\MemberModel;
use app\admin\model\UserType;
use com\IpLocationqq;
use com\KamiRestockService;
use org\Verify;
use think\Db;
use think\Request;

class Index extends Base
{
    /**
     * 是否需要图形验证码 / 极验（优先 verify_type，兼容 CODE_TYPE）
     * @return string ''|image|geetest
     */
    protected function captchaMode()
    {
        $verifyType = config('verify_type');
        if ($verifyType === null || $verifyType === '') {
            if (config('CODE_TYPE') == 1) {
                return 'geetest';
            }
            if (config('CODE_TYPE') == 0) {
                return 'image';
            }
            return '';
        }
        if (intval($verifyType) === 1) {
            return 'image';
        }
        if (config('CODE_TYPE') == 1) {
            return 'geetest';
        }
        return '';
    }

    /**
     * 登录
     */
    public function doLogin()
    {
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $referer = str_ireplace("http://", "", $referer);
        $referer = str_ireplace("https://", "", $referer);
        if (urldecode($referer) != $_SERVER['HTTP_HOST'] . '/m' . getadminpath() . '.html'
            && urldecode($referer) != $_SERVER['HTTP_HOST'] . '/m' . getadminpath()) {
            return json(['code' => 404, 'url' => '404.html', 'msg' => '404页面丢失']);
        }
        $username = input("param.username");
        $password = input("param.password");

        $result = $this->validate(compact('username', 'password'), 'MemberloginValidate');
        if (true !== $result) {
            return json(['code' => -5, 'url' => '', 'msg' => $result]);
        }

        $mode = $this->captchaMode();
        if ($mode === 'image') {
            $code = input("param.code");
            $verify = new Verify();
            if (!$code) {
                return json(['code' => -4, 'url' => '', 'msg' => '请输入验证码']);
            }
            if (!$verify->check($code)) {
                return json(['code' => -4, 'url' => '', 'msg' => '验证码错误']);
            }
        } elseif ($mode === 'geetest') {
            $gtresult = action("jingdian/Geetest/gtcheck");
            if ($gtresult == false) {
                return json(['code' => -4, 'url' => '', 'msg' => '验证码错误']);
            }
        }

        $hasUser = Db::name('admin')->where('username', $username)->find();
        if (empty($hasUser)) {
            return json(['code' => -1, 'url' => '', 'msg' => '管理员不存在']);
        }

        if (md5(md5($password) . config('auth_key')) != $hasUser['password']) {
            writelog($hasUser['id'], $username, '用户【' . $username . '】登录失败：密码错误', 2);
            return json(['code' => -2, 'url' => '', 'msg' => '账号或密码错误']);
        }

        if (1 != $hasUser['status']) {
            writelog($hasUser['id'], $username, '用户【' . $username . '】登录失败：该账号被禁用', 2);
            return json(['code' => -6, 'url' => '', 'msg' => '该账号被禁用']);
        }

        $user = new UserType();
        $info = $user->getRoleInfo($hasUser['groupid']);
        $token = md5($hasUser['username'] . $hasUser['password'] . $_SERVER['HTTP_HOST'] . date("Y-m-d") . getIP());
        session('uid', $hasUser['id']);
        session('username', $hasUser['username']);
        session('password', $hasUser['password']);
        session('portrait', $hasUser['portrait']);
        session('rolename', isset($info['title']) ? $info['title'] : '');
        session('rule', isset($info['rules']) ? $info['rules'] : '');
        session('name', isset($info['name']) ? $info['name'] : '');
        session('admintoken', $token);
        cookie('admintoken', $token);

        $param = [
            'loginnum' => $hasUser['loginnum'] + 1,
            'last_login_ip' => getIP(),
            'last_login_time' => time(),
            'token' => $token,
        ];
        Db::name('admin')->where('id', $hasUser['id'])->update($param);
        writelog($hasUser['id'], session('username'), '用户【' . session('username') . '】登录成功', 1);
        return json(['code' => 1, 'url' => url('index/index'), 'msg' => '登录成功！']);
    }

    /**
     * 登录页
     */
    public function login()
    {
        $request = Request::instance();
        if ($request->path() != 'm' . getadminpath()) {
            abort(404, '页面不存在');
        }
        if (!request()->isPost()) {
            $this->assign('captcha_mode', $this->captchaMode());
            return $this->fetch();
        }
        return json(['code' => -1, 'msg' => '请使用登录接口']);
    }

    /**
     * 图形验证码（本模块）
     */
    public function checkVerify()
    {
        $verify = new Verify();
        $verify->imageH = 32;
        $verify->imageW = 100;
        $verify->length = 4;
        $verify->useCurve = false;
        $verify->useNoise = false;
        $verify->fontSize = 14;
        return $verify->entry();
    }

    /**
     * 退出
     */
    public function loginOut()
    {
        session(null);
        cookie('admintoken', null);
        $this->redirect(url('index/login'));
    }

    public function index()
    {
        $sql = "SELECT sum(mamount) as jinri FROM `think_info` where mstatus<>2 and to_days(date_format(from_UNIXTIME(`update_time`),'%Y-%m-%d')) = to_days(now())";
        $info_jinri = Db::query($sql);
        $this->assign('info_jinri', $info_jinri);
        $sql = "SELECT sum(mamount) as zuori FROM `think_info` where mstatus<>2 and to_days(now())-to_days(date_format(from_UNIXTIME(`update_time`),'%Y-%m-%d')) =1 ";
        $info_zuori = Db::query($sql);
        $this->assign('info_zuori', $info_zuori);
        $sql = "SELECT count(1) as infonum from think_info";
        $info_info = Db::query($sql);
        $this->assign('info_info', $info_info);
        $sql = "SELECT count(id) as daifahuo from think_info where mstatus=1 and mstatus<>4 and mflid in(SELECT id from think_fl where type=1)";
        $info_daifahuo = Db::query($sql);
        $this->assign('info_daifahuo', $info_daifahuo);
        $daitixian = Db::name('member_tixian')->where('status', 0)->count();
        $this->assign('daitixian', $daitixian);
        $sql = "SELECT count(id) as weitiqu from think_info where mstatus=0 and mflid in(SELECT id from think_fl where type=0)";
        $info_weitiqu = Db::query($sql);
        $this->assign('info_weitiqu', $info_weitiqu);
        $shopnum = Db::name('fl')->count();
        $this->assign('shopnum', $shopnum);
        $membernum = Db::name('member')->count();
        $this->assign('membernum', $membernum);
        return $this->fetch();
    }

    /**
     * 商品管理（仅上架虚拟商品）
     */
    public function goods()
    {
        $key = input('key');
        // 移动端补货场景：仅上架虚拟商品
        $status = 1;
        $type = 0;
        $map = [];
        if ($key && $key !== "") {
            $map['mname|mnotice'] = ['like', "%" . $key . "%"];
        }

        $mlm = input('mlm');
        if ($mlm != '999' && $mlm !== null) {
            $map['think_fl.mlm'] = $mlm;
        }
        $arr = Db::name("category_group")->column("id,name");
        $member = new CateGoryModel();
        $Nowpage = input('get.page') ? input('get.page') : 1;
        $limits = config('list_rows');
        $count = $member->getAllCount($map, $status, $type);
        $allpage = intval(ceil($count / $limits));
        $lists = $member->getMemberByWhere($map, $Nowpage, $limits, $status, $type);
        if (!empty($lists)) {
            $data_mail = Db::query("SELECT count(mis_use) as count,mpid from think_mail where mis_use=0 GROUP BY mpid");
            $data_yiyong = Db::query("SELECT count(mis_use) as yiyong,mpid from think_mail where mis_use=1 GROUP BY mpid");
            $result = [];
            foreach ($data_mail as $val) {
                $result[$val['mpid']] = $val['count'];
            }
            $used = [];
            foreach ($data_yiyong as $val) {
                $used[$val['mpid']] = $val['yiyong'];
            }
            foreach ($lists as &$v) {
                if (!empty($result[$v['id']])) {
                    $v['count'] = $result[$v['id']];
                } else {
                    $v['count'] = 0;
                }
                if (!empty($used[$v['id']])) {
                    $v['yiyong'] = $used[$v['id']];
                } else {
                    $v['yiyong'] = 0;
                }
                $v['zongshu'] = $v['count'] + $v['yiyong'];
            }
            unset($v);
        }

        if ($mlm === null) {
            $mlm = 999;
        }

        $this->assign('count', $count);
        $this->assign('Nowpage', $Nowpage);
        $this->assign('allpage', $allpage);
        $this->assign('val', $key);
        $this->assign('status', $status);
        $this->assign('type', $type);
        $this->assign("search_user", $arr);
        $this->assign("mlm", $mlm);
        if (input('get.page')) {
            return json($lists);
        }
        return $this->fetch();
    }

    /**
     * 补货
     */
    public function addkami()
    {
        $param = inputself();
        $goodid = isset($param['goodid']) ? $param['goodid'] : 0;
        $FLData = Db::name('fl')->where('id', $goodid)->find();
        if (!request()->isPost()) {
            if (!$FLData) {
                return $this->error('商品不存在');
            }
            if ($FLData['status'] == 0) {
                return $this->error('该商品已下架');
            }
            $this->assign('shopdetail', $FLData);
            return $this->fetch();
        }

        $kami = isset($param['kami']) ? $param['kami'] : '';
        if (!ensure_kami_restock_service()) {
            return json(['code' => -1, 'msg' => '补货服务未加载，请检查 extend/com/KamiRestockService.php 是否存在', 'url' => url('index/goods')]);
        }
        $addqudao = 'madmin:' . intval(session('uid'));
        $flag = KamiRestockService::restock($goodid, $kami, $addqudao, getIP());
        $flag['url'] = url('index/goods');
        return json($flag);
    }

    public function order()
    {
        $key = input('key');
        $mstatus = input('mstatus');
        $map = [];
        if ($key && $key !== "") {
            $map['mcard|morder|lianxi|think_info.email'] = ['like', "%" . $key . "%"];
        }
        if ($mstatus !== null && $mstatus !== '') {
            $map['mstatus'] = $mstatus;
        }
        $order = new OrderModel();
        $Nowpage = input('get.page') ? input('get.page') : 1;
        $limits = config('list_rows');
        $count = $order->getAllCount($map);
        $allpage = intval(ceil($count / $limits));
        $lists = $order->getOrderByWhere($map, $Nowpage, $limits);

        $this->assign('count', $count);
        $this->assign('Nowpage', $Nowpage);
        $this->assign('allpage', $allpage);
        $this->assign('val', $key);
        $this->assign('mstatus', $mstatus);
        if (input('get.page')) {
            return json($lists);
        }
        return $this->fetch();
    }

    /**
     * 订单编辑
     */
    public function orderedit()
    {
        if (!request()->isPost()) {
            $orderid = input('id');
            $info = Db::name('info')->where('id', $orderid)->find();
            if (!$info) {
                return $this->error('订单不存在');
            }
            $shop = Db::name('fl')->where('id', $info['mflid'])->find();
            $this->assign('orderid', $orderid);
            $this->assign('info', $info);
            $this->assign('shopdetail', $shop ? $shop : ['mnamebie' => '未知商品']);
            return $this->fetch();
        }
        $param = inputself();
        $OrderM = new OrderModel();
        $result = $OrderM->editOrder($param);
        return json($result);
    }

    /**
     * 会员列表
     */
    public function member()
    {
        $key = input('key');
        $group_id = input('group_id');
        $map = [];
        if ($group_id != '999' && $group_id !== null) {
            $map['think_member.group_id'] = $group_id;
        }
        $map['closed'] = 0;

        if ($key && $key !== "") {
            $map['account|nickname|mobile'] = ['like', "%" . $key . "%"];
        }
        $arr = Db::name("member_group")->column("id,group_name");
        $Zmoney = Db::name("member")->sum('money');
        $Ztgmoney = Db::name("member")->sum('tg_money');
        $member = new MemberModel();
        $Nowpage = input('get.page') ? input('get.page') : 1;
        $limits = config('list_rows');
        $count = $member->getAllCount($map);
        $allpage = intval(ceil($count / $limits));
        $lists = $member->getMemberByWhere($map, $Nowpage, $limits);

        $Ip = new IpLocationqq('qqwry.dat');
        foreach ($lists as $k => $v) {
            $lists[$k]['last_login_time'] = date("Y-m-d H:i:s", $lists[$k]['last_login_time']);
            $userip = $lists[$k]['last_login_ip'];
            if (!empty($userip)) {
                $lists[$k]['ipaddr'] = $Ip->getlocation($userip);
            } else {
                $lists[$k]['ipaddr'] = ['country' => '未知', 'area' => '地区'];
            }
        }
        if ($group_id === null) {
            $group_id = 999;
        }
        $this->assign('count', $count);
        $this->assign('Nowpage', $Nowpage);
        $this->assign('allpage', $allpage);
        $this->assign("search_user", $arr);
        $this->assign("group_id", $group_id);
        $this->assign("Zmoney", $Zmoney);
        $this->assign("Ztgmoney", $Ztgmoney);
        $this->assign('val', $key);
        if (input('get.page')) {
            return json($lists);
        }
        return $this->fetch();
    }
}
