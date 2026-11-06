/**
 * Cloudflare Turnstile 前端助手（加载失败可降级图片）
 * 用法：TurnstileWidget.init({ siteKey, container, imageBox, modeInput, fallback, timeoutMs, verifyImg })
 */
(function (window) {
    'use strict';

    var state = {
        mode: 'turnstile',
        widgetId: null,
        ready: false,
        fallback: true,
        siteKey: '',
        container: null,
        imageBox: null,
        modeInput: null,
        verifyImg: null,
        timeoutMs: 8000
    };

    function $(sel) {
        if (!sel) return null;
        if (typeof sel === 'string') return document.querySelector(sel);
        return sel;
    }

    function setMode(mode) {
        state.mode = mode;
        if (state.modeInput) {
            state.modeInput.value = mode;
        }
        if (mode === 'image') {
            if (state.container) state.container.style.display = 'none';
            if (state.imageBox) state.imageBox.style.display = '';
            refreshImage();
        } else {
            if (state.container) state.container.style.display = '';
            if (state.imageBox) state.imageBox.style.display = 'none';
        }
    }

    function refreshImage() {
        if (!state.verifyImg) return;
        var src = state.verifyImg.getAttribute('data-src') || state.verifyImg.src;
        var base = src.split('?')[0];
        state.verifyImg.src = base + '?tm=' + Math.random();
    }

    function fallbackToImage(reason) {
        if (!state.fallback) return;
        if (state.mode === 'image') return;
        setMode('image');
        if (window.console && console.warn) {
            console.warn('[Turnstile] fallback image:', reason || '');
        }
    }

    function renderWidget() {
        if (!window.turnstile || !state.container || !state.siteKey) {
            fallbackToImage('no-turnstile-api');
            return;
        }
        try {
            state.widgetId = window.turnstile.render(state.container, {
                sitekey: state.siteKey,
                callback: function () {
                    state.ready = true;
                },
                'expired-callback': function () {
                    state.ready = false;
                },
                'error-callback': function () {
                    fallbackToImage('widget-error');
                }
            });
            state.ready = false;
        } catch (e) {
            fallbackToImage('render-fail');
        }
    }

    function loadScript(cb) {
        if (window.turnstile) {
            cb(true);
            return;
        }
        var existed = document.querySelector('script[data-turnstile-api="1"]');
        if (existed) {
            var t0 = Date.now();
            var timer = setInterval(function () {
                if (window.turnstile) {
                    clearInterval(timer);
                    cb(true);
                } else if (Date.now() - t0 > state.timeoutMs) {
                    clearInterval(timer);
                    cb(false);
                }
            }, 100);
            return;
        }
        var s = document.createElement('script');
        s.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        s.async = true;
        s.defer = true;
        s.setAttribute('data-turnstile-api', '1');
        var done = false;
        var watchdog = setTimeout(function () {
            if (!done) {
                done = true;
                cb(false);
            }
        }, state.timeoutMs);
        s.onload = function () {
            if (done) return;
            done = true;
            clearTimeout(watchdog);
            cb(!!window.turnstile);
        };
        s.onerror = function () {
            if (done) return;
            done = true;
            clearTimeout(watchdog);
            cb(false);
        };
        document.head.appendChild(s);
    }

    window.TurnstileWidget = {
        init: function (opts) {
            opts = opts || {};
            state.siteKey = opts.siteKey || '';
            state.container = $(opts.container || '#cf-turnstile');
            state.imageBox = $(opts.imageBox || '#turnstile-image-fallback');
            state.modeInput = $(opts.modeInput || '#captcha_mode');
            state.verifyImg = $(opts.verifyImg || '#veriimg');
            state.fallback = opts.fallback !== false && opts.fallback !== 0 && opts.fallback !== '0';
            state.timeoutMs = opts.timeoutMs || 8000;

            if (!state.siteKey) {
                fallbackToImage('empty-site-key');
                return this;
            }
            setMode('turnstile');
            loadScript(function (ok) {
                if (!ok) {
                    fallbackToImage('load-timeout');
                    return;
                }
                renderWidget();
            });
            return this;
        },
        getMode: function () {
            return state.mode;
        },
        /**
         * @returns {boolean|string} true 通过；false 未完成；'need-image' 需填图片
         */
        beforeSubmit: function () {
            if (state.mode === 'image') {
                return 'need-image';
            }
            if (!window.turnstile || state.widgetId === null) {
                fallbackToImage('not-ready');
                return 'need-image';
            }
            var token = window.turnstile.getResponse(state.widgetId);
            if (!token) {
                return false;
            }
            return true;
        },
        reset: function () {
            if (state.mode === 'image') {
                refreshImage();
                return;
            }
            if (window.turnstile && state.widgetId !== null) {
                try {
                    window.turnstile.reset(state.widgetId);
                } catch (e) {}
            }
            state.ready = false;
        },
        refreshImage: refreshImage
    };
})(window);
