
/*
 * EASYDROPDOWN - A Drop-down Builder for Styleable Inputs and Menus
 * Version: 2.1.3
 * License: Creative Commons Attribution 3.0 Unported - CC BY 3.0
 * http://creativecommons.org/licenses/by/3.0/
 * This software may be used freely on commercial and non-commercial projects with attribution to the author/copyright holder.
 * Author: Patrick Kunka
 * Copyright 2013 Patrick Kunka, All Rights Reserved
 */

(function(d) {
    function e() {
        this.isField = !0;
        this.keyboardMode = this.hasLabel = this.cutOff = this.disabled = this.inFocus = this.down = !1;
        this.nativeTouch = !0;
        this.wrapperClass = "dropdown";
        this.onChange = null
    }
    e.prototype = {
        constructor: e,
        instances: [],
        init: function(a, c) {
            var b = this;
            d.extend(b, c);
            b.$select = d(a);
            b.id = a.id;
            b.options = [];
            b.$options = b.$select.find("option");
            b.isTouch = "ontouchend" in document;
            b.$select.removeClass(b.wrapperClass + " dropdown");
            b.$select.is(":disabled") && (b.disabled = !0);
            b.$options.length && (b.$options.each(function(a) {
                var c =
                    d(this);
                c.is(":selected") && (b.selected = {
                    index: a,
                    title: c.text()
                }, b.focusIndex = a);
                c.hasClass("label") && 0 == a ? (b.hasLabel = !0, b.label = c.text(), c.attr("value", "")) : b.options.push({
                        domNode: c[0],
                        title: c.text(),
                        value: c.val(),
                        selected: c.is(":selected")
                    })
            }), b.selected || (b.selected = {
                index: 0,
                title: b.$options.eq(0).text()
            }, b.focusIndex = 0), b.render())
        },
        render: function() {
            var a = this;
            a.$container = a.$select.wrap('<div class="' + a.wrapperClass + (a.isTouch && a.nativeTouch ? " touch" : "") + (a.disabled ? " disabled" : "") + '"><span class="old"/></div>').parent().parent();
            a.$active = d('<span class="selected">' + a.selected.title + "</span>").appendTo(a.$container);
            a.$carat = d('<span class="carat"/>').appendTo(a.$container);
            a.$scrollWrapper = d("<div><ul/></div>").appendTo(a.$container);
            a.$dropDown = a.$scrollWrapper.find("ul");
            a.$form = a.$container.closest("form");
            d.each(a.options, function() {
                a.$dropDown.append("<li" + (this.selected ? ' class="active"' : "") + ">" + this.title + "</li>")
            });
            a.$items = a.$dropDown.find("li");
            a.maxHeight = 0;
            a.cutOff && a.$items.length > a.cutOff && a.$container.addClass("scrollable");
            for(i = 0; i < a.$items.length; i++) {
                var c = a.$items.eq(i);
                a.maxHeight += c.outerHeight();
                if(a.cutOff == i + 1) break
            }
            a.isTouch && a.nativeTouch ? a.bindTouchHandlers() : a.bindHandlers()
        },
        bindTouchHandlers: function() {
            var a = this;
            a.$container.on("click.easyDropDown", function() {
                a.$select.focus()
            });
            a.$select.on({
                change: function() {
                    var c = d(this).find("option:selected"),
                        b = c.text(),
                        c = c.val();
                    a.$active.text(b);
                    "function" === typeof a.onChange && a.onChange.call(a.$select[0], {
                        title: b,
                        value: c
                    })
                },
                focus: function() {
                    a.$container.addClass("focus")
                },
                blur: function() {
                    a.$container.removeClass("focus")
                }
            })
        },
        bindHandlers: function() {
            var a = this;
            a.query = "";
            a.$container.on({
                "click.easyDropDown": function() {
                    a.down || a.disabled ? a.close() : a.open()
                },
                "mousemove.easyDropDown": function() {
                    a.keyboardMode && (a.keyboardMode = !1)
                }
            });
            d("body").on("click.easyDropDown." + a.id, function(c) {
                c = d(c.target);
                var b = a.wrapperClass.split(" ").join(".");
                !c.closest("." + b).length && a.down && a.close()
            });
            a.$items.on({
                "click.easyDropDown": function() {
                    var c = d(this).index();
                    a.select(c);
                    a.$select.focus()
                },
                "mouseover.easyDropDown": function() {
                    if(!a.keyboardMode) {
                        var c = d(this);
                        c.addClass("focus").siblings().removeClass("focus");
                        a.focusIndex = c.index()
                    }
                },
                "mouseout.easyDropDown": function() {
                    a.keyboardMode || d(this).removeClass("focus")
                }
            });
            a.$select.on({
                "focus.easyDropDown": function() {
                    a.$container.addClass("focus");
                    a.inFocus = !0
                },
                "blur.easyDropDown": function() {
                    a.$container.removeClass("focus");
                    a.inFocus = !1
                },
                "keydown.easyDropDown": function(c) {
                    if(a.inFocus) {
                        a.keyboardMode = !0;
                        var b = c.keyCode;
                        if(38 == b || 40 == b || 32 ==
                            b) c.preventDefault(), 38 == b ? (a.focusIndex--, a.focusIndex = 0 > a.focusIndex ? a.$items.length - 1 : a.focusIndex) : 40 == b && (a.focusIndex++, a.focusIndex = a.focusIndex > a.$items.length - 1 ? 0 : a.focusIndex), a.down || a.open(), a.$items.removeClass("focus").eq(a.focusIndex).addClass("focus"), a.cutOff && a.scrollToView(), a.query = "";
                        if(a.down)
                            if(9 == b || 27 == b) a.close();
                            else {
                                if(13 == b) return c.preventDefault(), a.select(a.focusIndex), a.close(), !1;
                                if(8 == b) return c.preventDefault(), a.query = a.query.slice(0, -1), a.search(), clearTimeout(a.resetQuery), !1;
                                38 != b && 40 != b && (c = String.fromCharCode(b), a.query += c, a.search(), clearTimeout(a.resetQuery))
                            }
                    }
                },
                "keyup.easyDropDown": function() {
                    a.resetQuery = setTimeout(function() {
                        a.query = ""
                    }, 1200)
                }
            });
            a.$dropDown.on("scroll.easyDropDown", function(c) {
                a.$dropDown[0].scrollTop >= a.$dropDown[0].scrollHeight - a.maxHeight ? a.$container.addClass("bottom") : a.$container.removeClass("bottom")
            });
            if(a.$form.length) a.$form.on("reset.easyDropDown", function() {
                a.$active.text(a.hasLabel ? a.label : a.options[0].title)
            })
        },
        unbindHandlers: function() {
            this.$container.add(this.$select).add(this.$items).add(this.$form).add(this.$dropDown).off(".easyDropDown");
            d("body").off("." + this.id)
        },
        open: function() {
            var a = window.scrollY || document.documentElement.scrollTop,
                c = window.scrollX || document.documentElement.scrollLeft,
                b = this.notInViewport(a);
            this.closeAll();
            this.$select.focus();
            window.scrollTo(c, a + b);
            this.$container.addClass("open");
            this.$scrollWrapper.css("height", this.maxHeight + "px");
            this.down = !0
        },
        close: function() {
            this.$container.removeClass("open");
            this.$scrollWrapper.css("height", "0px");
            this.focusIndex = this.selected.index;
            this.query = "";
            this.down = !1
        },
        closeAll: function() {
            var a =
                    Object.getPrototypeOf(this).instances,
                c;
            for(c in a) a[c].close()
        },
        select: function(a) {
            "string" === typeof a && (a = this.$select.find("option[value=" + a + "]").index() - 1);
            var c = this.options[a],
                b = this.hasLabel ? a + 1 : a;
            this.$items.removeClass("active").eq(a).addClass("active");
            this.$active.text(c.title);
            this.$select.find("option").removeAttr("selected").eq(b).prop("selected", !0).parent().trigger("change");
            this.selected = {
                index: a,
                title: c.title
            };
            this.focusIndex = i;
            "function" === typeof this.onChange && this.onChange.call(this.$select[0], {
                title: c.title,
                value: c.value
            })
        },
        search: function() {
            var a = this,
                c = function(b) {
                    a.focusIndex = b;
                    a.$items.removeClass("focus").eq(a.focusIndex).addClass("focus");
                    a.scrollToView()
                };
            for(i = 0; i < a.options.length; i++) {
                var b = a.options[i].title.toUpperCase();
                if(0 == b.indexOf(a.query)) {
                    c(i);
                    return
                }
            }
            for(i = 0; i < a.options.length; i++)
                if(b = a.options[i].title.toUpperCase(), -1 < b.indexOf(a.query)) {
                    c(i);
                    break
                }
        },
        scrollToView: function() {
            if(this.focusIndex >= this.cutOff) {
                var a = this.$items.eq(this.focusIndex).outerHeight() * (this.focusIndex +
                    1) - this.maxHeight;
                this.$dropDown.scrollTop(a)
            }
        },
        notInViewport: function(a) {
            var c = a + (window.innerHeight || document.documentElement.clientHeight),
                b = this.$dropDown.offset().top + this.maxHeight;
            return b >= a && b <= c ? 0 : b - c + 5
        },
        destroy: function() {
            this.unbindHandlers();
            this.$select.unwrap().siblings().remove();
            this.$select.unwrap();
            delete Object.getPrototypeOf(this).instances[this.$select[0].id]
        },
        disable: function() {
            this.disabled = !0;
            this.$container.addClass("disabled");
            this.$select.attr("disabled", !0);
            this.down ||
            this.close()
        },
        enable: function() {
            this.disabled = !1;
            this.$container.removeClass("disabled");
            this.$select.attr("disabled", !1)
        }
    };
    var f = function(a, c) {
        a.id = a.id ? a.id : "EasyDropDown" + ("00000" + (16777216 * Math.random() << 0).toString(16)).substr(-6).toUpperCase();
        var b = new e;
        b.instances[a.id] || (b.instances[a.id] = b, b.init(a, c))
    };
    d.fn.easyDropDown = function() {
        var a = arguments,
            c = [],
            b;
        b = this.each(function() {
            if(a && "string" === typeof a[0]) {
                var b = e.prototype.instances[this.id][a[0]](a[1], a[2]);
                b && c.push(b)
            } else f(this, a[0])
        });
        return c.length ? 1 < c.length ? c : c[0] : b
    };
    d(function() {
        "function" !== typeof Object.getPrototypeOf && (Object.getPrototypeOf = "object" === typeof "test".__proto__ ? function(a) {
                return a.__proto__
            } : function(a) {
                return a.constructor.prototype
            });
        d("select.dropdown").each(function() {
            var a = d(this).attr("data-settings");
            settings = a ? d.parseJSON(a) : {};
            f(this, settings)
        })
    })
})(jQuery);




