/**
 * 规范化 CSS 尺寸：支持 400、'400px'、'80%'；非法回退默认
 * @param {*} v
 * @param {string} fallback
 * @returns {string}
 */
window.normalizeCssSize = function (v, fallback) {
  var def = fallback != null && String(fallback) !== '' ? String(fallback) : '400px';
  if (v == null || v === '') {
    return def;
  }
  if (typeof v === 'number' && isFinite(v) && v > 0) {
    return v + 'px';
  }
  v = String(v).replace(/\s+/g, '');
  if (!v) {
    return def;
  }
  if (/^\d+(\.\d+)?$/.test(v)) {
    return v + 'px';
  }
  if (/^\d+(\.\d+)?(px|%)$/i.test(v)) {
    return v;
  }
  return def;
};

/**
 * 首页弹窗公告：宽度 80% + max-width；内容区有内边距；不自动关闭，需手动点关闭
 * @param {string} html
 * @param {string|number|object} [maxWidthOrOpts]
 *   最大宽度默认 400px；对象可传 { maxWidth }
 * @returns {boolean}
 */
window.showHomeTanggNotice = function (html, maxWidthOrOpts) {
  if (typeof layer === 'undefined') {
    return false;
  }
  html = html == null ? '' : String(html);
  if (!html.replace(/\s+/g, '')) {
    return false;
  }
  var maxWidth = '400px';
  if (maxWidthOrOpts != null && typeof maxWidthOrOpts === 'object') {
    maxWidth = window.normalizeCssSize(maxWidthOrOpts.maxWidth, '400px');
  } else if (arguments.length >= 2) {
    maxWidth = window.normalizeCssSize(maxWidthOrOpts, '400px');
  }

  var wrapHtml = function (inner) {
    return (
      '<div class="tangg-notice-body">' +
      '<style type="text/css">' +
      '.tangg-notice-body{max-height:65vh;overflow-x:hidden;overflow-y:auto;box-sizing:border-box;' +
      'padding:16px 18px 18px;line-height:1.75;font-size:14px;color:#333;text-align:left;' +
      'word-break:break-word;overflow-wrap:break-word;white-space:normal;-webkit-overflow-scrolling:touch;}' +
      '.tangg-notice-body::-webkit-scrollbar{width:6px;}' +
      '.tangg-notice-body::-webkit-scrollbar-thumb{background:rgba(0,0,0,.18);border-radius:3px;}' +
      /* 去掉编辑器首行缩进/左偏移，避免换行后左右不齐 */ +
      '.tangg-notice-body p,.tangg-notice-body div,.tangg-notice-body span,.tangg-notice-body li,' +
      '.tangg-notice-body h1,.tangg-notice-body h2,.tangg-notice-body h3,.tangg-notice-body h4,.tangg-notice-body h5,.tangg-notice-body h6{' +
      'text-indent:0!important;margin-left:0!important;padding-left:0!important;text-align:left!important;' +
      'word-break:break-word;overflow-wrap:break-word;}' +
      '.tangg-notice-body ul,.tangg-notice-body ol{margin:0 0 .75em;padding-left:1.4em;}' +
      '.tangg-notice-body li{padding-left:0!important;}' +
      '.tangg-notice-body p{margin:0 0 .75em;}' +
      '.tangg-notice-body p:last-child{margin-bottom:0;}' +
      '.tangg-notice-body a{color:#1E9FFF;text-decoration:none;}' +
      '.tangg-notice-body a:hover{text-decoration:underline;}' +
      '.tangg-notice-body img,.tangg-notice-body video,.tangg-notice-body iframe{max-width:100%!important;height:auto!important;border-radius:4px;display:block;margin:8px 0;}' +
      '.tangg-notice-body table{width:100%!important;max-width:100%!important;table-layout:fixed;word-break:break-word;border-collapse:collapse;}' +
      '.tangg-notice-body td,.tangg-notice-body th{word-break:break-word;padding:6px 8px;text-align:left;}' +
      '.layui-layer-tangg .layui-layer-title{padding:0 50px 0 18px;height:46px;line-height:46px;font-size:15px;font-weight:600;color:#222;border-bottom:1px solid #eee;background:#fafafa;}' +
      '.layui-layer-tangg .layui-layer-btn{padding:10px 16px 14px;background:#fafafa;border-top:1px solid #eee;}' +
      '.layui-layer-tangg .layui-layer-btn a{height:34px;line-height:34px;padding:0 22px;border-radius:4px;}' +
      '</style>' +
      inner +
      '</div>'
    );
  };

  // 限制宽度后重新居中；滚动只发生在内容区内
  var polishLayer = function (layero) {
    try {
      if (!layero) {
        return;
      }
      var el = layero[0] || layero;
      if (el && el.style) {
        el.style.maxWidth = maxWidth;
        el.style.borderRadius = '8px';
        el.style.overflow = 'hidden';
        var w = el.offsetWidth || 0;
        var winW = window.innerWidth || document.documentElement.clientWidth || 0;
        if (w > 0 && winW > 0) {
          el.style.left = Math.max(0, (winW - w) / 2) + 'px';
        }
      } else if (typeof layero.css === 'function') {
        layero.css({ 'max-width': maxWidth, 'border-radius': '8px', overflow: 'hidden' });
      }
      if (typeof layero.find === 'function') {
        layero.find('.layui-layer-content').css({
          overflow: 'hidden',
          padding: '0',
          height: 'auto'
        });
      }
    } catch (e) {}
  };

  var openPc = function () {
    var idx = layer.open({
      type: 1,
      title: '商户公告',
      skin: 'layui-layer-tangg',
      content: wrapHtml(html),
      area: ['80%', 'auto'],
      btn: ['关闭公告'],
      btnAlign: 'c',
      shadeClose: false,
      shade: [0.55, '#000'],
      success: function (layero) {
        polishLayer(layero);
      },
      yes: function (index) {
        layer.close(index);
      }
    });
    return idx !== undefined;
  };

  // PC：有 layer.alert / layer.confirm 视为桌面 layer
  if (typeof layer.alert === 'function' || typeof layer.confirm === 'function') {
    try {
      if (typeof layer.ready === 'function') {
        layer.ready(function () {
          openPc();
        });
      } else {
        openPc();
      }
      return true;
    } catch (e) {
      return false;
    }
  }

  // mobile layer
  try {
    layer.open({
      title: '商户公告',
      content: wrapHtml(html),
      btn: '关闭公告',
      shadeClose: false,
      success: function (elem) {
        polishLayer(elem);
      },
      yes: function (index) {
        layer.close(index);
      }
    });
    return true;
  } catch (e2) {
    return false;
  }
};

