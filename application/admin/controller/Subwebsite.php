<?php
namespace app\admin\controller;
use app\admin\model\ConfigModel;
use app\admin\model\MemberModel;
use app\admin\model\FzauthModel;
use app\admin\model\MemberGroupModel;
use think\Db;
use com\IpLocationqq;

class Subwebsite extends Base
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
    
    //*********************************************会员列表*********************************************//
    /**
     * 子站列表
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function sublist(){

        $key = input('key');
        $group_id=input('group_id');
        if($group_id!='999' && $group_id!==null){
          $map['think_member.group_id']=$group_id;
        }
        $map['closed'] = 0;//0未删除，1已删除
   		$map['fzhost']=['neq',""];//0未删除，1已删除
        if($key&&$key!=="")
        {           
            $map['account|nickname|mobile|fzhost'] = ['like',"%" . $key . "%"];          
        }
        $arr=Db::name("member_group")->column("id,group_name"); //获取分组列表
        $Zmoney=Db::name("member")->where("fzhost<>'' and fzstatus=1")->column("sum(money)"); //获取剩余总额
        $Ztgmoney=Db::name("member")->where("fzhost<>'' and fzstatus=1")->column("sum(tg_money)"); //获取总推广佣金
        $Ztxmoney=Db::name("member_tixian")->where("status",1)->column("sum(paymoney)"); //获取总推广佣金             
        $member = new MemberModel();       
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $member->getAllCount($map);//计算总页面
        $allpage = intval(ceil($count / $limits));       
        $lists = $member->getMemberByWhere($map, $Nowpage, $limits);
         $Ip = new IpLocationqq('qqwry.dat'); // 实例化类 参数表示IP地址库文件
        foreach($lists as $k=>$v){
              $lists[$k]['last_login_time']=date("Y-m-d H:i:s",$lists[$k]['last_login_time']);
              $userip=$lists[$k]['last_login_ip'];
                if(!empty($userip)){
                    $lists[$k]['ipaddr'] = $Ip->getlocation($userip);
                }else{
                    $lists[$k]['ipaddr']=['country'=>'未知','area'=>'地区'];    	
                }            
          }
        if($group_id===null){
          $group_id=999;
        }
        $this->assign('count', $count);   
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign("search_user",$arr);
        $this->assign("group_id",$group_id);
        $this->assign("Zmoney",$Zmoney);
        $this->assign("Ztgmoney",$Ztgmoney);
        $this->assign("Ztxmoney",$Ztxmoney);
        $this->assign('val', $key);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }
    
    /**
     * 编辑子站
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function edit_submember()
    {
        $member = new MemberModel();
        $fzauth = new FzauthModel();
        if(request()->isAjax()){
            $param = input('post.');
           
            if($param['fzhost']==""){
            	return json(['code' => -1, 'data' => '', 'msg' => "分站域名不能为空"]);
            }
            $result=Db::name('member')->where('id',$param['id'])->update(['fzhost'=>$param['fzhost'],'fzstatus'=>$param['fzstatus']]);
            if($result===false){
            	return json(['code' => -1, 'data' => '', 'msg' => "更新会员信息出错"]);
            }
            
            $result=Db::name('fz_auth')->where('memberid',$param['id'])->update(['goodsname'=>$param['goodsname'],
								            									'goodsimgurl'=>$param['goodsimgurl'],
								            									'goodsxqnotice'=>$param['goodsxqnotice'],
								            									'goodsprice'=>$param['goodsprice'],
								            									'ordersauth'=>$param['ordersauth']
            																	]);
            if($result===false){
            	return json(['code' => -1, 'data' => '', 'msg' => "更新失败"]);
            }else{
				
                $map = array('name' => 'web_host','memberid'=>$param['id']);
				
				$exist=Db::name('child_config')->where($map)->find();	
				if(!$exist){
					Db::name('child_config')->insert($map);       		   		
				}
					Db::name('child_config')->where($map)->setField('value', $param['fzhost']);
				
            	return json(['code' => 1, 'data' => '', 'msg' => "更新成功"]);
            }
        }

        $id = input('param.id');     
        $fzresult=$fzauth->getOneFzauth($id);  
            
        $this->assign([
            'member' => $member->getOneMember($id),       
            'fzauth'=>$fzresult
        ]);
        return $this->fetch();
    }
    
    /**
     * 进入会员后台
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function login_member()
    {
        $param=inputself();
        $id=$param['id'];
        $hasUser = Db::name('member')->where('id', $id)->find();
        if($hasUser){
            // 进入会员中心使用一次性登录凭证
            $ticket = \com\AdminLoginTicket::issue($hasUser['id'], ['fzhost' => $hasUser['fzhost']]);
            $token=md5(md5($hasUser['account'] . $hasUser['password']).md5(date("Y-m-d")). config('auth_key'). config('token').$hasUser['fzhost']);
            Db::name('member')->where('id', $hasUser['id'])->update(['token'=>$token]);
            $url = 'http://'.$hasUser['fzhost'].url('@jingdian/user/ticketLogin', ['ticket' => $ticket]);
            return $this->redirect($url, 302);
        }
        return $this->fetch();
    }
    
    /**
     * 编辑子站DO
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function edit_submemberDo()
    {
       
        $param = input('post.');
        
        if($param['endtimetype']==1){
           	$endtime=0;
           	$sql="update think_fz_auth set endtime=0 where memberid=:memberid";
           	$result=Db::execute($sql,['memberid'=>$param['id']]);
        }
        if($param['endtimetype']==0){
           	$endtime=$param['endtime'];
           	$sql="update think_fz_auth set endtime=endtime+:endtime where memberid=:memberid";
           	$result=Db::execute($sql,['endtime'=>$endtime,'memberid'=>$param['id']]);
        }
           
        
        if($result){
          	return json(['code' => 1, 'data' => '', 'msg' => "更新成功"]);
        }else{
           	return json(['code' => -1, 'data' => '', 'msg' => "更新失败"]);
        }     
    }
    
    /**
     * 添加子站
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function add_submember()
    {
        if(request()->isAjax()){

            $param = input('post.');
           
            $hasUser=Db::name('member')->where('account',$param['account'])->find();
            if(!$hasUser){
            	return json(['code' => -1, 'data' => '', 'msg' => "会员帐号不存在"]);
            }
            if($param['fzhost']==""){
            	return json(['code' => -1, 'data' => '', 'msg' => "分站域名不能为空"]);
            }
            $hasfzhost=Db::name('member')->where('fzhost',$param['fzhost'])->find();
            if($hasfzhost){
            	return json(['code' => -1, 'data' => '', 'msg' => "该域名已经绑定其他账户[".$hasfzhost['account']."]"]);
            }
            if($hasUser['fzhost']!=""){
            	return json(['code' => -1, 'data' => '', 'msg' => "此会员已开通分站"]);
            }
            $result=Db::name('member')->where('account',$param['account'])->update(['fzhost'=>$param['fzhost'],'fzstatus'=>$param['fzstatus']]);
            if(!$result){
            	return json(['code' => -1, 'data' => '', 'msg' => "更新会员信息出错"]);
            }
            $hasUserAuth=Db::name('fz_auth')->where('memberid',$hasUser['id'])->find();
            if($hasUserAuth){
            	return json(['code' => -1, 'data' => '', 'msg' => "系统权限已存在"]);
            }
            if($param['endtimetype']==1){
            	$endtime=0;
            }
            if($param['endtimetype']==0){
            	$endtime=$param['endtime'];
            }
            Db::name('child_article')->insert(['memberid'=>$hasUser['id'],'title'=>'我是首页公告标题，可以修改','cate_id'=>1,'content'=>'我是首页公告内容，可以修改']);
            Db::name('child_article')->insert(['memberid'=>$hasUser['id'],'title'=>'我是免责声明，可以修改','cate_id'=>3,'content'=>'我是免责声明，可以修改']);
            
            $result=Db::name('fz_auth')->insert(['memberid'=>$hasUser['id'],
            									'starttime'=>time(),
            									'endtime'=>$endtime,
            									'goodsname'=>$param['goodsname'],
            									'goodsimgurl'=>$param['goodsimgurl'],
            									'goodsxqnotice'=>$param['goodsxqnotice'],
            									'goodsprice'=>$param['goodsprice'],
            									'ordersauth'=>$param['ordersauth']
            									]);
            if($result){
            	return json(['code' => 1, 'data' => '', 'msg' => "添加成功"]);
            }else{
            	return json(['code' => -1, 'data' => '', 'msg' => "添加失败"]);
            }
 
        }      
        return $this->fetch();
    }
    
     /**
     * 子站状态
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function submember_status()
    {
        $id = input('param.id');
        $status = Db::name('member')->where('id',$id)->value('fzstatus');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('member')->where('id',$id)->setField(['fzstatus'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        }
        else
        {
            $flag = Db::name('member')->where('id',$id)->setField(['fzstatus'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }
    
    }

}