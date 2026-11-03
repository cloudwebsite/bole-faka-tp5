//copyright @ baliwish 2016-2018
//web development library
//this plungin is based on jquery 1.9.1

var $I = function(em) {
    return document.getElementById(em)
};
var $Iv = function(em) {
    try {
        return document.getElementById(em).value
    } catch (e) {
        return""
    }
};
var $N = function(em) {
    return document.getElementsByName(em)
};
var $Nv = function(em) {
    try {
        return document.getElementsByName(em).value
    } catch (e) {
        return""
    }
};

//获取当前url参数
var GetRequest = function(key) {
    var reg = new RegExp("(^|&)" + key + "=([^&]*)(&|$)", "i");
    var r = document.location.search.substr(1).match(reg);
    if (r != null)
        return unescape(r[2]);
    return ""
}
//设置cookie
var SetCookies = function(Name, Value) {
    document.cookie = Name + "=" + Value;
    document.location.reload()
};
var CheckItemAll = function(form) {
    for (var i = 0; i < form.elements.length; i++) {
        if (form.elements[i].type == "checkbox") {
            if (form.elements[i].Name != "AllCheck") {
                form.elements[i].checked = form.CheckAll.checked
            }
        }
    }
};

//http://www.cnblogs.com/carekee/articles/1678041.html
//时间类处理函数
function daysBetween(DateOne, DateTwo)
{
    var OneMonth = DateOne.substring(5, DateOne.lastIndexOf('-'));
    var OneDay = DateOne.substring(DateOne.length, DateOne.lastIndexOf('-') + 1);
    var OneYear = DateOne.substring(0, DateOne.indexOf('-'));

    var TwoMonth = DateTwo.substring(5, DateTwo.lastIndexOf('-'));
    var TwoDay = DateTwo.substring(DateTwo.length, DateTwo.lastIndexOf('-') + 1);
    var TwoYear = DateTwo.substring(0, DateTwo.indexOf('-'));

    var cha = ((Date.parse(OneMonth + '/' + OneDay + '/' + OneYear) - Date.parse(TwoMonth + '/' + TwoDay + '/' + TwoYear)) / 86400000);
    return Math.abs(cha);
}

//阻止冒泡
function stopPropagation(e) {
    if (e.stopPropagation)
        e.stopPropagation();
    else
        e.cancelBubble = true;
}

//登录注册的提示部分
//判断是否是IE
function isIE() {
    if (!!window.ActiveXObject || "ActiveXObject" in window)
        return true;
    else
        return false;
}

//判断字符串全是数字
function isNum(string)
{
    if (string != null && string != "")
    {
        return !isNaN(string);
    }
    return false;
}

//判断字符串是否是邮箱

function isEmail(input) {
    var reg = /^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/;
    return reg.test(input);
}

//判断字符串是电话号码
function isMobile(input) {
    var reg = /^(13[0-9]|14[0-9]|15[0-9]|18[0-9]|17[0-9]|16[0-9]|19[0-9])\d{8}$/;
    return reg.test(input);
}



// 判断字符串是身份证

function isIDCard(input) {

    if (input.length != 0) {
        if (!checkCard(input)) {
            return false;
        } else {
            return true;
        }
    }

    return true;
}


