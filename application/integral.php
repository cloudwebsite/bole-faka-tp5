<?php use think\Route;
	Route::rule('jf','jingdian/integral/index');
	Route::rule('js/:id','jingdian/integral/goodsdetail');
	Route::rule('js','jingdian/integral/goodsdetail');
	
	Route::rule('mjf','mobile/integral/index');
	Route::rule('mjs/:id','mobile/integral/goodsdetail');
	Route::rule('mjs','mobile/integral/goodsdetail');