// layui.use('layer', function(){
// 	var layer = layui.layer;
// 		layer_photos();
// });
// //登陆提示
// malert = function(c,b,t,s,h){
//  var b=arguments[1] || false,t=arguments[2] || 0,s=arguments[3] || false;
//  layer.open({
//     content:c
// 	,skin:s
//     ,btn:b
//     ,time:t
// 	,yes:function(index){
//           layer.close(index);
//     }
//   });
// }
// released = function(type){
// 	 layer.load();
// 	$.get('/html/released',{type:type}, function(html){
// 		layer_ly(html,false,true,'560px','auto');
// 	});
// }
// sign = function(){ //每日签到
// 	Aform('sign','',function(datas){
// 		if(datas.state==-2){
// 			layer_lp('签到','login_sign'); return false;
// 		}
// 		Rs(datas);
// 	});
// }
// layer_lp = function(action,btn){//登陆提示
// 	layer.confirm('亲，您需要先<font color="red">登陆</font>才能<font color="#ff6600">'+action+'</font>哦！', {icon:0,
// 	  btn: ['去登陆','打酱油']
// 	}, function(){
// 	   layer.closeAll();
// 	   layer_login('再'+action,btn);
// 	}, function(){
// 	   layer.msg('好吧，亲，那您就继续打酱油吧！');
// 	});
// }
//  var login = function(captchaObj){//成功的回调
// 	 var captcha=$('#popup-captcha');
//         captchaObj.onSuccess(function () {
//             var validate = captchaObj.getValidate(),name=$('input[name=login_name]').val(),pass=$('input[name=login_pass]').val(),data="mode=1&login_name="+name+"&login_pass="+pass+"&geetest_challenge="+validate.geetest_challenge+"&geetest_validate="+validate.geetest_validate+"&geetest_seccode="+validate.geetest_seccode;
//              Aform('login',data,function(datas){
// 				 if(datas.state!=1){
// 					 captcha.hide();
// 					 captchaObj.reset(); // 调用该接口进行重置
// 					 Rs(datas);
// 					 return false;
// 				 }
// 				 UCheck();
// 	         });
//             });
//         $("#login_submit_button").click(function () {
// 			var name=$('input[name=login_name]'),pass=$('input[name=login_pass]');
// 			if(!judge(name.val())){
//               layer.tips('请输入正确的帐号(邮箱)',name); return false;
//             }
//             if(pass.val().length<6){
//               layer.tips('请输入正确的密码(6-20位)',pass);return false;
//             }
//             if (captcha.is(':visible') && !captchaObj.getValidate()) {
//               layer.tips('请先完成验证',captcha);return false;
//             }
// 			 captcha.show();
//         });
//         // 将验证码加到id为captcha的元素里
//         captchaObj.appendTo(captcha);
//     }

// function  logingt(){
// $.getScript("../js/static/gt.js", function() {
//     $.ajax({
//         url: "/html/StartCaptchaServlet?t=" + (new Date()).getTime(), // 加随机数防止缓存
//         type: "get",
//         dataType: "json",
//         success: function (data) {
//             initGeetest({
//                 gt: data.gt,
// 					width:'100%',
//                 challenge: data.challenge,
// 				new_captcha: data.new_captcha,
//                 product: "popup", // 产品形式，包括：float，embed，popup。注意只对PC版验证码有效
//                 offline: !data.success // 表示用户后台检测极验服务器是否宕机，一般不需要关注
//             },login);
//         }
//     });
// 	});
// }

// layer_login = function(t,c){
// 		var t=arguments[0] || false,c=arguments[1] || '';
// 		$.get('/html/login?c='+c,function(html){
// 			layer.closeAll('loading');
// 			title=(t)?'亲！先登个录'+t:'账号登陆';
// 			layer.open({
// 				type: 1,
// 				title: [title, 'border:none; background:#f2f2f2; color:#333;font-size:14px;'],
// 				shadeClose: true,
// 				shade: [0.1, '#000'],
// 				area: ['400px'],
// 				content: html,
// 				success: function (){
// 					logingt();
// 				}
// 			});
// 		});
// }

// function UCheck(str){
// 	var c=arguments[0] || 'ajax_login';
// 	if($('input[name="ureturn"]').length>0){
// 		var u=$.trim($('input[name="ureturn"]').val());
// 		CheckHzurl(u)?window.location.href=u:window.location.reload();
// 	}else if($('input[name="uclick"]').length>0){
// 		var c = $('input[name="uclick"]').val().split('|');
// 		if($.trim(c[0])=='click') $($.trim(c[1])).trigger("click");
// 	}else if($('.top_box').length>0){
// 		Aform(c,'',function(datas){
// 			if(datas.state==1){
// 				$.each(datas,function(key,val){
// 					if($('.'+key).length>0) $("."+key).html(val);
// 				});
// 			}else{
// 				Rs(datas);
// 			}
// 		});
// 	}
// 	layer.closeAll('page');
// }


