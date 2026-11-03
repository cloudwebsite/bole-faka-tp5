/*===========================
 *baliwish Jifenyun 2016-10-17
===========================*/


function CheckItemAll(form){for(var i=0;i<form.elements.length;i++){if(form.elements[i].type=="checkbox"){if(form.elements[i].Name!="AllCheck"){form.elements[i].checked=form.CheckAll.checked}}}};

//商品数量的调整
function input_numbox(obj,mode){
var $objs = $(obj);
$objs.find("button").click(function(){
var min =  $(this).parent().attr("data-numbox-min");
var max =  $(this).parent().attr("data-numbox-max");
var number = $(this).parent().find(".input-number").val();

if(min !== "" || min !=="undefined") {min = parseInt(min);}

if($(this).hasClass("input-chevron-down")){
if (max !== undefined && number >= max){return;}
$(this).parent().find(".input-number").val(parseInt(number)+1);
}else {
if (min !== undefined && number <= min){return;}
$(this).parent().find(".input-number").val(parseInt(number)-1);
}

if (mode == 1){ //需要计算,购物车
var count = 0;
var counttall = parseInt($("#countall").html());
var $inputnumber = $(".input-number");

for(var i=0;i<=$inputnumber.length-1;i++){
count = $(".input-number:eq('"+ i +"')").val()*eval($(".danjia:eq('"+ i +"')").html());
$(".cart-action-total:eq('"+ i +"')").html(count);
counttall += count;
}
$("#countall").html(counttall);
}

}); 

}


//移除购物车
function removecart(obj){
	if (obj == undefined || obj == "") {return false;}
	if(confirm("删除的礼品不能及时恢复，确定删除吗？")){
		console.log(".cart-item-row"+obj);
		$(".cart-item-row"+obj).remove();
	}
}
//设置tr高度
$(function(){
	var talg=$(".order_table").length;
	for(var i=1;i<=talg;i++){
		var trlg=$(".order_table").eq(i-1).find("tr").length;
		$(".order_table").eq(i-1).find(".m3,.m4,.m5").attr("rowspan",trlg-1);
	}
})