/**
 * 新方案：
 * - tanggjiange = 页面加载完成后延迟多少秒再弹
 * - 同一浏览器标签内弹过一次后，刷新不再弹（sessionStorage）；关掉标签再开会再弹
 */
window.isTanggDismissed = function () {
  try {
    if (sessionStorage.getItem('tangg_shown') === '1') {
      return true;
    }
  } catch (e) {}
  return false;
};

window.markTanggDismissed = function () {
  try {
    sessionStorage.setItem('tangg_shown', '1');
  } catch (e) {}
};

/** @deprecated 兼容旧名 */
window.getTanggCookie = function () {
  return window.isTanggDismissed() ? '1' : '';
};
window.setTanggCookie = function () {
  window.markTanggDismissed();
};

window.tryShowHomeTangg = function (tanggOn, cookieHit, html, maxWidthOrOpts) {
  if (window.__TANGG_SHOWN__) {
    return false;
  }
  if (parseInt(tanggOn, 10) !== 1) {
    return false;
  }
  if (window.isTanggDismissed() || parseInt(cookieHit, 10) === 1) {
    return false;
  }
  var ok = window.showHomeTanggNotice(html, maxWidthOrOpts);
  if (ok) {
    window.__TANGG_SHOWN__ = true;
    window.markTanggDismissed();
  }
  return ok;
};

/**
 * boot：页面就绪后延迟 delaySec 秒再弹；本标签内只弹一次；不自动关闭
 * opts.maxWidth：80% 之上的最大宽度，默认 400px
 */