/**
 * Created by 清水 on 2017/6/6.
 */

//    三级联动




//个人中心

var _person_con =  {
    //菜单展示
    shouMenu:function(ele,showDom){


            $(ele).bind('click',function(){
                    $(this).toggleClass('active');
                    $(this).siblings(showDom).toggleClass('active');

            });

    },
    //非商家
    show_motel:function(obj){

            $(obj).bind('click',function(){

                var dataType = $(this).attr('data-type');

                if(dataType=="no"){
                    $('.motel_box').show();
                    $('.motel_in').show();
                    $(this).removeClass('active');
                    $(this).find('.person_menu_lsit').removeClass('active');

                }else{
                    $('.motel_box').hide();
                    $('.motel_in').hide();
                }

            });


            $('.close_btn').bind('click',function(){
                $('.motel_box').hide();
                $('.motel_in').hide();
            });



    },
    changePass:function(){

        var oldPass = $.trim($('#old_pass').val()),

            newPass = $.trim($('#new_pass').val()),

            newPassAgain = $.trim($('#new_pass_again').val());

        if(oldPass==""){
            layer.tips('请输入旧密码', '#old_pass', {
                tips: [1, '#2987e6'],
                time: 2000
            });
            return false;
        }else if(newPass==""){
            layer.tips('请输入新密码', '#new_pass', {
                tips: [1, '#2987e6'],
                time: 2000
            });

            return false;
        }else if(newPassAgain==""){
            layer.tips('请确认输入新密码', '#new_pass_again', {
                tips: [1, '#2987e6'],
                time: 2000
            });

            return false;
        }else if(newPass!=newPassAgain){
            layer.tips('两次密码输入不一致', '#new_pass_again', {
                tips: [1, '#2987e6'],
                time: 2000
            });

            return false;
        }
        return true;

    },
    changeSafe:function(){

        var oldPass = $.trim($('#old_safe').val()),

            newPass = $.trim($('#new_safe').val()),

            newPassAgain = $.trim($('#new_safe_again').val());

        if(oldPass==""){
            layer.tips('请输入旧安全码', '#old_safe', {
                tips: [1, '#2987e6'],
                time: 2000
            });

            return false;
        }else if(newPass==""){
            layer.tips('请输入新安全码', '#new_safe', {
                tips: [1, '#2987e6'],
                time: 2000
            });

            return false;
        }else if(newPassAgain==""){
            layer.tips('请确认输入新安全码', '#new_safe_again', {
                tips: [1, '#2987e6'],
                time: 2000
            });
            return false;
        }else if(newPass!=newPassAgain){
            layer.tips('两次安全码输入不一致', '#new_safe_again', {
                tips: [1, '#2987e6'],
                time: 2000
            });
            return false;
        }
        return true;

    },
    setSafe:function(){

        var loginPass = $.trim($('#login_pass').val()),

            newSafe = $.trim($('#new_safe').val()),

            newSafeAgain = $.trim($('#new_safe_again').val());

            reg_safe  = /(?=^.*?\d)(?=^.*?[a-zA-Z])^[0-9a-zA-Z]{6,32}$/;

        if(loginPass==""){
            layer.tips('请输入登录密码', '#login_pass', {
                tips: [1, '#2987e6'],
                time: 2000
            });

            return false;
        }else if(newSafe==""){
            layer.tips('请输入安全码', '#new_safe', {
                tips: [1, '#2987e6'],
                time: 2000
            });
            return false;
        }else if(!reg_safe.test(newSafe)){
            layer.tips('安全码不少于6位且同时包含数字+字母', '#new_safe', {
                tips: [1, '#2987e6'],
                time: 2000
            });
            return false;


        }else if(newSafeAgain==""){
            layer.tips('请确认输入安全码', '#new_safe_again', {
                tips: [1, '#2987e6'],
                time: 2000
            });
            return false;
        }else if(newSafe!=newSafeAgain){
            layer.tips('两次安全码输入不一致', '#new_safe_again', {
                tips: [1, '#2987e6'],
                time: 2000
            });
            return false;
        }
        return true;

    },
    downJump:function(obj,time,url){
        var count = time;
        var timer = null;
        clearInterval(timer);
        timer = setInterval(fn,1000);

        function fn(){

            count--;

            if(count==0){

                window.location.href = url;
                clearInterval(timer);
                timer = null;
            }else {
                $(obj).html(count);
            }

        }

    },
    addActive:function(el){

       $(el).hover(function(){
           $(this).addClass('active');
       },function(){
           $(this).removeClass('active');
       });

    }


}