//function checkidno(obj) { 
var vcity = {0: "请选择", 11: "北京", 12: "天津", 13: "河北", 14: "山西", 15: "内蒙古",
    21: "辽宁", 22: "吉林", 23: "黑龙江", 31: "上海", 32: "江苏",
    33: "浙江", 34: "安徽", 35: "福建", 36: "江西", 37: "山东", 41: "河南",
    42: "湖北", 43: "湖南", 44: "广东", 45: "广西", 46: "海南", 50: "重庆",
    51: "四川", 52: "贵州", 53: "云南", 54: "西藏", 61: "陕西", 62: "甘肃",
    63: "青海", 64: "宁夏", 65: "新疆", 71: "台湾", 81: "香港", 82: "澳门", 91: "国外"
};
checkCard = function(obj)
{
    //var card = document.getElementById('card_no').value; 
    //是否为空 
    // if(card === '') 
    // { 
    //  return false; 
    //} 
    //校验长度，类型 
    if (isCardNo(obj) === false)
    {
        return false;
    }
    //检查省份 
    if (checkProvince(obj) === false)
    {
        return false;
    }
    //校验生日 
    if (checkBirthday(obj) === false)
    {
        return false;
    }
    //检验位的检测 
    if (checkParity(obj) === false)
    {
        return false;
    }
    return true;
};
//检查号码是否符合规范，包括长度，类型 
isCardNo = function(obj)
{
    //身份证号码为15位或者18位，15位时全为数字，18位前17位为数字，最后一位是校验位，可能为数字或字符X 
    var reg = /(^\d{15}$)|(^\d{17}(\d|X)$)/;
    if (reg.test(obj) === false)
    {
        return false;
    }
    return true;
};
//取身份证前两位,校验省份 
checkProvince = function(obj)
{
    var province = obj.substr(0, 2);
    if (vcity[province] == undefined)
    {
        return false;
    }
    return true;
};
//检查生日是否正确 
checkBirthday = function(obj)
{
    var len = obj.length;
    //身份证15位时，次序为省（3位）市（3位）年（2位）月（2位）日（2位）校验位（3位），皆为数字 
    if (len == '15')
    {
        var re_fifteen = /^(\d{6})(\d{2})(\d{2})(\d{2})(\d{3})$/;
        var arr_data = obj.match(re_fifteen);
        var year = arr_data[2];
        var month = arr_data[3];
        var day = arr_data[4];
        var birthday = new Date('19' + year + '/' + month + '/' + day);
        return verifyBirthday('19' + year, month, day, birthday);
    }
    //身份证18位时，次序为省（3位）市（3位）年（4位）月（2位）日（2位）校验位（4位），校验位末尾可能为X 
    if (len == '18')
    {
        var re_eighteen = /^(\d{6})(\d{4})(\d{2})(\d{2})(\d{3})([0-9]|X)$/;
        var arr_data = obj.match(re_eighteen);
        var year = arr_data[2];
        var month = arr_data[3];
        var day = arr_data[4];
        var birthday = new Date(year + '/' + month + '/' + day);
        return verifyBirthday(year, month, day, birthday);
    }
    return false;
};
//校验日期 
verifyBirthday = function(year, month, day, birthday)
{
    var now = new Date();
    var now_year = now.getFullYear();
    //年月日是否合理 
    if (birthday.getFullYear() == year && (birthday.getMonth() + 1) == month && birthday.getDate() == day)
    {
        //判断年份的范围（3岁到100岁之间) 
        var time = now_year - year;
        if (time >= 0 && time <= 130)
        {
            return true;
        }
        return false;
    }
    return false;
};
//校验位的检测 
checkParity = function(obj)
{
    //15位转18位 
    obj = changeFivteenToEighteen(obj);
    var len = obj.length;
    if (len == '18')
    {
        var arrInt = new Array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        var arrCh = new Array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        var cardTemp = 0, i, valnum;
        for (i = 0; i < 17; i++)
        {
            cardTemp += obj.substr(i, 1) * arrInt[i];
        }
        valnum = arrCh[cardTemp % 11];
        if (valnum == obj.substr(17, 1))
        {
            return true;
        }
        return false;
    }
    return false;
};
//15位转18位身份证号 
changeFivteenToEighteen = function(obj)
{
    if (obj.length == '15')
    {
        var arrInt = new Array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        var arrCh = new Array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        var cardTemp = 0, i;
        obj = obj.substr(0, 6) + '19' + obj.substr(6, obj.length - 6);
        for (i = 0; i < 17; i++)
        {
            cardTemp += obj.substr(i, 1) * arrInt[i];
        }
        obj += arrCh[cardTemp % 11];
        return obj;
    }
    return obj;
};


function decToHex(str) {
    if (str == undefined) {
        return;
    }
    var res = [];
    for (var i = 0; i < str.length; i++)
        res[i] = ("00" + str.charCodeAt(i).toString(16)).slice(-4);
    return "\\u" + res.join("\\u");
}
function hexToDec(str) {
    if (str == undefined) {
        return;
    }
    str = str.replace(/\\/g, "%");
    return unescape(str);
}


