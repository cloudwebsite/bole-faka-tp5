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
};//判断字符串全是数字
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


//将form转为AJAX提交

function ajaxSubmit(frm, fn)
{
    //alert("xxx");
    //alert("aaass:"+frm);
    var dataPara = getFormJson(frm);
    $.ajax({
        url: frm.action,
        type: frm.method,
        data: dataPara,
        dataType: 'json',
        success: function(data)
        {
            if (data.result == -1)
            {
                // alert(data.data);
                window.location.href = '/';
                return;
            }
            fn(data);
        }
    });
}

//将form中的值转换为键值对。
function getFormJson(frm)
{
    var o = {};
    var a = $(frm).serializeArray();
    $.each(a, function()
    {
        if (o[this.name] !== undefined)
        {
            if (!o[this.name].push)
            {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else
        {
            o[this.name] = this.value || '';
        }
    });
    return o;
}

function msgAlert(msg)
{
    if (title)
    {
        $('#divMsgAlert h3').html(title);
    }
    $('#divMsgAlert span').html(msg);
    $('#divMsgAlert').modal();

}

function receipt(receipt_url)
{
    window.location.href = receipt_url;
}

//全选
function selall() {
    $(".sel").click(function() {
        $(".selids").each(function() {
            this.checked = !this.checked;
        });
    });
}


function ajaxPost(url, para)
{
    var valArr = [];
    $(".selids:checked").each(function(i) {
        if (this.checked) {
            valArr.push($(this).val());
        }
    });
    var ids = valArr.join(',');
    $.ajax({
        url: url,
        data: {ids: ids, status: status},
        type: 'post',
        dataType: 'json',
        success: function(data)
        {

            if (data.result == 1)
            {
                msgAlert(data.data);
            }
            else
            {
                msgAlert(data.data);
            }
        }
    });
}

function setpage() {


    var totalPage = $('#totalPages').val();// 总页数

    var pageSize = 2;// 每页显示行数

    var currentPage = $('#nowPage').val();// 当前页数

    // 前三行始终显示

    var tempStr = '';
    alert("sss:" + currentPage);
    if (currentPage >= 1) {
        tempStr += '<li><a href="javascript:;" onclick="goPage(1)" >首页</a></li>';
    }

    if (currentPage >= 1) {
        tempStr += '<li ><a href="javascript:;" onclick="goPage(' + (currentPage - 1) + ')"><i><</i>上一页</a></li>';
    }

    for (var i = 1; i <= totalPage; i++) {
        if (currentPage == i) {
            tempStr += '<li class="active"><a href="javascript:;" onclick="goPage(' + i + ')">' + i + ' <span class="sr-only">(current)</span></a></li>';
            continue;
        } else {
            tempStr += '<li><a href="javascript:;" onclick="goPage(' + i + ')">' + i + '</a></li>';
        }

    }

    if (currentPage <= totalPage) {
        tempStr += '<li><a href="javascript:;" onclick="goPage(' + (currentPage + 1) + ')" >下一页<i>></i></a></li>';
    }

    if (currentPage <= totalPage) {

        tempStr += '<li><a href="javascript:;" onclick="goPage(' + totalPage + ')" >尾页</a></li>';

    }
    tempStr = '<ul class="pagination pagination-lg">' + tempStr + '</ul>';

    $('#nav').html(tempStr);
}

function setpage2() {
    var totalPage = $('#totalPages').val();// 总页数
    var pageSize = 2;// 每页显示行数
    var currentPage = $('#nowPage').val();// 当前页数
    //alert(currentPage);
    // 前三行始终显示
    if (parseInt(totalPage) > 1)
    {
        currentPage = parseInt(currentPage);
        if (currentPage)
            var tempStr = '';
        tempStr += '<li><a href="javascript:;" onclick="goPage(1)" >首页</a></li>';
        tempStr += '<li ><a href="javascript:;" onclick="goPage(' + (parseInt(currentPage) - 1) + ')"><i><<</i>上一页</a></li>';

        for (var i = 1; i <= totalPage; i++) {
            if (currentPage - 2 > 1 && totalPage > 5 && i == currentPage - 2)
            {
                tempStr += '<li><a href="javascript:;">...</a></li>';
            }
            if (currentPage == i) {
                tempStr += '<li class="active"><a href="javascript:;" onclick="goPage(' + i + ')">' + i + ' <span class="sr-only">(current)</span></a></li>';
                continue;
            } else {
                if (totalPage > 5)
                {
                    if ((i >= (currentPage - 2) && i <= (currentPage + 2)) && totalPage > 5)
                    {
                        tempStr += '<li><a href="javascript:;" onclick="goPage(' + i + ')">' + i + '</a></li>';
                    }
                }
                else
                {
                    tempStr += '<li><a href="javascript:;" onclick="goPage(' + i + ')">' + i + '</a></li>';
                }
            }
            if (currentPage + 2 < totalPage && totalPage > 5 && i == currentPage + 2)
            {
                tempStr += '<li><a href="javascript:;">...</a></li>';
            }
        }


        if (parseInt(currentPage) == totalPage) {
            tempStr += '<li><a class="cor-lgray" href="javascript:;">下一页>></i></a></li>';
        } else {
            tempStr += '<li><a href="javascript:;" onclick="goPage(' + (parseInt(currentPage) + 1) + ')" >下一页>></i></a></li>';
        }

        tempStr += '<li><a href="javascript:;" onclick="goPage(' + totalPage + ')" >尾页</a></li>';
        tempStr = '<ul class="pagination pagination-lg">' + tempStr + '</ul>';
    }
    else
    {
        tempStr = '<ul class="pagination pagination-lg"><li class="active"><a href="javascript:;" target="_parent" >1</a></li></ul>';
    }
    $('#nav').html(tempStr);
}


//警告弹窗

function msgbox(content, title, link) {
    var html;
    var link = arguments[2] ? "location.href=\"" + arguments[2] + "\"" : 'javascript:deleteobj("MessageBox");';
    var title = arguments[1] ? arguments[1] : '提示';
    if (content == "undefined") {
        content == "";
    }
    html = "\
<div id='MessageBox' class='MessageBox'>\
<div class='MessageBoxMain animated bounceIn' style='-webkit-animation-duration: .25s;animation-duration: .25s'>\
<div class='MessageBoxTitle'>" + title + "</div>\
<div class='MessageBoxContent text-center'>" + content + "</div>\
<div class='MessageBoxBtn'>"
    if (arguments[2]) {
        html += "<a class='btn' onclick=deleteobj('MessageBox') style='margin-right:10px; border:1px solid #DBDBDC;height:28px;line-height:100%;vertical-align: top;'>取消</a>"
    }
    html += "<a class='MessageBoxBtnItem' onclick='" + link + "'>确定</a>";
    html += "</div></div>";
//document.getElementsByTagName('body')[0].innerHTML += html;
    jQuery("body").append(html);
}

function deleteobj(obj)
{
    var my = document.getElementById(obj);
    if (my != null) {
        my.parentNode.removeChild(my);
    }
}
//警告弹窗

function msgbox(content, title, link) {
    var html;
    var link = arguments[2] ? "location.href=\"" + arguments[2] + "\"" : 'javascript:deleteobj("MessageBox");';
    var title = arguments[1] ? arguments[1] : '提示';
    if (content == "undefined") {
        content == "";
    }
    html = "\
<div id='MessageBox' class='MessageBox'>\
<div class='MessageBoxMain animated bounceIn' style='-webkit-animation-duration: .25s;animation-duration: .25s'>\
<div class='MessageBoxTitle'>" + title + "</div>\
<div class='MessageBoxContent text-center'>" + content + "</div>\
<div class='MessageBoxBtn'>"
    if (arguments[2]) {
        html += "<a class='btn' onclick=deleteobj('MessageBox') style='margin-right:10px; border:1px solid #DBDBDC;height:28px;line-height:100%;vertical-align: top;'>取消</a>"
    }
    html += "<a class='MessageBoxBtnItem' onclick='" + link + "'>确定</a>";
    html += "</div></div>";
//document.getElementsByTagName('body')[0].innerHTML += html;
    jQuery("body").append(html);
}

function deleteobj(obj)
{
    var my = document.getElementById(obj);
    if (my != null) {
        my.parentNode.removeChild(my);
    }
}

function msgboxcallback(obj)
{

    if (typeof (obj) == 'function') {
        deleteobj("MessageBox");
        setTimeout(function() {
            obj();
        }, 1000);
        return true;
    } else {
        return false;
    }
}