function form_reg(data) {

    if(!data){
        return;
    }

    this.formBox = data.$ele;//最外层盒子

    this.init();
    this.s_province = $(this.formBox).find("#s_province").val();
    this.s_city = $(this.formBox).find("#s_city").val();
    this.s_county = $(this.formBox).find("#s_county").val();

    this.shopName = $.trim($('#shop_name').val());//店铺名字

    this.logo = $('#file-list').children();//店铺图标

    this.shopArea = $.trim($(this.formBox).find('#shop_area').val());//店铺介绍

    this.qq   =   $.trim($(this.formBox).find('#qq').val());//qq

    this.email = $.trim($(this.formBox).find('#email').val());//email
    this.mobile = $.trim($(this.formBox).find('#mobile').val());//mobile

    this.regQQ = /[1-9]([0-9]{5,11})/;

    this.regEmail = /\w[-\w.+]*@([A-Za-z0-9][-A-Za-z0-9]+\.)+[A-Za-z]{2,14}/;


    this.phoneHm = /[0-9-()（）]{7,18}/;

    this.phoneHm2 =  /0?(13|14|15|18)[0-9]{9}/;
    this.regmobile = /^1(3|4|5|7|8)\d{9}$/;



}

form_reg.prototype = {

    //入口函数
    init:function(){
        this.event();
    },

    //event

    event:function(){

        //radio

        var radio = $(this.formBox).find('.js_radio');

        var radio2 = $(this.formBox).find('.js_radio2');
        var _this = this;


        radio.live('click',function(){
            var index = $(this).index();
            $(this).addClass('active').siblings().removeClass('active');

            $('#js_status').find('.form_status_item').addClass('active').eq(index).siblings().removeClass('active');

        });

        radio2.live('click',function(){

            $(this).addClass('active').siblings().removeClass('active');



        });


    },
    form_sub:function(){

            if(this.shopName==""){
                layer.alert("请输入店铺名");

                this.scrollDom('#shop_name');
                return false;
            }else if(this.logo.length<=0){
                layer.alert("请上传店铺图标");
                this.scrollDom('#browse');
                return false;
            }else if(this.shopArea==""||this.shopArea>=200){
                layer.alert("店铺介绍内容不得多于200字且不能为空");
                this.scrollDom('#shop_area');
                return false;
            }else if(this.qq==""|| !(this.regQQ.test(this.qq))){
                layer.alert("请填写正确的QQ号");
                this.scrollDom('#qq');
                return false;
            }else if( this.email==""||!(this.regEmail.test(this.email))){
                layer.alert("请填写正确的邮箱号");
                this.scrollDom('#email');
                return false;
            }else if( this.mobile==""||!(this.regmobile.test(this.mobile))){
                layer.alert("请填写正确的手机号");
                this.scrollDom('#mobile');
                return false;
            }else if(this.s_province=="省份"|| this.s_city == "地级市" || this.s_county == "市、县级市"){
                layer.alert("请选择地区");
                this.scrollDom('#mobile');
                return false;
            }
            return true;

    },
    scrollDom:function(el){
        var top = $(el).offset().top;

        $('body,html').animate({'scrollTop':top},500);

    },
    form_qiye:function(){

        var fr = $.trim($(this.formBox).find('#fr').val());//企业法人

        var name =  $.trim($(this.formBox).find('#qy_name').val());//企业名字

        var bh = $.trim($(this.formBox).find('#bh').val());//编号

        var phone = $.trim($(this.formBox).find('#phone').val());//电话

        var sf = $(this.formBox).find('#file-list2').children();//身份证

        var zz = $(this.formBox).find('#file-list3').children();//营业执照


        if(fr==""){
            layer.alert("请输入企业法人");
            this.scrollDom('#fr');
            return false
        }else if(name==""){
            layer.alert("请填写营业执照上的企业名称");
            this.scrollDom('#qy_name');
            return false
        }else if(bh==""){
            layer.alert("三证合一请填写统一社会信用代码");
            this.scrollDom('#bh');
            return false
        }else if(phone==""||!this.phoneHm.test(phone)&&!this.phoneHm2.test(phone)){
            layer.alert("请填写公司区号-固话或者手机号码");
            this.scrollDom('#phone');
            return false
        }else if(sf.length<=0){
            layer.alert("请上传身份证");
            this.scrollDom('#browse2');
            return false
        }else if(zz.length<=0){
            layer.alert("请上传营业执照");
            this.scrollDom('#browse3');
            return false

        }

        return true;


    },
    reg_person:function(){
        var len = $(this.formBox).find('#file-list4').children();

        if($("#name").val().length<=0){
            layer.alert("请填写真实姓名");
            this.scrollDom('#name');
            return false
        }else if($("#sfz_sn").val().length<=0){
            layer.alert("请填写身份证号码");
            this.scrollDom('#sfz_sn');
            return false
        }else if(len.length<=0){
            layer.alert("请上传身份证");
            this.scrollDom('#browse4');
            return false
        }
        return true;
    },
    time_down:function(){
        //手机验证倒计时
        var timer=null; //timer变量，控制时间
        var count = 60; //间隔函数，1秒执行
        var curCount=0;//当前剩余秒数
        var code = ""; //验证码
        var codeLength = 6;//验证码长度

        function sendMessage() {


            // var username = $.trim($(".userVal").val());
            // if(nametest(username)){
            //     layer.tips('用户名已经存在!', '.userVal', {
            //         tips: [1, '#ff5742']
            //     });
            //     return false;
            // }
            // var phone=$.trim($('.phoneVal').val());
            // if(phonetest(phone)){
            //     layer.tips('该手机号已经注册过!', '.phoneVal', {
            //         tips: [1, '#ff5742']
            //     });
            //     return false;
            // }
            curCount = count;

            //设置button效果，开始计时
            $(".send-code").attr("disabled", "true");
            $(".send-code").val( curCount + "s");
            $(".send-code").addClass('active');
            timer = window.setInterval(SetRemainTime, 1000); //启动计时器，1秒执行一次
            SetRemainTime();


        }
        //timer处理函数
        function SetRemainTime() {
            if (curCount == 0) {
                window.clearInterval(timer);//停止计时器
                $(".send-code").removeAttr("disabled");//启用按钮
                $(".send-code").removeClass('active');
                $(".send-code").val("重新发送");

                // code = ""; //清除验证码。如果不清除，过时间后，输入收到的验证码依然有效
            }
            else {
                curCount--;
                $(".send-code").val( curCount + "s") ;
            }
        }



        return sendMessage();

    },
    change_att_email:function(){
        var val = $.trim($(this.formBox).find("#change_email").val()),

            code = $.trim($(this.formBox).find("#code").val());

            if(val==""){
                layer.alert("请输入邮箱号");

                return false;
            }else if(!this.regEmail.test(val)){
                layer.alert("请输入正确的邮箱号");

                return false;
            }else if(code==""){
                layer.alert("请输入验证码");
                return false;
            }


            return true;

    },
    reg_email:function(){
            var email = $.trim($(this.formBox).find("#change_email").val());

        if(email==""){
            layer.alert("请输入邮箱号");

            return false;
        }else if(!this.regEmail.test(email)){
            layer.alert("请输入正确的邮箱号");

            return false;
        }else if(email_check() == false){
            return false;
        }

        //发送短信
            $.ajax({
                url:sendcode_url,
                type:"post",
                dataType:"json",
                data:{email:email,types:'regcode'},
                success:function(data){
                    //layer.closeAll();
                    if(data.status=='1'){
                        layer.msg('发送成功');
                    }else{
                        layer.msg('发送验证码失败');
                    }
                },
                error:function(){
                    layer.msg('链接错误');
                    //layer.closeAll();
                }
            });
        this.time_down();
        return true;

    },
    change_att_phone:function(){
        var val = $.trim($(this.formBox).find("#change_phone").val()),

            code = $.trim($(this.formBox).find("#phone_code").val());

        if(val==""){
            layer.alert("请输入手机号");

            return false;
        }else if(!this.phoneHm2.test(val)){
            layer.alert("请输入正确的手机号");

            return false;
        }else if(code==""){
            layer.alert("请输入验证码");
            return false;
        }


        return true;

    },
    reg_phone:function(){
        var phone = $.trim($(this.formBox).find("#change_phone").val());

        if(phone==""){
            layer.alert("请输入手机号");

            return false;
        }else if(!this.phoneHm2.test(phone)){
            layer.alert("请输入正确的手机号");

            return false;
        }else if(phone_check() == false){
            return false;
        }

        this.time_down();
        //发送短信
            $.ajax({
                url:sendcode_url,
                type:"post",
                dataType:"json",
                data:{mobile:phone,types:'regcode'},
                success:function(data){
                    //layer.closeAll();
                    if(data.status=='1'){
                        layer.msg('发送成功');
                    }else{
                        layer.msg('发送验证码失败');
                    }
                },
                error:function(){
                    layer.msg('链接错误');
                    //layer.closeAll();
                }
            });
        return false;

    }



}
//初始化
new form_reg({
    $ele:"#form_wrap"
});

