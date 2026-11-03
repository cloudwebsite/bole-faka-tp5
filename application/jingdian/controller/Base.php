<?php
namespace app\jingdian\controller;

use app\jingdian\model\BaseModel;
use app\jingdian\model\GoodsListModel;
use think\Controller;
use think\Db;
use com\AccessEncryption;
use com\ConfigGuard;

class Base extends Controller
{
  public function _initialize()
  {

    // 站点配置缓存：只写入库表配置项
    $config = cache('db_config_data');
    if (!$config) {
      $config = rebuild_db_config_cache();
    }
    config($config);
    $MainMobile = config('WEB_MOBILE');
    config('MainMobile', $MainMobile);
    $pcbanner = config('web_pcbanner');
    $pcbanner = str_replace('\\', '/', $pcbanner);
    config('web_pcbanner', $pcbanner);
    config('web_music', htmlspecialchars_decode(config('web_music')));
    config('web_site_cnzz', htmlspecialchars_decode(config('web_site_cnzz')));

    //--------检测分站begin
    $arrayMain = explode(',', config('main_webhost'));
    if (count($arrayMain) <= 0) {
      $this->error('Error:001 Msg:请联系管理员配置主站HOST');
    }
    if (!in_array($_SERVER['HTTP_HOST'], $arrayMain)) {
      $hasUser = Db::name('member')->where('fzhost', $_SERVER['HTTP_HOST'])->find();
      if (!$hasUser) {
        $this->error('Error:002 Msg:非法HOST');
      }
      if ($hasUser['fzstatus'] == 1) {
        $hasUserAuth = Db::name('fz_auth')->where('memberid', $hasUser['id'])->find();
        if (!$hasUserAuth) {
           $this->error('Error:003 Msg:请联系主站配置权限');
         }
         $store = $hasUserAuth['starttime'] + $hasUserAuth['endtime'] * 24 * 60 * 60 - time();
        if ($store <= 0 && $hasUserAuth['endtime'] > 0) {
          $this->error('Error:004 Msg:已到期,请联系管理员续费->' . $store);
       }
        $configChild = cache('config_child_' . $hasUser['id']);
        if (!$configChild) {
          $configChild = rebuild_child_config_cache($hasUser['id']);
        }
        // 分站仅合并允许的配置项，不改写主站配置缓存
        $configChild = ConfigGuard::filterChildRuntime(is_array($configChild) ? $configChild : []);
        foreach ($configChild as $k => $v) {
          $config[$k] = $v;
         }
        $hasUser['auth'] = $hasUserAuth;
        config($config);
		config('web_music', htmlspecialchars_decode(config('web_music')));
		config('web_site_cnzz', htmlspecialchars_decode(config('web_site_cnzz')));
        session('child_useraccount', $hasUser);
      } else {
         $this->error('Error:003 Msg:此分站已被禁止');
      }
     } else {
       session('child_useraccount', null);
     }	 
    //------检测分站end

    $pwd = self::generate_password();
    $cookieToken = cookie('tokenid');

    if (empty($cookieToken)) {
      cookie('tokenid', $pwd);
    }
    if (strstr($cookieToken, "|")) {
      cookie('tokenid', $pwd);
    }
    if (strlen($cookieToken) < 64) {
      cookie('tokenid', $pwd);
    }
    $cookieToken = cookie('tokenid');

    $model = new BaseModel();
    $cate = $model->getAllCate();
    $href = $model->getYqHref();
    if (config('moban_index') == 4 || config('moban_index') == 5) {
      $pcbanner = $model->getPCbanner();
      foreach ($pcbanner as &$v) {
        $v['images'] = str_replace('\\', '/', $v['images']);
      }
      $this->assign('pcbanner', $pcbanner);
    }

    $navigation = $model->getAllNavigation();
    $GoodList = new GoodsListModel();
    $data_flName = $GoodList->getAllGoodsName();
    $data_lmshop = $GoodList->getAllGoods($data_flName);
    $this->assign('GoodsLmShop', $data_lmshop);
    $this->assign('navigation', $navigation);


    //获取爆款促销产品
    if (config('moban_index') == 4 || config('moban_index') == 5) {
      $pcAllHot = $model->getAllHot();
      $this->assign('pcallhot', $pcAllHot);
    }

    // 会员登录仅认 cookie 或一次性登录凭证，不从地址栏读长期令牌
    $usertoken = cookie('usertoken');

    $module     = strtolower(request()->module());
    $controller = strtolower(request()->controller());
    $action     = strtolower(request()->action());
    $url        = $module . "/" . $controller . "/" . $action;
	
    //--------检测分站管理员begin
	if((($controller=="user"&& $action!="usorder"&& $action!="index"&& $action!="reg"&& $action!="regmobile"&& $action!="ticketlogin"&& $action!="ticketconsume") || $controller=="config" || $controller=="goodsset" || $controller=="navigation" || $controller=="friend" || $controller=="article")&&session('useraccount.fzstatus')== 1){
		$hasUser = Db::name('member')->where('id', session('useraccount.id'))->find();
		$hasUserAuth = Db::name('fz_auth')->where('memberid', session('useraccount.id'))->find();      
		$configChild = cache('config_child_' . session('useraccount.id'));
	if (!$configChild) {
		$configChild = rebuild_child_config_cache(session('useraccount.id'));
	}
		$hasUser['auth'] = $hasUserAuth;
		session('child_useraccount', $hasUser);
	 }
	if ($controller=="config" || $controller=="goodsset" || $controller=="navigation" || $controller=="friend" || $controller=="article") {
		if(session('useraccount.fzstatus') != 1){
		  $this->error('未开通分站或被关闭', url('user/ussub'));
		  }
		if ((session('child_useraccount.auth')['starttime'] + session('child_useraccount.auth')['endtime'] * 24 * 60 * 60 - time()) <= 0 && session('child_useraccount.auth')['endtime'] <> 0) {
		  $this->error('分站已到期,请续费', url('user/ussub')); 
		  }
        // 分站后台表单提交校验令牌
        if (request()->isPost() && !check_form_token(false)) {
            if (request()->isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                exit(json_encode(['code' => -1, 'msg' => '令牌验证失败，请刷新页面后重试'], JSON_UNESCAPED_UNICODE));
            }
            $this->error('令牌验证失败，请刷新页面后重试');
        }
        if (!\think\Session::has('__token__')) {
            \think\Request::instance()->token();
        }
        $this->assign('csrf_token', \think\Session::get('__token__'));
	}
    //------检测分站管理员end


    if (config('web_reg_type') == 0) {
      //不能注册
      if (in_array($url, ['jingdian/user/index', 'jingdian/user/reg'])) {

        $this->error('管理员未开启会员系统模式', url('jingdian/index/index'));
      }
    } elseif (config('web_reg_type') == 1 || config('web_reg_type') == 2) {
      if (session('useraccount.id') && session('useraccount.account')) {
        $hasUser = Db::name('member')->where('id', session('useraccount.id'))->find();
        $token = md5(md5($hasUser['account'] . $hasUser['password']) . md5(date("Y-m-d")) . config('auth_key') . config('token') . $_SERVER['HTTP_HOST']);
        if ($usertoken == $token) {
          if ($hasUser['status'] == 0) {
            session('useraccount', null);
            cookie('usertoken', null);
            $this->error('此账户已禁用', url('jingdian/index/index'));
          }
          self::checkMemberGroup(session('useraccount.id'));
          self::checkMemberMarket(session('useraccount.id'));
          $this->assign('useraccount', session('useraccount'));
          session('useraccount', $hasUser);
          cookie('usertoken', $token);
        } else {
          session('useraccount', null);
          cookie('usertoken', null);
        }
      } else if (strlen($usertoken) == 32) {
        $hasUser = Db::name('member')->where('token', $usertoken)->find();
        $token = md5(md5($hasUser['account'] . $hasUser['password']) . md5(date("Y-m-d")) . config('auth_key') . config('token') . $_SERVER['HTTP_HOST']);
        if ($usertoken == $token) {
          if ($hasUser['status'] == 0) {
            session('useraccount', null);
            cookie('usertoken', null);
            $this->error('此账户已禁用', url('jingdian/index/index'));
          }
          self::checkMemberGroup($hasUser['id']);
          self::checkMemberMarket($hasUser['id']);
          session('useraccount', $hasUser);
          cookie('usertoken', $token);
        } else {
          session('useraccount', null);
          cookie('usertoken', null);
        }
      }

      if (!session('useraccount.id') && !session('useraccount.account')) {
        if (in_array($url, ['jingdian/user/usorder', 'jingdian/user/getpass', 'jingdian/user/uscenter', 'jingdian/user/usintegrallog', 'jingdian/user/uspay', 'jingdian/user/uspaylog', 'jingdian/user/usupdatepass', 'jingdian/user/redeemkami'])) {
          $this->error('请先登录', url('jingdian/user/index'));
        }
      }
    }

    //分销session
    $pidParam = inputself();
    if (isset($pidParam['pid'])) {
      session('userpid', $pidParam['pid']);
    } elseif (session('child_useraccount.id') && !session('userpid')) {
      session('userpid', session('child_useraccount.id'));
    }
    //访问加密
	if(config('accessencryption')==2&&request()->controller()!="Mqapi"){
		$visitors = new AccessEncryption(); // 实例化访客密码访问
		$visitors->BLEncrypt(config('access_pwd'));
	}
	//访问加密
    $this->assign('cate', $cate);
    $this->assign('href', $href);
  }