// function deling(id){
// 	uploader.removeFile(id);
// 	$("#file-"+id).remove();
// 	($('#FileListing tr').length<=0)?$('#pluing').hide():$('#pluing u').html(uploader.files.length);
// }

function budget(){
	var money = $("input[name=money]").val(),Role = $("input[name=role]:checked").val(),fees = $("input[name=fees]:checked").val();
	if(Role && money && fees){
		var fee = money*0.02;
		$("#budget").show();
		if(fees=='buy'){
			$("#bm").html(money*1+fee*1);
			$("#sm").html(money);
		}else if(fees=='sell'){
			$("#bm").html(money);
			$("#sm").html(money-fee);
		}else{
			$("#bm").html(money*1+(fee*0.5)*1);
			$("#sm").html(money-(fee*0.5));
		}
    }else{
		$("#budget").hide();
	}

}

function verify(str){
	var code = $('input[name=vcode]').val();
	if((str=='phone' && code.length!=4) || (str=='email' && code.length!=6)){
		$('input[name=vcode]').val('');$('input[name=vcode]').focus();layer.tips('验证码错误，请重新输入', $('input[name=vcode]'));return false;
	}else if(str=='phone' || str=='email'){
		Aform("verify",$("form *[value!='']").serialize()+"&mode="+str);  
	}
}


sendbtn = function(name,time) {
	var type=$(name).attr('data');
	if(!judge($('input[name='+type+']').val(),type)){
		layer.tips($('input[name='+type+']').attr('placeholder'),$('input[name='+type+']'),{tips:[3,'#f90']});$('input[name='+type+']').focus();
		return false;
	}
	$(name).attr("disabled",true);
	Aform('sendinfo',$("form").serialize(),function(datas){
		datas.state==-1?sendtime(name,time):$(name).attr("disabled",false);
		Rs(datas);
	});
}
sendtime= function(name,time){
	var second=arguments[1] || 60;
	$(name).attr("disabled",true);
	$(name).val(second + "秒后可以重发").addClass("layui-btn-disabled");
	function update(num) {
		if (num == second) {   
			$(name).removeClass("layui-btn-disabled"); 
			$(name).val("发送验证码");
			$(name).attr("disabled", false);
		}else {
			var printnr = second - num;
			$(name).val(printnr + "秒后可以重发");
		}
	}
	function uupdate(i) {
		return function () {
			update(i);
		}
	}
	for (var i = 1; i <= second; i++) {
		setTimeout(uupdate(i), i * 1000);
	}
}

$('.pingfen_btn').live('hover',function(event){
	(event.type=='mouseenter')?$(this).addClass("active"):$(this).removeClass("active");
	
}) 

$('.uim p,.uim a').live('click', function(){//Q
	var	$this=$(this),$im="",$box=$this.closest('span'),$div=$this.parents(),$arr =$box.attr("uinfo").split("|"),loading= layer.load();;
	$this.siblings().each(function(){
		$im+=$(this).html()+",";
	})
	$.get("/html/contact",{cim:$this.html(),im:$im,type:$div.attr('class')}, function(html){
	layer_ly(html,'<img src="'+$arr[1]+'" style="width:20px;height:20px;margin-left:-10px;float:left;margin-top:10px;padding:1px;border: 1px solid #ddd;"><strong style="float:left;max-width:130px;overflow:hidden;color:#ff6600;padding:0 5px;">'+$arr[0]+'</strong> <span style="color:#666;float:left;">'+$div.attr('title')+'</span>',true,'330px');
	});
});

