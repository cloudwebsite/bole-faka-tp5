<?php
namespace app\admin\model;
use think\Model;
use think\Db;

class KamiModel extends Model
{
    protected $name = 'mail';
    protected $autoWriteTimestamp = true;

    public function getmailBywhere($map, $Nowpage, $limits)
    {
        return $this->field("think_mail.*,IFNULL(think_fl.mnamebie,IFNULL(think_fl.mname,'未知')) as name,think_addmaillog.userip,think_addmaillog.addqudao,think_addmaillog.addbiaoshi")
            ->join('think_fl', 'think_fl.id = think_mail.mpid', 'LEFT')
            ->join('think_addmaillog', 'think_addmaillog.id = think_mail.addid', 'LEFT')
            ->where($map)->page($Nowpage, $limits)
            ->order('think_mail.id desc')
            ->select();
    }

    public function getAllCount($map)
    {
        return $this->where($map)->count();
    }

    public function delKami($id)
    {
        try {
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        } catch (\Exception $e) {
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 入库批次日志列表
     */
    public function getAddmailLogByWhere($map, $Nowpage, $limits)
    {
        return Db::name('addmaillog')->alias('a')
            ->field("a.*,IFNULL(f.mnamebie,IFNULL(f.mname,'未知')) as mname")
            ->join('think_fl f', 'f.id = a.goodid', 'LEFT')
            ->where($map)
            ->order('a.id desc')
            ->page($Nowpage, $limits)
            ->select();
    }

    public function getAddmailLogCount($map)
    {
        return Db::name('addmaillog')->alias('a')->where($map)->count();
    }

    /**
     * 批量把 addqudao 转成可读文案
     * 约定：纯数字=PC后台管理员UID；madmin:{uid}=移动后台；空=历史 madmin 未记操作人
     * @param array $vals
     * @return array 原值 => 展示名
     */
    public static function mapAddqudaoLabels($vals)
    {
        $uids = [];
        foreach ((array)$vals as $v) {
            $v = trim((string)$v);
            if ($v === '') {
                continue;
            }
            if (ctype_digit($v)) {
                $uids[] = intval($v);
            } elseif (preg_match('/^madmin:(\d+)$/i', $v, $m)) {
                $uids[] = intval($m[1]);
            } elseif (preg_match('/^admin:(\d+)$/i', $v, $m)) {
                $uids[] = intval($m[1]);
            }
        }
        $adminMap = [];
        if ($uids) {
            $adminMap = Db::name('admin')->where('id', 'in', array_unique($uids))->column('username', 'id');
        }
        $out = [];
        foreach ((array)$vals as $v) {
            $key = (string)$v;
            if (isset($out[$key])) {
                continue;
            }
            $out[$key] = self::formatAddqudao($v, $adminMap);
        }
        return $out;
    }

    /**
     * 解析管理员显示名
     */
    protected static function adminDisplayName($uid, $adminMap = null)
    {
        $uid = intval($uid);
        if (is_array($adminMap) && isset($adminMap[$uid])) {
            return $adminMap[$uid];
        }
        $name = Db::name('admin')->where('id', $uid)->value('username');
        return $name ? $name : ('#' . $uid);
    }

    /**
     * 单条 addqudao 可读文案
     * @param mixed $val
     * @param array|null $adminMap id=>username
     */
    public static function formatAddqudao($val, $adminMap = null)
    {
        $val = trim((string)$val);
        if ($val === '') {
            // 旧版 madmin 补货写空串，无法还原操作人
            return '移动后台（历史·未记操作人）';
        }
        if (preg_match('/^madmin:(\d+)$/i', $val, $m)) {
            return '移动后台 · ' . self::adminDisplayName($m[1], $adminMap);
        }
        if (preg_match('/^admin:(\d+)$/i', $val, $m)) {
            return 'PC后台 · ' . self::adminDisplayName($m[1], $adminMap);
        }
        if (ctype_digit($val)) {
            // 兼容旧 PC 后台：只存 UID
            return 'PC后台 · ' . self::adminDisplayName($val, $adminMap);
        }
        // API 等自定义渠道字符串原样展示
        return $val;
    }

    /**
     * 按批次只删未使用卡密；若该批已无剩余卡密则删日志
     * @param int $addid
     * @return array
     */
    public function delUnusedByAddid($addid)
    {
        $addid = intval($addid);
        if ($addid < 1) {
            return ['code' => -1, 'msg' => '批次ID无效', 'unused' => 0, 'used' => 0, 'deleted' => 0, 'log_deleted' => 0];
        }
        $unused = (int)Db::name('mail')->where(['addid' => $addid, 'mis_use' => 0])->count();
        $used = (int)Db::name('mail')->where(['addid' => $addid, 'mis_use' => ['neq', 0]])->count();

        Db::startTrans();
        try {
            $deleted = 0;
            if ($unused > 0) {
                $deleted = (int)Db::name('mail')->where(['addid' => $addid, 'mis_use' => 0])->delete();
                if ($deleted === false) {
                    Db::rollback();
                    return ['code' => -1, 'msg' => '删除未用卡密失败', 'unused' => $unused, 'used' => $used, 'deleted' => 0, 'log_deleted' => 0];
                }
            }
            $remain = (int)Db::name('mail')->where(['addid' => $addid])->count();
            $logDeleted = 0;
            if ($remain === 0) {
                $logDeleted = (int)Db::name('addmaillog')->where('id', $addid)->delete();
                if ($logDeleted === false) {
                    Db::rollback();
                    return ['code' => -1, 'msg' => '删除入库日志失败', 'unused' => $unused, 'used' => $used, 'deleted' => $deleted, 'log_deleted' => 0];
                }
            }
            Db::commit();
            $msg = '已删除未用卡密 ' . $deleted . ' 条';
            if ($used > 0) {
                $msg .= '；已售 ' . $used . ' 条保留，入库日志保留';
            } elseif ($logDeleted > 0) {
                $msg .= '；该批已无剩余卡密，入库日志已删除';
            }
            return [
                'code' => 1,
                'msg' => $msg,
                'unused' => $unused,
                'used' => $used,
                'deleted' => $deleted,
                'log_deleted' => $logDeleted,
            ];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => -1, 'msg' => $e->getMessage(), 'unused' => $unused, 'used' => $used, 'deleted' => 0, 'log_deleted' => 0];
        }
    }
}
