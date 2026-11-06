<?php
namespace app\pay\controller;
use app\jingdian\model\BaseModel;
use think\Controller;

class Base extends Controller
{
    public function _initialize()
    {
     	$config = cache('db_config_data');
        if(!$config){
            $config = function_exists('rebuild_db_config_cache') ? rebuild_db_config_cache() : load_config();
            if (!function_exists('rebuild_db_config_cache')) {
                cache('db_config_data', $config);
            }
        }
        config($config);

        // 异步回调不依赖前台分类数据，避免初始化异常被打成 404 页
        $action = strtolower((string)request()->action());
        $callbackActions = [
            'callbackalipay', 'callbackweixin', 'callbackepay', 'callbackmzf',
            'notifyyzf', 'callback_usdt', 'callback',
        ];
        if (!in_array($action, $callbackActions, true)) {
            try {
                $model = new BaseModel();
                $this->assign('cate', $model->getAllCate());
                $this->assign('href', $model->getYqHref());
            } catch (\Exception $e) {
                $this->assign('cate', []);
                $this->assign('href', '');
            }
        }

        $this->url_host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT']=='80'? '' : ($_SERVER['SERVER_PORT']=='443'? '' : ':'.$_SERVER['SERVER_PORT'])));
    }
}