$(document).ready(function() {
	if($.isFunction($.fn.tipso)) $('.tips').tipso();
	$(".checkLen").each(function(){
		CheckLen(this,parseInt($(this).attr('d_len')))
	});
	updateEndTime();
	$('.tablelist:not(.noodd) tbody tr:odd,.imgtable tbody tr:odd').addClass('odd');


	$('#ly_browse').click(function(){
		layer_iframe('html/browse','最近浏览记录','800px','430px');
	});

	$('#ly_kfqq').click(function(){
		$.get("/html/csc", function(html){
			layer_ly(html,'管理客服',true,'390px');
		});
	});


	if($('#ly_gotop').length>0){
		$(window).scroll(function(e){
			h = $(window).height();
			t = $(document).scrollTop();
			if(t > h){
				$('#ly_gotop').show();
			}else{
				$('#ly_gotop').hide();
			}
		});
		$('#ly_gotop').click(function(){
			$("html,body").animate({ scrollTop: 0 },300);
		})
	}


	$(".searchtype").click(function(){
		$(".searchlist").toggle();
	})
	$(".searchlist li").click(function(){
		$(".searchtype").html($(this).html());
		$(this).addClass('cur').siblings().removeClass("cur");
		$(".searchlist").hide();
	})
	
	

	$('.imfav').click(function(){
		var action=$(this).attr('id');
		Aform('fav','info='+$(this).attr('info'),function(datas){
			if(datas.state==-2){
				layer_lp('收藏',action); return false;
			}
			Rs(datas);
		});
	});

  var canHide = false; //标记是否可隐藏层
    function doHide(id){   //是否隐藏层中这里处理
     if(canHide){
    $(id).hide();   //先将层隐藏起来
		$(".toggle_center").removeClass("mcur");
	 }
    }

    $(".toggle_center").hover(function(){ //鼠标进入
     $(this).parent().next().show(); //显示
	 $(this).addClass("mcur");
     canHide = false; //标记不可隐藏
    },function(){
	 $this=$(this).parent().next();
     canHide = true; //鼠标移出可隐藏
     window.clearTimeout(t); //将上次的定时器清除,重新设置
     var t = window.setTimeout(function(){doHide($this)},300); //在间隔1000毫秒后执行是否隐藏处理
    }
    );
    //主要依靠定时器来将两者关联起来
    $(".toggle_pop,.rev_pop").hover(function(){ //鼠标进入
     canHide = false;    //不可隐藏
    },function(){
	 $this=$(this);
     canHide = true;     //鼠标移出可隐藏
     window.clearTimeout(t);
     var t = window.setTimeout(function(){doHide($this)},300);
    });

	$(".top_box li:not(.not)").live({
		mouseenter:function(){//鼠标进入
			$(this).addClass("curr");
		} ,
		mouseleave:function(){
			$(this).removeClass("curr");
		}
	});


	$(".w_d_list .l2,.w_list .l2").hover(
			function(){
				var that=$(this);
				item1Timer=setTimeout(function(){
					$(that).children("span").show().animate({"top":0,height:"100%"},300);
				},100);
			},
			function(){
				var that=$(this);
						clearTimeout(item1Timer);
						$(that).children("span").animate({"top":"50%",height:0},300,function(){
							$(that).children("span").hide();
					});
			}
	);
})



// $(".o_number").live('keyup',function(){
//      var txt=$(this).val();
//      $(this).val(txt.replace(/\D|^0/g,''));
// });
//
//
// $('.checkLen').live('keyup', function(){
//      CheckLen(this,parseInt($(this).attr('d_len')));
//  });
//
// $(".installing").live('click',function(){
// 	$.get('/html/installing?'+attArr(this), function(html){
// 		layer_ly(html,'安装服务详情',true,'600px');
// 	});
// });

// $(".preview").live('click',function(){
// 	layer_iframe('html/preview/'+$('input[name=good]').val()+"/"+$('input[name=action]').val(),' ','880px','510px');
// });
//
// $('.serve_btn').live('click', function(){
// 	var ing=layer.load(),$this=$(this),number = typeof($(this).attr("number"))=="undefined"?readmeta('Or-Number'):$(this).attr("number");
// 	$Me=$('.Edition a.cur');
// 	Aform('u','', function(datas){
//         if(datas.state==-2){layer_login('购买','.'+$this.attr('class'));return false;}
// 		$.post('/deal/addserve',{number:number,money:parseInt($Me.attr('data')),edition:$Me.attr('title'),piece:$('#p_number').val()},function(html){
// 			layer_ly(html,'服务下单',false,'700px');
// 		});
// 	});
// });
//
// $('.allmoney').live('click', function(){
// 	$.post('/html/allmoney',{number:$(this).attr('number')}, function(html){
// 		layer_ly(html,false,true,'650px',"","",true);
// 	});
// });


// $('.Edition a').live('click', function(){
// 	var $Price=$(this).closest("div").find(".price_m"),$Price_range=$(this).closest("div").find(".Price_range");
// 	if($(this).hasClass("cur")){
// 		$Price.html($Price_range.html());
// 		$(this).removeClass("cur");
// 	}else{
// 		$Price.html($(this).attr("data"));
// 		$(this).addClass("cur").siblings("a").removeClass("cur");
// 	}
// });


// $(".addred").live('click', function(){
// 	var s=$(this).siblings("#addred_num"),d=1;
// 	if($(this).val()=='-'){d=-1;}
// 	var num=parseInt(s.val())+d;
// 	if(s.attr('data')=='day'){
// 		if(num<=0 || num>60){layer.alert("交易周期，不能小于1天或大于60天",{icon:2});return false;}
// 	}else if(s.attr('data')=='piece'){
// 		if(num<=0){layer.alert("购买数量不能小于1件",{icon:2});return false;}
// 		$(".ly_total").html(toDecimal2(parseInt($(".ly_money").html())*num));
// 	}
// 	s.val(num);
// });