//*******判断密码强度***************
function checkStrong(sValue) {
    var modes = 0;
    //正则表达式验证符合要求的
    if (sValue.length < 1)
        return modes;
    if (/\d/.test(sValue))
        modes++; //数字
    if (/[a-z]/.test(sValue))
        modes++; //小写
    if (/[A-Z]/.test(sValue))
        modes++; //大写  
    if (/\W/.test(sValue))
        modes++; //特殊字符

    //逻辑处理
    switch (modes) {
        case 1:
            return 1;
            break;
        case 2:
            return 2;
        case 3:
        case 4:
            return sValue.length < 12 ? 3 : 4
            break;
    }
}







function FloatModelSettingJS() {
    if ($Iv('formname') == '') {
        msgbox("请输入商城名称");
        return false;
    }
    if ($Iv('formdomain') == '') {
        msgbox("请输入域名名称");
        return false;
    }
    if ($Iv('brandlogo') == '') {
        msgbox("请上传您的Logo");
        return false;
    }
    if ($Iv('qrcode') == '') {
        msgbox("请上传您的二维码");
        return false;
    }
    if ($Iv('formobile') == '') {
        msgbox("客服电话不能为空");
        return false;
    }
    $("form").submit();
    return true;
}



//发送验证码
//
function sendmsg(val) {
    $.get("/Wap/Login/sendMsg.html", {val: val}, function(data) {
        if (data.status == 200) {
            alert(data.msg);
            if (data.type == 1) {
                var text = "您好，百礼汇已向您的手机" + val + "发送了验证码，请您及时查看！";
            } else {
                var text = "您好，百礼汇已向您的邮箱" + val + "发送了验证码，请您及时查看！";
            }
        } else {
            alert(data.msg);
        }
    }, 'json');
}



//注册页面倒计时
function timeClock(obj, n, objvalue) {
    var phonenum = $I(objvalue).value;
    //phonenum = phonenum.replace(/[^0-9]/ig,"");//去掉字符串中非数字的字符
    if (isMobile(phonenum)) {
        //msgbox("我们已将短信发到您填入的手机上，请查收");
        sendmsg(phonenum);//发送短信信息
    } else if (isEmail(phonenum)) {
        //msgbox("我们给您发送了一封邮件，请查收");
        //console.log("isEmail");
    } else {
        //console.log("isNull"); 
        mui.alert("请输入手机号码");
        $("#" + objvalue).parent(".has-feedback").addClass("error");
        return false;
    }
    var t = obj.innerHTML;
    (function() {
        if (n > 0) {
            obj.disabled = true;
            obj.style.background = '#DCDCDC';
            obj.innerHTML = '倒计时(' + (n--) + ')秒';
            setTimeout(arguments.callee, 1000);
        } else {
            obj.style.background = '#00A9FD';
            obj.disabled = false;
            obj.innerHTML = t;
        }
    })();
}

function StringPriceFomart(num)
{
    var num = num.toString();
    var str = num;
    str = str.split("").reverse().join("");//反向字符串    
    var key = 0;//记录当前位置
    var arr = new Array();
    for (var i = str.length; i > 0; i--) {
        if (key == 3) {
            key = 0;
            arr.push(num.substr(i, 3));
            key++;
        } else {
            key++;
        }
        if (i == 1 && key == (str.length % 3) && (str.length % 3) != 0) {
            arr.push(num.substr(0, key));
        } else if (i == 1 && (str.length % 3) == 0) {
            arr.push(num.substr(0, 3));
        }
    }
    arr = arr.reverse();//将数组反向
    result = arr.join(",");//按逗号拼接
    return result;
}





//商品数量NumBox的调整
function input_numbox(obj, mode) {
    var $objs = $(obj);
    $objs.find("button").click(function() {
        var min = $(this).parent().attr("data-numbox-min");
        min=parseInt(min);
        var max = $(this).parent().attr("data-numbox-max");
        max=parseInt(max);
        var number = $(this).parent().find(".input-number").val();
        number=parseInt(number);
        console.log(number);

        if (min !== "" || min !== "undefined") {
            min = parseInt(min);
            if (min < 0) {
                min = 0;
            }
        }
        if ($(this).hasClass("input-chevron-up")) {
            if (max!== undefined && number>=max) {
                number = max;
            } else {
                number = parseInt(number) + 1;
            }
            $(this).parent().find(".input-number").val(number);
        } else {
            if (min !== undefined && number <= min) {
                number = min;
            } else {
                number = parseInt(number) - 1;
            }
            $(this).parent().find(".input-number").val(number);
        }

    });

}


