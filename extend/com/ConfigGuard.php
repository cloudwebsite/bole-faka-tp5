<?php
namespace com;

/**
 * 站点与分站配置的可写项、敏感项控制
 */
class ConfigGuard
{
    /** 允许使用的公共库 CDN 前缀 */
    public static $cdnpublicAllow = [
        '//cdn.staticfile.net/',
        '//lib.baomitu.com/',
        '//cdn.bootcdn.net/ajax/libs/',
        '//s1.pstatp.com/cdn/expire-1-M/',
    ];

    /**
     * 分站后台允许修改的配置项
     * web_host 由域名校验单独处理，仍列入以便更新
     */
    public static $childAllow = [
        'web_title', 'web_logo', 'weixin_erweima', 'm_title', 'shop_name', 'web_host',
        'web_site_title', 'web_site_description', 'web_site_keyword', 'web_site_copy',
        'web_qq', 'web_mobile', 'web_wangwang',
        'moban_index', 'wapmoban_index', 'lianxi_mobile', 'lianxi_email',
        'web_daohang', 'web_louceng', 'web_lishi', 'm_status',
        'wap_moban2_css', 'wap_moban3_css', 'pc_jingdian_css',
        'kami_tou', 'kami_wei',
        'hotcolor', 'baocolor',
        'pc_jingdian_title_beijing_color', 'pc_jingdian_title_color',
        'pc_jingdian_gg_color', 'pc_jd_footer_color',
    ];

    /**
     * 分站运行时不得覆盖主站的敏感配置（即使库中有脏数据也不生效）
     */
    public static $childRuntimeDeny = [
        'token', 'cdnpublic', 'access_pwd', 'accessencryption', 'admin_allow_ip', 'is_https',
        'alisms_appkey', 'alisms_appsecret', 'alisms_signname',
        'alimoban_id', 'alimoban_reg', 'alimoban_fahuo', 'alimoban_tixian',
        'alimoban_tixiandaozhang', 'alimoban_tixianchehui', 'alimoban_resetpwd',
        'Geetest_ID', 'Geetest_KEY', 'CODE_TYPE',
        'turnstile_site_key', 'turnstile_secret_key', 'turnstile_fallback_image',
        'app_id', 'ali_public_key', 'rsa_private_key', 'mch_id', 'wx_appid', 'wx_apiKey', 'wx_appKey',
        'pay_mqkey', 'pay_yzfid', 'pay_yzfkey', 'pay_mzfid', 'pay_mzfkey',
        'epay_url', 'epay_pid', 'epay_key',
        'usdt_proxy', 'usdt_wallet', 'usdt_expiration_time', 'usdt_rate', 'usdt_apiurl', 'usdt_apitoken',
        'is_payurl', 'pay_domain',
        'blfk_pay_alipay', 'blfk_pay_wxpay', 'blfk_pay_Unionpay', 'blfk_pay_usdtpay',
        'wx_h5', 'wx_jsapi', 'balance_pay_only', 'recharge_kami_only', 'recharge_kami_open',
        'qiniu_ak', 'qiniu_sk', 'qiniu_bucket', 'qiniu_domain',
        'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_senduser',
        'main_webhost', 'sub_webhost', 'sub_year', 'sub_forever', 'sub_zizhu',
        'auth_key', 'web_site_close', 'list_rows',
        'select_mcard', 'select_mobile', 'select_cookie', 'select_openid',
        'web_site_cnzz', 'web_music',
    ];

    /** 主站仅超级管理员（uid=1）可改 */
    public static $adminSuperOnly = [
        'token', 'access_pwd', 'accessencryption', 'admin_allow_ip',
        'alisms_appsecret', 'Geetest_KEY', 'turnstile_secret_key', 'rsa_private_key', 'wx_apiKey', 'wx_appKey',
        'pay_mqkey', 'pay_yzfkey', 'pay_mzfkey', 'epay_key', 'usdt_apitoken',
        'qiniu_sk', 'mail_password',
        // 页脚/挂件 HTML（客服、统计等）
        'web_site_cnzz', 'web_music',
        // 短链接开关（会改写路由文件）
        'shorturl',
    ];