//    点击QQ显示



//查看电话

$('.js_phone_btn').live('click',function(){

    layer.open({
        type:1,
        skin: 'layui-layer-pub', //样式类名
        closeBtn: 0, //不显示关闭按钮
        anim: 2,
        shadeClose: true, //开启遮罩关闭
        content: '<div class="contact_text"><p><span class="title"><b>联系电话</b>'+ $(this).attr('data_v') +'</span></p></div>',
        title:'卖家联系电话'
    });
});


//弹出层
function confirm($content){

    //自定页
    layer.open({
        type: 1,
        skin: 'layer-ui-demo', //样式类名
        closeBtn: 1, //不显示关闭按钮
        anim: 0,
        shadeClose: true, //开启遮罩关闭
        content:$content
    });
}



//


/*!
 * jQuery Raty - A Star Rating Plugin
 *
 * Licensed under The MIT License
 *
 * @version        2.5.2
 * @author         Washington Botelho
 * @documentation  wbotelhos.com/raty
 *
 */

;(function(b) {
        var a = {
            init: function(c) {
                return this.each(function() {
                        a.destroy.call(this);
                        this.opt = b.extend(true, {}, b.fn.raty.defaults, c);
                        var e = b(this)
                            , g = ["number", "readOnly", "score", "scoreName"];
                        a._callback.call(this, g);
                        if (this.opt.precision) {
                            a._adjustPrecision.call(this);
                        }
                        this.opt.number = a._between(this.opt.number, 0, this.opt.numberMax);
                        this.opt.path = this.opt.path || "";
                        if (this.opt.path && this.opt.path.slice(this.opt.path.length - 1, this.opt.path.length) !== "/") {
                            this.opt.path += "/";
                        }
                        this.stars = a._createStars.call(this);
                        this.score = a._createScore.call(this);
                        a._apply.call(this, this.opt.score);
                        var f = this.opt.space ? 4 : 0
                            , d = this.opt.width || (this.opt.number * this.opt.size + this.opt.number * f);
                        if (this.opt.cancel) {
                            this.cancel = a._createCancel.call(this);
                            d += (this.opt.size + f);
                        }
                        if (this.opt.readOnly) {
                            a._lock.call(this);
                        } else {
                            e.css("cursor", "pointer");
                            a._binds.call(this);
                        }
                        if (this.opt.width !== false) {
                            e.css("width", "170px");
                        }
                        a._target.call(this, this.opt.score);
                        e.data({
                            settings: this.opt,
                            raty: true
                        });
                    }
                );
            },
            _adjustPrecision: function() {
                this.opt.targetType = "score";
                this.opt.half = true;
            },
            _apply: function(c) {
                if (c && c > 0) {
                    c = a._between(c, 0, this.opt.number);
                    this.score.val(c);
                }
                a._fill.call(this, c);
                if (c) {
                    a._roundStars.call(this, c);
                }
            },
            _between: function(e, d, c) {
                return Math.min(Math.max(parseFloat(e), d), c);
            },
            _binds: function() {
                if (this.cancel) {
                    a._bindCancel.call(this);
                }
                a._bindClick.call(this);
                a._bindOut.call(this);
                a._bindOver.call(this);
            },
            _bindCancel: function() {
                a._bindClickCancel.call(this);
                a._bindOutCancel.call(this);
                a._bindOverCancel.call(this);
            },
            _bindClick: function() {
                var c = this
                    , d = b(c);
                c.stars.on("click.raty", function(e) {
                        c.score.val((c.opt.half || c.opt.precision) ? d.data("score") : this.alt);
                        if (c.opt.click) {
                            c.opt.click.call(c, parseFloat(c.score.val()), e);
                        }
                    }
                );
            },
            _bindClickCancel: function() {
                var c = this;
                c.cancel.on("click.raty", function(d) {
                        c.score.removeAttr("value");
                        if (c.opt.click) {
                            c.opt.click.call(c, null , d);
                        }
                    }
                );
            },
            _bindOut: function() {
                var c = this;
                b(this).on("mouseleave.raty", function(d) {
                        var e = parseFloat(c.score.val()) || undefined;
                        a._apply.call(c, e);
                        a._target.call(c, e, d);
                        if (c.opt.mouseout) {
                            c.opt.mouseout.call(c, e, d);
                        }
                    }
                );
            },
            _bindOutCancel: function() {
                var c = this;
                c.cancel.on("mouseleave.raty", function(d) {
                        b(this).attr("src", c.opt.path + c.opt.cancelOff);
                        if (c.opt.mouseout) {
                            c.opt.mouseout.call(c, c.score.val() || null , d);
                        }
                    }
                );
            },
            _bindOverCancel: function() {
                var c = this;
                c.cancel.on("mouseover.raty", function(d) {
                        b(this).attr("src", c.opt.path + c.opt.cancelOn);
                        c.stars.attr("src", c.opt.path + c.opt.starOff);
                        a._target.call(c, null , d);
                        if (c.opt.mouseover) {
                            c.opt.mouseover.call(c, null );
                        }
                    }
                );
            },
            _bindOver: function() {
                var c = this
                    , d = b(c)
                    , e = c.opt.half ? "mousemove.raty" : "mouseover.raty";
                c.stars.on(e, function(g) {
                        var h = parseInt(this.alt, 10);
                        if (c.opt.half) {
                            var f = parseFloat((g.pageX - b(this).offset().left) / c.opt.size)
                                , j = (f > 0.5) ? 1 : 0.5;
                            h = h - 1 + j;
                            a._fill.call(c, h);
                            if (c.opt.precision) {
                                h = h - j + f;
                            }
                            a._roundStars.call(c, h);
                            d.data("score", h);
                        } else {
                            a._fill.call(c, h);
                        }
                        a._target.call(c, h, g);
                        if (c.opt.mouseover) {
                            c.opt.mouseover.call(c, h, g);
                        }
                    }
                );
            },
            _callback: function(c) {
                for (i in c) {
                    if (typeof this.opt[c[i]] === "function") {
                        this.opt[c[i]] = this.opt[c[i]].call(this);
                    }
                }
            },
            _createCancel: function() {
                var e = b(this)
                    , c = this.opt.path + this.opt.cancelOff
                    , d = b("<img />", {
                    src: c,
                    alt: "x",
                    title: this.opt.cancelHint,
                    "class": "raty-cancel"
                });
                if (this.opt.cancelPlace == "left") {
                    e.prepend("&#160;").prepend(d);
                } else {
                    e.append("&#160;").append(d);
                }
                return d;
            },
            _createScore: function() {
                return b("<input />", {
                    type: "hidden",
                    name: this.opt.scoreName
                }).appendTo(this);
            },
            _createStars: function() {
                var e = b(this);
                for (var c = 1; c <= this.opt.number; c++) {
                    var f = a._getHint.call(this, c)
                        , d = (this.opt.score && this.opt.score >= c) ? "starOn" : "starOff";
                    d = this.opt.path + this.opt[d];
                    b("<img />", {
                        src: d,
                        alt: c,
                        title: f
                    }).appendTo(this);
                    if (this.opt.space) {
                        e.append((c < this.opt.number) ? "&#160;" : "");
                    }
                }
                return e.children("img");
            },
            _error: function(c) {
                b(this).html(c);
                b.error(c);
            },
            _fill: function(d) {
                var m = this
                    , e = 0;
                for (var f = 1; f <= m.stars.length; f++) {
                    var g = m.stars.eq(f - 1)
                        , l = m.opt.single ? (f == d) : (f <= d);
                    if (m.opt.iconRange && m.opt.iconRange.length > e) {
                        var j = m.opt.iconRange[e]
                            , h = j.on || m.opt.starOn
                            , c = j.off || m.opt.starOff
                            , k = l ? h : c;
                        if (f <= j.range) {
                            g.attr("src", m.opt.path + k);
                        }
                        if (f == j.range) {
                            e++;
                        }
                    } else {
                        var k = l ? "starOn" : "starOff";
                        g.attr("src", this.opt.path + this.opt[k]);
                    }
                }
            },
            _getHint: function(d) {
                var c = this.opt.hints[d - 1];
                return (c === "") ? "" : (c || d);
            },
            _lock: function() {
                var d = parseInt(this.score.val(), 10)
                    , c = d ? a._getHint.call(this, d) : this.opt.noRatedMsg;
                b(this).data("readonly", true).css("cursor", "").attr("title", c);
                this.score.attr("readonly", "readonly");
                this.stars.attr("title", c);
                if (this.cancel) {
                    this.cancel.hide();
                }
            },
            _roundStars: function(e) {
                var d = (e - Math.floor(e)).toFixed(2);
                if (d > this.opt.round.down) {
                    var c = "starOn";
                    if (this.opt.halfShow && d < this.opt.round.up) {
                        c = "starHalf";
                    } else {
                        if (d < this.opt.round.full) {
                            c = "starOff";
                        }
                    }
                    this.stars.eq(Math.ceil(e) - 1).attr("src", this.opt.path + this.opt[c]);
                }
            },
            _target: function(f, d) {
                if (this.opt.target) {
                    var e = b(this.opt.target);
                    if (e.length === 0) {
                        a._error.call(this, "Target selector invalid or missing!");
                    }
                    if (this.opt.targetFormat.indexOf("{score}") < 0) {
                        a._error.call(this, 'Template "{score}" missing!');
                    }
                    var c = d && d.type == "mouseover";
                    if (f === undefined) {
                        f = this.opt.targetText;
                    } else {
                        if (f === null ) {
                            f = c ? this.opt.cancelHint : this.opt.targetText;
                        } else {
                            if (this.opt.targetType == "hint") {
                                f = a._getHint.call(this, Math.ceil(f));
                            } else {
                                if (this.opt.precision) {
                                    f = parseFloat(f).toFixed(1);
                                }
                            }
                            if (!c && !this.opt.targetKeep) {
                                f = this.opt.targetText;
                            }
                        }
                    }
                    if (f) {
                        f = this.opt.targetFormat.toString().replace("{score}", f);
                    }
                    if (e.is(":input")) {
                        e.val(f);
                    } else {
                        e.html(f);
                    }
                }
            },
            _unlock: function() {
                b(this).data("readonly", false).css("cursor", "pointer").removeAttr("title");
                this.score.removeAttr("readonly", "readonly");
                for (var c = 0; c < this.opt.number; c++) {
                    this.stars.eq(c).attr("title", a._getHint.call(this, c + 1));
                }
                if (this.cancel) {
                    this.cancel.css("display", "");
                }
            },
            cancel: function(c) {
                return this.each(function() {
                        if (b(this).data("readonly") !== true) {
                            a[c ? "click" : "score"].call(this, null );
                            this.score.removeAttr("value");
                        }
                    }
                );
            },
            click: function(c) {
                return b(this).each(function() {
                        if (b(this).data("readonly") !== true) {
                            a._apply.call(this, c);
                            if (!this.opt.click) {
                                a._error.call(this, 'You must add the "click: function(score, evt) { }" callback.');
                            }
                            this.opt.click.call(this, c, {
                                type: "click"
                            });
                            a._target.call(this, c);
                        }
                    }
                );
            },
            destroy: function() {
                return b(this).each(function() {
                        var d = b(this)
                            , c = d.data("raw");
                        if (c) {
                            d.off(".raty").empty().css({
                                cursor: c.style.cursor,
                                width: c.style.width
                            }).removeData("readonly");
                        } else {
                            d.data("raw", d.clone()[0]);
                        }
                    }
                );
            },
            getScore: function() {
                var d = [], c;
                b(this).each(function() {
                        c = this.score.val();
                        d.push(c ? parseFloat(c) : undefined);
                    }
                );
                return (d.length > 1) ? d : d[0];
            },
            readOnly: function(c) {
                return this.each(function() {
                        var d = b(this);
                        if (d.data("readonly") !== c) {
                            if (c) {
                                d.off(".raty").children("img").off(".raty");
                                a._lock.call(this);
                            } else {
                                a._binds.call(this);
                                a._unlock.call(this);
                            }
                            d.data("readonly", c);
                        }
                    }
                );
            },
            reload: function() {
                return a.set.call(this, {});
            },
            score: function() {
                return arguments.length ? a.setScore.apply(this, arguments) : a.getScore.call(this);
            },
            set: function(c) {
                return this.each(function() {
                        var e = b(this)
                            , f = e.data("settings")
                            , d = b.extend({}, f, c);
                        e.raty(d);
                    }
                );
            },
            setScore: function(c) {
                return b(this).each(function() {
                        if (b(this).data("readonly") !== true) {
                            a._apply.call(this, c);
                            a._target.call(this, c);
                        }
                    }
                );
            }
        };
        b.fn.raty = function(c) {
            if (a[c]) {
                return a[c].apply(this, Array.prototype.slice.call(arguments, 1));
            } else {
                if (typeof c === "object" || !c) {
                    return a.init.apply(this, arguments);
                } else {
                    b.error("Method " + c + " does not exist!");
                }
            }
        }
        ;
        b.fn.raty.defaults = {
            cancel: false,
            cancelHint: "Cancel this rating!",
            cancelOff: "cancel-off.png",
            cancelOn: "cancel-on.png",
            cancelPlace: "left",
            click: undefined,
            half: false,
            halfShow: true,
            hints: ["bad", "poor", "regular", "good", "gorgeous"],
            iconRange: undefined,
            mouseout: undefined,
            mouseover: undefined,
            noRatedMsg: "Not rated yet!",
            number: 5,
            numberMax: 20,
            path: "",
            precision: false,
            readOnly: false,
            round: {
                down: 0.25,
                full: 0.6,
                up: 0.76
            },
            score: undefined,
            scoreName: "score",
            single: false,
            size: 16,
            space: true,
            starHalf: "star-half.png",
            starOff: "star-off.png",
            starOn: "star-on.png",
            target: undefined,
            targetFormat: "{score}",
            targetKeep: false,
            targetText: "",
            targetType: "hint",
            width: undefined
        };
    }
)(jQuery);