// $('.see_stats').live('click', function(){
// 	$.get("/html/stats",{number:$(this).attr('id')}, function(html){
// 		layer_ly(html,'网站统计详情',true,'652px');
// 	});
// });
//
// $('.login_click').live('click', function(){//登陆
// 	var $id=$(this).attr("id");
// 	if($id=='qq' || $id=='baidu'){
// 		var $w=720,$h=420;
// 	}else if($id=='sina'){
// 		var $w=770,$h=530;
// 	}else{
// 		var $w=570,$h=530;
// 	}
// 	openwindow("/oauth/login/"+$id,$(this).attr("title"),$w,$h);
// });


function openwindow(url,name,iWidth,iHeight){
	var url,iWidth,iHeight, iTop = (window.screen.availHeight-30-iHeight)/2,iLeft = (window.screen.availWidth-10-iWidth)/2;
	window.open(url,name,'height='+iHeight+',,innerHeight='+iHeight+',width='+iWidth+',innerWidth='+iWidth+',top='+iTop+',left='+iLeft+',toolbar=no,menubar=no,scrollbars=auto,resizable=yes,location=yes,status=no');
}

function replace_html(dathtml){
	dathtml=dathtml.replace(/<img[^>]*>/ig,'');
    dathtml=dathtml.replace(/<a[^>]*>/ig,'');
    dathtml=dathtml.replace(/<\/a>/ig,'');
    return dathtml;
}

function CheckLen(i,cLen) {
	var str = $(i).val().replace(/[, ]/g,'');
	myinfo = getStrleng(str,cLen);
	if(myinfo[0] > cLen * 2) {
		$(i).val(str.substring(0, myinfo[1]- 1));
		$count=0;
	}else{
		$count=Math.floor((cLen * 2 - myinfo[0]) / 2);
	}
	$check_tisp=$(i).parent().siblings('#check_count');
	if($check_tisp.length<=0){
		$check_tisp=$(i).parent().parent().find('#check_count');
	}
	$check_tisp.html($count);
	$(i).val($(i).val().replace(/[, ]/g,''));
}
function getStrleng(str,cLen) {
	myLen = 0;
	i = 0;
	for (; (i < str.length) && (myLen <= cLen * 2); i++) {
		if (str.charCodeAt(i) > 0 && str.charCodeAt(i) < 128)
			myLen++;
		else
			myLen += 2;
	}
	return [myLen,i]
}

function judge(str,type){//判断
	if(type=='phone'){//手机
		var partten = /^0?1[3|4|5|7|8][0-9]\d{8}$/;
	}else if(type=='tel'){//固话
		var partten = /^(?:(?:0\d{2,3})-)?(?:\d{7,8})(-(?:\d{3,}))?$/;
	}else if(type=='domain'){//域名
		var partten = /^([\w-]+\.)+((com)|(cn)|(com\.cn)|(net)|(net\.cn)|(org)|(org\.cn)|(ac)|(ac\.cn)|(asia)|(audio)|(band)|(bid)|(bike)|(biz)|(blue)|(club)|(cab)|(camp)|(care)|(cash)|(cc)|(cheap)|(city)|(click)|(cloud)|(club)|(cn\.com)|(co)|(com\.hk)|(com\.tw)|(cool)|(date)|(design)|(download)|(email)|(engineer)|(eu)|(fail)|(farm)|(fish)|(flowers)|(fund)|(game)|(games)|(gift)|(gov\.cn)|(green)|(guide)|(guru)|(help)|(hiphop)|(hk)|(host)|(hosting)|(house)|(in)|(info)|(ink)|(io)|(it)|(jp)|(kim)|(la)|(land)|(lawyer)|(life)|(limo)|(link)|(live)|(loan)|(lol)|(ltd)|(ltd\.uk)|(market)|(me)|(me\.uk)|(media)|(mobi)|(mom)|(name)|(news)|(ninja)|(onLine)|(online)|(org\.uk)|(party)|(photo)|(pics)|(pink)|(plc\.uk)|(poker)|(press)|(pro)|(pub)|(pw)|(red)|(ren)|(rocks)|(science)|(sexy)|(sg)|(sh)|(shoes)|(shop)|(site)|(so)|(social)|(software)|(solar)|(space)|(store)|(studio)|(tax)|(tech)|(tips)|(tm)|(today)|(tools)|(top)|(town)|(toys)|(trade)|(travel)|(tv)|(tw)|(us)|(vc)|(video)|(vip)|(wang)|(watch)|(website)|(wiki)|(win)|(work)|(ws)|(xin)|(xyz)|(gg)|(zone)|(中国)|(信息)|(公司)|(在线)|(移动)|(网址)|(网店)|(网络))$/;
	}else{//邮箱
		var partten = /^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+((\.[a-zA-Z0-9_-]{2,3}){1,2})$/;
	}
    if (!partten.test(str)){
        return false;
    }else{
		return true;
	}
}


