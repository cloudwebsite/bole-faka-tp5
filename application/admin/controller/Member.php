<?php

namespace app\admin\controller;
use app\admin\model\MemberModel;
use app\admin\model\MemberGroupModel;
use app\admin\model\MemberGroupPriceModel;
use app\admin\model\MemberPriceModel;
use app\admin\model\CateGoryModel;
use think\Db;
use com\IpLocationqq;

class Member extends Base
{
    //*********************************************会员组*********************************************//
    /**
     * [group 会员组]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function group(){

        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['group_name'] = ['like',"%" . $key . "%"];          
        }      
        $group = new MemberGroupModel(); 
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');
        $count = $group->getAllCount($map);         //获取总条数
        $allpage = intval(ceil($count / $limits));  //计算总页面      
        $lists = $group->getAll($map, $Nowpage, $limits);  
       
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign('val', $key);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }
    
    /**
     * [group 会员组自定义价格]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function groupprice(){

        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['think_fl.mnamebie|think_member_group.group_name'] = ['like',"%" . $key . "%"];          
        }      
        $group = new MemberGroupPriceModel(); 
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');
        $count = $group->getAllCount($map);         //获取总条数
        $allpage = intval(ceil($count / $limits));  //计算总页面      
        $lists = $group->getAll($map, $Nowpage, $limits);  
       	$this->assign('count', $count);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign('val', $key);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch('groupprice');
    }

    /**
     * [add_groupprice 添加会员组价格]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function add_groupprice()
    {
        
        if(request()->isAjax()){
            $param = input('post.');
            $dataFL=Db::name('fl')->where(['id'=>$param['goodid']])->find();
            if($dataFL==false){
            	return json(['code' => -1, 'data' => '', 'msg' => '商品ID出错']);
            }
           
            if($dataFL['mprice']<$param['price']*100){
            	return json(['code' => -1, 'data' => '', 'msg' => '不能高于商品默认价格']);
            }
            $param['price']=$param['price']*100;
            $group = new MemberGroupPriceModel();
            $flag = $group->insertGrouppirce($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $group = new MemberGroupModel();
        $category=new CateGoryModel();
        $id = input('param.id');
        $this->assign('group',$group->getGroup());
        $this->assign('category',$category->getOneFl($id));
        $this->assign('id',$id);
        return $this->fetch();
    } 
    
    /**
     * [add_group 添加会员组]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function add_group()
    {
        if(request()->isAjax()){
            $param = input('post.');
            $group = new MemberGroupModel();
            $flag = $group->insertGroup($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        return $this->fetch();
    }


    /**
     * [edit_group 编辑会员组]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function edit_group()
    {
        $group = new MemberGroupModel();
        if(request()->isPost()){           
            $param = input('post.');
            $flag = $group->editGroup($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $this->assign('group',$group->getOne($id));
        return $this->fetch();
    }
    
    /**
     * [edit_groupprice 编辑会员组价格]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
     
    public function edit_groupprice()
    {
        if (request()->isPost()){
            $param = input('post.');    
            $auth_rule = Db::name('member_group_price');
            
            foreach ($param['price'] as $id => $sort){
				$result=$auth_rule->where(array('id' => $id ))->find();				 
				if($result['price']<>$sort*100){				 	
				 	$result=Db::execute("update think_child_fl set mprice=mprice+:tempmprice where goodid=:goodid and mprice<>-1 and memberid in(select id from think_member where group_id=:groupid)",['tempmprice'=>$sort*100-$result['price'],'goodid'=>$result['goodid'],'groupid'=>$result['membergroupid']]);
					
					$auth_rule->where(array('id' => $id ))->setField('price' , $sort*100);
				}
				 
            }
            return json(['code' => 1, 'msg' => '更新成功']);
        }
    }
    
    /**
     * [edit_money 变动金额]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function edit_money()
    {
       
        if(request()->isPost()){           
            $param = input('post.');
            $memberId = intval($param['id'] ?? 0);
            $changeMoney = floatval($param['newmoney'] ?? 0);
            if($memberId <= 0){
            	return json(['code' => '-1', 'data' => '', 'msg' => "会员ID无效，请刷新页面后重试"]);
            }
            if($changeMoney <= 0){
            	return json(['code' => '-1', 'data' => '', 'msg' => "变动金额必须大于0"]);
            }
            $superpwd=$param['superpwd'];
            $sql="select * from think_admin where id=:id";
            $hasM=Db::query($sql,['id'=>session('uid')]);
            if($hasM[0]['superpassword']!=md5(md5($superpwd) . config('auth_key'))){
            	return json(['code' => '-1', 'data' => '', 'msg' => "超级密码不正确"]);
            }
            // 绑定参数勿用 :money，避免与字段名冲突导致未真正更新
            if(isset($param['status']) && (string)$param['status'] === '1'){
            	$sql = "update think_member set money=money+:addmoney where id=:memberid";
            	$bind = ['addmoney'=>$changeMoney,'memberid'=>$memberId];
            }else{
            	$sql = "update think_member set money=money-:addmoney where id=:memberid and money>=:needmoney";
            	$bind = ['addmoney'=>$changeMoney,'needmoney'=>$changeMoney,'memberid'=>$memberId];
            }           
           	$updatemoney = Db::execute($sql,$bind);
           	if($updatemoney === false || $updatemoney < 1){
           		return json(['code' => '-1', 'data' => '', 'msg' => "金额变动失败（未更新到会员）"]);
           	}
           	if((string)$param['status'] === '1'){
           		writemoneylog($memberId,"管理员[admin]操作增加",0,$changeMoney);
           	}else{
           		writemoneylog($memberId,"管理员[admin]操作扣除",1,$changeMoney);
           	}
           	return json(['code' => '1', 'data' => '', 'msg' => "金额变动成功"]);
        }
        
    }
    
     /**
     * [edit_point 变动积分]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function edit_point()
    {
      
        if(request()->isPost()){           
            $param = input('post.');
            $memberId = intval($param['id'] ?? 0);
            $changePoint = floatval($param['newpoint'] ?? 0);
            if($memberId <= 0){
            	return json(['code' => '-1', 'data' => '', 'msg' => "会员ID无效，请刷新页面后重试"]);
            }
            if($changePoint <= 0){
            	return json(['code' => '-1', 'data' => '', 'msg' => "变动积分必须大于0"]);
            }
            
            if(isset($param['status']) && (string)$param['status'] === '1'){
            	$sql = "update think_member set integral=integral+:addpoint where id=:memberid";
            	$bind = ['addpoint'=>$changePoint,'memberid'=>$memberId];
            }else{
            	$sql = "update think_member set integral=integral-:addpoint where id=:memberid and integral>=:needpoint";
            	$bind = ['addpoint'=>$changePoint,'needpoint'=>$changePoint,'memberid'=>$memberId];
            }           
           	$updatemoney = Db::execute($sql,$bind);
           	if($updatemoney === false || $updatemoney < 1){
           		return json(['code' => '-1', 'data' => '', 'msg' => "积分变动失败（未更新到会员）"]);
           	}
           	if((string)$param['status'] === '1'){
           		writeintegrallog($memberId,"管理员[admin]操作增加",0,$changePoint);
           	}else{
           		writeintegrallog($memberId,"管理员[admin]操作扣除",1,$changePoint);
           	}
           	return json(['code' => '1', 'data' => '', 'msg' => "积分变动成功"]);
        }
        
    }


    /**
     * [del_group 删除会员组]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function del_group()
    {
        $id = input('param.id');
        $group = new MemberGroupModel();
        $flag = $group->delGroup($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
    /**
     * [del_groupprice 删除会员组价格]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function del_groupprice()
    {
        $id = input('param.id');
        $group = new MemberGroupPriceModel();
        $flag = $group->delGroup($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    /**
     * [group_status 会员组状态]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function group_status()
    {
        $id=input('param.id');
        $status = Db::name('member_group')->where(array('id'=>$id))->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('member_group')->where(array('id'=>$id))->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        }
        else
        {
            $flag = Db::name('member_group')->where(array('id'=>$id))->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }   
    } 


    //*********************************************会员列表*********************************************//
    /**
     * 会员列表
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function index(){

        $key = input('key');
        $group_id=input('group_id');
        if($group_id!='999' && $group_id!==null){
          $map['think_member.group_id']=$group_id;
        }
        $map['closed'] = 0;//0未删除，1已删除
   
        if($key&&$key!=="")
        {           
            $map['account|nickname|mobile'] = ['like',"%" . $key . "%"];          
        }
        $arr=Db::name("member_group")->column("id,group_name"); //获取分组列表
        $Zmoney=Db::name("member")->column("sum(money)"); //获取剩余总额
        $Ztgmoney=Db::name("member")->column("sum(tg_money)"); //获取总推广佣金          
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
        $this->assign('val', $key);
        if(input('get.page'))
        {
            return json($lists);
        }
        return $this->fetch();
    }
    
    
    /**
     * [member 会员私密价格]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function memberprice(){

        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['think_member.account|think_fl.mnamebie'] = ['like',"%" . $key . "%"];          
        }      
        $group = new MemberPriceModel(); 
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');
        $count = $group->getAllCount($map);         //获取总条数
        $allpage = intval(ceil($count / $limits));  //计算总页面      
        $lists = $group->getAll($map, $Nowpage, $limits);  
       
       	$this->assign('count', $count);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign('val', $key);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch('memberprice');
    }


    
     /**
     * [add_memberprice 添加会员私密价格]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function add_memberprice()
    {
        
        if(request()->isAjax()){
            $param = input('post.');
          	$member = new MemberModel();
          	$result=$member->getOneMemberByaccount($param['account']);
          	
          	if($result['id']<=0){
          		return json(['code' => '-1', 'data' => '', 'msg' => '会员不存在']);
          	}
          	
          	$dataFL=Db::name('fl')->where(['id'=>$param['goodid']])->find();
            if($dataFL==false){
            	return json(['code' => -1, 'data' => '', 'msg' => '商品ID出错']);
            }
            
            if($dataFL['mprice']<$param['price']*100){
            	return json(['code' => -1, 'data' => '', 'msg' => '不能高于商品默认价格']);
            }
            
          	$param['memberid']=$result['id'];
       
            $param['price']=$param['price']*100;
            $group = new MemberPriceModel();
            $flag = $group->insertGrouppirce($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $group = new MemberGroupModel();
        $category=new CateGoryModel();
        $id = input('param.id');
        $this->assign('group',$group->getGroup());
        $this->assign('category',$category->getOneFl($id));
        $this->assign('id',$id);
        return $this->fetch();
    } 
    
    /**
     * 添加会员
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function add_member()
    {
        if(request()->isAjax()){

            $param = input('post.');
            $param['password'] = md5(md5($param['password']) . config('auth_key'));
            $member = new MemberModel();
            $flag = $member->insertMember($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $group = new MemberGroupModel();
        $this->assign('group',$group->getGroup());
        return $this->fetch();
    }


    /**
     * 编辑会员
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function edit_member()
    {
        $member = new MemberModel();
        if(request()->isAjax()){
            $param = input('post.');
            // 余额/积分/佣金请走「变动金额」「变动积分」，此处不可直接改
            unset($param['money'], $param['tg_money'], $param['integral'], $param['token'], $param['fzhost'], $param['fzstatus']);
            if(empty($param['password'])){
                unset($param['password']);
            }else{
                $param['password'] = md5(md5($param['password']) . config('auth_key'));
            }
            $flag = $member->editMember($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $id = input('param.id');
        $group = new MemberGroupModel();
        $this->assign([
            'member' => $member->getOneMember($id),
            'group' => $group->getGroup()
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
        	$token=md5(md5($hasUser['account'] . $hasUser['password']).md5(date("Y-m-d")). config('auth_key'). config('token').$_SERVER['HTTP_HOST']);
        	session('useraccount', $hasUser);
			cookie('usertoken',$token);
        	return $this->redirect(url('@jingdian/user/uscenter'),302);
        }
        return $this->fetch();
    }
    
    
    /**
     * [edit_memberprice 编辑会员私密价格]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
     
    public function edit_memberprice()
    {
        if (request()->isPost()){
            $param = input('post.');    
            $auth_rule = Db::name('member_price');
            
            foreach ($param['price'] as $id => $sort){
				 $auth_rule->where(array('id' => $id ))->setField('price' , $sort*100);
            }
            return json(['code' => 1, 'msg' => '更新成功']);
        }
    }


    /**
     * 删除会员
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function del_member()
    {
        $id = input('param.id');
        $member = new MemberModel();
        $oneMeber=$member->getOneMember($id);
        if($oneMeber['money']>0 || $oneMeber['tg_money']>0){
        	return json(['code' => -1, 'data' => '', 'msg' => "该用户不能被删除。（有可用余额或者可用佣金）"]);
        }
        $map['pid1|pid2|pid3']=$id;
        $result=Db::name('member')->where($map)->count();
        if($result>0){
        	return json(['code' => -1, 'data' => '', 'msg' => "该用户不能被删除。（有下级用户）"]);
        }
        
        $flag = $member->delMember($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
    
    
    /**
     * [del_memberprice 删除会员私密价格]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function del_memberprice()
    {
        $id = input('param.id');
        $group = new MemberPriceModel();
        $flag = $group->delGroup($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }



    /**
     * 会员状态
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function member_status()
    {
        $id = input('param.id');
        $status = Db::name('member')->where('id',$id)->value('status');//判断当前状态情况
        if($status==1)
        {
            $flag = Db::name('member')->where('id',$id)->setField(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已禁止']);
        }
        else
        {
            $flag = Db::name('member')->where('id',$id)->setField(['status'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已开启']);
        }
    
    }
    
     /**
     * 分销商
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function member_distribut()
    {
        $id = input('param.id');
        $distribut = Db::name('member')->where('id',$id)->value('is_distribut');//判断当前状态情况
        if($distribut==1)
        {
            $map['pid1|pid2|pid3']=$id;
	        $result=Db::name('member')->where($map)->count();
	        if($result>0){
	        	return json(['code' => -1, 'data' => '', 'msg' => "不能取消该用户的分销商身份。（有下级用户）"]);
	        }
            $flag = Db::name('member')->where('id',$id)->setField(['is_distribut'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已取消分销商']);
        }
        else
        {
            $flag = Db::name('member')->where('id',$id)->setField(['is_distribut'=>1]);
            return json(['code' => 0, 'data' => $flag['data'], 'msg' => '已设置成分销商']);
        }
    
    }
    
    /**
     * 会员密码
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function edit_pass()
    {
        $id = input('param.id');
        $parama=mt_rand(100000,999999);
        $pass= md5(md5($parama) . config('auth_key'));
        $flag = Db::name('member')->where('id',$id)->setField(['password'=>$pass]);
        
        if($flag==false){
        	return json(['code' => -1, 'data' => '', 'msg' => '修改失败']); 
        }else{
        	return json(['code' => 1, 'data' => $parama, 'msg' => '密码重置成功请牢记']); 
        }
         
    }

}