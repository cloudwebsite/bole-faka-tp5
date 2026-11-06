$(function() {
	var a = 100;
	$(".person_wallet_recharge .ul li").click(function(e) {
		$("#money").html("0.00");
		$("#buynum").val("");
		$("#remark").html("");
	
		var mpid = $(this).children("[name=mpid]").val();
		var countnum = $(this).children("[name=countnum]").val();
		var mprice = $(this).children("[name=mprice]").val();
		var xqnotice = $(this).children("[name=xqnotice]").val();
		var mmin = $(this).children("[name=mmin]").val();
		var mmax = $(this).children("[name=mmax]").val();
		var sendbeishu = $(this).children("[name=sendbeishu]").val();
		$(this).addClass("current").siblings("li").removeClass("current");
		$(this).children(".sel").show(0).parent().siblings().children(".sel").hide(0);
		$('#mpid2').val(mpid);
		$('#mmin2').val(mmin);
		$('#mmax2').val(mmax);
		$('#sendbeishu2').val(sendbeishu);
		$('#kucun').html(countnum);
		if(mpirce<0.01){
			$("#price").html(toDecimal4(mprice));
		}else{
			$("#price").html(toDecimal2(mprice));
		}

		$("#remark").html(xqnotice);
	});

	$("#submit").click(function(e) {
		var buynum = $("#buynum").val();
		if(!$(".person_wallet_recharge .ul li").hasClass('current')) {
			layer.open({
				content: '请选择商品<br>',
				btn: '我知道了',
				time: 30 //2秒后自动关闭
			});
			return false;
		}

		if($(".person_wallet_recharge .ul li").hasClass('current')) {
			if(buynum <= 0 || !buynum) {
				layer.open({
					content: '请输入正确的购买数量',
					btn: '我知道了',
					time: 30 //2秒后自动关闭
				});
				
				return false;
			}
		}
		
		if(!checkform()){
			return false;
		}
		
		var contact = $('#lianxi').val();
		if(contact.length != 0) {
			var reg = /^(\d){11}$/;
			if(!reg.test(contact)) {
				layer.open({
					content: '请填写正确的联系手机<br>',
					btn: '我知道了',
					time: 30 //2秒后自动关闭
				});
				
				return false;
			}
		}

		var money = $("#money").html();
		var buynum = $("#buynum").val();
		$('[name=p3_Amt]').val(money);
		$('[name=p2_Order]').val(getOrder());
		var mpid = $('#mpid2').val();
		$('[name=p5_Pid]').val(mpid);
		$('[name=lianxi]').val(contact);
    	var openid=$('[name=openid]').val();
    	var isweixin={$isweixin};
    	if(money<0.01){
    		layer.open({
					content: '购买金额必能小于0.01元',
					btn: '我知道了',
					time: 30 //2秒后自动关闭
				});
				
				return false;
    	}
    	
    	if(isweixin==1){
    		$.actions({
			actions: [{
				text: "微  &nbsp;&nbsp;信1",
				onClick: function() {
					//do something
					$('[name=pd_FrpId]').val("gzhcode");
					$('[name=pa_MP]').val("gzhcode|" + contact + "||" + parseInt(buynum)+"|"+openid);
					$("#frm").submit();
				},
				className: "color-primary"
			}],
			title: "请选择支付方式"
			});
    	}else{
    		$.actions({
			actions: [{
				text: "微  &nbsp;&nbsp;信2",
				onClick: function() {
					//do something
					$('[name=pd_FrpId]').val("wxwap");
					$('[name=pa_MP]').val("wxwap|" + contact + "||" + parseInt(buynum)+"|"+openid);
					$("#frm").submit();
				},
				text: "支付宝",
				onClick: function() {
					//do something
					$('[name=pd_FrpId]').val("alipaywap");
					$('[name=pa_MP]').val("alipaywap|" + contact + "||" + parseInt(buynum)+"|"+openid);
					$("#frm").submit();
				},
				className: "color-primary"
			}],
			title: "请选择支付方式"
		});
    	}
    	
		
		return false;
	});

	function getOrder() {
		//设定时间格式化函数
		Date.prototype.format = function(format) {
			var args = {
				"M+": this.getMonth() + 1,
				"d+": this.getDate(),
				"h+": this.getHours(),
				"m+": this.getMinutes(),
				"s+": this.getSeconds(),
			};
			if(/(y+)/.test(format))
				format = format.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
			for(var i in args) {
				var n = args[i];
				if(new RegExp("(" + i + ")").test(format))
					format = format.replace(RegExp.$1, RegExp.$1.length == 1 ? n : ("00" + n).substr(("" + n).length));
			}
			return format;
		};

		var order = new Date().format("yyyyMMddhhmmss") + randomNum(10);
		return order;
	}

	function randomNum(n) {
		var t = '';
		for(var i = 0; i < n; i++) {
			t += Math.floor(Math.random() * 10);
		}
		return t;
	}
});