    /** 站点图片类配置字段 */
    public static $imagePathKeys = [
        'web_logo', 'jifen_logo', 'weixin_erweima', 'web_wapbanner', 'web_pcbanner',
    ];

    /** 主站配置、分站与分销相关设置允许写入的键 */
    public static $adminAllow = [
        // 基本
        'web_title', 'web_logo', 'jifen_logo', 'weixin_erweima', 'm_title', 'shop_name', 'web_host',
        'web_site_title', 'web_site_description', 'web_site_keyword', 'web_site_icp',
        'web_site_cnzz', 'web_music', 'web_site_copy', 'web_qq', 'web_mobile', 'web_wangwang', 'web_site_close',
        // 查询
        'list_rows', 'select_mcard', 'select_mobile', 'select_cookie', 'select_openid',
        // 系统
        'admin_allow_ip', 'is_https', 'accessencryption', 'access_pwd',
        'loginerrornum', 'frozentime',
        'login_ip_max', 'login_ip_window', 'login_ip_ban', 'login_account_max', 'login_account_window',
        'web_gzh', 'shorturl',
        // 短信 / 验证码
        'alisms_appkey', 'alisms_appsecret', 'alisms_signname',
        'alimoban_id', 'alimoban_reg', 'alimoban_fahuo', 'alimoban_tixian',
        'alimoban_tixiandaozhang', 'alimoban_tixianchehui', 'alimoban_resetpwd',
        'CODE_TYPE', 'Geetest_ID', 'Geetest_KEY',
        'turnstile_site_key', 'turnstile_secret_key', 'turnstile_fallback_image',
        // 支付
        'balance_pay_only', 'recharge_kami_only', 'recharge_kami_open',
        'blfk_pay_alipay', 'blfk_pay_wxpay', 'blfk_pay_Unionpay', 'blfk_pay_usdtpay',
        'wx_h5', 'wx_jsapi',
        'app_id', 'ali_public_key', 'rsa_private_key', 'mch_id', 'wx_appid', 'wx_apiKey', 'wx_appKey',
        'pay_mqkey', 'pay_yzfid', 'pay_yzfkey', 'pay_mzfid', 'pay_mzfkey',
        'epay_url', 'epay_pid', 'epay_key',
        'usdt_proxy', 'usdt_wallet', 'usdt_expiration_time', 'usdt_rate', 'usdt_apiurl', 'usdt_apitoken',
        'is_payurl', 'pay_domain',
        // token（仅超管且非占位文案）
        'token',
        // 前端
        'cdnpublic', 'lianxi_mobile', 'lianxi_email', 'web_daohang', 'web_louceng', 'web_lishi',
        'm_status', 'wap_moban2_css', 'wap_moban3_css', 'pc_jingdian_css',
        'kami_tou', 'kami_wei', 'tangg', 'tanggjiange', 'tangg_content',
        'order_disclaimer', 'order_disclaimer_wait',
        'waphot', 'web_wapbanner', 'web_pcbanner',
        'moban_index', 'wapmoban_index',
        // 颜色
        'hotcolor', 'baocolor', 'pc_jingdian_title_beijing_color', 'pc_jingdian_title_color',
        'pc_jingdian_gg_color', 'pc_jd_footer_color',
        // 邮箱
        'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_senduser',
        // 会员注册
        'web_reg_type', 'web_reg_xingshi', 'web_reg_status', 'web_reg_point', 'web_reg_money',
        // 七牛
        'qiniu_ak', 'qiniu_sk', 'qiniu_bucket', 'qiniu_domain',
        // 分站 / 分销（subwebsite / market）
        'main_webhost', 'sub_zizhu', 'sub_year', 'sub_forever', 'sub_webhost', 'fz_selectauth',
        'fz_money', 'fz_host', 'fz_ip',
        'fx_cengji', 'fx_neigou', 'fx_pid1', 'fx_pid2', 'fx_pid3', 'fx_point',
        'fx_txmoney', 'fx_txcount', 'fx_sxftype', 'fx_sxf',
    ];