function filterStr(str){
	if(str.length>100){
			str = str.substring(0,100);
	}
	var pattern = new RegExp("[`~!@#$^&*()=|{}':;',\\[\\].<>/?~！@#￥……&*（）——|{}【】‘；：”“'。，、？%+_]");
	var specialStr = "";
	for(var i=0;i<str.length;i++)
	{
		 specialStr += str.substr(i, 1).replace(pattern, '');
	}
	return specialStr;
}

function readmeta(str){ //获取META
	return $("meta[name="+str+"]").attr("content");
}

layer_photos = function(obj){
	var obj=arguments[0] || '.bfiles';
	if($(obj).length>0){
		layer.photos({
		photos: obj
		,shift: 5
		});
	}
}


Aform = function(type,data,callback,as){
	var as = arguments[3] || false;
	$.ajax({
			  type: "POST",
			  url: "/Aform/index/"+type,
			  async: as,
			  data: data,
			  dataType: "json",
			  success: function(datas) {
				if(callback){
					callback(datas);
				}else{
					Rs(datas);
				}
			},error: function(){
			layer.closeAll('loading');
			layer.msg('网络异常，请重试！');
			return false;
			}
	});
}

dform = function(datas,type,callback){
	$.ajax({
		type: "POST",
		url: "/Dform/"+type,
		async: true,
		data: datas,
		dataType: "json",
		success: function(datas) {
			if(callback){
				callback(datas);
			}else{
				Rs(datas);
			}
		},error: function(){
			layer.closeAll('loading');
			layer.msg('网络异常，请重试！');
			return false;
		}
	});
}

Rs = function(datas){
	 layer.closeAll('loading');
	  switch(datas.state){
	  case -1:
		  layer.tips(datas.info,datas.element,{tips:[datas.tips||1,datas.color||'#f90'],time:datas.time||4000});$(datas.element).val('').focus();
	      if(datas.fun) eval(datas.fun);
	  break;
	  case -2:
		 layer.confirm(datas.info || '登录失效，请重新登录！', {icon:datas.icon||8,btn:[(datas.btn1||'登陆'),(datas.btn2||'取消')]}, function(index){
		     datas.url?location.href=datas.url:layer_login();
		     layer.close(index);
		 },function(index){
		     layer.close(index);
		 });
		 if(datas.fun) eval(datas.fun);
	  break;
	  case -3:
	      if(datas.fun) eval(datas.fun);
	  break;
	  case 301:
		  location.href=datas.url;
	  break;
	  default:
		  if(!datas.clear)layer.alert(datas.info,{icon:datas.state}, function(index){(datas.fun)?eval(datas.fun)+layer.close(index):((datas.url)?((datas.url==1)?location.reload():location.href=datas.url):((datas.element)?$(datas.element).val('').focus()+layer.close(index):layer.close(index)));});
	  }
}

//弹出层
layer_ly = function(html,tit,sc,w,h){
	var t=arguments[1] || false,sc=arguments[2] || false,w=arguments[3] || 'auto',h=arguments[4] || 'auto';
	layer.open({
		type: 1,
		title: tit,
		closeBtn: 1,
		shade: [0.05, '#000'],
		area: [w,h],
		shadeClose: sc,
		content: html
	});
	layer.closeAll('loading');
}


//弹出iframe
layer_iframe = function(f,t,w,h,s,c){
	var h=arguments[3] || '100px',s=arguments[4] || 'no',c=(c)?true:false;
	layer.open({
		type: 2,
		title:t,
		shadeClose:c,
		shade: [0.05, '#000'],
		area: [w,h],
		content: ['/'+f, s],
		success: function(layero, index){
			if(h=='100px'){window[layero.find('iframe')[0]['name']].iframeautos(index);} //得到iframe页的窗口对象，执行iframe页的方法：iframeWin.method();
		}
	});
}


function iframeautos(index){
	$height=$(document).height()+44;
	if($height>$(window.parent.window).height()){$height=parseInt($(window.parent.window).height()*0.9);}
	$top=(($(window.parent.window).height()-$height)/2)+"px";
	parent.layer.iframeAuto(index);
	parent.layer.style(index,{height:$height+"px",top:$top});
}


Player_alert= function(t,i,c){
	layer.closeAll();
	layer.alert(t,{icon:i}, function(index){(c==1)?location.reload():((c==0)?layer.close(index):window.location.href=c);});
}

