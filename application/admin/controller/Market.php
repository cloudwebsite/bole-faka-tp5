<?php
namespace app\admin\controller;
use app\admin\model\ConfigModel;
use think\Db;

class Market extends Base
{
    
    
    /**
     * 
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function index()
    {
        $configModel = new ConfigModel();
        $list = $configModel->getAllConfig();
        $config = [];
        foreach ($list as $k => $v) {
            $config[trim($v['name'])] = $v['value'];
        }
        $this->assign('config',$config);
        return $this->fetch();
    }

}