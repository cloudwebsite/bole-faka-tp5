<?php
namespace app\admin\model;
use think\Model;
use think\Db;

class RechargeKamiModel extends Model
{
    protected $name = 'recharge_kami';
    protected $autoWriteTimestamp = true;

    public function getListByWhere($map, $Nowpage, $limits)
    {
        return $this->where($map)->page($Nowpage, $limits)->order('id desc')->select();
    }

    public function getAllCount($map)
    {
        return $this->where($map)->count();
    }

    public function delUnused($id)
    {
        try {
            $row = $this->where('id', $id)->find();
            if (!$row) {
                return ['code' => 0, 'data' => '', 'msg' => '记录不存在'];
            }
            if ((int)$row['status'] === 1) {
                return ['code' => 0, 'data' => '', 'msg' => '已使用卡密不可删除'];
            }
            $this->where('id', $id)->where('status', 0)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        } catch (\Exception $e) {
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}
