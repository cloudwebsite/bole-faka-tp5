<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class ConfigModel extends Model
{
    protected $name = 'config';

    //获取配置所有信息
    public function getAllConfig()
    {
        return $this->select();
    }


    //保存信息（仅 update 已存在 name，禁止 insert）
    public function SaveConfig($map,$value)
    {
        try{
            if (!is_array($map) || empty($map['name']) || is_array($value)) {
                return ['code' => -1, 'msg' => '参数错误'];
            }
            $exist = $this->where($map)->find();
            if (!$exist) {
                return ['code' => -1, 'msg' => '配置项不存在'];
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