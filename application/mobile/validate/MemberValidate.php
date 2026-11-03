<?php

namespace app\mobile\validate;
use think\Validate;

class MemberValidate extends Validate
{
    protected $rule = [
    	['account', 'require|max:20|min:6|unique:member', '用户名不能为空|用户名长度不能大于20个字符|用户名长度不能小于6个字符|该会员已经存在'],
    	['password', 'require', '密码不能为空'],
    	['mobile', 'require|length:11|unique:member', '手机号必填|手机号码必须为11位，请检查。|手机号已存在'],
    	['email', 'require|email|unique:member', '联系邮箱不能为空|邮箱格式不正确|邮箱已经存在'],
    	['qq', 'require|max:12|min:5|unique:member', '联系QQ不能为空|QQ长度错误|QQ长度错误|QQ号已经存在'],
    ];

}