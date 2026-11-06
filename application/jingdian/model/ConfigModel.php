<?php

namespace app\jingdian\model;
use think\Model;
use think\Db;
class ConfigModel extends Model
{
    protected $name = 'child_config';

    //获取配置所有信息
    public function getAllConfig()
    {
        return $this->where('memberid',session('useraccount.id'))->select();
    }


    /**
     * 保存信息
     * @param array $map name + memberid
     * @param mixed $value
     * @param bool  $allowInsert 仅白名单调用方可 true；禁止通用请求任意 insert
     */
    public function SaveConfig($map, $value, $allowInsert = false)
    {
        try{
            if (!is_array($map) || empty($map['name']) || empty($map['memberid'])) {
                return ['code' => -1, 'msg' => '参数错误'];
            }
            if (is_array($value)) {
                return ['code' => -1, 'msg' => '配置值不合法'];
            }
        	$exist=$this->where($map)->find();
        	if(!$exist){
                if (!$allowInsert) {
                    return ['code' => -1, 'msg' => '配置项不存在'];
                }
                $row = $map;
                $row['value'] = (string)$value;
        		$this->insert($row);
                return ['code' => 1, 'msg' => '保存成功'];
        	}

        	$result = $this->where($map)->setField('value', (string)$value);
        	
            if(false === $result){            
                return ['code' => -1, 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'msg' => '保存成功'];
            }
        }catch( \Exception $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

}
