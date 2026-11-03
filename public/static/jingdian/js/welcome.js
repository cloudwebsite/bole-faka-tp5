function showLocale(objD) { var str; var yy = objD.getYear(); if (yy < 1900) yy = yy + 1900; var MM = objD.getMonth() + 1; if (MM < 10) MM = '0' + MM; var dd = objD.getDate(); if (dd < 10) dd = '0' + dd; var hh = objD.getHours(); if (hh < 10) hh = '0' + hh; var mm = objD.getMinutes(); if (mm < 10) mm = '0' + mm; var ss = objD.getSeconds(); if (ss < 10) ss = '0' + ss; var ww = objD.getDay(); if (ww == 0) ww = "星期日"; if (ww == 1) ww = "星期一"; if (ww == 2) ww = "星期二"; if (ww == 3) ww = "星期三"; if (ww == 4) ww = "星期四"; if (ww == 5) ww = "星期五"; if (ww == 6) ww = "星期六"; str = "现在是：" + yy + "年" + MM + "月" + dd + "日  " + ww + " " + hh + ":" + mm + ":" + ss; return (str) } function tick() { var today; today = new Date(); document.getElementById("welcome").innerHTML = showLocale(today); window.setTimeout("tick()", 1000) } tick();
//End Get LocalTime Code

function showAsks() {
    now = new Date(), hour = now.getHours()
    if ((hour >= 0) && (hour <= 5)) { document.getElementById("showAsks").innerHTML = " 哇,准备玩通宵啊?请注意身体!"; }
    else if ((hour >= 5) && (hour <= 7)) { document.getElementById("showAsks").innerHTML = " 早上好,哇哦,好早哦,祝您今天有个好心情!"; }
    else if ((hour >= 7) && (hour <= 11)) { document.getElementById("showAsks").innerHTML = " 上午好,祝你玩得开心!"; }
    else if ((hour >= 11) && (hour <= 12)) { document.getElementById("showAsks").innerHTML = " 中午好,逛完记得午休哦!"; }
    else if ((hour >= 12) && (hour <= 13)) { document.getElementById("showAsks").innerHTML = " 午休时间,请注意休息哦!"; }
    else if ((hour >= 14) && (hour <= 17)) { document.getElementById("showAsks").innerHTML = " 下午好,工作怎么样，还顺利吧!"; }
    else if ((hour >= 17) && (hour <= 18)) { document.getElementById("showAsks").innerHTML = " 傍晚好,晚餐吃什么呢?吃完去散散步吧!"; }
    else if ((hour >= 18) && (hour <= 22)) { document.getElementById("showAsks").innerHTML = " 晚上好,尽情享受属于你自己的时间吧!"; }
    else if (hour >= 22) { document.getElementById("showAsks").innerHTML = "夜深了,为了您的身体健康,请早点休息!"; }
};
showAsks();

