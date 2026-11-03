<?php

namespace app\admin\controller;
use app\admin\model\KamiModel;
use app\admin\model\CateGoryGroupModel;
use com\StatsService;
use com\KamiRestockService;
use think\Db;

class Kami extends Base
{
    //*********************************************卡密*********************************************//

    /**
     * 商品卡密总览（虚拟商品）
     */
    public function goods()
    {
        $key = input('key');
        $map = ['type' => 0];
        if ($key !== null && $key !== '') {
            $map['mnamebie|mname'] = ['like', '%' . $key . '%'];
        }
        $Nowpage = input('get.page') ? input('get.page') : 1;
        $limits = config('list_rows');
        $count = Db::name('fl')->where($map)->count();
        $allpage = intval(ceil($count / $limits));
        $lists = Db::name('fl')->where($map)->order('id desc')->page($Nowpage, $limits)->select();
        foreach ($lists as $k => $v) {
            $lists[$k]['kamicount'] = Db::name('mail')->where(['mpid' => $v['id'], 'mis_use' => 0])->count();
            if (!empty($v['create_time']) && is_numeric($v['create_time'])) {
                $lists[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            } elseif (empty($v['create_time'])) {
                $lists[$k]['create_time'] = '';
            }
        }
        if (input('get.page')) {
            return json($lists);
        }
        $this->assign('Nowpage', $Nowpage);
        $this->assign('allpage', $allpage);
        $this->assign('count', $count);
        $this->assign('val', $key);
        return $this->fetch();
    }
   
    public function index()
    {
        $key = input('key');
        $goodsid = input('id');
        $addid = intval(input('addid', 0));
        $misuse = input('misuse', '99'); // 99全部 0未用 1已用
        $syddhao = trim((string)input('syddhao', ''));
        $starttime = trim((string)input('starttime', ''));
        $endtime = trim((string)input('endtime', ''));
        $map = [];
        if ($goodsid != 0 && !empty($goodsid)) {
            $map['think_mail.mpid'] = $goodsid;
        }
        if ($addid > 0) {
            $map['think_mail.addid'] = $addid;
        }
        if ($key && $key !== '') {
            $map['think_mail.musernm'] = ['like', '%' . $key . '%'];
        }
        if ($syddhao !== '') {
            $map['think_mail.syddhao'] = ['like', '%' . $syddhao . '%'];
        }
        if ($misuse !== '' && $misuse !== null && intval($misuse) !== 99) {
            $map['think_mail.mis_use'] = intval($misuse) === 1 ? 1 : 0;
        }
        $st = $starttime !== '' ? strtotime($starttime) : 0;
        $et = $endtime !== '' ? strtotime($endtime) : 0;
        if ($st > 0 && $et > 0) {
            if ($et < $st) {
                $tmp = $st;
                $st = $et;
                $et = $tmp;
            }
            // 结束日含当天
            if (strlen($endtime) <= 10) {
                $et = $et + 86400 - 1;
            }
            $map['think_mail.create_time'] = ['between', [$st, $et]];
        } elseif ($st > 0) {
            $map['think_mail.create_time'] = ['egt', $st];
        } elseif ($et > 0) {
            if (strlen($endtime) <= 10) {
                $et = $et + 86400 - 1;
            }
            $map['think_mail.create_time'] = ['elt', $et];
        }
        $limitlast = config('list_rows');
        $KamiM = new KamiModel();
        $count = $KamiM->getAllCount($map);
        $allpage = intval(ceil($count / $limitlast));
        $Nowpage = input('get.page') ? input('get.page') : 1;
        $result = $KamiM->getmailBywhere($map, $Nowpage, $limitlast);
        $goodsdetail = Db::name('fl')->where('id', $goodsid)->find();
        if (input('get.page')) {
            $rows = [];
            $rawQudao = [];
            foreach ($result as $row) {
                $item = is_object($row) ? $row->toArray() : $row;
                $ct = isset($item['create_time']) ? $item['create_time'] : 0;
                if (is_numeric($ct) && $ct > 0) {
                    $item['create_time'] = date('Y-m-d H:i:s', $ct);
                }
                $rawQudao[] = isset($item['addqudao']) ? $item['addqudao'] : '';
                $rows[] = $item;
            }
            $qudaoLabels = KamiModel::mapAddqudaoLabels($rawQudao);
            foreach ($rows as &$item) {
                $qd = isset($item['addqudao']) ? (string)$item['addqudao'] : '';
                $item['addqudao_label'] = isset($qudaoLabels[$qd]) ? $qudaoLabels[$qd] : KamiModel::formatAddqudao($qd);
            }
            unset($item);
            return $rows;
        }
        $this->assign('goodsname', $goodsdetail ? $goodsdetail['mnamebie'] : '');
        $this->assign('Nowpage', $Nowpage);
        $this->assign('allpage', $allpage);
        $this->assign('val', $key);
        $this->assign('count', $count);
        $this->assign('goodsid', $goodsid);
        $this->assign('addid', $addid);
        $this->assign('misuse', $misuse === '' || $misuse === null ? '99' : (string)$misuse);
        $this->assign('syddhao', $syddhao);
        $this->assign('starttime', $starttime);
        $this->assign('endtime', $endtime);
        $this->assign('back_goods_url', url('Kami/goods'));
        $this->assign('addlog_url', url('Kami/addlog', $goodsid ? ['goodid' => $goodsid] : []));

        return $this->fetch();
    }

    /**
     * 入库批次日志（全站或按商品 goodid）
     */
    public function addlog()
    {
        $goodid = input('goodid');
        $key = input('key');
        $addqudao = input('addqudao');
        $userip = input('userip');

        if (!input('get.page')) {
            $goodsname = '';
            if ($goodid !== null && $goodid !== '' && intval($goodid) > 0) {
                $g = Db::name('fl')->where('id', intval($goodid))->find();
                $goodsname = $g ? $g['mnamebie'] : '';
            }
            $this->assign('Nowpage', 1);
            $this->assign('allpage', 1);
            $this->assign('count', 0);
            $this->assign('goodid', ($goodid !== null && $goodid !== '' && intval($goodid) > 0) ? intval($goodid) : '');
            $this->assign('goodsname', $goodsname);
            $this->assign('val', $key);
            $this->assign('addqudao', $addqudao);
            $this->assign('userip', $userip);
            $this->assign('back_goods_url', url('Kami/goods'));
            return $this->fetch();
        }

        $map = [];
        if ($goodid !== null && $goodid !== '' && intval($goodid) > 0) {
            $map['a.goodid'] = intval($goodid);
        }
        if ($key && $key !== '') {
            $map['a.addbiaoshi'] = ['like', '%' . $key . '%'];
        }
        if ($addqudao && $addqudao !== '') {
            $q = trim($addqudao);
            // 支持按管理员用户名筛，也兼容直接输 UID / 渠道串
            $adminIds = Db::name('admin')->where('username', 'like', '%' . $q . '%')->column('id');
            if ($adminIds) {
                $idStrs = array_map('strval', $adminIds);
                $map['a.addqudao'] = ['in', array_values(array_unique(array_merge($idStrs, [$q])))];
            } else {
                $map['a.addqudao'] = ['like', '%' . $q . '%'];
            }
        }
        if ($userip && $userip !== '') {
            $map['a.userip'] = trim($userip);
        }

        $KamiM = new KamiModel();
        $Nowpage = max(1, intval(input('get.page')));
        $limits = config('list_rows') ?: 15;
        $count = $KamiM->getAddmailLogCount($map);
        $allpage = max(1, intval(ceil($count / $limits)));
        $lists = $KamiM->getAddmailLogByWhere($map, $Nowpage, $limits);

        $rawQudao = [];
        foreach ($lists as $row) {
            $rawQudao[] = isset($row['addqudao']) ? $row['addqudao'] : '';
        }
        $qudaoLabels = KamiModel::mapAddqudaoLabels($rawQudao);

        $rows = [];
        foreach ($lists as $row) {
            $ct = $row['create_time'];
            $qd = isset($row['addqudao']) ? (string)$row['addqudao'] : '';
            $rows[] = [
                'id' => $row['id'],
                'goodid' => $row['goodid'],
                'mname' => isset($row['mname']) ? $row['mname'] : '',
                'addbiaoshi' => $row['addbiaoshi'],
                'addnum' => $row['addnum'],
                'successnum' => $row['successnum'],
                'failnum' => $row['failnum'],
                'existnum' => $row['existnum'],
                'addqudao' => $qd,
                'addqudao_label' => isset($qudaoLabels[$qd]) ? $qudaoLabels[$qd] : KamiModel::formatAddqudao($qd),
                'userip' => $row['userip'],
                'create_time_fmt' => is_numeric($ct) && $ct > 0 ? date('Y-m-d H:i:s', $ct) : $ct,
            ];
        }

        return json([
            'list' => $rows,
            'count' => $count,
            'allpage' => $allpage,
        ]);
    }

    /**
     * 按批次删除未使用卡密（不删已售）
     */
    public function del_by_addid()
    {
        $addid = intval(input('param.addid', 0));
        if ($addid < 1) {
            return json(['code' => -1, 'msg' => '批次ID无效']);
        }
        $KamiM = new KamiModel();
        $flag = $KamiM->delUnusedByAddid($addid);
        return json($flag);
    }
  
      /**
     * 删除卡密
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function del_kami()
    {
        $id = input('param.id');
        $KamiM = new KamiModel();
        $flag = $KamiM->delKami($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
     /**
     * [add_group 添加卡密]
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function add_kami()
    {
        $goodid = input('param.id');
		$shopdetail=Db::name('fl')->where('id',$goodid)->find();
        $this->assign('uid', session('uid'));
        $this->assign('shopdetail', $shopdetail);
        $this->assign('back_goods_url', url('Kami/goods'));
        $this->assign('back_stock_url', url('Kami/index', ['id' => $goodid]));
        return $this->fetch();
    }

    /**
     * 后台补货入库（原 api/Apimail/addadminkami，改为会话鉴权；与 madmin 共用 KamiRestockService）
     */
    public function save_kami()
    {
        if (!request()->isPost()) {
            return json(['code' => -1, 'msg' => '非法请求']);
        }
        if (!ensure_kami_restock_service()) {
            return json(['code' => -1, 'msg' => '补货服务未加载，请检查 extend/com/KamiRestockService.php 是否存在']);
        }
        $param = inputself();
        $goodid = isset($param['mpid']) ? $param['mpid'] : 0;
        $kami = isset($param['values']) ? $param['values'] : '';
        $flag = KamiRestockService::restock($goodid, $kami, session('uid'), getIP());
        if (!isset($flag['url'])) {
            $flag['url'] = url('Kami/index', ['id' => $goodid]);
        }
        // PC 原接口成功时 code 恒为 1（含「补货不成功」文案）；保持兼容
        if (isset($flag['addid']) && intval($flag['addid']) > 0 && intval($flag['code']) === -1
            && isset($flag['success']) && intval($flag['success']) === 0) {
            $flag['code'] = 1;
        }
        return json($flag);
    }
	 /**
     * 删除所有卡密
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function del_all()
    {
		$param=inputself();
    	$mpid=$param['mpid'];
		$result=Db::name('mail')->where(['mpid'=>$mpid])->delete();
		if($result===false){
            return json(['code' => -1, 'count' => '', 'msg' => '删除失败']);
        }else{
        	return json(['code' => 1, 'count' => $result, 'msg' => '删除成功'.$result.'条数据']);
        }
    }
	 /**
     * 删除所有卡密
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function del_guoqi()
    {
    
		$param=inputself();
    	$mpid=$param['mpid'];
		$result=Db::name('mail')->where(['mis_use'=>1])->delete();
		if($result===false){
            return json(['code' => -1, 'count' => '', 'msg' => '清理失败']);
        }else{
        	return json(['code' => 1, 'count' => $result, 'msg' => '删除成功'.$result.'条数据']);
        }	
    }
     /**
     * 导出卡密
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function export_kami()
    {
			$param=inputself();
 			$data=[];
	 		$param['create_time']=time();
	 		$param['userip']=getIP();
	 		$param['mflid']=isset($param['mflid'])?$param['mflid']:'';
 			$param['buynum']=isset($param['buynum'])?$param['buynum']:'';
 			$superpwd=isset($param['superpwd'])?$param['superpwd']:'';
 			if($superpwd===''){
 				return $this->error('请输入超级密码');
 			}
 			$hasAdmin=Db::name('admin')->where('id', session('uid'))->find();
 			if(empty($hasAdmin) || empty($hasAdmin['superpassword']) || $hasAdmin['superpassword']!=md5(md5($superpwd) . config('auth_key'))){
 				return $this->error('超级密码不正确');
 			}
 			if($param['mflid']=='' || $param['buynum']==''){
 				return $this->error('商品或导出数量不能为空');
 			}
 			$param['buynum']=intval($param['buynum']);
 			if($param['buynum']<1 || $param['buynum']>5000){
 				return $this->error('导出数量须在 1～5000 之间');
 			}
 			$dataFl=Db::name('fl')->where('id',$param['mflid'])->find();
 			if(!$dataFl){
 					$msg='商品异常';
    				$code=-1;
    				$returnData=[		
					'code'=>$code,
					'msg'=>$msg,
					'data'=>$data		
		 			];
		 			return json($returnData);
 			}
 			$decrypt=$dataFl['decrypt'];
 			
 			$code=1;
        	$msg="获取成功";
        	
 			//开启事务
    		Db::startTrans();
    		try
    		{
    			//添加订单信息		
    			$orderid=createOrder();
    			$sql="insert ignore into think_info set mcard=:mcard,morder=:morder,mstatus=1,create_time=:create_time,update_time=:update_time,userip=:userip,mflid=:mflid,buynum=:buynum,maddtype=88";
           		$bool = Db::execute($sql,[
 								'mcard'=>$orderid.'_'.$param['buynum'],
 								'morder'=>$orderid.'_'.$param['buynum'],
 								'create_time'=>$param['create_time'],
 								'update_time'=>0,
 								'userip'=>$param['userip'],
 								'mflid'=>$param['mflid'],
 								'buynum'=>$param['buynum']				
 								]);
           
    			if($bool==false)
    			{
    				// 回滚事务
    				Db::rollback();
    				$msg='添加订单状态失败';
    				$code=-1;
    				$returnData=[		
					'code'=>$code,
					'msg'=>$msg,
					'data'=>$data		
		 			];
		 			return json($returnData);
		    				     
    			}
				$mailsql="SELECT * from think_mail where mpid=:mpid and mis_use=0 ORDER BY id asc LIMIT :beishu for update";
				$maildata =Db::query($mailsql,['mpid'=>$param['mflid'],
										'beishu'=>$param['buynum']
									 ]);
    			if($maildata==false)
    			{
    				// 回滚事务
                    Db::rollback();
                    $msg='提取卡密出错001';
                    $code=-1;
                    $returnData=[		
					'code'=>$code,
					'msg'=>$msg,
					'data'=>$data		
		 			];
		 			return json($returnData);
                    
    			}
    			$file="";
                $html="";
                if($decrypt==0){
                	foreach ($maildata as $v) 
	                {
	                	$mails[] = $v['musernm'];
	                    $ids[] = $v['id'];
	                    $file .= $v['musernm'];
	                    $file .= "\r";
	                    $html .= $v['musernm'];
	                    $html .= '<br />';
	                    $data[]=$v['musernm'];
	                }
                }elseif($decrypt==1){
                	foreach ($maildata as $v) 
	                {
	                	$mails[] =passport_decrypt($v['mpasswd'],DECRYPT_KEY);
	                    $ids[] = $v['id'];
	                    $file .= passport_decrypt($v['mpasswd'],DECRYPT_KEY);
	                    $file .= "\r";
	                    $html .= passport_decrypt($v['mpasswd'],DECRYPT_KEY);
	                    $html .= '<br/>';
	                    $data[]=passport_decrypt($v['mpasswd'],DECRYPT_KEY);
	                }
                }
                
                
                
               
    			$arr_string = join(',', $ids);
                $sql = "update think_mail set mis_use=1,update_time=:update_time,syddhao=:orderid where id in(".$arr_string.")";
         
                $updatemail = Db::execute($sql,['update_time'=>time(),'orderid'=>$orderid]);
    			if($updatemail==false)
    			{
    				// 回滚事务
                    Db::rollback();
                    
                    $msg='更新卡密状态出错001';
                    $code=-1;
                    $returnData=[		
					'code'=>$code,
					'msg'=>$msg,
					'data'=>$data		
		 			];
		 			return json($returnData);
		                  

    			}
    			
    			
    			
    			
    		}catch(\Exception $e){
					// 回滚事务
                    Db::rollback();
                    $msg=$e->getMessage();
                    $code=-1;
                    $returnData=[		
					'code'=>$code,
					'msg'=>$msg,
					'data'=>$data		
		 			];
		 			return json($returnData);
                    
    		}
    		Db::commit();
    		$returnData=[		
			'code'=>$code,
			'msg'=>$msg,
			'data'=>$data		
 			];
 			
            $sor = fopen('upload/'.$orderid.'_'.$param['buynum'].'.txt',"w");
            $fwbool=fwrite($sor,$file);
            
            fclose($sor);
            $this->redirect(url('kami/Downloadurl',['orderid'=>$orderid,'buynum'=>$param['buynum']]));   
			//$this->picDownload($orderid.'_'.$param['buynum'].'.txt');
    }

    /**
     * 导出记录查询（maddtype=88）
     * 首屏不查库；Ajax 才分页。禁止对 mail 表逐条 count（大库会 502）。
     */
    public function export_log()
    {
        $key = input('key');
        $mflid = input('mflid');
        $userip = input('userip');

        // 首屏只出壳，避免与 Ajaxpage 重复打库
        if (!input('get.page')) {
            $this->assign('Nowpage', 1);
            $this->assign('allpage', 1);
            $this->assign('val', $key);
            $this->assign('userip', $userip);
            $this->assign('mflid', $mflid);
            $this->assign('count', 0);
            return $this->fetch();
        }

        $map = [];
        if ($key && $key !== '') {
            $map['i.mcard|i.morder'] = ['like', '%' . $key . '%'];
        }
        if ($mflid !== null && $mflid !== '' && intval($mflid) > 0) {
            $map['i.mflid'] = intval($mflid);
        }
        if ($userip && $userip !== '') {
            $map['i.userip'] = trim($userip);
        }
        // 无任何条件时默认只看近 30 天，防止全表扫爆
        if (empty($map)) {
            $map['i.create_time'] = ['egt', time() - 86400 * 30];
        }

        $OrderM = new \app\admin\model\OrderModel();
        $Nowpage = max(1, intval(input('get.page')));
        $limits = 15;
        $count = $OrderM->getExportCount($map);
        $allpage = max(1, intval(ceil($count / $limits)));
        $lists = $OrderM->getExportOrderByWhere($map, $Nowpage, $limits);

        $rows = [];
        foreach ($lists as $row) {
            $ct = $row['create_time'];
            $rows[] = [
                'id' => $row['id'],
                'mcard' => $row['mcard'],
                'mflid' => $row['mflid'],
                'buynum' => $row['buynum'],
                'mail_count' => $row['buynum'], // 用申请数量，不再扫 mail 表
                'userip' => $row['userip'],
                'mnamebie' => isset($row['mnamebie']) ? $row['mnamebie'] : '',
                'mname' => isset($row['mname']) ? $row['mname'] : '',
                'create_time_fmt' => is_numeric($ct) ? date('Y-m-d H:i:s', $ct) : $ct,
            ];
        }

        return json([
            'list' => $rows,
            'count' => $count,
            'allpage' => $allpage,
        ]);
    }
	

	//兼容部分浏览器下载时二次请求导出方法
	function Downloadurl(){
		$param=inputself();
	    $this->picDownload($param['orderid'].'_'.$param['buynum'].'.txt');
	}	
	
	//批量下载图片
 function picDownload($orderid){	 
            $filename = "./upload/" . $orderid . ".zip";
			if(!file_exists($uf)){		
            // 生成文件
            $zip = new \ZipArchive ();
            // 使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释
            if ($zip->open ($filename ,\ZipArchive::OVERWRITE) !== true) {
                //OVERWRITE 参数会覆写压缩包的文件 文件必须已经存在
                if($zip->open ($filename ,\ZipArchive::CREATE) !== true){
                    // 文件不存在则生成一个新的文件 用CREATE打开文件会追加内容至zip
                    exit ( '无法打开文件，或者文件创建失败' );
                }
            }
			
					//iconv('utf-8','gb2312',$v),因为我的$v中含有中文，file_exists不识别中文，需要转码
                $urlfile=$_SERVER['DOCUMENT_ROOT']."/upload/".$orderid;			
                if(file_exists($urlfile)){
					//get_basename($v)，原来的basename()不识别中文，新建函数获取文件名
					//iconv('utf-8','gb2312',get_basename($v))还是中文问题，没有中文的话basename($v)即可
                    $zip->addFile($urlfile, iconv('utf-8','gb2312',$orderid));
                } else {
						//                    die('图片地址不对哦');
                    echo 'error';
				//删除在服务器上创建的$filename压缩文件
                    @unlink($filename);
                    exit;
                }
            
        // 关闭
        $zip->close ();
		}
        //下面是输出下载;
		
        header ( "Cache-Control: max-age=0" );
        header ( "Content-Description: File Transfer" );
        header ( 'Content-disposition: attachment; filename=' . basename ( $filename ) ); // 文件名
        header ( "Content-Type: application/zip" ); // zip格式的
        header ( "Content-Transfer-Encoding: binary" ); // 告诉浏览器，这是二进制文件
        header ( 'Content-Length: ' . filesize ( $filename ) ); // 告诉浏览器，文件大小
        @readfile ( $filename );//输出文件;
        @unlink($filename);
            exit;
      }

    /**
     * 卡密消耗统计页（原 api KeySalesstatistics）
     */
    public function key_stats()
    {
        $type = intval(input('type', 0));
        $starttime = input('starttime', date('Y-m-d', time() - 86400 * 30));
        $endtime = input('endtime', date('Y-m-d'));
        $this->assign('types', StatsService::$keyTypes);
        $this->assign('type', $type);
        $this->assign('starttime', $starttime);
        $this->assign('endtime', $endtime);
        $this->assign('back_goods_url', url('Kami/goods'));
        return $this->fetch();
    }

    /**
     * 卡密消耗统计数据 Ajax
     */
    public function key_stats_data()
    {
        $type = input('type', 0);
        $starttime = input('starttime', '');
        $endtime = input('endtime', '');
        $page = input('page', 1);
        $limit = input('limit', config('list_rows') ?: 20);
        $res = StatsService::keySales($type, $starttime, $endtime, $page, $limit);
        return json($res);
    }

}