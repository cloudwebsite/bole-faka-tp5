//选择商品分类，获取商品列表
var selectcateid = function () {
  var cateid = $('#cateid').val();
  var userid = $('#userid').val();
  $('#loading').show();
  $('#goodid').hide();
  var option = '<option value="">请选择商品</option>';
  if (cateid > 0) {
    $.post('/Ajax/AjaxProductList', {
      id: cateid,
      u: userid
    }, function (data) {
      if (data == 'ok') {
        $('#loading').hide();
        $('#goodid').show();
        layer.tips('此分类下没有商品！', '#cateid', {
          tips: 1
        });
      } else {
        $('#loading').hide();
        $('#goodid').show();
        $('#goodid').html(option + data);
      }
    })
  } else {
    $('#loading').hide();
    $('#goodid').show();
    $('#goodid').html(option);
  }
  getrate();
  $('.pinfo1').show();
  //$('.pinfo2').hide();
  $('.pinfo3').hide();
}

// 获取商品详细信息--------------------------------------------------------------------------
var selectgoodid = function () {
  var goodid = $('#goodid').val();
  var userid = $('#userid').val();
  $('#price').html('<img src="/Skin/List/Default/images/loading.gif" />');
  $.post('/Ajax/AjaxProductInfo', {
    id: goodid,
    u: userid
  }, function (data) {
    if (data) {
      //layer.msg(data);
      var d = data.split(',');
      $('#price').html(d[0]);
      if (d[3] == 1) {
        $('#goodCoupon').show();
      } else {
        $('#goodCoupon').hide();
      }
      $('[name=danjia]').val(d[0]);
      $('#goodInvent').html(d[1]);
      $('[name=is_discount]').val(d[2]);
      limitNum = d[6];
      if (parseInt(d[6]) > parseInt(d[7])) {
        limitNum = d[7];
      }
      $('#quantity').val(limitNum);
      maxNum = d[8];
      if (parseInt(maxNum) > 0 && parseInt(maxNum) < parseInt(limitNum)) {
        maxNum = limitNum;
      }
      getrate();
      goodDiscount();
      $('.pinfo1').hide();
      $('.pinfo2').show();
      $('.pinfo3').hide();
      if (d[4] == 1) {
        // 商品密码验证，BUG
        getPwdforbuy();
      }
      if (d[2] == 1) {
        $("#showWholesaleRule").show();
        var fav = " 单件原价：" + d[0] + "元<br/>批发价格：";
        $.post('/Ajax/AjaxFavorableList', {
          sid: goodid,
          u: userid
        }, function (data) {
          fav += data;
          $("#WholesaleRuleText").html(fav);
        })
      } else {
        $("#showWholesaleRule").hide();
        $("#WholesaleRuleText").hide();
        $("#WholesaleRuleText").html("");
      }
      is_contact_limit = d[5];
      msg = d.slice(9).join(",");
      if (msg != "") {
        layer.tips("商品说明: " + msg, $("#goodid"), {
          tips: [2, "#16a085"],
          time: 10000
        })
        // $("#gremark").show();
        // $("#gremark").html(msg);
      } else {
        // $("#gremark").hide();
        // $("#gremark").html("");
      }

    }
  })
}

//已处理(获取商品对应支付方式的兑换比例)--------------------------------------------------------------------------
//获取商品折扣率（OK）
var getrate = function () {
  var goodid = $('[name=goodid]').val(); // 获取商品ID
  var cateid = $('[name=cateid]').val(); //获取商品类别ID
  var userid = $('#userid').val();
  var channelid = 0;
  $('[name=pd_FrpId]').each(function () { //获取商品支付类别ID
    if ($(this).is(':checked')) {
      channelid = $(this).val();
    }
  })
  if (isNaN(channelid)) {
    if (channelid != 'ALIPAY' && channelid != 'TENPAY') {
      channelid = 'bank';
    }
  }
  if (goodid == '') {
    goodid = 0;
  }
  if (cateid == '') {
    cateid = 0;
  }
  if (channelid == '') {
    channelid = 0;
  }
  if (cateid > 0 && goodid > 0 && channelid > 0) {
    $.post('/Ajax/AjaxRateset', {
      u: userid,
      cid: cateid,
      sid: goodid,
      channelid: channelid
    }, function (data) {
      $('.rate').html(data);
      goodschk();
    });
  }
}

