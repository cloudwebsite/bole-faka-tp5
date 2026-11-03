<?php

namespace app\admin\validate;
use think\Validate;

class CateGoryGroupValidate extends Validate
{
    protected $rule = [
        ['name', 'unique:category_group', '类目已经存在']
    ];

}