//颜色拾取器回调
function colorpickerchage(obj, target) {
    if (target == "" || target == undefined) {
        return;
    }
    var o = $(obj);
    var val = o.val();

    if ($("#" + target) !== undefined) {
        $("#" + target).css("background", val);
    }
    else if ($("." + target) !== undefined) {
        $("." + target).css("background", val);
    }
    else {
        return;
    }
}




//PC端地址选择插件
//本插件依赖的文件有
//mui地址库city.data-3.js
//地址选中板块，跟手机版公用一个地址库

function distpickerChose(Province, City, Area, town) { 
    var tmpProvincedata = "", tmpCitydata = "", tmpAreadata = "";
    var j = 0, k = 0, m = 0;
    tmpProvincedata += "<option value='请选择' selected='selected'>请选择</option>";
    for (var i = 0; i <= cityData3.length - 1; i++) {
        if (cityData3[i].text == Province) {
            // //console.log("Province" + i);
            j = i;//缓存当前城市
            tmpProvincedata += "<option value='" + cityData3[i].text + "' selected='selected'>" + cityData3[i].text + "</option>";
        } else {
            tmpProvincedata += "<option value='" + cityData3[i].text + "'>" + cityData3[i].text + "</option>"
        }
        jQuery("#Province").html(tmpProvincedata);
    }
    //二级行政区

    if ((Province !== "") && (cityData3[j].children !== undefined)) {
        ////console.log("Province:" + j);
        //tmpCitydata="";
        tmpCitydata += "<option value='请选择' selected='selected'>请选择</option>";
        for (i = 0; i <= cityData3[j].children.length - 1; i++) {
            if (cityData3[j].children[i].text == City) {
                k = i;
                tmpCitydata += "<option value='" + cityData3[j].children[i].text + "' selected='selected'>" + cityData3[j].children[i].text + "</option>";
            } else {
                tmpCitydata += "<option value='" + cityData3[j].children[i].text + "'>" + cityData3[j].children[i].text + "</option>"
            }

        }
        jQuery("#City").html(tmpCitydata);
        ////console.log("tmpCitydata:" + tmpCitydata);

    }

    //三级行政区
    tmpAreadata = "";
    ////console.log("city:" + k);
    if ((cityData3[j].children[k].children !== undefined)) {
        jQuery("#Area").removeClass('hidden');
        ////console.log("k:" + k);
        tmpAreadata += "<option value='请选择' selected='selected'>请选择</option>";
        for (i = 0; i <= cityData3[j].children[k].children.length - 1; i++) {
            if (cityData3[j].children[k].children[i].text == Area) {
                m = i;
                tmpAreadata += "<option value='" + cityData3[j].children[k].children[i].text + "' selected='selected'>" + cityData3[j].children[k].children[i].text + "</option>";
            } else {
                tmpAreadata += "<option value='" + cityData3[j].children[k].children[i].text + "'>" + cityData3[j].children[k].children[i].text + "</option>"
            }
        }

    } else {
        jQuery("#Area").addClass('hidden');
        jQuery("#Town").addClass('hidden');
        return false; //三级行政区没有了，不再继续执行
    }
    //console.log(cityData3[j].children[k].children);
    jQuery("#Area").html(tmpAreadata);

    //四级行政区
    tmpTowndata = "";    
    if ((cityData3[j].children[k].children[m].children !== undefined)) {
        jQuery("#Town").removeClass('hidden');
        tmpTowndata += "<option value='请选择' selected='selected'>请选择</option>";
        for (i = 0; i <= cityData3[j].children[k].children[m].children.length - 1; i++) { 
            //alert(cityData3[j].children[k].children[m].children[i].text+","+town);
            if (cityData3[j].children[k].children[m].children[i].text == town) {             
                tmpTowndata += "<option value='" + cityData3[j].children[k].children[m].children[i].text + "' selected='selected'>" + cityData3[j].children[k].children[m].children[i].text + "</option>";
            } else {
                tmpTowndata += "<option value='" + cityData3[j].children[k].children[m].children[i].text + "'>" + cityData3[j].children[k].children[m].children[i].text + "</option>"
            }
        }
    } else {
        jQuery("#Town").addClass('hidden');
        return false; //四级行政区没有了，不再继续执行
    }
    jQuery("#Town").html(tmpTowndata);

}

