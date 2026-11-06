<?php

namespace app\admin\controller;
use app\admin\model\OrderModel;
use com\StatsService;
use think\Db;
use think\Session;


class Order extends Base
{
    /**
     * 订单列表
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function index(){

        $key = input('key');
        $mstatus = input('mstatus');
		$type=input('get.type') ? input('get.type'):999;
        $map = [];
        if($key&&$key!=="")
        {
            $map['mcard|morder|lianxi|think_info.email'] = ['like',"%" . $key . "%"];          
        }
        if($mstatus&&$mstatus!=="")
        {
            $map['mstatus'] = $mstatus;          
        }
		if($type=="888"){
			$reltype="0";
		}
		if($type=="1"){			
			$reltype="1";
		}
        if($type!=="999"&&$type!==""){
          $map['think_fl.type']=$reltype;
        }
		
        $OrderM = new OrderModel();       
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = $OrderM->getAllCount($map);//计算总页面
        $allpage = intval(ceil($count / $limits));       
        $lists = $OrderM->getOrderByWhere($map, $Nowpage, $limits); 
        //$alllists = $OrderM->getallOrderByWhere($map);   
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign('val', $key);
        $this->assign('mstatus', $mstatus);
		//Session::set('alllists',$alllists);
		Session::set('count',$count);
        $this->assign('count', $count);
        $this->assign('type', $type);
        if(input('get.page'))
        {
            return json($lists);
        }
      //print_r($lists);
        return $this->fetch();
    }
  
  /**
     * 订单附加信息详情
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function getOrderAttach(){
      
 		$number=trim(input('param.number'));
 		$OrderM = new OrderModel();       
		$orderattach=$OrderM->getOrderAttach($number);
       
            return json($orderattach);
    }
  
   	/*
 	 * 修改订单信息
 	 */
 	public function UpdateOrder(){
 		$param=inputself();
 		$OrderM = new OrderModel();       
 		$result=$OrderM->editOrder($param);
 		return json($result);		
 	}
  
      /**
     * 删除卡密
     * @author [来利云商业源码&智能建站平台] [366802485@qq.com]
     */
    public function del_order()
    {
        $id = input('param.id');
 		$OrderM = new OrderModel();       
        $flag = $OrderM->del_order($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
	
	
	
	/**
 * csv 批量导出
 * @param $mpid
 */
public function export()
{		
		$head = ['订单号','商品名称', '状态', '类型', '电话', '邮箱', '附加信息', '添加时间'];
		$data = Session::get('alllists');
		$count = Session::get('count');
		$mark = $fileName = date('YmdHis');// 文件名称可根据自己情况设定
		set_time_limit(0);
        $sqlCount = $count;
      

        $sqlLimit = 100000;//每次只从数据库取100000条以防变量缓存太大
        // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        $limit = 100000;
        // buffer计数器
        $cnt = 0;
        $fileNameArr = array();
        // 逐行取出数据，不浪费内存
        for ($i = 0; $i < ceil($sqlCount / $sqlLimit); $i++) {
            $fp = fopen($mark . '_' . $i . '.csv', 'w'); //生成临时文件
      //     chmod('attack_ip_info_' . $i . '.csv',777);//修改可执行权限
            $fileNameArr[] = $mark . '_' .  $i . '.csv';
        // 将数据通过fputcsv写到文件句柄
            fputcsv($fp, $head);
            $dataArr = $data;
            foreach ($dataArr as $a) {
                $cnt++;
                if ($limit == $cnt) {
                    //刷新一下输出buffer，防止由于数据过多造成问题
                    ob_flush();
                    flush();
                    $cnt = 0;
                }
                fputcsv($fp, $a);
            }
            fclose($fp);  //每生成一个文件关闭
        }
        //进行多个文件压缩
        $zip = new \ZipArchive();
        $filename = $mark . ".zip";
        $zip->open($filename, \ZipArchive::CREATE);   //打开压缩包
        foreach ($fileNameArr as $file) {
            $zip->addFile($file, basename($file));   //向压缩包中添加文件
        }
        $zip->close();  //关闭压缩包
        foreach ($fileNameArr as $file) {
            unlink($file); //删除csv临时文件
        }
        //输出压缩文件提供下载
        header("Cache-Control: max-age=0");
        header("Content-Description: File Transfer");
        header('Content-disposition: attachment; filename=' . basename($filename)); // 文件名
        header("Content-Type: application/zip"); // zip格式的
        header("Content-Transfer-Encoding: binary"); //
        header('Content-Length: ' . filesize($filename)); //
        @readfile($filename);//输出文件;
        @unlink($filename); //删除压缩包临时文件
    }

    /**
     * 销售统计页（原 api Salesstatistics）
     */
    public function sales_stats()
    {
        $type = intval(input('type', 0));
        $starttime = input('starttime', date('Y-m-d', time() - 86400 * 30));
        $endtime = input('endtime', date('Y-m-d'));
        $this->assign('types', StatsService::$salesTypes);
        $this->assign('type', $type);
        $this->assign('starttime', $starttime);
        $this->assign('endtime', $endtime);
        return $this->fetch();
    }

    /**
     * 销售统计数据 Ajax
     */
    public function sales_stats_data()
    {
        $type = input('type', 0);
        $starttime = input('starttime', '');
        $endtime = input('endtime', '');
        $page = input('page', 1);
        $limit = input('limit', config('list_rows') ?: 20);
        $res = StatsService::sales($type, $starttime, $endtime, $page, $limit);
        return json($res);
    }

}