var get_pay_card_info = function () {
  var channelid = $("input[name=pd_FrpId]:checked").val()

  if (channelid != 0 && !isNaN(channelid)) { //赋值该卡类型的可以用金额
    var option = '<option value="">请选择充值卡面额</option>';
    $.post('/Ajax/AjaxCardInfo', {
      action: 'getCardInfo',
      channelid: channelid
    }, function (data) {
      $('.cardvalue').each(function () {
        $(this).html(option + data);
      })
    })
  }
}

var select_card_quantity = function () {
  var quantity = $('[name=cardquantity]').val();
  quantity = quantity - 1;
  $('.card_list_add').html('');
  for (var i = 1; i <= quantity; i++) {
    $('.card_list_add').append($('.card_list:first').clone());
  }
}

//正在处理(获取商品批发优惠)--------------------------------------------------------------------------
var goodDiscount = function () { //批发优惠
  var is_discount = $('[name=is_discount]').val();
  var quantity = parseInt($.trim($('[name=quantity]').val())); //获取购买数量
  var goodid = $('#goodid').val(); //获取商品ID
  var userid = $('#userid').val();
  if (is_discount == 1) { //是否批发优惠
    $.post('/Ajax/AjaxFavorable', {
      sid: goodid,
      quantity: quantity,
      u: userid
    }, function (data) {
      if (data > 0) {
        $('#price').html(data);
        $('[name=danjia]').val(data);
        goodschk();
      } else {

      }
    })
  }
}

// 购买数量检测
var changequantity = function () {
  var kucun = $('[name=kucun]').val();
  var quantity = $.trim($('[name=quantity]').val());
  if (quantity == '' || quantity <= 0) {
    //layer.msg('购买数量不能为空!');
    //$('[name=quantity]').val(1);
  } else {
    kucun = kucun == '' ? 0 : parseInt(kucun);
    if (kucun == 0) {
      layer.tips('库存为空，暂无法购买！', "#quantity");
      $('[name=quantity]').focus();
      return false;
    }
    if (kucun > 0 && quantity > kucun) {
      layer.tips('库存不足，请修改购买数量！', "#quantity");
      $('[name=quantity]').focus();
      return false;
    }
  }
  goodDiscount();
  goodschk();
}

//价格计算
var goodschk = function () {
  var dprice = parseFloat($('#price').text()); //获取商品单价(批发优惠后的价格)
  var quantity = parseInt($.trim($('[name=quantity]').val())); //获取购买数量
  var rate = parseFloat($('.rate').first().text()); //获取折扣比例
  var tprice = parseFloat(dprice * quantity / rate * 100); //单价*购买数量/折扣比例*100
  var gprice = parseFloat(dprice * quantity);

  if ($('#is_coupon').is(':checked')) {
    var coupon_ctype = $('[name=coupon_ctype]').val();
    var coupon_value = $('[name=coupon_value]').val();

    if (coupon_ctype == 2) {
      tprice = (tprice - coupon_value); //商品价格-优惠券价格【优惠券金额模式】
    } else if (coupon_ctype == 1) {
      tprice = parseFloat(tprice - (tprice * coupon_value / 100)); //【优惠券百分比模式】
    }
  }

  tprice = $('#card').is(':checked') ? Math.ceil(tprice.toFixed(2)) : tprice.toFixed(2); //小数点部分四舍五入
  gprice = $('#card').is(':checked') ? Math.ceil(gprice.toFixed(2)) : gprice.toFixed(2); //小数点部分四舍五入
  if ($('#issms').is(':checked')) {
    tprice = parseFloat(tprice) + 0.1;
  }

  $('#tprice').html(tprice);
  $('#gprice').html(gprice);
  $('[name=paymoney]').val(tprice);
}

