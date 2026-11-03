<?php
namespace app\admin\controller;

use think\Config;
use think\Db;

/**
 * 在线更新：融合 Git 双源（GitHub / Gitee）升级
 *
 * - GitHub: cloudwebsite/bole-faka-tp5
 * - Gitee:  lailiyun/bole-faka-tp5
 *
 * 配置：直接使用 data/git_update.php；Token 由后台弹窗写回同一文件
 * 文件更新完成后自动执行 upgrade/sql/*.sql（见 syncSql）
 */
class Upgrade extends Base
{
    /** @var \GitOnlineUpdate\GitUpdater|null */
    protected $updater;

    public function _initialize()
    {
        parent::_initialize();
        $autoload = ROOT_PATH . 'extend' . DS . 'GitOnlineUpdate' . DS . 'autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;
        }
    }

    /**
     * 升级页
     */
    public function index()
    {
        $cfg = $this->loadUserConfig();
        $mirrors = isset($cfg['mirrors']) && is_array($cfg['mirrors']) ? $cfg['mirrors'] : [];
        $this->assign('git_provider', isset($cfg['provider']) ? $cfg['provider'] : 'github');
        $this->assign('git_ref', isset($cfg['ref']) ? $cfg['ref'] : 'main');
        $this->assign('git_mirrors', $mirrors);
        $this->assign('token_github_set', $this->mirrorTokenSet($mirrors, 'github'));
        $this->assign('token_gitee_set', $this->mirrorTokenSet($mirrors, 'gitee'));
        $localCommit = $this->readLocalCommit();
        $this->assign('local_version', $this->readLocalVersion());
        $this->assign('local_commit', $localCommit);
        $this->assign('local_commit_short', $localCommit !== '' ? substr($localCommit, 0, 12) : '');
        $this->assign('upgrade_disabled', false);
        return $this->fetch();
    }

    /**
     * 弹窗保存 GitHub / Gitee Token（写回 data/git_update.php）
     */
    public function saveToken()
    {
        if (!request()->isPost() && !request()->isAjax()) {
            return json(['code' => 0, 'msg' => '非法请求']);
        }
        $github = trim((string) input('post.github_token', ''));
        $gitee = trim((string) input('post.gitee_token', ''));
        $clearGithub = (int) input('post.clear_github', 0) === 1;
        $clearGitee = (int) input('post.clear_gitee', 0) === 1;

        try {
            $cfg = $this->loadUserConfig();
            if (!isset($cfg['mirrors']) || !is_array($cfg['mirrors'])) {
                $cfg['mirrors'] = [];
            }
            foreach (['github', 'gitee'] as $name) {
                if (!isset($cfg['mirrors'][$name]) || !is_array($cfg['mirrors'][$name])) {
                    $cfg['mirrors'][$name] = [
                        'owner' => $name === 'github' ? 'cloudwebsite' : 'lailiyun',
                        'repo'  => 'bole-faka-tp5',
                        'token' => '',
                    ];
                }
            }
            if ($clearGithub) {
                $cfg['mirrors']['github']['token'] = '';
            } elseif ($github !== '') {
                $cfg['mirrors']['github']['token'] = $github;
            }
            if ($clearGitee) {
                $cfg['mirrors']['gitee']['token'] = '';
            } elseif ($gitee !== '') {
                $cfg['mirrors']['gitee']['token'] = $gitee;
            }
            $this->writeUserConfig($cfg);
            return json([
                'code' => 1,
                'msg'  => 'Token 已保存',
                'data' => [
                    'github_set' => $this->mirrorTokenSet($cfg['mirrors'], 'github'),
                    'gitee_set'  => $this->mirrorTokenSet($cfg['mirrors'], 'gitee'),
                ],
            ]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 兼容旧前端：返回「是否有更新」摘要
     */
    public function getVersion()
    {
        try {
            $updater = $this->makeUpdater();
            $check = $updater->check();
            if ((int)$check['code'] === 0) {
                return json(['code' => 1, 'msg' => $check['msg'], 'data' => []]);
            }
            if ((int)$check['code'] === 1 && !empty($check['data'])) {
                $logs = isset($check['data']['logs']) ? $check['data']['logs'] : [];
                $content = '';
                foreach ($logs as $log) {
                    $content .= '<p>' . htmlspecialchars(isset($log['title']) ? $log['title'] : '', ENT_QUOTES, 'UTF-8') . '</p>';
                }
                if ($content === '') {
                    $content = '<p>发现新版本，请使用页面「开始更新」。</p>';
                }
                $row = [
                    'version'   => isset($check['data']['version']) ? $check['data']['version'] : '',
                    'content'   => $content,
                    'isupgrade' => 1,
                ];
                return json(['code' => 1, 'msg' => 'ok', 'data' => [$row]]);
            }
            return json(['code' => 0, 'msg' => isset($check['msg']) ? $check['msg'] : '检查失败', 'data' => []]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'msg' => $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * Git 升级 API 分发（供 updater-client.js 调用）
     * 参数：action=data|check|down|...&provider=github|gitee
     */
    public function api()
    {
        if (!request()->isAjax() && !request()->isPost() && strtoupper(request()->method()) !== 'GET') {
            return json(['code' => 0, 'msg' => '非法请求']);
        }
        $action = trim((string) input('action', ''));
        if ($action === '') {
            return json(['code' => 0, 'msg' => '缺少 action']);
        }
        try {
            $updater = $this->makeUpdater();
            $params = input();
            if (!is_array($params)) {
                $params = [];
            }
            $ret = $updater->handle($action, $params);
            return json($ret);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'msg' => $e->getMessage(), 'data' => null]);
        }
    }

    /**
     * 更新完成后同步数据结构：自动执行 upgrade/sql 中未跑过的脚本
     * 参数 dry_run=1 仅预览
     */
    public function syncSql()
    {
        if (!request()->isAjax() && !request()->isPost() && strtoupper(request()->method()) !== 'GET') {
            return json(['code' => 0, 'msg' => '非法请求']);
        }
        try {
            $dry = (int) input('dry_run', 0) === 1;
            $ret = $this->makeSqlMigrator()->run($dry);
            return json([
                'code' => !empty($ret['ok']) ? 1 : 0,
                'msg'  => isset($ret['msg']) ? $ret['msg'] : '',
                'data' => $ret,
            ]);
        } catch (\Throwable $e) {
            return json(['code' => 0, 'msg' => $e->getMessage(), 'data' => null]);
        }
    }

    /**
     * 旧接口占位：请走 Git 升级流程
     */
    public function down($version = '')
    {
        return json(['code' => 2, 'msg' => '请使用本页「开始更新」（Git 双源升级），不再支持旧云端升级包。']);
    }

    public function install($file = '', $version = '')
    {
        return json(['code' => 2, 'msg' => '请使用本页「开始更新」（Git 双源升级），不再支持旧云端升级包。']);
    }

    /**
     * @return \GitOnlineUpdate\GitUpdater
     */
    protected function makeUpdater()
    {
        if (!class_exists('\\GitOnlineUpdate\\GitUpdater')) {
            throw new \RuntimeException('未找到 GitOnlineUpdate 组件，请确认 extend/GitOnlineUpdate 已部署');
        }
        $user = $this->loadUserConfig();
        $provider = strtolower(trim((string) input('provider', '')));
        if ($provider === '' || !isset($user['mirrors'][$provider])) {
            $provider = isset($user['provider']) ? strtolower($user['provider']) : 'github';
        }
        if (!isset($user['mirrors'][$provider]) || !is_array($user['mirrors'][$provider])) {
            throw new \RuntimeException('未配置镜像源: ' . $provider);
        }
        $mirror = $user['mirrors'][$provider];

        $root = rtrim(ROOT_PATH, '/\\');
        $config = [
            'provider'            => $provider,
            'owner'               => isset($mirror['owner']) ? $mirror['owner'] : '',
            'repo'                => isset($mirror['repo']) ? $mirror['repo'] : '',
            'token'               => isset($mirror['token']) ? $mirror['token'] : '',
            'ref'                 => isset($user['ref']) ? $user['ref'] : 'main',
            'root'                => $root . DIRECTORY_SEPARATOR,
            'cache'               => $root . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'git_update' . DIRECTORY_SEPARATOR,
            'version_file'        => $root . DIRECTORY_SEPARATOR . 'version',
            'commit_file'         => $root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'commit',
            'version_strategy'    => isset($user['version_strategy']) ? $user['version_strategy'] : 'file',
            'remote_version_path' => isset($user['remote_version_path']) ? $user['remote_version_path'] : 'version',
            'changelog_path'      => isset($user['changelog_path']) ? $user['changelog_path'] : 'CHANGELOG.md',
            'changelog_limit'     => isset($user['changelog_limit']) ? (int)$user['changelog_limit'] : 20,
            'archive_threshold'   => isset($user['archive_threshold']) ? (int)$user['archive_threshold'] : 80,
            'auto_star'           => !isset($user['auto_star']) || $user['auto_star'],
            'star_all_mirrors'    => !isset($user['star_all_mirrors']) || $user['star_all_mirrors'],
            'mirrors'             => isset($user['mirrors']) ? $user['mirrors'] : [],
            'power_dirs'          => [
                ''             => '根目录',
                'application'  => 'application',
                'public'       => 'public',
                'extend'       => 'extend',
                'thinkphp'     => 'thinkphp',
                'runtime'      => 'runtime',
            ],
            'power_files' => [],
            // 另有 Fs::criticalSkipPaths / shouldSkip 硬编码兜底，防止漏配整包覆盖本机配置
            'skip_files'  => [
                'README.md',
                'LICENSE.md',
                'LICENSE',
                '.gitignore',
                '.env',
                'conn.php',
                'public/favicon.ico',
                'favicon.ico',
                'application/config.php',
                'application/admin.php',
                'data/git_update.php',
                'data/commit',
                'data/install.lock',
                'data/sql_applied.json',
                'application/database.php',
                'application/admin/database.php',
                'application/jingdian/database.php',
                'application/mobile/database.php',
                'application/madmin/database.php',
                'application/pay/database.php',
            ],
            'skip_prefixes' => [
                'data/',
                'runtime/',
                'docs/',
                '单文件安装/',
                '.git/',
                '.github/',
                '.gitee/',
                'public/upload/',
                'public/uploads/',
            ],
            'cleanup_paths' => [],
            'reset_opcache' => true,
            'ui' => [
                'releases_url' => '',
                'manual_url'   => '',
                'manual_tip'   => '跨版本过多或更新失败时，请到 GitHub/Gitee 下载完整包手动覆盖；data/、conn.php、public/favicon.ico、application/config.php、application/admin.php 不会被在线更新覆盖。升级 SQL 在 upgrade/sql/，更新结束会自动执行。',
            ],
            'on_complete' => function () {
                $wipe = function ($dir) use (&$wipe) {
                    if (!is_dir($dir)) {
                        return;
                    }
                    $items = @scandir($dir);
                    if ($items === false) {
                        return;
                    }
                    foreach ($items as $item) {
                        if ($item === '.' || $item === '..') {
                            continue;
                        }
                        $path = $dir . DIRECTORY_SEPARATOR . $item;
                        if (is_dir($path)) {
                            $wipe($path);
                            @rmdir($path);
                        } else {
                            @unlink($path);
                        }
                    }
                };
                $wipe(ROOT_PATH . 'runtime' . DS . 'cache');
                $wipe(ROOT_PATH . 'runtime' . DS . 'temp');
                if (function_exists('opcache_reset') && extension_loaded('Zend OPcache')) {
                    @opcache_reset();
                }
                $verFile = ROOT_PATH . 'version';
                if (is_file($verFile)) {
                    $v = trim((string) file_get_contents($verFile));
                    if ($v !== '') {
                        try {
                            Config::set('version', $v);
                        } catch (\Throwable $e) {
                        }
                    }
                }
                // 兜底：前端 syncUrl 失败时仍尝试执行待跑 SQL
                try {
                    $this->makeSqlMigrator()->run(false);
                } catch (\Throwable $e) {
                }
            },
        ];

        return new \GitOnlineUpdate\GitUpdater($config);
    }

    /**
     * @return \GitOnlineUpdate\Support\SqlMigrator
     */
    protected function makeSqlMigrator()
    {
        $prefix = (string) Config::get('database.prefix');
        if ($prefix === '') {
            $prefix = 'think_';
        }
        return new \GitOnlineUpdate\Support\SqlMigrator(
            ROOT_PATH,
            $prefix,
            function ($sql) {
                Db::execute($sql);
            }
        );
    }

    protected function loadUserConfig()
    {
        $file = ROOT_PATH . 'data' . DS . 'git_update.php';
        $cfg = [];
        if (is_file($file)) {
            $cfg = include $file;
        }
        if (!is_array($cfg)) {
            $cfg = [];
        }
        if (empty($cfg['mirrors']) || !is_array($cfg['mirrors'])) {
            $cfg['mirrors'] = [
                'github' => [
                    'owner' => 'cloudwebsite',
                    'repo'  => 'bole-faka-tp5',
                    'token' => '',
                ],
                'gitee' => [
                    'owner' => 'lailiyun',
                    'repo'  => 'bole-faka-tp5',
                    'token' => '',
                ],
            ];
        }
        foreach (['github', 'gitee'] as $name) {
            if (!isset($cfg['mirrors'][$name]) || !is_array($cfg['mirrors'][$name])) {
                $cfg['mirrors'][$name] = [
                    'owner' => $name === 'github' ? 'cloudwebsite' : 'lailiyun',
                    'repo'  => 'bole-faka-tp5',
                    'token' => '',
                ];
            }
            if (!isset($cfg['mirrors'][$name]['owner'])) {
                $cfg['mirrors'][$name]['owner'] = $name === 'github' ? 'cloudwebsite' : 'lailiyun';
            }
            if (!isset($cfg['mirrors'][$name]['repo'])) {
                $cfg['mirrors'][$name]['repo'] = 'bole-faka-tp5';
            }
            if (!array_key_exists('token', $cfg['mirrors'][$name])) {
                $cfg['mirrors'][$name]['token'] = '';
            }
        }
        if (empty($cfg['provider'])) {
            $cfg['provider'] = 'github';
        }
        if (empty($cfg['ref'])) {
            $cfg['ref'] = 'main';
        }
        return $cfg;
    }

    protected function readLocalVersion()
    {
        $f = ROOT_PATH . 'version';
        if (is_file($f)) {
            return trim((string) file_get_contents($f));
        }
        return (string) config('version');
    }

    protected function readLocalCommit()
    {
        $f = ROOT_PATH . 'data' . DS . 'commit';
        if (!is_file($f)) {
            return '';
        }
        return trim((string) file_get_contents($f));
    }

    protected function mirrorTokenSet(array $mirrors, $name)
    {
        return !empty($mirrors[$name]['token']);
    }

    /**
     * 写回 data/git_update.php（保留仓库/分支等，更新 Token）
     */
    protected function writeUserConfig(array $cfg)
    {
        $dir = ROOT_PATH . 'data';
        $file = $dir . DS . 'git_update.php';
        if (!is_dir($dir)) {
            throw new \RuntimeException('data 目录不存在');
        }
        if (is_file($file) && !is_writable($file)) {
            throw new \RuntimeException('data/git_update.php 不可写');
        }
        if (!is_file($file) && !is_writable($dir)) {
            throw new \RuntimeException('data 目录不可写');
        }
        $out = [
            'provider'            => isset($cfg['provider']) ? $cfg['provider'] : 'github',
            'ref'                 => isset($cfg['ref']) ? $cfg['ref'] : 'main',
            'mirrors'             => [],
            'auto_star'           => !isset($cfg['auto_star']) || $cfg['auto_star'],
            'star_all_mirrors'    => !isset($cfg['star_all_mirrors']) || $cfg['star_all_mirrors'],
            'version_strategy'    => isset($cfg['version_strategy']) ? $cfg['version_strategy'] : 'file',
            'remote_version_path' => isset($cfg['remote_version_path']) ? $cfg['remote_version_path'] : 'version',
            'archive_threshold'   => isset($cfg['archive_threshold']) ? (int)$cfg['archive_threshold'] : 80,
        ];
        foreach (['github', 'gitee'] as $name) {
            $out['mirrors'][$name] = [
                'owner' => isset($cfg['mirrors'][$name]['owner']) ? $cfg['mirrors'][$name]['owner'] : ($name === 'github' ? 'cloudwebsite' : 'lailiyun'),
                'repo'  => isset($cfg['mirrors'][$name]['repo']) ? $cfg['mirrors'][$name]['repo'] : 'bole-faka-tp5',
                'token' => isset($cfg['mirrors'][$name]['token']) ? $cfg['mirrors'][$name]['token'] : '',
            ];
        }
        $export = var_export($out, true);
        $content = "<?php\n/**\n * Git 在线升级配置（直接使用）\n * Token 由后台弹窗写回；勿把带 Token 的版本提交到公开仓库\n */\nreturn " . $export . ";\n";
        $ok = @file_put_contents($file, $content, LOCK_EX);
        if ($ok === false) {
            throw new \RuntimeException('写入 data/git_update.php 失败');
        }
    }

}
