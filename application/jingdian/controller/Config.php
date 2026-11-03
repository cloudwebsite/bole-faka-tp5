<?php
namespace app\jingdian\controller;
use app\jingdian\model\ConfigModel;
use com\ConfigGuard;
use think\Db;

class Config extends Base
{


    /**
     * 获取配置参数
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function index() {
        if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	$this->error('未知错误',url('user/uscenter'));
        }
        $fzconfig = cache('config_child_' . session('useraccount.id'));
        if (!$fzconfig) {
            $fzconfig = load_config_child(session('useraccount.id'));
            cache('config_child_' . session('useraccount.id'), $fzconfig);
        }
		$subhosts = explode(',', config('sub_webhost'));
        $this->assign('fzconfig',$fzconfig);
		$this->assign('subhosts', $subhosts);
        return $this->fetch();
    }



    /**
     * 保存分站可改配置（校验域名占用；仅允许已知配置项）
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function save($config){
    	if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	$this->error('未知错误',url('user/uscenter'));
        }
        if (!check_form_token()) {
            $this->error('令牌验证失败，请刷新页面后重试');
        }
        if (!$config || !is_array($config)) {
            $this->error('参数错误');
        }

        $memberId = (int)session('useraccount.id');
        $param = inputself();
        $hostCheck = ConfigGuard::validateChildHost(
            isset($param['hostprefix']) ? $param['hostprefix'] : '',
            isset($param['merchanthost']) ? $param['merchanthost'] : '',
            $memberId
        );
        if (!$hostCheck['ok']) {
            $this->error($hostCheck['msg']);
        }

        $toSave = ConfigGuard::filterChildSave($config);
        if (empty($hostCheck['skip']) && !empty($hostCheck['url'])) {
            $toSave['web_host'] = $hostCheck['url'];
        }

        if (!$toSave) {
            $this->error('没有可保存的合法配置项');
        }

        $configModel = new ConfigModel();
        foreach ($toSave as $name => $value) {
            $map = array('name' => $name, 'memberid' => $memberId);
            // 允许的配置项：已有则更新，首次保存可补齐
            $configModel->SaveConfig($map, $value, true);
        }

        if (isset($toSave['web_host'])) {
            Db::name('member')->where('id', $memberId)->update(['fzhost' => $toSave['web_host']]);
        }

        cache('config_child_' . $memberId, null);
        $this->success('保存成功！');
    }

}