//地址插件
function distpicker(Province, City, Area,Town) {
    distpickerChose(Province, City, Area,Town);//初始化第一级行政区
    jQuery("#Province").change(function() {
        distpickerChose(jQuery(this).val())
    });
    jQuery("#City").change(function() {
        distpickerChose(jQuery("#Province").val(), jQuery("#City").val(), "")
    });
    jQuery("#Area").change(function() {
        distpickerChose(jQuery("#Province").val(), jQuery("#City").val(), jQuery("#Area").val())
    });
}


/*Lazy Load - jQuery plugin for lazy loading images Version:  1.7.7 */
(function($) {
    $.fn.lazyload = function(options) {
        var settings = {threshold: 0, failure_limit: 0, event: "scroll", effect: "show", container: window, skip_invisible: true};
        if (options) {
            if (null !== options.failurelimit) {
                options.failure_limit = options.failurelimit;
                delete options.failurelimit
            }
            $.extend(settings, options)
        }
        var elements = this;
        if (0 == settings.event.indexOf("scroll")) {
            $(settings.container).bind(settings.event, function(event) {
                var counter = 0;
                elements.each(function() {
                    if (settings.skip_invisible && !$(this).is(":visible"))
                        return;
                    if ($.abovethetop(this, settings) || $.leftofbegin(this, settings)) {
                    } else if (!$.belowthefold(this, settings) && !$.rightoffold(this, settings)) {
                        $(this).trigger("appear")
                    } else {
                        if (++counter > settings.failure_limit) {
                            return false
                        }
                    }
                });
                var temp = $.grep(elements, function(element) {
                    return!element.loaded
                });
                elements = $(temp)
            })
        }
        this.each(function() {
            var self = this;
            self.loaded = false;
            $(self).one("appear", function() {
                if (!this.loaded) {
                    $("<img />").bind("load", function() {
                        $(self).hide().attr("src", $(self).data("original"))[settings.effect](settings.effectspeed);
                        self.loaded = true
                    }).attr("src", $(self).data("original"))
                }
            });
            if (0 != settings.event.indexOf("scroll")) {
                $(self).bind(settings.event, function(event) {
                    if (!self.loaded) {
                        $(self).trigger("appear")
                    }
                })
            }
        });
        $(window).bind("resize", function(event) {
            $(settings.container).trigger(settings.event)
        });
        $(settings.container).trigger(settings.event);
        return this
    };
    $.belowthefold = function(element, settings) {
        if (settings.container === undefined || settings.container === window) {
            var fold = $(window).height() + $(window).scrollTop()
        } else {
            var fold = $(settings.container).offset().top + $(settings.container).height()
        }
        return fold <= $(element).offset().top - settings.threshold
    };
    $.rightoffold = function(element, settings) {
        if (settings.container === undefined || settings.container === window) {
            var fold = $(window).width() + $(window).scrollLeft()
        } else {
            var fold = $(settings.container).offset().left + $(settings.container).width()
        }
        return fold <= $(element).offset().left - settings.threshold
    };
    $.abovethetop = function(element, settings) {
        if (settings.container === undefined || settings.container === window) {
            var fold = $(window).scrollTop()
        } else {
            var fold = $(settings.container).offset().top
        }
        return fold >= $(element).offset().top + settings.threshold + $(element).height()
    };
    $.leftofbegin = function(element, settings) {
        if (settings.container === undefined || settings.container === window) {
            var fold = $(window).scrollLeft()
        } else {
            var fold = $(settings.container).offset().left
        }
        return fold >= $(element).offset().left + settings.threshold + $(element).width()
    };
    $.extend($.expr[':'], {"below-the-fold": function(a) {
            return $.belowthefold(a, {threshold: 0, container: window})
        }, "above-the-fold": function(a) {
            return!$.belowthefold(a, {threshold: 0, container: window})
        }, "right-of-fold": function(a) {
            return $.rightoffold(a, {threshold: 0, container: window})
        }, "left-of-fold": function(a) {
            return!$.rightoffold(a, {threshold: 0, container: window})
        }})
})(jQuery);

