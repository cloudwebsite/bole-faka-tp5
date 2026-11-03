<?php
return [
    'default_filter' => 'trim,strip_tags,htmlspecialchars',
    //模板参数替换
    'view_replace_str' => array(
        '__CSS__' => '/static/madmin/css',
        '__JS__'  => '/static/madmin/js',
        '__IMG__' => '/static/madmin/images',
        '__GZH__' => '/static/mobile/gzh',
        //geetest
	    '__GT__' => '/static/jingdian/geetest',
	    '__LAYUI__'=>'/static/layui',      			
    ),
];
