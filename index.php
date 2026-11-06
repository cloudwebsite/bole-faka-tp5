<?php
/**
 * 项目根目录误访问引导页
 * 正式站点运行目录必须指向 public/
 */
header('Content-Type: text/html; charset=utf-8');

$publicExists = is_dir(__DIR__ . DIRECTORY_SEPARATOR . 'public')
    && is_file(__DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php');
$publicUrl = 'public/';
$nginxRule = <<<'NGINX'
location / {
    if (!-e $request_filename) {
        rewrite ^(.*)$ /index.php/$1 last;
    }
}
NGINX;
$nginxRule = trim($nginxRule) . "\n";
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>伯乐发卡 · 请设置运行目录为 public</title>
<style>
:root {
  --bg: #f0f4f8;
  --card: #fff;
  --text: #1a2332;
  --muted: #6b7a90;
  --line: #e2e8f0;
  --accent: #2563eb;
  --ok: #16a34a;
  --warn: #b45309;
}
* { box-sizing: border-box; }
body {
  margin: 0; min-height: 100vh; color: var(--text);
  font: 14px/1.75 "Segoe UI", "PingFang SC", "Microsoft YaHei", sans-serif;
  background:
    radial-gradient(1000px 420px at 8% -8%, #dbeafe 0%, transparent 55%),
    radial-gradient(800px 360px at 100% 0%, #ccfbf1 0%, transparent 50%),
    var(--bg);
}
.wrap { max-width: 820px; margin: 0 auto; padding: 36px 16px 56px; }
.brand { font-size: 12px; letter-spacing: .12em; text-transform: uppercase; color: #0d9488; font-weight: 700; }
h1 { margin: 8px 0 8px; font-size: 26px; line-height: 1.35; }
.lead { margin: 0 0 22px; color: var(--muted); }
.card {
  background: var(--card); border: 1px solid var(--line); border-radius: 14px;
  padding: 18px 20px; margin-bottom: 14px; box-shadow: 0 10px 28px rgba(15, 23, 42, .05);
}
.card h2 { margin: 0 0 10px; font-size: 16px; }
.alert {
  padding: 12px 14px; border-radius: 12px; margin-bottom: 14px;
  border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8;
}
.alert.warn { border-color: #fcd34d; background: #fffbeb; color: var(--warn); }
.alert.ok { border-color: #86efac; background: #f0fdf4; color: #166534; }
ol { margin: 0; padding-left: 20px; }
li { margin: 4px 0; }
code, .path {
  font-family: Consolas, Monaco, monospace;
  background: #f3f4f6; padding: 1px 7px; border-radius: 6px;
}
.muted { color: var(--muted); font-size: 13px; }
.prebox {
  position: relative; margin-top: 10px; background: #0f172a; color: #e2e8f0;
  border-radius: 10px; padding: 14px 14px 14px; overflow: auto;
}
.prebox pre {
  margin: 0; font: 12.5px/1.65 Consolas, Monaco, monospace; white-space: pre;
}
.prebox button {
  position: absolute; top: 10px; right: 10px; border: 0; border-radius: 8px;
  height: 30px; padding: 0 12px; background: #334155; color: #fff;
  font-size: 12px; font-weight: 600; cursor: pointer;
}
.prebox button:hover { background: #475569; }
.actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 6px; }
.btn {
  display: inline-flex; align-items: center; justify-content: center;
  min-height: 40px; padding: 0 16px; border-radius: 10px; text-decoration: none;
  font-weight: 700; font-size: 14px;
}
.btn-primary { background: var(--accent); color: #fff; }
.btn-ghost { background: #fff; color: #334155; border: 1px solid #cbd5e1; }
</style>
</head>
<body>
<div class="wrap">
  <div class="brand">来利云 · 伯乐发卡高级版</div>
  <h1>请把网站运行目录指向 public</h1>
  <p class="lead">你现在打开的是<strong>项目根目录</strong>。ThinkPHP 站点必须把面板「运行目录 / 网站目录」设为 <code>public</code>，前后台路由与静态资源才会正常。</p>

  <?php if ($publicExists): ?>
  <div class="alert ok">已检测到本目录下存在 <code>public/index.php</code>。改好运行目录后，也可先点下方按钮试访问。</div>
  <?php else: ?>
  <div class="alert warn">未检测到 <code>public/index.php</code>。若尚未安装源码，请先完成整包上传或单文件在线安装。</div>
  <?php endif; ?>

  <div class="card">
    <h2>宝塔面板 · 运行目录</h2>
    <ol>
      <li>打开 <code>网站</code> → 当前站点 <code>设置</code> → <code>网站目录</code></li>
      <li>运行目录选中项目下的 <code>/public</code></li>
      <li>保存后，再配置下方 Nginx 伪静态</li>
      <li>重新访问域名首页完成安装或进入站点</li>
    </ol>
    <p class="muted" style="margin:10px 0 0;">示例：项目在 <code>/www/wwwroot/你的域名</code>，则运行目录为 <code>/www/wwwroot/你的域名/public</code></p>
  </div>

  <div class="card">
    <h2>Nginx 伪静态（运行目录 = public 后粘贴）</h2>
    <p class="muted" style="margin:0;">宝塔：网站 → 设置 → <strong>伪静态</strong> → 清空后粘贴（不要包 <code>server {}</code>）。Apache 一般用 <code>public/.htaccess</code> 即可。</p>
    <div class="prebox">
      <button type="button" id="btnCopy">复制</button>
      <pre id="nginxRule"><?= htmlspecialchars($nginxRule, ENT_QUOTES, 'UTF-8') ?></pre>
    </div>
  </div>

  <div class="card">
    <h2>改完后常见现象</h2>
    <div class="muted">未指向 <code>public</code> 时：安装页打不开、后台路由 404、CSS/JS 丢失、前台无法渲染。改完并配好伪静态后刷新即可。</div>
  </div>

  <div class="actions">
    <?php if ($publicExists): ?>
    <a class="btn btn-primary" href="<?= htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8') ?>">先访问 public/</a>
    <?php endif; ?>
    <a class="btn btn-ghost" href="javascript:location.reload();">设置后刷新本页</a>
  </div>
</div>
<script>
(function () {
  var btn = document.getElementById('btnCopy');
  var pre = document.getElementById('nginxRule');
  if (!btn || !pre) return;
  btn.addEventListener('click', function () {
    var text = pre.textContent || '';
    function ok() {
      btn.textContent = '已复制';
      setTimeout(function () { btn.textContent = '复制'; }, 1500);
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(ok).catch(function () {
        fallback();
      });
    } else {
      fallback();
    }
    function fallback() {
      var ta = document.createElement('textarea');
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy'); ok(); } catch (e) {}
      document.body.removeChild(ta);
    }
  });
})();
</script>
</body>
</html>