function attArr(is){
	var attArr = is.attributes,arr = [];
	for (var i=0;i<attArr.length;i++)
	{
			arr.push(attArr[i].name+"="+attArr[i].nodeValue.replace(/[~'!<>@#$%^&*()-+_=:]/g,""));
	}
	return arr.join("&");
}


list = function(page){
		var page = arguments[0] || 0,loading= layer.load();
		if(page>1){
			scTop(".mwz");
		}else if(page=='R'){
			page = parseInt($('.ohave').html()) || 0;
			page = page+"&Rf=1";
		}
		$.ajax({
		  type: "POST",
		  url : "/Apage/",
		  async: true,      //是否同步
		  data : $("form *[value!='']").serialize()+"&page="+page,
		  dataType: "json",
		  success: function(result) {
			if(result.state==-2){
				Rs(result);
				return false;
			}
			$.each(result,function(key,val){
				$("."+key).empty();
				$("."+key).html(val);
			});
			Cxchange();
			layer.close(loading);
		  },error: function(){
			layer.msg('网络异常，请重试！');
			layer.close(loading);
			return false;
		  }
		});
}
$(".message_a").live('click', function(){
	$(this).removeClass('message_a');
	bulk('batch=message_read&id='+$(this).attr('data_id'));
})

function message_operate(i,n,b,s){
	var data = getFormJson();
	data['action'] = $(i).attr('action');
	data['batch'] = arguments[2] || data['batch'];
	data['scene'] = arguments[3] || 0;
	if(n!=0){
		if(data.C1 === undefined){
			layer.alert('至少选择一条操作对象',{icon:5});return false;
		}
	}else{
		 data['all'] = 1;
	}
       bulk(data);
}


function bulk(str){
          $.ajax({
              type: "POST",
              url: "/execute/routine/",
              async: false,      //异步
              data: str,
			  dataType: "json",
              success: function(datas){
				Rs(datas);
				return false;
              }, error: function(){
              layer.msg('网络异常，请重试');
              return false;
		      }
          });
}

$(".cart_delete").live('click', function(){
	var $this=$(this),number=$this.attr('number'),mode = typeof($(this).attr("mode"))=="undefined"?0:1;
	Aform("cart_delete",'&number='+number+'&mode='+mode,function(datas){
		if(mode==1){
			var $dl=$this.closest("dl"),$obj=($dl.children("ul").length>1)?$this.closest("ul"):$dl;
			$obj.slideUp(500,function(){$(this).remove();carmoney();});
		}else{
			$.each(datas,function(key,val){
			if($('.'+key).length>0) $("."+key).html(val);
			});
			$this.closest("dd").slideUp(500,function(){$(this).remove();});
			if(typeof(datas.cart_data)!="undefined") $('.top_cart').slideDown(500,function(){$('.top_cart dt').remove();$(this).append(datas.cart_data);})
		}
		Rs(datas);
	})
});

$(".cart_empty").live('click', function(){
	var $this=$(this),mode = typeof($(this).attr("mode"))=="undefined"?0:1;
	layer.confirm("确定要<strong style='color:red'>清空购物车</strong>吗？",{icon:3}, function(index){
		Aform("cart_empty",'&mode='+mode,function(datas){
			if(datas.state==1){
				$.each(datas,function(key,val){ 
						if($('.'+key).length>0) $("."+key).html(val);
				});
				(mode==1)?(carmoney())+($this.remove()):layer.msg('购物车已清空！',{offset:'t',icon:1});
			}
			layer.close(index);
		})
	});
});

function getDate(addtime){
    var d = new Date(addtime);
    return d.toISOString().substr(0, 10) + ' ' + d.toTimeString().substr(0, 5);
}

    
//倒计时函数
function updateEndTime(){
	var date = new Date();
	var time = date.getTime();

	$(".ttime").each(function(i){
	var endDate =this.getAttribute("endTime"); //结束时间字符串
	var endDate1 = eval('new Date('+ endDate.replace(/\d+(?=-[^-]+$)/, function (a) { return parseInt(a, 10) - 1; }).match(/\d+/g) + ')');
	var endTime = endDate1.getTime();
	var lag = (endTime - time) / 1000;   
	if(lag > 0)
	{var second = Math.floor(lag % 60); 
	var minite = Math.floor((lag / 60) % 60);
	var hour = Math.floor((lag / 3600) % 24);
	var day = Math.floor((lag / 3600) / 24);
	$(this).html("<span class='bold'>"+day+"</span>天"+"<span  class='bold'>"+hour+"</span>小时"+"<span  class='bold'>"+minite+"</span  class='bold'>分"+"<span  class='bold'>"+second+"</span>秒");
	}
	else
	$(this).html("已结束");
	});
	setTimeout("updateEndTime()",1000);
}

function toDecimal2(x) {    
	var f = parseFloat(x);    
	if (isNaN(f)) {    
		return false;    
	}    
	var f = Math.round(x*100)/100;    
	var s = f.toString();    
	var rs = s.indexOf('.');    
	if (rs < 0) {    
		rs = s.length;    
		s += '.';    
	}    
	while (s.length <= rs + 2) {    
		s += '0';    
	}    
	return s;    
}

function ifstr(str,key){
    rs=(str.indexOf(key)!=-1)?true:false; 
	return rs;    
}
function CheckHzurl(u) {
    var RegUrl = new RegExp();
    RegUrl.compile("^(http|https)://[A-Za-z0-9]{1,8}\.huzhan\.com?[A-Za-z0-9-_%&\?\/.=]+$");
    if (!RegUrl.test(u)) {
        return false;
    }
    return true;
}

function getFormJson(form) {
	var form=arguments[0] || "form *[value!='']";
	var o = {};
	var a = $(form).serializeArray();
	$.each(a, function () {
		if (o[this.name] !== undefined) {
			if (!o[this.name].push) o[this.name] = [o[this.name]];
			o[this.name].push(this.value || '');
		} else {
			o[this.name] = this.value || '';
		}
	});
	return o;
}

function usize(limit){  
    var limit=limit.toLowerCase();//转换为小写     
    if(limit.indexOf('b')==-1){ //如果无单位,加单位递归转换  
        limit=limit+"b";         
    }  
    var reCat=/[0-9]*[a-z]b/;  
    if(!reCat.test(limit)&&limit.indexOf('b')!=-1){ //如果单位是b,转换为kb加单位递归  
        limit=limit.substring(0,limit.indexOf('b')); //去除单位,转换为数字格式  
        limit=Math.ceil((limit/1024))+'kb';
    }  
    if(limit.indexOf('kb')!=-1&&limit.length>5){ //如果为kb,转换为mb加单位递归  
        limit=limit.substring(0,limit.indexOf('kb')); //去除单位,转换为数字格式  
        limit=Math.ceil((limit/1024))+'mb';  
    }  
    if(limit.indexOf('mb')!=-1&&limit.length>5){ //如果为mb,转换为gb加单位递归  
        limit=limit.substring(0,limit.indexOf('mb'));//去除单位,转换为数字格式  
        limit=Math.ceil((limit/1024))+"gb";  
    }  
    if(limit.indexOf('gb')!=-1&&limit.length>5){ //如果为gb,转换为tb加单位递归  
        limit=limit.substring(0,limit.indexOf('gb'));//去除单位,转换为数字格式  
        limit=Math.ceil((limit/1024))+"tb";
        return limit;       //tb为最大单位转换后跳出  
    }         
    return limit;  
}  

progress = function(is){ 
	layer.closeAll('loading'); //关闭加载层
	$("form").keypress(function(e) {
		if(e.which == 13) {
		return false;
		}
	});
	if($('#progress').length==0){
		$("body").append("<div id=\"progress\"><span id=\"progress-bar\" ><span id=\"progress-in\" style=\"width:1%\"></span></span><br><span id=\'progress-tisp\'>正在上传数据（<span id=\"progress-upload\">1</span>/<span id=\"progress-uploaded\">0</span>），请稍等…</span><ul><strong>回执信息：</strong></ul></div>");
	}
	var index = layer.open({
		type: 1,
		shade: [0.02, '#000'],
		title: false, //不显示标题
		closeBtn: 0,
		content: $('#progress')
	});
	if(is){
		$('#progress-tisp').html("数据正在提交中，请稍等…");
		$('#progress-in').width("100%");
		return false;
	}
}


function scTop(is,add){
	if(!add){add=0;}
	$("html, body").animate({scrollTop: $(is).offset().top-add}, 800);
}

function Cxchange(){
	$(":checkbox[name=C1]:not(:disabled):not(:checked)").length>0 || $(":checkbox[name=C1]:not(:disabled)").length<=0?Cchange($(":checkbox[name=xuan]"),false):Cchange($(":checkbox[name=xuan]"),true);
}

function Cchange(data,is){
	data.attr("checked",is);
	if(typeof(form)!=="undefined") form.render('checkbox'); //刷新渲染select
}

function check_login(go_url){
	$.ajax({
        url:"/Ajax/check_login",
        type:'post',
        dataType:'json',
        success:function(data){
            if (data.status == 1) { //登录状态
                window.location.href = go_url;

            } else{//非登录状态
                window.location.href = "/Account/login/pre_url/" + data.pre_url;
                layer.msg(data.msg);
            }
        },
    });
}


function user_receipt_goods(obj){
    var id = $(obj).attr('rel');
    layer.confirm('确定要确认收货吗？', {
        title:"确认收货",
        btn: ['确认','点错'] //按钮
    }, function(){
        $.ajax({
            type:'post',
            url:"/Ajax/user_receipt_goods",
            dataType:'json',
            data:{id:id},
            success:function(data){
                if(data.status == 1){
                    layer.alert(data.msg);
                    window.location.reload();
                }else{
                    layer.alert(data.msg);
                }
            },
        })
    }, function(){

    });
}


function seller_delivery_goods(obj){
    var id = $(obj).attr("rel");
    layer.confirm('确定要确认发货吗？', {
        title:"确认发货",
        btn: ['确认','点错'] //按钮
    }, function(){
        $.ajax({
            type:'post',
            url:"/Ajax/seller_delivery_goods",
            dataType:'json',
            data:{id:id},
            success:function(data){
                if(data.status == 1){
                    layer.alert(data.msg);
                    window.location.reload();
                }else{
                    layer.alert(data.msg);
                }
            },
        })
    }, function(){

    });
}


// 收藏函数
function sc(obj,change_type){
    var pid = $(obj).attr("rel");
    var type = $(obj).attr("data_type");
    var status = $(obj).attr("data_status");

    _this = $(obj);
    if (status == 1) {
        layer.msg("您已经收藏过了",{icon:2,time:2000});
        return;
    }
    $.ajax({
        type: "post",
        url: "/Ajax/collect_ajax",
        dataType: "json",
        data: {pid:pid,type:type},
        success: function(data) {
            if (data.status == '1') {
                if(change_type == 1){
                	_this.empty().html("<i class='iconfont icon-shoucang-shoucang'></i>已收藏");
                	_this.attr("data_status",1);
                }else if(change_type == 2){
                	_this.empty().html("已收藏");
                	_this.attr("data_status",1);
                }
                layer.msg(data.msg,{icon:1,time:2000});
            } else if(data.status == '-1'){
                layer.msg(data.msg,{icon:2,time:2000});
                setTimeout(window.location.href = "/Account/login/pre_url/" + data.pre_url,2000);
            }else{
                layer.msg(data.msg,{icon:2,time:2000});
            }
        },
    })
}


(function($){//最新交易
$.fn.extend({
        Scroll:function(opt,callback){
                //参数初始化
                if(!opt) var opt={};
                var _btnUp = $("#"+ opt.up);//Shawphy:向上按钮
                var _btnDown = $("#"+ opt.down);//Shawphy:向下按钮
                var timerID;
                var _this=this.eq(0).find("ul:first");
                var     lineH=_this.find("li:first").height()+5, //获取行高
                        line=opt.line?parseInt(opt.line,10):parseInt(this.height()/lineH,10), //每次滚动的行数，默认为一屏，即父容器高度
                        speed=opt.speed?parseInt(opt.speed,10):3000; //卷动速度，数值越大，速度越慢（毫秒）
                        timer=opt.timer //?parseInt(opt.timer,10):3000; //滚动的时间间隔（毫秒）
                if(line==0) line=1;
                var upHeight=0-line*lineH;
                //滚动函数
                var scrollUp=function(){
                        _btnUp.unbind("click",scrollUp); //Shawphy:取消向上按钮的函数绑定
                        _this.animate({
                                marginTop:upHeight
                        },speed,function(){
                                for(i=1;i<=line;i++){
                                        _this.find("li:first").appendTo(_this);
                                }
                                _this.css({marginTop:0});
                                _btnUp.bind("click",scrollUp); //Shawphy:绑定向上按钮的点击事件
                        });

                }
                //Shawphy:向下翻页函数
                var scrollDown=function(){
                        _btnDown.unbind("click",scrollDown);
                        for(i=1;i<=line;i++){
                                _this.find("li:last").show().prependTo(_this);
                        }
                        _this.css({marginTop:upHeight});
                        _this.animate({
                                marginTop:0
                        },speed,function(){
                                _btnDown.bind("click",scrollDown);
                        });
                }
               //Shawphy:自动播放
                var autoPlay = function(){
                        if(timer)timerID = window.setInterval(scrollUp,timer);
                };
                var autoStop = function(){
                        if(timer)window.clearInterval(timerID);
                };
                 //鼠标事件绑定
                _this.hover(autoStop,autoPlay).mouseout();
                _btnUp.css("cursor","pointer").click( scrollUp ).hover(autoStop,autoPlay);//Shawphy:向上向下鼠标事件绑定
                _btnDown.css("cursor","pointer").click( scrollDown ).hover(autoStop,autoPlay);

        }       
})




})(jQuery);
