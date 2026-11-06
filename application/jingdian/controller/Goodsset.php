<?php
namespace app\jingdian\controller;
use app\jingdian\model\GoodsSetModel;
use app\admin\model\CateGoryGroupModel;
use app\admin\model\CateGoryModel;
use app\jingdian\model\CommonModel;
use app\jingdian\model\IndexModel;
use think\Db;

class Goodsset extends Base
{
	
	/*
    *商品列表Do
    */
    public function usgoodslist(){
    	if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	$this->error('未知错误',url('user/uscenter'));
        }
    	if(session('useraccount.id')){  
    		$group = new CateGoryGroupModel(); 
    		$key = input('key');
    		$count=0;
	    	$Nowpage=1;
	        $allpage=0;
	        $type=input('get.type') ? input('get.type'):1;
	        $Nowpage = input('get.page') ? input('get.page'):1;
		    $limits = input('get.limit') ? input('get.limit'):config('list_rows');// 获取总条数
	    	$mlm=input('get.mlm') ? input('get.mlm'):999;
	    	$map = [];
        if($mlm!='999' && $type!=3){
                $map['b.mlm']=$mlm;
         }elseif($mlm!='999' && $type==3){
                $map['think_fl.mlm']=$mlm;
         }
	    	if($type==2){
	    		$map['think_child_fl.status']=0;
	    	}
	    	
	        if($key&&$key!=="")
	        {
	            $map['think_child_fl.mname|b.mname'] = ['like',"%" . $key . "%"];          
	        }
	        
	    	 //初始化商品类
        	$GoodsSetM=new GoodsSetModel();
        	 //获取商品列表
        	$lists=$GoodsSetM->getGoodsByWhere($map, $Nowpage, $limits,session('useraccount.id'),$type);
        	    		    	
	    	$count=$GoodsSetM->getAllCount($map,session('useraccount.id'),$type);    	    	
	    	
	    	//出售中的商品
	    	$csingcount=Db::name('child_fl')->where(['memberid'=>session('useraccount.id')])->count();
	    	//仓库中的商品
	    	$ckingcount=Db::name('child_fl')->where(['memberid'=>session('useraccount.id')])->where('status=0')->count();
	    	//未添加的的商品
	    	$noaddingcount=Db::query("SELECT count(id) as c from think_fl where status=1 and id not in(SELECT goodid from think_child_fl where memberid=:memberid)",['memberid'=>session('useraccount.id')]);
	    	$this->assign('csingcount', $csingcount);
	    	$this->assign('ckingcount', $ckingcount);
	    	$this->assign('noaddingcount', $noaddingcount[0]['c']);
	    	$this->assign('count', $count); 
        	$this->assign('Nowpage', $Nowpage); //当前页
        	$this->assign('allpage', $allpage); //总页数 
        	$this->assign('val', $key);
        	$this->assign('type', $type);
        	$this->assign('group', $group->getGroup());
        	$this->assign("mlm",$mlm);
	    	
	    	if(input('get.page'))
	        {
	            return json($lists);
	        }	    		
	    	return $this->fetch('/goodsset/index'); 
    	}
    	$this->assign('former_url', '');
        return $this->fetch('/user/index'); 
               
    }
	/*
	 * 一键添加主站商品
	 */
	public function yjAddGoods(){
		$param=inputself();
    	if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	$this->error('未知错误',url('user/uscenter'));
        }
    	if(!session('useraccount.id')){
    		$this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}
    	$shopxq = Db::name('fl')->where('status', 1)->select();
    	$rows = [];
    	foreach ($shopxq as $v) {
    		$rows[] = $this->buildChildFlRow($v, $param);
    	}
    	$result = $this->insertChildFlRows($rows);
    	if($result===false){
    		return json(['code'=>-1,'msg'=>'添加失败','sucessCount'=>0]);
    	}else{
    		return json(['code'=>1,'msg'=>'添加成功','successCount'=>$result]);
    	}
    }
	
	/*
	 * 批量设置利润
	 */
	public function yjUpdateProfit(){
		$param=inputself();
    	if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	$this->error('未知错误',url('user/uscenter'));
        }
    	if(!session('useraccount.id')){
    		$this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}
    	//获取商品
		$shopxq=Db::name('child_fl')->where('memberid',session('useraccount.id'))->select();
		//创建模型对象
		$model = new GoodsSetModel;
    	$data='';
    	foreach ($shopxq as $v) {
            $goodinfo=new CateGoryModel();
			$onegoods=$goodinfo->getOneMember($v['goodid']);//分站价格同步  
			if(session('child_useraccount.auth')['goodsprice']!=0){
				$sort=$onegoods['sort'];
    			if($param['moneytype']==1){
					$mprice=ceil($onegoods['mprice']*(abs($param['money'])+100)/100);
					$marketprice=ceil($onegoods['marketprice']*(abs($param['money'])+100)/100);
					$mprice_bz=round($onegoods['mprice_bz']*(1+abs($param['money'])/100), 2);
					
				}
				if($param['moneytype']==0){
					$mprice=$onegoods['mprice']+abs($param['money'])*100;
					$marketprice=$onegoods['marketprice']+abs($param['money'])*100;
					$mprice_bz=$onegoods['mprice_bz']+abs($param['money']);
				}
    		}
			//创建自定义数据
			$data[] = ['id' => $v['id'],'mprice'=>$mprice,'marketprice'=>$marketprice,'mprice_bz'=>$mprice_bz,'sort'=>$sort];
            //$result=Db::name("child_fl")->where("memberid",session('useraccount.id'))->where("id",$v['id'])->update(array("mprice"=>$mprice,"marketprice"=>$marketprice,"mprice_bz"=>$mprice_bz,"sort"=>$sort));
    	}
		
			//批量更新,自动识别更新和写入
			$result = $model -> saveAll($data,true);
    	if($result===false){
    		return json(['code'=>-1,'msg'=>'修改失败','sucessCount'=>0]);
    	}else{
    		return json(['code'=>1,'msg'=>'修改成功','successCount'=>$result]);
    	}
    }
    
	
	/*
	 * 批量设置勾选利润
	 */
	public function yjUpdateProfitChecked(){
		$param=inputself();
    	if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	$this->error('未知错误',url('user/uscenter'));
        }
    	if(!session('useraccount.id')){
    		$this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}
    	if($param['profitsub_id']==''){
    		return json(['code'=>-1,'msg'=>'没有选中商品','sucessCount'=>0]);
    	}
    	$ids = $this->parseIdList($param['profitsub_id']);
    	if (!$ids) {
    		return json(['code'=>-1,'msg'=>'商品 ID 不合法','sucessCount'=>0]);
    	}
    	$shopxq = Db::name('fl')->where('status', 1)->where('id', 'in', $ids)->select();
    	$result = 0;
    	foreach ($shopxq as $v) {
    		$sort=$v['sort'];
			$mprice = -1;
			$marketprice = -1;
			$mprice_bz = -1;
			if(session('child_useraccount.auth')['goodsprice']!=0){
    			if($param['moneytype']==1){
					$mprice=ceil($v['mprice']*(abs($param['money'])+100)/100);
					$marketprice=ceil($v['marketprice']*(abs($param['money'])+100)/100);
					$mprice_bz=round($v['mprice_bz']*(1+abs($param['money'])/100), 2);
					
				}
				if($param['moneytype']==0){
					$mprice=$v['mprice']+abs($param['money'])*100;
					$marketprice=$v['marketprice']+abs($param['money'])*100;
					$mprice_bz=$v['mprice_bz']+abs($param['money']);
				}
    		}
    		
            $n=Db::name("child_fl")->where("memberid",session('useraccount.id'))->where("goodid",$v['id'])->update(array("mprice"=>$mprice,"marketprice"=>$marketprice,"mprice_bz"=>$mprice_bz,"sort"=>$sort));
			if ($n === false) {
				return json(['code'=>-1,'msg'=>'修改失败','sucessCount'=>0]);
			}
			$result += (int)$n;
    	}
		
    	return json(['code'=>1,'msg'=>'修改成功','successCount'=>$result]);
    }
    
	
    /*
	 * 一键添加主站选中商品
	 */
	public function yjAddGoodsChecked(){
		$param=inputself();
    	if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	$this->error('未知错误',url('user/uscenter'));
        }
    	if(!session('useraccount.id')){
    		$this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}
    	if($param['ids']==''){
    		return json(['code'=>-1,'msg'=>'没有选中商品','sucessCount'=>0]);
    	}
    	$ids = $this->parseIdList($param['ids']);
    	if (!$ids) {
    		return json(['code'=>-1,'msg'=>'商品 ID 不合法','sucessCount'=>0]);
    	}
    	$shopxq = Db::name('fl')->where('status', 1)->where('id', 'in', $ids)->select();
    	$rows = [];
    	foreach ($shopxq as $v) {
    		$rows[] = $this->buildChildFlRow($v, $param);
    	}
    	$result = $this->insertChildFlRows($rows);
    	if($result===false){
    		return json(['code'=>-1,'msg'=>'添加失败','sucessCount'=>0]);
    	}else{
    		return json(['code'=>1,'msg'=>'添加成功','successCount'=>$result]);
    	}
    }
    
    
    /*
	 * 添加主站一个商品
	 */
	public function yjAddGoodsOne(){
		$param=inputself();
    	if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	$this->error('未知错误',url('user/uscenter'));
        }
    	if(!session('useraccount.id')){
    		$this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}
    	//获取商品
    	$shopxq=Db::name('fl')->where('id',$param['goodid'])->where('status',1)->find();
		if($shopxq){
			$imgurl=-1;
    		$kamitou=-1;
    		$kamiwei=-1;
    		$xqnotice=-1;
			$mprice=-1;
			$mprice_bz=-1;
			$marketprice=-1;
    		$sort=$shopxq['sort'];
			if(session('child_useraccount.auth')['goodsprice']!=0){
    			if($param['moneytype']==1){
					$mprice=ceil($shopxq['mprice']*(abs($param['money'])+100)/100);
					$marketprice=ceil($shopxq['marketprice']*(abs($param['money'])+100)/100);
					$mprice_bz=round($shopxq['mprice_bz']*(1+abs($param['money'])/100), 2);
					
				}
				if($param['moneytype']==0){
					$mprice=$shopxq['mprice']+abs($param['money'])*100;
					$marketprice=$shopxq['marketprice']+abs($param['money'])*100;
					$mprice_bz=$shopxq['mprice_bz']+(abs($param['money']));
				}
    		}
  		
        if(session('child_useraccount.auth')['goodsimgurl']==0){
    			$data['imgurl']=-1;
    		}else{
    			if($param['imgurl']==0){
            $imgurl='';
          }
    		}
    		if($param['kamitou']==0){
    			$kamitou='';
    		}
    		if($param['kamiwei']==0){
    			$kamiwei='';
    		}
    		$result=Db::execute("INSERT ignore into think_child_fl (goodid,memberid,imgurl,kamitou,kamiwei,xqnotice,mprice,marketprice,mprice_bz,sort) values (:goodid,:memberid,:imgurl,:kamitou,:kamiwei,:xqnotice,:mprice,:marketprice,:mprice_bz,:sort)",
    					['goodid'=>$shopxq['id'],
    					'memberid'=>session('useraccount.id'),
    					'imgurl'=>$imgurl,
    					'kamitou'=>$kamitou,
    					'kamiwei'=>$kamiwei,
    					'xqnotice'=>$xqnotice,
    					'mprice'=>$mprice,
    					'marketprice'=>$marketprice,
    					'mprice_bz'=>$mprice_bz,
    					'sort'=>$sort
    					
    					]);
    		if($result===false){
	    		return json(['code'=>-1,'msg'=>'添加失败','sucessCount'=>0]);
	    	}else{
	    		return json(['code'=>1,'msg'=>'添加成功','successCount'=>$result]);
	    	}			
		}else{
			return json(['code'=>-1,'msg'=>'添加失败,商品不存在或者已下架','sucessCount'=>0]);
		}
    	
    	
    	
    }
    
    /*
     * 一键删除所有商品
     */
    public function yjDeleAllGoods(){
    	if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	$this->error('未知错误',url('user/uscenter'));
        }
    	if(!session('useraccount.id')){
    		$this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}
    	$result=Db::execute("delete from think_child_fl where memberid=:memberid",['memberid'=>session('child_useraccount.id')]);
    	if($result===false){
    		return json(['code'=>-1,'msg'=>'删除失败','sucessCount'=>0]);
    	}else{
    		return json(['code'=>1,'msg'=>'删除成功','successCount'=>$result]);
    	}
    }
    /*
     * 删除指定商品
     */
    public function DelGoodsByid(){
    	if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	$this->error('未知错误',url('user/uscenter'));
        }
    	if(!session('useraccount.id')){
    		$this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}
    	$goodid=input('param.goodid');
    	$result=Db::execute("delete from think_child_fl where memberid=:memberid and goodid=:goodid",['memberid'=>session('child_useraccount.id'),'goodid'=>$goodid]);
    	if($result===false){
    		return json(['code'=>-1,'msg'=>'删除失败','sucessCount'=>0]);
    	}else{
    		return json(['code'=>1,'msg'=>'删除成功','successCount'=>$result]);
    	}
    }
    
    /*
     * 编辑商品
     */
    public function editgood(){
    	if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	$this->error('未知错误',url('user/uscenter'));
        }
    	if(!session('useraccount.id')){
    		$this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}
    	$param=inputself();
    	if (request()->isPost()){
    		
    		$data=[];
    		
    		$data['mprice_bz']=isset($param['jc_mprice_bz'])?-1:$param['mprice_bz'];
    		$data['mnotice']=isset($param['jc_mnotice'])?-1:$param['mnotice'];
    		$data['msgboxtip']=isset($param['jc_msgboxtip'])?-1:$param['msgboxtip'];
    		$data['color']=isset($param['jc_color'])?-1:$param['color'];	
    		$data['marketprice']=isset($param['jc_marketprice'])?-1:$param['marketprice'];   		
    		$data['kamitou']=isset($param['jc_kamitou'])?-1:$param['kamitou'];
    		$data['kamiwei']=isset($param['jc_kamiwei'])?-1:$param['kamiwei'];
    		$data['status']=isset($param['jc_status'])?-1:$param['status'];
    		$data['tuijian']=isset($param['jc_tuijian'])?-1:$param['tuijian'];
    		$data['hot']=isset($param['jc_hot'])?-1:$param['hot'];
    		$data['zkongge']=isset($param['jc_zkongge'])?-1:$param['zkongge'];
    		$data['ykongge']=isset($param['jc_ykongge'])?-1:$param['ykongge'];
    		$data['sort']=isset($param['sort'])?$param['sort']:0;
    		if(session('child_useraccount.auth')['goodsname']==0){
    			$data['mname']=-1;
    		}else{
    			$data['mname']=isset($param['jc_mname'])?-1:$param['mname'];
    		}
    		if(session('child_useraccount.auth')['goodsimgurl']==0){
    			$data['imgurl']=-1;
    		}else{
    			if(!isset($param['jc_imgurl'])){
    				if(isset($param['yunimgurl'])){
    					$data['yunimgurl']=$param['yunimgurl'];
    					$data['imgurl']='';
    				}elseif(isset($param['imgurl'])){
    					$data['yunimgurl']='';
    					$data['imgurl']=$param['imgurl'];
    				}			
    			}else{
    				$data['imgurl']=-1;
    			}
    			
    		}
    		
    		if(session('child_useraccount.auth')['goodsxqnotice']==0){
    			$data['xqnotice']=-1;
    		}else{
    			$data['xqnotice']=isset($param['jc_xqnotice'])?-1:$param['xqnotice'];
    		}
    		if(session('child_useraccount.auth')['goodsprice']==0){
    			$data['mprice']=-1;
    		}else{
    			$data['mprice']=isset($param['jc_mprice'])?-1:$param['mprice'];
    		}
    		
    		
    		
    		
    		if($data['marketprice']!=-1){
    			$data['marketprice']=$data['marketprice']*100;
    		}
    		if($data['mprice']!=-1){
    			$CommonM=new CommonModel();
	 			$childminmoney=$CommonM->GetChildMinMoneyBygoodid(session('child_useraccount.id'),1,$param['goodid']);
	 			if($data['mprice']>=$childminmoney){
	    			$data['mprice']=$data['mprice']*100;
	    		}elseif($data['mprice']<$childminmoney){
	    			return $this->error('商品单价不能低于您的进货单价',url('editGood',['id'=>$param['goodid']]));
	    		}
    		}
    		
    		
    		$result=Db::name('child_fl')->where(['memberid'=>session('child_useraccount.id'),'goodid'=>$param['goodid']])->update($data);
    		if($result===false){
    			return $this->error('编辑失败');
    		}else{
    			return $this->success('编辑成功',url('usgoodslist'));
    		}
    		

    		
    	}
    	
    	$goodid=$param['id'];
    	$varchar='b.*,b.mname as z_mname,b.mprice_bz as z_mprice_bz,b.mprice as z_mprice,b.mnotice as z_mnotice
		,b.imgurl as z_imgurl,b.yunimgurl as z_yunimgurl,b.marketprice as z_marketprice,b.xqnotice as z_xqnotice,b.status as z_status
		,b.sort as z_sort,b.msgboxtip as z_msgboxtip,b.tuijian as z_tuijian,b.hot as z_hot,b.ykongge as z_ykongge
		,b.zkongge as z_zkongge,b.color as z_color,b.kamitou as z_kamitou,b.kamiwei as z_kamiwei';
		$result=Db::name('child_fl')->field($varchar.',think_child_fl.*')->join('think_fl b','b.id = think_child_fl.goodid','LEFT')
            	->where('b.status',1)->where(['think_child_fl.memberid'=>session('child_useraccount.id'),'think_child_fl.goodid'=>$goodid])->find();
    	//初始化商品类
        $GoodsSetM=new GoodsSetModel();
        $result=$GoodsSetM->replaceFzImgurl($result);
    	$this->assign('goods', $result);
    	return $this->fetch(''); 
    }
    
    
    /*
     * 用户订单
     */
    public function usOrder(){
    	if(session('useraccount.id')!=session('child_useraccount.id') || !session('useraccount.id')){
        	return $this->error('未知错误',url('user/uscenter'));
        }
        if(session('child_useraccount.auth')['ordersauth']==0){
        	return $this->error('无权访问',url('user/uscenter'));
        }
    	if(!session('useraccount.id')){
    		$this->assign('former_url', '');
        	return $this->fetch('/user/index'); 
    	}
    	if(session('useraccount.id')){
	        $param=inputself();
	        $key = trim(session('useraccount.id'));
	        $map = [];
	        
	        $count=0;
	        $Nowpage=1;
	        $allpage=0;
	        $NowIp=getIP();
	        $lists=[];
	        if($key&&$key!==""&&$key!=="undefined"&&$key!=="null"&&!empty($key))
	        {
				$map['childid'] = $key;        
		       	if(isset($param['mcard'])){
		       		if($param['mcard']!=''){
		       			$map['mcard|morder']=$param['mcard'];
		       		}
		       		
		       	}
		       	if(isset($param['mstatus'])){
		       		if($param['mstatus']==999){
		       			
		       		}elseif($param['mstatus']==888){
		       			$map['mstatus'] = ['not in','1,2,4,5'];
		       		}else{
		       			$map['mstatus'] = $param['mstatus']; 
		       		}
		       		
		       	}
		       
		       	$IndexM = new IndexModel();  
		        $Nowpage = input('get.page') ? input('get.page'):1;
		        $limits = config('list_rows');// 获取总条数
		        $count = $IndexM->getAllCount($map); 	
		        $allpage = intval(ceil($count / $limits));   //计算总页面    
		        $lists = $IndexM->getOrderByWhere($map, $Nowpage, $limits);
	       
	        }
	        
	        $mcard="";
	        $mstatus=999;
	       
	        if(isset($param['mcard'])){		       
		       		$mcard=$param['mcard'];		       		       	
		    }
		    if(isset($param['mstatus'])){
       			$mstatus=$param['mstatus'];
		    }	
		    $this->assign('mcard', $mcard); 
		    $this->assign('mstatus', $mstatus); 
	        $this->assign('count', $count); 
	        $this->assign('Nowpage', $Nowpage); //当前页
	        $this->assign('allpage', $allpage); //总页数 
	        $this->assign('val', $key);
	        
	       if(input('get.page'))
	        {
	            return json(array_values($lists));
	        }
	        return $this->fetch('/goodsset/usorder'); 

	    }
	    
        $this->assign('former_url', '');
        return $this->fetch('/user/index'); 
    	
    }

    /**
     * 将逗号分隔 ID 转为正整数数组（防 IN 注入）
     * @return int[]
     */
    protected function parseIdList($raw)
    {
        if (is_array($raw)) {
            $parts = $raw;
        } else {
            $parts = preg_split('/[,\s]+/', (string)$raw, -1, PREG_SPLIT_NO_EMPTY);
        }
        $ids = [];
        foreach ($parts as $p) {
            if (!ctype_digit((string)$p)) {
                continue;
            }
            $id = (int)$p;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * 按主站商品 + 请求参数构造 child_fl 行
     */
    protected function buildChildFlRow(array $v, array $param)
    {
        $imgurl = -1;
        $kamitou = -1;
        $kamiwei = -1;
        $xqnotice = -1;
        $mprice = -1;
        $mprice_bz = -1;
        $marketprice = -1;
        $sort = $v['sort'];
        if (session('child_useraccount.auth')['goodsprice'] != 0) {
            if ($param['moneytype'] == 1) {
                $mprice = ceil($v['mprice'] * (abs($param['money']) + 100) / 100);
                $marketprice = ceil($v['marketprice'] * (abs($param['money']) + 100) / 100);
                $mprice_bz = round($v['mprice_bz'] * (1 + abs($param['money']) / 100), 2);
            }
            if ($param['moneytype'] == 0) {
                $mprice = $v['mprice'] + abs($param['money']) * 100;
                $marketprice = $v['marketprice'] + abs($param['money']) * 100;
                $mprice_bz = $v['mprice_bz'] + abs($param['money']);
            }
        }
        if (session('child_useraccount.auth')['goodsimgurl'] != 0 && isset($param['imgurl']) && $param['imgurl'] == 0) {
            $imgurl = '';
        }
        if (isset($param['kamitou']) && $param['kamitou'] == 0) {
            $kamitou = '';
        }
        if (isset($param['kamiwei']) && $param['kamiwei'] == 0) {
            $kamiwei = '';
        }
        return [
            'goodid' => (int)$v['id'],
            'memberid' => (int)session('useraccount.id'),
            'imgurl' => $imgurl,
            'kamitou' => $kamitou,
            'kamiwei' => $kamiwei,
            'xqnotice' => $xqnotice,
            'mprice' => $mprice,
            'marketprice' => $marketprice,
            'mprice_bz' => $mprice_bz,
            'sort' => $sort,
        ];
    }

    /**
     * 绑定参数批量 INSERT IGNORE child_fl
     * @return int|false 成功条数或 false
     */
    protected function insertChildFlRows(array $rows)
    {
        if (!$rows) {
            return 0;
        }
        $sql = 'INSERT IGNORE INTO think_child_fl (goodid,memberid,imgurl,kamitou,kamiwei,xqnotice,mprice,marketprice,mprice_bz,sort) VALUES (:goodid,:memberid,:imgurl,:kamitou,:kamiwei,:xqnotice,:mprice,:marketprice,:mprice_bz,:sort)';
        $success = 0;
        foreach ($rows as $row) {
            $n = Db::execute($sql, [
                'goodid' => $row['goodid'],
                'memberid' => $row['memberid'],
                'imgurl' => $row['imgurl'],
                'kamitou' => $row['kamitou'],
                'kamiwei' => $row['kamiwei'],
                'xqnotice' => $row['xqnotice'],
                'mprice' => $row['mprice'],
                'marketprice' => $row['marketprice'],
                'mprice_bz' => $row['mprice_bz'],
                'sort' => $row['sort'],
            ]);
            if ($n === false) {
                return false;
            }
            $success += (int)$n;
        }
        return $success;
    }
}