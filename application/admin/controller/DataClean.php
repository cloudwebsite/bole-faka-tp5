<?php
namespace app\admin\controller;

use com\DataCleanService;

/**
 * 数据清理（承接 Login 硬编码清理 + 收紧后的按项清理）
 */
class DataClean extends Base
{
    public function index()
    {
        $this->assign('default_days', 90);
        $this->assign('default_limit', 1000);
        $this->assign('item_labels', DataCleanService::$itemLabels);
        $this->assign('groups', DataCleanService::$groups);
        return $this->fetch();
    }

    /**
     * 预览影响条数
     */
    public function preview()
    {
        if (!request()->isPost() && !request()->isAjax()) {
            return json(['code' => -1, 'msg' => '非法请求']);
        }
        $days = DataCleanService::normalizeDays(input('days', 90));
        $keys = input('keys/a', []);
        $keys = DataCleanService::filterValidKeys($keys);
        if (empty($keys)) {
            return json(['code' => -1, 'msg' => '请至少勾选一项', 'data' => []]);
        }
        $counts = DataCleanService::preview($days, $keys);
        $total = 0;
        foreach ($counts as $c) {
            $total += intval($c);
        }
        $lines = [];
        foreach ($counts as $k => $c) {
            $label = isset(DataCleanService::$itemLabels[$k]) ? DataCleanService::$itemLabels[$k] : $k;
            $lines[] = ['key' => $k, 'label' => $label, 'count' => intval($c)];
        }
        return json([
            'code' => 1,
            'msg' => 'ok',
            'days' => $days,
            'cutoff' => date('Y-m-d H:i:s', DataCleanService::cutoff($days)),
            'total' => $total,
            'data' => $lines,
        ]);
    }

    /**
     * 执行清理
     */
    public function run()
    {
        if (!request()->isPost()) {
            return json(['code' => -1, 'msg' => '请使用 POST']);
        }
        $days = DataCleanService::normalizeDays(input('days', 90));
        $limit = DataCleanService::normalizeLimit(input('limit', 1000));
        $loop = intval(input('loop', 0)) === 1;
        $confirm_danger = intval(input('confirm_danger', 0)) === 1;
        $keys = DataCleanService::filterValidKeys(input('keys/a', []));
        if (empty($keys)) {
            return json(['code' => -1, 'msg' => '请至少勾选一项']);
        }
        if (in_array('info_all', $keys, true) && !$confirm_danger) {
            return json(['code' => -1, 'msg' => '勾选「订单-全部」需二次确认（confirm_danger=1）']);
        }

        $deleted = DataCleanService::run($days, $keys, $limit, $loop);
        $total = 0;
        $parts = [];
        foreach ($deleted as $k => $n) {
            $total += intval($n);
            $label = isset(DataCleanService::$itemLabels[$k]) ? DataCleanService::$itemLabels[$k] : $k;
            $parts[] = $label . ':' . intval($n);
        }
        $desc = '数据清理 days=' . $days . ' limit=' . $limit . ' 合计=' . $total . ' [' . implode('; ', $parts) . ']';
        writelog(session('uid'), session('username'), $desc, 1);

        $remainHint = false;
        foreach (['info_unpaid', 'info_all', 'mail_used'] as $big) {
            if (in_array($big, $keys, true) && !$loop) {
                $left = DataCleanService::preview($days, [$big]);
                if (!empty($left[$big]) && intval($left[$big]) > 0) {
                    $remainHint = true;
                    break;
                }
            }
        }

        return json([
            'code' => 1,
            'msg' => $remainHint
                ? ('清理完成，共删除 ' . $total . ' 条/个；大表仍有剩余，可再次执行或勾选「循环清完」')
                : ('清理完成，共删除 ' . $total . ' 条/个'),
            'days' => $days,
            'total' => $total,
            'data' => $deleted,
            'remain' => $remainHint ? 1 : 0,
        ]);
    }
}