    /**
     * 过滤主站待保存配置
     * @param array $posted
     * @param int   $uid
     * @return array
     */
    public static function filterAdminSave(array $posted, $uid = 0)
    {
        $out = [];
        $allow = array_flip(self::$adminAllow);
        $super = array_flip(self::$adminSuperOnly);
        foreach ($posted as $name => $value) {
            if (!is_string($name) || !isset($allow[$name])) {
                continue;
            }
            if (is_array($value)) {
                continue;
            }
            if ($value === '***密文***') {
                continue;
            }
            if (isset($super[$name]) && (int)$uid !== 1) {
                continue;
            }
            if ($name === 'token') {
                // 停用页占位文案，禁止写库
                if ($value === '' || strpos((string)$value, '废弃') !== false || strpos((string)$value, '*') !== false) {
                    continue;
                }
            }
            if ($name === 'cdnpublic') {
                if (!in_array((string)$value, self::$cdnpublicAllow, true)) {
                    continue;
                }
            }
            if ($name === 'is_https' && !in_array((string)$value, ['http', 'https'], true)) {
                continue;
            }
            if ($name === 'shorturl') {
                $value = ((string)$value === '1') ? '1' : '0';
            }
            if ($name === 'web_site_cnzz' || $name === 'web_music') {
                $value = self::sanitizeTrustedHtml((string)$value);
            }
            if ($name === 'kami_tou' || $name === 'kami_wei'
                || $name === 'order_disclaimer' || $name === 'tangg_content') {
                $value = self::sanitizeCardHtml((string)$value);
            }
            if ($name === 'order_disclaimer_wait') {
                $w = (int)$value;
                $value = (string)($w < 1 ? 10 : $w);
            }
            if (in_array($name, self::$imagePathKeys, true)) {
                $checked = self::sanitizeImagePath((string)$value);
                if ($checked === false) {
                    continue;
                }
                $value = $checked;
            }
            $out[$name] = is_scalar($value) ? (string)$value : '';
        }
        return $out;
    }