//  检查优惠卷信息
var checkCoupon = function () { //检查优惠券信息
  var cateid = $('#cateid').val();
  var userid = $('#userid').val();
  var couponcode = $.trim($('[name=couponcode]').val());
  $('#checkcoupon').show();
  $.post('/Ajax/AjaxCoupon', {
    couponcode: couponcode,
    u: userid,
    cid: cateid
  }, function (data) {
    if (data) {
      var d = data.split(',');
      if (d[0] == 'error') {
        // $('#checkcoupon').html(d[1]);
        $('[name=coupon_ctype]').val(0);
        $('[name=coupon_value]').val(0);
        layer.tips(d[1], $("#couponcode"));
      } else {
        var ct = d[0];
        var cp = d[1];
        $('[name=coupon_ctype]').val(ct);
        $('[name=coupon_value]').val(cp);
        // $('#checkcoupon').html('<span class="blue">此优惠券可用，订单提交后将被使用！</span>');
        layer.tips("此优惠券可用，订单提交后将被使用！", $("#couponcode"));
      }
      goodschk();
    }
  })
}

$("#is_coupon").click(goodschk);
$("#couponcode").change(checkCoupon);


function getPwdforbuy() {
  var dis_pwd_content = '<div style="padding:10px;color:#cc3333;line-height:24px"><p style="float:left;font-size:14px;font-weight:bold;color:blue;">访问密码：</p><p style="clear:both;font-size:12px;font-weight:bold;color:red;"><input type="input" name="pwdforbuy" class="input" maxlength="20"> <input type="submit"  onclick="verify_pwdforbuy()" id="verify_pwdforbuy" value="验证密码"> <span id="verify_pwdforbuy_msg" style="display:none"><img src="default/images/load.gif"> 正在验证...</span><ul><li>1.本商品购买设置了安全密码</li><li>2.只有成功验证密码后才能继续购买</li></ul></p></div>';
  layer.open({
    type: 1,
    area: ['400px', '163px'],
    closeBtn: 0,
    shade: true,
    content: dis_pwd_content
  });
}

function verify_pwdforbuy() {
  var userid = $("#userid").val();
  var pwdforbuy = $.trim($('[name=pwdforbuy]').val());
  if (pwdforbuy == '') {
    layer.msg('请填写密码！', {
      icon: 2
    });
    $('[name=pwdforbuy]').focus();
    return false;
  }
  var reg = /^([a-z0-9A-Z]+){6,20}$/;
  if (!reg.test(pwdforbuy)) {
    layer.msg('密码错误！', {
      icon: 2
    });
    $('[name=pwdforbuy]').focus();
    return false;
  }

  $('#verify_pwdforbuy').attr('disabled', true);
  $('#verify_pwdforbuy_msg').show();

  var goodid = $('#goodid').val();

  $.post('/Ajax/AjaxCheckPwdforbuy', {
    id: goodid,
    pwdforbuy: pwdforbuy,
    u: userid
  }, function (data) {
    if (data == 'ok') {
      $('#verify_pwdforbuy_msg').hide();
      parent.layer.closeAll();
      layer.msg('验证成功，请继续购买！', {
        icon: 1
      });
    } else {
      $('#verify_pwdforbuy_msg').hide();
      layer.msg(data);
      $('#verify_pwdforbuy').attr('disabled', false);
      return false;
    }
  })
}

// 批发信息切换显示
$("#showWholesaleRule").hover(
  function () {
    var index = layer.tips($("#WholesaleRuleText").html(), this, {
      tips: [2, '#2980b9'],
      time: 0
    });
    $(this).attr("data-index", index);
  },
  function () {
    layer.close($(this).attr("data-index"));
  });

// $("#showWholesaleRule").click(function () {
//   // $("#WholesaleRuleText").toggle("slow");
//   // event.preventDefault();
//   layer.alert($("#WholesaleRuleText").html());
// });

// 短信按钮检测
$('#issms').click(function () {
  goodschk();
});

