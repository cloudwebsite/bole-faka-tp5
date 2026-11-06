<?php
namespace app\madmin\validate;
use think\Validate;
class MemberloginValidate extends Validate
{
    protected $rule = [
    	['username', 'require', '用户名不能为空'],
    	['password', 'require', '密码不能为空'],
    ];

}