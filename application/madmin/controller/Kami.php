<?php
namespace app\madmin\controller;

use app\admin\model\KamiModel;
use think\Db;

/**
 * 移动端卡密：入库日志 / 列表 / 按批删未用（复用 admin KamiModel）
 */
class Kami extends Base
{
    /**
     * 入库批次日志（全站或按商品 goodid）
     */
    public function addlog()
    {
        $goodid = input('goodid');
        $key = input('key');

        if (!input('get.page')) {
            $goodsname = '';
            if ($goodid !== null && $goodid !== '' && intval($goodid) > 0) {
                $g = Db::name('fl')->where('id', intval($goodid))->find();
                $goodsname = $g ? (isset($g['mnamebie']) ? $g['mnamebie'] : $g['mname']) : '';
            }
            $this->assign('Nowpage', 1);
            $this->assign('allpage', 1);
            $this->assign('count', 0);
            $this->assign('goodid', ($goodid !== null && $goodid !== '' && intval($goodid) > 0) ? intval($goodid) : '');
            $this->assign('goodsname', $goodsname);
            $this->assign('val', $key);
            return $this->fetch();
        }

        $map = [];
        if ($goodid !== null && $goodid !== '' && intval($goodid) > 0) {
            $map['a.goodid'] = intval($goodid);
        }
        if ($key && $key !== '') {
            $map['a.addbiaoshi'] = ['like', '%' . $key . '%'];
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
                'existnum' => $row['existnum'],
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
     * 卡密列表（商品维度，可按 addid）
     */
    public function index()
    {
        $goodsid = intval(input('id', 0));
        $addid = intval(input('addid', 0));
        $key = input('key');
        $misuse = input('misuse', '99');

        if ($goodsid < 1) {
            return $this->error('请指定商品');
        }

        if (!input('get.page')) {
            $goodsdetail = Db::name('fl')->where('id', $goodsid)->find();
            $this->assign('goodsname', $goodsdetail ? $goodsdetail['mnamebie'] : '');
            $this->assign('goodsid', $goodsid);
            $this->assign('addid', $addid > 0 ? $addid : '');
            $this->assign('val', $key);
            $this->assign('misuse', $misuse);
            $this->assign('count', 0);
            $this->assign('Nowpage', 1);
            $this->assign('allpage', 1);
            return $this->fetch();
        }

        $map = ['think_mail.mpid' => $goodsid];
        if ($addid > 0) {
            $map['think_mail.addid'] = $addid;
        }
        if ($key && $key !== '') {
            $map['think_mail.musernm'] = ['like', '%' . $key . '%'];
        }
        if ($misuse === '0' || $misuse === '1') {
            $map['think_mail.mis_use'] = intval($misuse);
        }

        $KamiM = new KamiModel();
        $Nowpage = max(1, intval(input('get.page')));
        $limits = config('list_rows') ?: 15;
        $count = $KamiM->getAllCount($map);
        $allpage = max(1, intval(ceil($count / $limits)));
        $lists = $KamiM->getmailBywhere($map, $Nowpage, $limits);

        $rows = [];
        foreach ($lists as $row) {
            $ct = isset($row['create_time']) ? $row['create_time'] : 0;
            $rows[] = [
                'id' => $row['id'],
                'musernm' => $row['musernm'],
                'mis_use' => $row['mis_use'],
                'addid' => isset($row['addid']) ? $row['addid'] : '',
                'userip' => isset($row['userip']) ? $row['userip'] : '',
                'addqudao' => isset($row['addqudao']) ? $row['addqudao'] : '',
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
     * 按批次删除未使用卡密
     */
    public function del_by_addid()
    {
        $addid = intval(input('param.addid', 0));
        if ($addid < 1) {
            return json(['code' => -1, 'msg' => '批次ID无效']);
        }
        $KamiM = new KamiModel();
        return json($KamiM->delUnusedByAddid($addid));
    }

    /**
     * 删除单条卡密
     */
    public function del_kami()
    {
        $id = input('param.id');
        $KamiM = new KamiModel();
        $flag = $KamiM->delKami($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
}