/*
*
*
* */

//提现
;(function ($){
    //基类
    function shopClass(ele,options){
        this.ele = ele;
        this.opts = $.extend({}, $.fn.shop.defaults,options);

    }
    //添加法法
    shopClass.prototype  = {
        init:function(){
            this.mouseEvent();
        },

        //键盘事件
        mouseEvent:function(){
            var _this = this;
            var nowNum =parseInt($(_this.opts.inputVal).val())/_this.opts.rmb;
            var num_reg = /^\d+(\.\d+)?$/;
            var has_use = parseFloat($(_this.opts.has_container).text());

            $(_this.opts.inputVal).on('keydown',function(e){
                var ev = e.keyCode;

                $(_this.opts.inputVal).on('keyup',function(e){
                    ev = e.keyCode;
                    nowNum =parseInt($(_this.opts.inputVal).val())/_this.opts.rmb;

                   if(nowNum>has_use){
                       nowNum = has_use;
                       if(has_use<=0){
                           $(_this.opts.inputVal).val("");
                       }else{
                           $(_this.opts.inputVal).val(has_use);
                       }

                        layer.tips("最多可提现金额为"+has_use,$(_this.opts.inputVal),{
                            tips: [1, '#2987e6'],
                            time: 2000

                        });

                        return false;
                    }else if(!num_reg.test(nowNum)){
                        $(_this.opts.inputVal).val("");
                        $(_this.opts.fyInp).text(0.00);
                        $(_this.opts.dz).text(0.00);
                        return false;
                    }else if($(_this.opts.inputVal).val()==""){
                        $(_this.opts.inputVal).val("");
                        $(_this.opts.fyInp).text(0.00);
                        $(_this.opts.dz).text(0.00);
                        return false;
                    }

                    //现在INPUT的值

                    var biLi = _this.opts.bili;//比例值

                    var result  = (nowNum*biLi).toFixed(2);

                    $(_this.opts.fyInp).text(result);//提现费用

                    $(_this.opts.dz).text((nowNum-result).toFixed(2));
                })

            });

        }


    }



    $.fn.shop = function(options){
        var startShop = new shopClass(this,options);
        startShop.init();
        return this;
    }
    //默认值
    $.fn.shop.defaults = {
        "container":".kiting_form",//主容器名字
        "has_container":"#has_monet",
        "inputVal":"#je",//输入框名字
        "fyInp"  :".clearing-fy-num",//提现费用显示区域类名
        "dz"  :".clearing-dz-num",//实际到账显示区域名字
        "bili":0.01,//比例值
        "rmb" :1
    }

})(jQuery);




