/**
 * Created by 清水 on 2017/7/1.
 */

//签到
!(function($) {

    var arry = [
       ' <div class="qd-motel-box">',
        ' <div class="qd-motel-in">',
        ' <div class="qd_content">',
        ' <span class="qd_title">每日签到</span>',
        '  <div class="qd_content_in">',
        '  <div class="qd_num_icon qd_jf"></div>',
        '   <p class="color_y">第<span class="day_box"></span>天</p>',
        '  <p class="qd_success">签到成功积分 <span class="qd_jf"></span></p>',
        '  <p class="qd_success">明日签到获取积分 <span class="tomorrow_jf"></span></p>',
        '  </div>',
        '   <span class="qd_close_btn">确定</span>',
        '  </div>',
        '   </div>',
        '  </div>'

    ];


    function addMotal(ele, options) {
        this.ele = ele;

        this.opts = $.extend({}, $.fn.qd_motal.defaults, options);

    }

    var str;
    addMotal.prototype = {

        creatDom: function() {
            if (str) {
                return;
            }
            str = arry.join("");
            
            $(this.ele).append(str);

            $(this.ele).find('.qd-motel-box').show();//显示
            $(this.ele).find('.qd-motel-box').find('.qd_jf').text("+"+this.opts.num);//签到积分
            $(this.ele).find('.qd-motel-box').find('.tomorrow_jf').text("+"+this.opts.tomorrow_num);//签到积分

            $(this.ele).find('.qd-motel-box').find('.day_box').text(this.opts.day);//当前签到天数

            $(this.ele).find('.check-line').attr('href',this.opts.url);


        },
        eventFn: function() {

            var _this = this;
            $(this.ele).find('.qd-motel-box').on('click', function() {

                $(_this.ele).find('.qd-motel-box').remove();
                str = false;

            });

        }

    }

    $.fn.qd_motal = function(options) {
        var startApend = new addMotal(this, options);

        startApend.creatDom();

        startApend.eventFn();
    }
    $.fn.qd_motal.defaults = {num:4,url:"",day:1};

})(jQuery);


$(document).on('click','.qd_btn',function(){
    if($(this).hasClass("active")){
        return false;
    }
    _this = $(this);



    //签到函数应用
    /*
    *       num：代表签到赠送的积分
    *
    *       day：代表当前签到是第几天
    *
    * */

    $.ajax({
        url:"/Ajax/user_sign",
        type:'post',
        dataType:'json',
        success:function(data){
            if (data.status == 1) {
                _this.addClass("active");
                _this.html("已签到");
                $('body').qd_motal({num:data.today_sign,day:data.sign_day,tomorrow_num:data.tomorrow_sign});
            } else{
                
                layer.msg(data.msg, {icon: 2});
            }
        },
    });




    // var _this = $(this);
    // $.ajax({
    //     url:"/Ajax/user_sign",
    //     type:'post',
    //     dataType:'json',
    //     success:function(data){
    //         if (data['state'] == '1') {
    //             var point_num = _this.attr("data-point");
    //             $('body').qd_motal({num:point_num});
    //             _this.addClass("active").html("已签到");
    //             return false;
    //         }else {
    //             layer.msg('系统异常,请稍后再试!',{icon:2,time:3000});
    //             return false;
    //         }
    //     },
    // })

});