window.bootHomeTanggNotice = function (opts) {
  opts = opts || {};
  var tanggOn = opts.tanggOn;
  var html = opts.html;
  var noticeOpts = {
    maxWidth: opts.maxWidth
  };
  var delaySec = parseInt(opts.delaySec != null ? opts.delaySec : opts.cookieSec, 10);
  if (!delaySec || delaySec < 0) {
    delaySec = 0;
  }
  if (window.__TANGG_SHOWN__) {
    return;
  }
  if (parseInt(tanggOn, 10) !== 1) {
    return;
  }
  if (window.isTanggDismissed()) {
    return;
  }
  if (!html || !String(html).replace(/\s+/g, '')) {
    if (typeof console !== 'undefined' && console.warn) {
      console.warn('首页自动公告已开启，但公告内容为空，本次不弹出。请到后台「网站配置 → 前端配置」填写「首页弹窗公告内容」并保存；若刚加过配置项，请确认已入库并清除网站配置缓存后再试。');
    }
    return;
  }
  var run = function () {
    if (window.__TANGG_SHOWN__ || window.isTanggDismissed()) {
      return;
    }
    if (window.showHomeTanggNotice(html, noticeOpts)) {
      window.__TANGG_SHOWN__ = true;
      window.markTanggDismissed();
    }
  };
  var schedule = function () {
    setTimeout(run, delaySec * 1000);
  };
  if (typeof layer !== 'undefined' && typeof layer.ready === 'function') {
    layer.ready(schedule);
  } else if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', schedule);
  } else {
    schedule();
  }
};

/**
 * 下单前免责条款：有内容则倒计时后才能同意（兼容 PC layer.confirm 与 mobile layer.open）
 */
window.runWithOrderDisclaimer = function (html, waitSec, onAgree, onDisagree) {
  var go = function () {
    if (typeof onAgree === 'function') {
      onAgree();
    }
  };
  var refuse = function () {
    if (typeof onDisagree === 'function') {
      onDisagree();
    } else {
      window.location.href = '/';
    }
  };
  if (!html || typeof layer === 'undefined') {
    go();
    return;
  }
  waitSec = parseInt(waitSec, 10);
  if (!waitSec || waitSec < 1) {
    waitSec = 10;
  }
  var countDown = waitSec;
  var timer = null;

  var clearTimer = function () {
    if (timer) {
      clearInterval(timer);
      timer = null;
    }
  };

  if (typeof layer.confirm === 'function') {
    layer.confirm(html, {
      time: 0,
      area: ['80%', 'auto'],
      btn: ['同意', '不同意'],
      title: '免责条款',
      success: function (layero) {
        var $btn = layero.find('.layui-layer-btn0');
        $btn.text('请等待' + countDown + '秒').attr('style', 'pointer-events: none;');
        timer = setInterval(function () {
          countDown--;
          if (countDown <= 0) {
            clearTimer();
            $btn.text('同意').removeAttr('style');
          } else {
            $btn.text('请等待' + countDown + '秒').attr('style', 'pointer-events: none;');
          }
        }, 1000);
      },
      end: function () {
        clearTimer();
      }
    }, function (index) {
      clearTimer();
      layer.close(index);
      go();
    }, function () {
      clearTimer();
      refuse();
    });
    return;
  }

  layer.open({
    title: '免责条款',
    content: html,
    btn: ['请等待' + countDown + '秒', '不同意'],
    shadeClose: false,
    yes: function (index) {
      if (countDown > 0) {
        return false;
      }
      clearTimer();
      layer.close(index);
      go();
    },
    no: function () {
      clearTimer();
      refuse();
    },
    success: function (elem) {
      var $btn0 = null;
      try {
        $btn0 = $(elem).find('.layui-m-layerbtn span').eq(0);
        if (!$btn0.length) {
          $btn0 = $(elem).find('.layui-m-layerbtn').find('span').first();
        }
      } catch (e) {}
      timer = setInterval(function () {
        countDown--;
        if (countDown <= 0) {
          clearTimer();
          if ($btn0 && $btn0.length) {
            $btn0.text('同意');
          }
        } else if ($btn0 && $btn0.length) {
          $btn0.text('请等待' + countDown + '秒');
        }
      }, 1000);
    },
    end: function () {
      clearTimer();
    }
  });
};