    /**
     * 页脚挂件 HTML：保留客服/统计脚本，去掉 iframe 等嵌入标签与危险属性
     */
    public static function sanitizeTrustedHtml($html)
    {
        $html = (string)$html;
        if ($html === '') {
            return '';
        }
        $html = preg_replace('#<\s*(iframe|frame|frameset|object|embed|meta|base|link)[^>]*>#i', '', $html);
        $html = preg_replace('#<\s*/\s*(iframe|frame|frameset|object|embed|meta|base|link)\s*>#i', '', $html);
        $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/\s(href|src|action|formaction|xlink:href)\s*=\s*([\'"]?)\s*javascript:/i', ' $1=$2#blocked:', $html);
        $html = preg_replace('/\s(href|src)\s*=\s*([\'"]?)\s*data:/i', ' $1=$2#blocked:', $html);
        return $html;
    }

    /**
     * 发卡头尾附加内容：去掉脚本与嵌入标签，保留普通排版
     */
    public static function sanitizeCardHtml($html)
    {
        $html = self::sanitizeTrustedHtml((string)$html);
        $html = preg_replace('#<\s*script\b[^>]*>.*?<\s*/\s*script\s*>#is', '', $html);
        $html = preg_replace('#<\s*script\b[^>]*/?\s*>#i', '', $html);
        return $html;
    }

    /**
     * 校验站点图片路径（相对路径、常见图片后缀；不接受外链与上级目录）
     * @return string|false
     */
    public static function sanitizeImagePath($path)
    {
        $path = trim(str_replace('\\', '/', (string)$path));
        if ($path === '') {
            return '';
        }
        if (preg_match('#^(https?:)?//#i', $path) || strpos($path, '..') !== false) {
            return false;
        }
        $path = ltrim($path, '/');
        if (!preg_match('/^[A-Za-z0-9_\-\.\/]+$/', $path)) {
            return false;
        }
        if (!preg_match('/\.(jpe?g|png|gif|webp|bmp)$/i', $path)) {
            return false;
        }
        return $path;
    }

    /**
     * 订单查询勾选框场景规范化（原 list_rows 隐式分支）
     */
    public static function normalizeOrderQueryScene(array $config)
    {
        if (!isset($config['list_rows'])) {
            return $config;
        }
        $on = function ($v) {
            return (isset($v) && ($v === 'on' || $v === '1' || $v === 1)) ? '1' : 'off';
        };
        return [
            'list_rows' => (string)$config['list_rows'],
            'select_mcard' => $on(isset($config['select_mcard']) ? $config['select_mcard'] : null),
            'select_mobile' => $on(isset($config['select_mobile']) ? $config['select_mobile'] : null),
            'select_cookie' => $on(isset($config['select_cookie']) ? $config['select_cookie'] : null),
            'select_openid' => $on(isset($config['select_openid']) ? $config['select_openid'] : null),
        ];
    }

    /**
     * 过滤分站待保存配置（不含 web_host，域名单独处理）
     */
    public static function filterChildSave(array $posted)
    {
        $out = [];
        $allow = array_flip(self::$childAllow);
        foreach ($posted as $name => $value) {
            if (!is_string($name) || !isset($allow[$name])) {
                continue;
            }
            if ($name === 'web_host') {
                continue;
            }
            if (is_array($value)) {
                continue;
            }
            if ($name === 'kami_tou' || $name === 'kami_wei') {
                $value = self::sanitizeCardHtml((string)$value);
            }
            if (in_array($name, self::$imagePathKeys, true)) {
                $checked = self::sanitizeImagePath((string)$value);
                if ($checked === false) {
                    continue;
                }
                $value = $checked;
            }
            $out[$name] = is_scalar($value) ? (string)$value : '';
        }
        return $out;
    }

    /**
     * 分站配置合并进主站运行配置前的过滤
     */
    public static function filterChildRuntime(array $child)
    {
        $out = [];
        $allow = array_flip(self::$childAllow);
        $deny = array_flip(self::$childRuntimeDeny);
        foreach ($child as $k => $v) {
            $key = is_string($k) ? $k : (string)$k;
            if (isset($deny[$key])) {
                continue;
            }
            if (!isset($allow[$key])) {
                continue;
            }
            $out[$key] = $v;
        }
        return $out;
    }

    /**
     * 校验并规范化分站域名（前缀、后缀白名单、占用检测）
     * @return array{ok:bool,url?:string,msg?:string,skip?:bool}
     */
    public static function validateChildHost($hostprefix, $merchanthost, $memberId)
    {
        $hostprefix = strtolower(trim((string)$hostprefix));
        $merchanthost = trim((string)$merchanthost);

        // 空后缀 = 不修改域名
        if ($merchanthost === '') {
            return ['ok' => true, 'skip' => true];
        }

        $hostprefix = preg_replace('/^https?:\/\//', '', $hostprefix);
        $hostprefix = rtrim($hostprefix, '.');
        $merchanthost = ltrim($merchanthost, '.');

        if ($hostprefix === '' || !preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/i', $hostprefix)) {
            return ['ok' => false, 'msg' => '域名前缀不合法'];
        }

        $subhosts = array_filter(array_map('trim', explode(',', (string)config('sub_webhost'))));
        $suffixOk = false;
        foreach ($subhosts as $sub) {
            $sub = ltrim(strtolower($sub), '.');
            if ($sub !== '' && strtolower($merchanthost) === $sub) {
                $suffixOk = true;
                break;
            }
        }
        if (!$suffixOk) {
            return ['ok' => false, 'msg' => '域名后缀不在允许列表中'];
        }

        $url = strtolower($hostprefix . '.' . ltrim($merchanthost, '.'));

        $mains = array_filter(array_map('trim', explode(',', (string)config('main_webhost'))));
        foreach ($mains as $main) {
            if (strtolower($main) === $url) {
                return ['ok' => false, 'msg' => '不能使用主站域名'];
            }
        }

        $occupied = \think\Db::name('member')
            ->where('fzhost', $url)
            ->where('id', '<>', (int)$memberId)
            ->find();
        if ($occupied) {
            return ['ok' => false, 'msg' => '域名已被占用'];
        }

        $cfgHit = \think\Db::name('child_config')
            ->where(['name' => 'web_host', 'value' => $url])
            ->where('memberid', '<>', (int)$memberId)
            ->find();
        if ($cfgHit) {
            return ['ok' => false, 'msg' => '域名已被占用'];
        }

        return ['ok' => true, 'skip' => false, 'url' => $url];
    }
}
