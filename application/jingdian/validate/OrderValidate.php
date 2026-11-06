<?php
namespace app\jingdian\validate;
use think\Validate;

class OrderValidate extends Validate
{
    protected $rule = [
        ['mcard', 'require', '订单号不能为空']
    ];
    protected $scene = [
    'name_email' => ['mcard'],
  	];

}