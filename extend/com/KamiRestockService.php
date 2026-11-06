<?php
namespace com;

use think\Db;

/**
 * 虚拟商品卡密补货（PC admin / 移动 madmin 共用）
 * 兼容 PHP 7.4+
 */
class KamiRestockService
{
    /**
     * @param int          $goodid   商品 ID（fl.id）
     * @param string       $kamiText 卡密文本（多行）
     * @param string|int   $addqudao 渠道：PC 纯 UID；madmin 用 madmin:{uid}
     * @param string|null  $userip
     * @return array{code:int,msg:string,success?:int,existnum?:int,addnum?:int,addid?:int}
     */
    public static function restock($goodid, $kamiText, $addqudao, $userip = null)
    {
        $goodid = intval($goodid);
        $kamiText = str_replace(["\r\n", "\n", "\r"], "\r\n", (string)$kamiText);
        if ($userip === null || $userip === '') {
            $userip = getIP();
        }

        $FLData = Db::name('fl')->where('id', $goodid)->find();
        if ($kamiText === '') {
            return ['code' => -1, 'msg' => '卡密不能为空'];
        }
        if (!$FLData) {
            return ['code' => -1, 'msg' => '商品异常'];
        }
        if ((int)$FLData['status'] === 0) {
            return ['code' => -1, 'msg' => '该商品已下架'];
        }
        if ((int)$FLData['type'] !== 0) {
            return ['code' => -1, 'msg' => '商品不是虚拟商品'];
        }

        $textarray = explode("\r\n", $kamiText);
        $rows = [];
        $now = time();
        foreach ($textarray as $val) {
            $temptext = trim($val);
            if ($temptext === '') {
                continue;
            }
            if ((int)$FLData['decrypt'] === 1) {
                $rows[] = [
                    'musernm' => md5($temptext),
                    'mpasswd' => passport_encrypt($temptext, DECRYPT_KEY),
                    'mpid' => $goodid,
                    'create_time' => $now,
                ];
            } else {
                $rows[] = [
                    'musernm' => $temptext,
                    'mpasswd' => '',
                    'mpid' => $goodid,
                    'create_time' => $now,
                ];
            }
        }
        $num = count($rows);
        if ($num < 1) {
            return ['code' => -1, 'msg' => '卡密不能为空'];
        }

        Db::startTrans();
        try {
            $addbiaoshi = date('YmdHis') . str_pad((string)mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $logOk = Db::name('addmaillog')->insert([
                'addbiaoshi' => $addbiaoshi,
                'addqudao' => (string)$addqudao,
                'userip' => $userip,
                'create_time' => $now,
                'goodid' => $goodid,
                'addnum' => $num,
                'failnum' => 0,
            ]);
            if ($logOk === false) {
                Db::rollback();
                return ['code' => -1, 'msg' => '标识出错'];
            }
            $biaoshiId = (int)Db::name('addmaillog')->getLastInsID();

            $success = 0;
            foreach ($rows as $r) {
                $n = Db::execute(
                    'INSERT IGNORE INTO think_mail (musernm,mpasswd,mpid,addid,create_time) VALUES (:musernm,:mpasswd,:mpid,:addid,:create_time)',
                    [
                        'musernm' => $r['musernm'],
                        'mpasswd' => $r['mpasswd'],
                        'mpid' => $r['mpid'],
                        'addid' => $biaoshiId,
                        'create_time' => $r['create_time'],
                    ]
                );
                if ($n === false) {
                    Db::rollback();
                    return ['code' => -1, 'msg' => '添加卡密出错'];
                }
                $success += (int)$n;
            }
            $existnum = $num - $success;
            $resultLog = Db::name('addmaillog')->where('id', $biaoshiId)->update([
                'successnum' => $success,
                'failnum' => $existnum,
                'existnum' => $existnum,
            ]);
            if ($resultLog === false) {
                Db::rollback();
                return ['code' => -1, 'msg' => '更新添加日志出错'];
            }
            Db::commit();

            $msg = ($success > 0 ? '恭喜你，操作成功' : '补货不成功')
                . '<br>总共数量：' . $num . '<br>成功数量：' . $success . '<br>存在数量：' . $existnum;

            return [
                'code' => $success > 0 ? 1 : -1,
                'msg' => $msg,
                'success' => $success,
                'existnum' => $existnum,
                'addnum' => $num,
                'addid' => $biaoshiId,
            ];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }
}
