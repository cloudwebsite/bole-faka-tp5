<?php
namespace app\admin\controller;
use app\admin\model\RechargeKamiModel;
use think\Db;

class RechargeKami extends Base
{
    public function index()
    {
        $key = input('key');
        $status = input('status', '');
        $batch_no = input('batch_no', '');
        $map = [];
        if ($key !== null && $key !== '') {
            $map['code'] = ['like', '%' . $key . '%'];
        }
        if ($status !== '' && $status !== null) {
            $map['status'] = (int)$status;
        }
        if ($batch_no !== null && $batch_no !== '') {
            $map['batch_no'] = $batch_no;
        }

        $KamiM = new RechargeKamiModel();
        $Nowpage = input('get.page') ? input('get.page') : 1;
        $limits = config('list_rows');
        $count = $KamiM->getAllCount($map);
        $allpage = intval(ceil($count / $limits));
        $lists = $KamiM->getListByWhere($map, $Nowpage, $limits);
        foreach ($lists as $k => $v) {
            $lists[$k]['create_time'] = $v['create_time'] ? date('Y-m-d H:i:s', $v['create_time']) : '';
            $lists[$k]['use_time'] = $v['use_time'] ? date('Y-m-d H:i:s', $v['use_time']) : '-';
        }

        if (input('get.page')) {
            return json($lists);
        }
        $this->assign('Nowpage', $Nowpage);
        $this->assign('allpage', $allpage);
        $this->assign('count', $count);
        $this->assign('val', $key);
        $this->assign('status', $status);
        $this->assign('batch_no', $batch_no);
        return $this->fetch();
    }

    public function add()
    {
        return $this->fetch();
    }

    /**
     * 按面额批量生成
     */
    public function generate()
    {
        if (!request()->isPost()) {
            return json(['code' => -1, 'msg' => '非法请求']);
        }
        $money = floatval(input('post.money'));
        $num = intval(input('post.num'));
        if ($money <= 0) {
            return json(['code' => -1, 'msg' => '面额必须大于0']);
        }
        if ($num < 1 || $num > 2000) {
            return json(['code' => -1, 'msg' => '数量需在1-2000之间']);
        }

        $batch_no = date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $admin_id = intval(session('uid'));
        $now = time();
        $success = 0;
        $fail = 0;

        Db::startTrans();
        try {
            for ($i = 0; $i < $num; $i++) {
                $code = $this->makeCode();
                $n = Db::execute(
                    'INSERT IGNORE INTO think_recharge_kami (code,money,status,batch_no,admin_id,create_time,update_time) VALUES (:code,:money,0,:batch_no,:admin_id,:create_time,:update_time)',
                    [
                        'code' => $code,
                        'money' => $money,
                        'batch_no' => $batch_no,
                        'admin_id' => $admin_id,
                        'create_time' => $now,
                        'update_time' => $now,
                    ]
                );
                if ($n) {
                    $success++;
                } else {
                    // 极低概率碰撞，再试一次
                    $code = $this->makeCode();
                    $n2 = Db::execute(
                        'INSERT IGNORE INTO think_recharge_kami (code,money,status,batch_no,admin_id,create_time,update_time) VALUES (:code,:money,0,:batch_no,:admin_id,:create_time,:update_time)',
                        [
                            'code' => $code,
                            'money' => $money,
                            'batch_no' => $batch_no,
                            'admin_id' => $admin_id,
                            'create_time' => $now,
                            'update_time' => $now,
                        ]
                    );
                    if ($n2) {
                        $success++;
                    } else {
                        $fail++;
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => -1, 'msg' => $e->getMessage()]);
        }

        return json([
            'code' => 1,
            'msg' => '生成完成：成功' . $success . '张' . ($fail ? '，失败' . $fail . '张' : '') . '，批次' . $batch_no,
            'batch_no' => $batch_no,
            'url' => url('recharge_kami/index', ['batch_no' => $batch_no]),
        ]);
    }

    /**
     * 导入：每行一张；支持 卡密 或 卡密|面额；统一面额用 money 字段
     */
    public function import()
    {
        if (!request()->isPost()) {
            return json(['code' => -1, 'msg' => '非法请求']);
        }
        $defaultMoney = floatval(input('post.money'));
        $text = input('post.values', '');
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = explode("\n", $text);
        $rows = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $code = $line;
            $money = $defaultMoney;
            if (strpos($line, '|') !== false) {
                $parts = explode('|', $line, 2);
                $code = trim($parts[0]);
                $money = floatval(trim($parts[1]));
            }
            if ($code === '' || $money <= 0) {
                continue;
            }
            $rows[] = ['code' => $code, 'money' => $money];
        }
        if (count($rows) < 1) {
            return json(['code' => -1, 'msg' => '无有效卡密（需统一面额或 卡密|面额）']);
        }
        if (count($rows) > 2000) {
            return json(['code' => -1, 'msg' => '单次最多导入2000张']);
        }

        $batch_no = date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $admin_id = intval(session('uid'));
        $now = time();
        $success = 0;
        $fail = 0;

        Db::startTrans();
        try {
            foreach ($rows as $r) {
                $n = Db::execute(
                    'INSERT IGNORE INTO think_recharge_kami (code,money,status,batch_no,admin_id,create_time,update_time) VALUES (:code,:money,0,:batch_no,:admin_id,:create_time,:update_time)',
                    [
                        'code' => $r['code'],
                        'money' => $r['money'],
                        'batch_no' => $batch_no,
                        'admin_id' => $admin_id,
                        'create_time' => $now,
                        'update_time' => $now,
                    ]
                );
                if ($n) {
                    $success++;
                } else {
                    $fail++;
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => -1, 'msg' => $e->getMessage()]);
        }

        return json([
            'code' => 1,
            'msg' => '导入完成：成功' . $success . '张，失败/重复' . $fail . '张，批次' . $batch_no,
            'batch_no' => $batch_no,
            'url' => url('recharge_kami/index', ['batch_no' => $batch_no]),
        ]);
    }

    public function del()
    {
        $id = input('param.id');
        $KamiM = new RechargeKamiModel();
        $flag = $KamiM->delUnused($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    /**
     * 导出未使用卡密
     */
    public function export()
    {
        $batch_no = input('batch_no', '');
        $map = ['status' => 0];
        if ($batch_no !== '') {
            $map['batch_no'] = $batch_no;
        }
        $list = Db::name('recharge_kami')->where($map)->order('id asc')->select();
        $filename = 'recharge_kami_' . date('YmdHis') . '.txt';
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        foreach ($list as $row) {
            echo $row['code'] . '|' . $row['money'] . "\r\n";
        }
        exit;
    }

    private function makeCode($len = 18)
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max = strlen($chars) - 1;
        $code = '';
        for ($i = 0; $i < $len; $i++) {
            $code .= $chars[mt_rand(0, $max)];
        }
        return $code;
    }
}
