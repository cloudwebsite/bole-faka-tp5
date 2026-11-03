<?php

namespace app\admin\validate;
use think\Validate;

class CateGoryGroupValidate extends Validate
{
    protected $rule = [
        ['mname', 'unique:fl', '商品已经存在']
    ];

}