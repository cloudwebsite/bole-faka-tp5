<?php
namespace app\mobile\controller;

use app\jingdian\model\BaseModel;
use think\Controller;
use think\Db;
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
    config('web_music', htmlspecialchars_decode(config('web_music')));
    config('web_site_cnzz', htmlspecialchars_decode(config('web_site_cnzz')));
    //--------检测分站begin
    $arrayMain = explode(',', config('main_webhost'));
    if (count($arrayMain) <= 0) {
      exit('Error:001 Msg:请联系管理员配置主站HOST');
    }

     if (!in_array($_SERVER['HTTP_HOST'], $arrayMain)) {
       $hasUser = Db::name('member')->where('fzhost', $_SERVER['HTTP_HOST'])->find();
       if (!$hasUser) {
         exit('Error:002 Msg:非法HOST');
       }
       if ($hasUser['fzstatus'] == 1) {
         $hasUserAuth = Db::name('fz_auth')->where('memberid', $hasUser['id'])->find();
         if (!$hasUserAuth) {
           exit('Error:003 Msg:请联系主站配置权限');
         }
         $store = $hasUserAuth['starttime'] + $hasUserAuth['endtime'] * 24 * 60 * 60 - time();
         if ($store <= 0 && $hasUserAuth['endtime'] > 0) {
           exit('Error:004 Msg:已到期,请联系管理员续费->' . $store);
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
         exit('Error:003 Msg:此分站已被禁止');
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

    $wapbanner = $model->getWAPbanner();
    foreach ($wapbanner as &$v) {
      $v['images'] = str_replace('\\', '/', $v['images']);
    }
    $this->assign('wapbanner', $wapbanner);
    $kamibanner = $model->getKmbanner();
    foreach ($kamibanner as &$v) {
      $v['images'] = str_replace('\\', '/', $v['images']);
    }
    $this->assign('kamibanner', $kamibanner);
    //获取爆款促销产品
    if (config('wapmoban_index') == 3) {
      $pcAllHot = $model->getAllHot();

      $this->assign('pcallhot', $pcAllHot);
    }

    $usertoken = cookie('usertoken');
    $module     = strtolower(request()->module());
    $controller = strtolower(request()->controller());
    $action     = strtolower(request()->action());
    $url        = $module . "/" . $controller . "/" . $action;
    if (config('web_reg_type') == 0) {
      //不能注册
      if (in_array($url, ['mobile/user/index', 'mobile/user/reg'])) {

        $this->error('管理员未开启会员系统模式', url('mobile/index/index'));
      }
    } elseif (config('web_reg_type') == 1 || config('web_reg_type') == 2) {
     if (session('useraccount.id') && session('useraccount.account')) {
        $hasUser = Db::name('member')->where('token', $usertoken)->find();
        $token = md5(md5($hasUser['account'] . $hasUser['password']) . md5(date("Y-m-d")) . config('auth_key') . config('token') . $_SERVER['HTTP_HOST']);
        if ($usertoken == $token) {
          if ($hasUser['status'] == 0) {
            session('useraccount', null);
            cookie('usertoken', null);
            $this->error('此账户已禁用', url('mobile/index/index'));
          }
          self::checkMemberGroup(session('useraccount.id'));
          self::checkMemberMarket(session('useraccount.id'));
          session('useraccount', $hasUser);
          cookie('usertoken', $token);
          $this->assign('useraccount', session('useraccount'));
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
            $this->error('此账户已禁用', url('mobile/index/index'));
          }
          self::checkMemberGroup(session('useraccount.id'));
          self::checkMemberMarket(session('useraccount.id'));
          session('useraccount', $hasUser);
          cookie('usertoken', $token);
          $this->assign('useraccount', session('useraccount'));
        } else {
          session('useraccount', null);
          cookie('usertoken', null);
        }
      }

      if (!session('useraccount.id') && !session('useraccount.account')) {
        if (in_array($url, ['mobile/user/usorder', 'mobile/user/getpass', 'mobile/user/uscenter', 'mobile/user/usintegrallog', 'mobile/user/uspay', 'mobile/user/uspaylog', 'mobile/user/usupdatepass', 'mobile/user/redeemkami'])) {
          $this->error('请先登录', url('mobile/user/index'));
        }
      }
    }





    //分销session
    $pidParam = inputself();
    if (isset($pidParam['pid'])) {
      session('userpid', $pidParam['pid']);
    } elseif (session('child_useraccount.id')) {
      session('userpid', session('child_useraccount.id'));
    }






    $cate = $model->getAllCate();
    $href = $model->getYqHref();
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

    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
      $password .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $password;
  }
}