  /*
     * 更新用户等级
     */
  public function checkMemberGroup($memberid)
  {
    $hasUser = Db::name('member')->where('id', $memberid)->find();
    $groupUser = Db::name('member_group')->where('id', $hasUser['group_id'])->find();
    $newGroup = Db::name('member_group')->where('point', 'elt', $hasUser['integral'])->order('point desc')->limit(1)->find();
    if ($groupUser['point'] < 0) {
      //代理不升级
    } else {
      if ($groupUser && $newGroup) {
        if ($groupUser['point'] < $newGroup['point']) {
          //更新用户等级
          Db::name('member')->where('id', $memberid)->update(['group_id' => $newGroup['id']]);
        }
      } else if ($newGroup) {
        //更新用户等级
        Db::name('member')->where('id', $memberid)->update(['group_id' => $newGroup['id']]);
      }
    }
  }

  /*
     * 更新分销商
     */
  public function checkMemberMarket($memberid)
  {
    $hasUser = Db::name('member')->where('id', $memberid)->find();
    if ($hasUser['integral'] >= config('fx_point') && $hasUser['is_distribut'] == 0) {
      //更新用户为分销商
      Db::name('member')->where('id', $memberid)->update(['is_distribut' => 1]);
    }
  }

  public function generate_password($length = 64)
  {
    // 密码字符集，可任意添加你需要的字符 
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
      // 这里提供两种字符获取方式 
      // 第一种是使用 substr 截取$chars中的任意一位字符； 
      // 第二种是取字符数组 $chars 的任意元素 
      // $password .= substr($chars, mt_rand(0, strlen($chars) – 1), 1); 
      $password .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $password;
  }
}

