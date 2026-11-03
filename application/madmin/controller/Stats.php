<?php
namespace app\madmin\controller;

use com\StatsService;

/**
 * 移动端只读统计（复用 StatsService）
 */
class Stats extends Base
{
    public function sales()
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

    public function sales_data()
    {
        $type = input('type', 0);
        $starttime = input('starttime', '');
        $endtime = input('endtime', '');
        $page = input('page', 1);
        $limit = input('limit', config('list_rows') ?: 20);
        return json(StatsService::sales($type, $starttime, $endtime, $page, $limit));
    }

    public function keys()
    {
        $type = intval(input('type', 0));
        $starttime = input('starttime', date('Y-m-d', time() - 86400 * 30));
        $endtime = input('endtime', date('Y-m-d'));
        $this->assign('types', StatsService::$keyTypes);
        $this->assign('type', $type);
        $this->assign('starttime', $starttime);
        $this->assign('endtime', $endtime);
        return $this->fetch();
    }

    public function keys_data()
    {
        $type = input('type', 0);
        $starttime = input('starttime', '');
        $endtime = input('endtime', '');
        $page = input('page', 1);
        $limit = input('limit', config('list_rows') ?: 20);
        return json(StatsService::keySales($type, $starttime, $endtime, $page, $limit));
    }
}
