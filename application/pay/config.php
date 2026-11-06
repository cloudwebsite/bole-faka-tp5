<?php
return [
    'default_filter' => 'trim,strip_tags,htmlspecialchars',

    //模板参数替换
    'view_replace_str' => array(
        
		'__ZFIMG__' => '/static/zypay/img',	
		 '__CSS__' => '/static/jingdian/css',
        '__JS__'  => '/static/jingdian/js',
        '__IMG__' => '/static/jingdian/images',	
        '__LAYJS__' => '/static/admin/js',
        '__UPLOAD__' => '/upload',	
        '__ZYPAY__' => '/pay/zypay',
        '__ZYCARDPAY__' => '/pay/card',	
        '__ZFBGF__' => '/pay/zfb',
        '__WXGF__' => '/pay/wxpay/example/native.php',	
    //917模版
        '__LOAD917__' => '/static/jingdian/917/',
	//ak模版
	    '__MOBANAK__' => '/static/jingdian/ak/',
    
    ),
   

   
];