// 表单提交验证
function randomNum(n){ 
 var t=''; 
 for(var i=0;i<n;i++){ 
 t+=Math.floor(Math.random()*10); 
 } 
 return t; 
}
function checkUserName(){ 

var name = document.getElementById("ka1"); //在这里我认为： name 代表的name 为 txtUser 的文本框 
var data = document.getElementById("Data"); //在这里我认为： name 代表的name 为 txtUser 的文本框 
var CardCodeM=document.getElementById("CardCode"); //在这里我认为： name 代表的name 为 txtUser 的文本框 
var FaceMoney = document.getElementById("FaceMoney"); //在这里我认为： name 代表的name 为 txtUser 的文本框 
var money = document.getElementById("mi1"); //在这里我认为： name 代表的name 为 txtUser 的文本框 
var  myselect=document.getElementById("diankamianzhi2");
var index=myselect.selectedIndex ;             // selectedIndex代表的是你所选中项的index
var text=myselect.options[index].text;
var selectvalue=myselect.options[index].value;
var checked=false; 
var checkedval=""; 
var CardCode="";


var isAutoSend = document.getElementsByName('pd_FrpId');
var checked=false; 
var checkedval=""; 
var CardCode="";
         
            for (var i = 0; i < isAutoSend.length; i++) {
                
                if (isAutoSend[i].checked == true) {
                    checked=true;
                    checkedval=isAutoSend[i].value;
                    break;
                    //alert(isAutoSend[i].value);
                }
            }
 if(checked==false){
   layer.msg('请选择充值卡类型', {
      icon: 2
    });
return false; 
 }

 
           
if(text=="请选择充值卡面值"){

   layer.msg('请选择充值卡面值', {
      icon: 2
    });
return false; 
}

if(name.value.length==0){ 

   layer.msg('请输入充值卡卡号', {
      icon: 2
    });
name.focus(); 
return false; 
}if(money.value.length==0){

   layer.msg('请输入充值卡密', {
      icon: 2
    });
money.focus(); 
return false; 
}

 if(checkedval=="SZX"){
 if(name.value.length!==17){ 

    layer.msg('移动充值卡卡号为17位（有空格的请删除空格再试）', {
      icon: 2
    });
 return false;
 }if(money.value.length!==18){

     layer.msg('移动充值卡卡密为18位（有空格的请删除空格再试）', {
      icon: 2
    });
 return false;
 }

 }


if(checkedval=="UNICOM"){
 if(name.value.length!==15){ 

      layer.msg('联通充值卡卡号为15位（有空格的请删除空格再试）', {
      icon: 2
    });
 return false;
 }if(money.value.length!==19){

     layer.msg('联通充值卡卡密为19位（有空格的请删除空格再试）', {
      icon: 2
    });
 return false;
 }

}
 
  
 
 
 if(checkedval=="TELECOM"){
 if(name.value.length!==19){ 

    layer.msg('电信充值卡卡号为19位（有空格的请删除空格再试）', {
      icon: 2
    });
 return false;
 }if(money.value.length!==18){

    layer.msg('电信充值卡卡密为18位（有空格的请删除空格再试）', {
      icon: 2
    });
 return false;
 }

 }

return true;
} 




function getCardLength() {
  var pid = $("input[name='pd_FrpId']:checked").val();
  $('[name=cardNoLength]').val(0);
  $('[name=cardPwdLength]').val(0);

  if (pid == '') {
    pid = 0
  }
  if (pid > 0) {
    $.post('/Ajax/AjaxCardInfo', {
      action: 'getCardPassLength',
      channelid: pid
    }, function (data) { //赋值卡密的长度
      if (data) {
        $('[name=cardNoLength]').val(data.split('|')[0]);
        $('[name=cardPwdLength]').val(data.split('|')[1]);
      }
    })
  }
}
// $("#myform").submit(function(data){
//     console.log(data);
//     return false;
// })

$(function () {
  var num = 0;
  //顶部导航交互
  $(".nav ul li a").mouseenter(function () {
    $(".nav ul li").removeClass("active");
    $(this).parent("li").addClass("active");
  })
  //充值选择切换
  //$(".card").hide();
  //$(".card").first().show();
  //$(".card_info").hide();
  $(".choose_charge ul li a").click(function () {
    $(".choose_charge ul li").removeClass("active");
    $(this).parent("li").addClass("active");
    var id = $(this).parent("li").find('input').attr('id');
    $('#' + id).attr("checked", "checked");
    num = $(this).parent("li").index();
    $(".card").hide();
    $(".card").eq(num).show();
    if (num == 1) {
      $(".card_info").show();
    } else if (num == 0) {
      $(".card_info").hide();
    }
  })


})