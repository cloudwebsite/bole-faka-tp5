<?php

namespace app\admin\controller;
use app\admin\model\ConfigModel;
use com\ConfigGuard;
use think\Db;

class Config extends Base
{


    /**
     * 获取配置参数
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function index() {
        $configModel = new ConfigModel();
        $list = $configModel->getAllConfig();
        $config = [];
        foreach ($list as $k => $v) {
            $config[trim($v['name'])] = $v['value'];
        }
        $this->assign('config',$config);
        return $this->fetch();
    }



    /**
     * 批量保存站点配置（仅允许已知配置项；短链开关仅超管）
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function save($config){
        if (!check_form_token()) {
            $this->error('令牌验证失败，请刷新页面后重试');
        }
        if (!$config || !is_array($config)) {
            $this->error('参数错误');
        }

        // 订单查询相关勾选与分页一并规范化
        if (isset($config['list_rows'])) {
            $config = ConfigGuard::normalizeOrderQueryScene($config);
        }

        $uid = (int)session('uid');
        $config = ConfigGuard::filterAdminSave($config, $uid);
        if (!$config) {
            $this->error('没有可保存的合法配置项');
        }

        // 短链接开关仅超管可改；路由文件使用固定模板写入
        if (isset($config['shorturl'])) {
            if ((int)$uid !== 1) {
                unset($config['shorturl']);
            } else {
                if ($config['shorturl'] == '1') {
                    $mobile = "<?php use think\\Route;Route::rule('mg/:mpid','mobile/Index/goodsdetail');Route::rule('mg','mobile/Index/goodsdetail');Route::rule('mc/:lmid','mobile/index/categorybyid');";
                    $jingdian = "<?php use think\\Route;Route::rule('pg/:mpid','jingdian/Index/goodsdetail');Route::rule('pg','jingdian/Index/goodsdetail');Route::rule('pc/:lmid','jingdian/index/goodscategory');Route::rule('createOrder','jingdian/Mqapi/createOrder');Route::rule('getOrder','jingdian/Mqapi/getOrder');Route::rule('checkOrder','jingdian/Mqapi/checkOrder');Route::rule('closeOrder','jingdian/Mqapi/closeOrder');Route::rule('getState','jingdian/Mqapi/getState');Route::rule('appHeart','jingdian/Mqapi/appHeart');Route::rule('appPush','jingdian/Mqapi/appPush');";
                    file_put_contents(ROOT_PATH . 'application/jingdian.php', $jingdian);
                    file_put_contents(ROOT_PATH . 'application/mobile.php', $mobile);
                } else {
                    $jingdian = "<?php use think\\Route;Route::rule('createOrder','jingdian/Mqapi/createOrder');Route::rule('getOrder','jingdian/Mqapi/getOrder');Route::rule('checkOrder','jingdian/Mqapi/checkOrder');Route::rule('closeOrder','jingdian/Mqapi/closeOrder');Route::rule('getState','jingdian/Mqapi/getState');Route::rule('appHeart','jingdian/Mqapi/appHeart');Route::rule('appPush','jingdian/Mqapi/appPush');";
                    file_put_contents(ROOT_PATH . 'application/jingdian.php', $jingdian);
                    file_put_contents(ROOT_PATH . 'application/mobile.php', '');
                }
            }
        }

        $configModel = new ConfigModel();
        foreach ($config as $name => $value) {
            $map = array('name' => $name);
            $configModel->SaveConfig($map, $value);
        }
        cache('db_config_data', null);
        $this->success('保存成功！');
    }

}
