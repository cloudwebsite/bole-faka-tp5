<?php

namespace app\mobile\validate;
use think\Validate;

class MemberloginValidate extends Validate
{
    protected $rule = [
    	['account', 'require|max:20|min:6', '用户名不能为空|用户名长度不能大于20个字符|用户名长度不能小于6个字符'],
    	['password', 'require', '密码不能为空'],
    ];

}