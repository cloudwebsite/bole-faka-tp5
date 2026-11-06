<?php
namespace GitOnlineUpdate;

use GitOnlineUpdate\Http\Client;
use GitOnlineUpdate\Provider\GiteeProvider;
use GitOnlineUpdate\Provider\GitHubProvider;
use GitOnlineUpdate\Provider\ProviderInterface;
use GitOnlineUpdate\Support\Fs;
use GitOnlineUpdate\Support\Response;

/**
 * Git 在线升级主入口
 *
 * 支持 action:
 *  data | check | down | copy | down_info | down_zip | down_status
 *  copy_zip | remove | re_version
 */
class GitUpdater
{
    /** @var array */
    protected $config;

    /** @var ProviderInterface */
    protected $provider;

    /** @var Client */
    protected $http;

    public function __construct(array $config)
    {
        $this->config = array_merge($this->defaults(), $config);
        $this->http = new Client([
            'timeout'    => $this->config['timeout'],
            'user_agent' => $this->config['user_agent'],
        ]);
        $this->provider = $this->makeProvider($this->config['provider']);
        Fs::mkdir($this->config['cache']);
    }

    public static function fromConfigFile($file)
    {
        if (!is_file($file)) {
            throw new \InvalidArgumentException('配置文件不存在: ' . $file);
        }
        $config = require $file;
        if (!is_array($config)) {
            throw new \InvalidArgumentException('配置文件必须返回 array');
        }
        return new self($config);
    }

    protected function defaults()
    {
        return [
            'provider'            => 'gitee',
            'owner'               => '',
            'repo'                => '',
            'token'               => '',
            'ref'                 => 'master',
            'root'                => '',
            'cache'               => sys_get_temp_dir() . '/git-online-update/',
            'version_file'        => '',
            'commit_file'         => '',
            'version_strategy'    => 'file',
            'remote_version_path' => 'version',
            // 产品更新日志（展示用；优先于纯 git 提交说明）
            'changelog_path'      => 'CHANGELOG.md',
            'changelog_limit'     => 20,
            'power_dirs'          => ['' => '根目录'],
            'power_files'         => [],
            'skip_files'          => ['README.md', 'LICENSE.md', '.gitignore'],
            'skip_prefixes'       => ['.github/', '.gitee/'],
            'archive_threshold'   => 80,
            'archive_name'        => 'update.zip',
            'cleanup_paths'       => [],
            'reset_opcache'       => true,
            'timeout'             => 60,
            'user_agent'          => 'GitOnlineUpdate/1.0',
            'on_complete'         => null,
            'ui'                  => [],
            // 检查更新 / 更新完成时尝试给仓库点 Star（需对应 token）
            'auto_star'           => true,
            // 同时给 mirrors 里其它源也点 Star
            'star_all_mirrors'    => true,
            // ['github'=>['owner'=>'','repo'=>'','token'=>''], 'gitee'=>...]
            'mirrors'             => [],
        ];
    }

    protected function makeProvider($name)
    {
        $name = strtolower((string) $name);
        if ($name === 'github') {
            return new GitHubProvider($this->config, $this->http);
        }
        return new GiteeProvider($this->config, $this->http);
    }

    /**
     * 按镜像配置构造临时 Provider（用于双源 Star）
     */
    protected function makeProviderForMirror($name, array $mirror)
    {
        $cfg = array_merge($this->config, [
            'owner' => isset($mirror['owner']) ? $mirror['owner'] : '',
            'repo'  => isset($mirror['repo']) ? $mirror['repo'] : '',
            'token' => isset($mirror['token']) ? $mirror['token'] : '',
        ]);
        $name = strtolower((string) $name);
        if ($name === 'github') {
            return new GitHubProvider($cfg, $this->http);
        }
        return new GiteeProvider($cfg, $this->http);
    }

    public function getProvider()
    {
        return $this->provider;
    }

    public function getConfig()
    {
        return $this->config;
    }

    /**
     * 统一分发
     * @param string $action
     * @param array  $params
     * @return array JSON 结构
     */
    public function handle($action, array $params = [])
    {
        try {
            switch ($action) {
                case 'data':
                    return Response::ok('success', $this->systemData());
                case 'check':
                    return $this->check();
                case 'down':
                    return $this->downloadFile(isset($params['file']) ? $params['file'] : '');
                case 'copy':
                    return $this->applyFiles(isset($params['commitish']) ? $params['commitish'] : '');
                case 'down_info':
                    return $this->archiveInfo();
                case 'down_zip':
                    return $this->downloadArchive(isset($params['url']) ? $params['url'] : '');
                case 'down_status':
                    return Response::ok('success', $this->archiveStatus());
                case 'copy_zip':
                    return $this->applyArchive(isset($params['commitish']) ? $params['commitish'] : '');
                case 'remove':
                    $files = [];
                    if (!empty($params['files']) && is_array($params['files'])) {
                        $files = $params['files'];
                    } elseif (!empty($params['file'])) {
                        $files = [$params['file']];
                    }
                    return $this->removeFiles($files);
                case 're_version':
                    return $this->reVersion(isset($params['version']) ? $params['version'] : '');
                case 'star':
                    return $this->starNow();
                default:
                    return Response::fail('未知操作: ' . $action);
            }
        } catch (\Throwable $e) {
            return Response::fail($e->getMessage());
        }
    }

    /**
     * 处理请求并直接输出 JSON
     */
    public function dispatch($action, array $params = [], $exit = true)
    {
        Response::output($this->handle($action, $params), $exit);
    }

    public function getLocalVersion()
    {
        if ($this->config['version_file'] === '') {
            return '';
        }
        return Fs::read($this->config['version_file'], '');
    }

    public function getLocalCommit()
    {
        if ($this->config['commit_file'] === '') {
            return '';
        }
        return Fs::read($this->config['commit_file'], '');
    }

    public function systemData()
    {
        $powerdir = [];
        foreach ($this->config['power_dirs'] as $rel => $label) {
            $path = $this->absPath(is_int($rel) ? $label : $rel);
            $key = is_int($rel) ? $label : $label;
            $powerdir[$key] = Fs::isWritablePath($path);
        }

        $powerfile = true;
        foreach ($this->config['power_files'] as $rel) {
            if (!Fs::isWritablePath($this->absPath($rel))) {
                $powerfile = false;
                break;
            }
        }

        $mirrors = [];
        if (!empty($this->config['mirrors']) && is_array($this->config['mirrors'])) {
            foreach ($this->config['mirrors'] as $name => $m) {
                $mirrors[$name] = [
                    'owner' => isset($m['owner']) ? $m['owner'] : '',
                    'repo'  => isset($m['repo']) ? $m['repo'] : '',
                    'url'   => $this->mirrorHomeUrl($name, $m),
                ];
            }
        }

        return [
            'version'   => $this->getLocalVersion(),
            'commit'    => $this->getLocalCommit(),
            'ref'       => $this->config['ref'],
            'provider'  => $this->provider->name(),
            'owner'     => $this->config['owner'],
            'repo'      => $this->config['repo'],
            'mirrors'   => $mirrors,
            'powerdir'  => $powerdir,
            'powerfile' => $powerfile,
            'ui'        => array_merge([
                'releases_url' => $this->provider->getReleasesUrl(),
                'manual_url'   => '',
                'manual_tip'   => '',
            ], $this->config['ui']),
        ];
    }

    protected function mirrorHomeUrl($name, array $m)
    {
        $owner = isset($m['owner']) ? $m['owner'] : '';
        $repo = isset($m['repo']) ? $m['repo'] : '';
        if (strtolower((string) $name) === 'github') {
            return 'https://github.com/' . $owner . '/' . $repo;
        }
        return 'https://gitee.com/' . $owner . '/' . $repo;
    }

    public function check()
    {
        $this->maybeAutoStar();

        $base = $this->getLocalCommit();
        $head = $this->config['ref'];
        $localVersion = $this->getLocalVersion();
        $remoteVersion = $this->provider->getRemoteVersion(
            $head,
            $this->config['version_strategy'],
            $this->config['remote_version_path']
        );
        $headSha = $this->resolveHeadSha($head);

        // 1) 根目录 version 对比：本地不低于远端 → 已是最新（发版只需改 version 推一次）
        if ($localVersion !== '' && $this->versionCmp($remoteVersion, $localVersion) <= 0) {
            if ($headSha !== '' && (!$this->isCommitSha($base) || strtolower($base) !== $headSha)) {
                $this->writeCommit($headSha);
            }
            $logs = $this->buildDisplayLogs([], $remoteVersion, $localVersion, false);
            return Response::make(0, '已是最新版本（' . $remoteVersion . '）', [
                'version'   => $remoteVersion,
                'commitish' => $headSha !== '' ? $headSha : $head,
                'logs'      => $logs,
                'files'     => [
                    'added'    => [],
                    'modified' => [],
                    'removed'  => [],
                    'zip'      => [],
                ],
                'meta'      => [
                    'ahead_by'       => 0,
                    'status'         => 'identical',
                    'provider'       => $this->provider->name(),
                    'use_archive'    => false,
                    'by_version'     => true,
                    'commit_synced'  => ($headSha !== '' && strtolower((string) $base) !== $headSha),
                ],
            ]);
        }

        // 2) 远端版本更高，且无合法本机 commit → 整包（首次安装/旧站）
        if ($base === '' || !$this->isCommitSha($base)) {
            $reason = $base === ''
                ? '本地尚无 data/commit，且版本低于远端（' . $localVersion . ' → ' . $remoteVersion . '）'
                : ('本地 commit 无效（' . $base . '），版本低于远端');
            return $this->fullArchiveCheckResult($remoteVersion, $localVersion, $head, $headSha, $reason, true);
        }

        // 3) 有本机 commit：按提交增量（版本已判定有更新）
        // 远端若做过 orphan/force 重写历史，旧 SHA 与 main 无共同祖先 → 整包兜底
        try {
            $diff = $this->provider->compare($base, $head);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if ($this->isUnrelatedHistoryError($msg)) {
                return $this->fullArchiveCheckResult(
                    $remoteVersion,
                    $localVersion,
                    $head,
                    $headSha,
                    '本地 commit（' . substr($base, 0, 12) . '）与远端历史无共同祖先（仓库可能已全量重写）',
                    true
                );
            }
            throw $e;
        }

        $files = $diff['files'];
        $files['added'] = $this->filterPaths($files['added']);
        $files['modified'] = $this->filterPaths($files['modified']);
        $files['removed'] = $this->filterPaths($files['removed']);
        $files['zip'] = [];

        $changeCount = count($files['added']) + count($files['modified']);
        $useArchive = $this->config['archive_threshold'] > 0
            && $changeCount >= (int) $this->config['archive_threshold'];

        if ($useArchive) {
            $files['zip'] = [$this->config['archive_name']];
            $files['added'] = [];
            $files['modified'] = [];
        }

        $commitLogs = [];
        foreach ($diff['commits'] as $c) {
            $commitLogs[] = [
                'sha'     => isset($c['sha']) ? $c['sha'] : '',
                'title'   => isset($c['title']) ? $c['title'] : $remoteVersion,
                'date'    => $this->formatDate(isset($c['date']) ? $c['date'] : ''),
                'message' => isset($c['message']) ? $c['message'] : '',
            ];
        }

        // 版本更高但业务文件无差异（仅文档等被 skip）→ 仍算最新，回写 commit / 可同步 version
        if ($diff['status'] === 'identical' || ($changeCount === 0 && empty($files['removed']) && empty($files['zip']))) {
            $syncSha = '';
            if (!empty($diff['commitish']) && $this->isCommitSha($diff['commitish'])) {
                $syncSha = strtolower(trim((string) $diff['commitish']));
            }
            if ($syncSha === '') {
                $syncSha = $headSha;
            }
            if ($syncSha !== '' && strtolower($base) !== $syncSha) {
                $this->writeCommit($syncSha);
            }
            $this->maybeWriteVersionFromRemote();
            $logs = $this->buildDisplayLogs($commitLogs, $remoteVersion, $localVersion, false);
            return Response::make(0, '已是最新版本（' . $remoteVersion . '）', [
                'version'   => $remoteVersion,
                'commitish' => $syncSha !== '' ? $syncSha : ($diff['commitish'] ?: $head),
                'logs'      => $logs,
                'files'     => $files,
                'meta'      => [
                    'ahead_by'      => 0,
                    'status'        => 'identical',
                    'provider'      => $this->provider->name(),
                    'use_archive'   => false,
                    'by_version'    => true,
                    'commit_synced' => ($syncSha !== '' && strtolower($base) !== $syncSha),
                ],
            ]);
        }

        $logs = $this->buildDisplayLogs($commitLogs, $remoteVersion, $localVersion, true);
        if (!$logs) {
            $logs[] = [
                'sha'     => $diff['commitish'],
                'title'   => $remoteVersion,
                'date'    => date('Y-m-d'),
                'message' => '发现新版本 ' . $remoteVersion . '（本地 ' . $localVersion . '）',
            ];
        }

        return Response::ok('success', [
            'version'   => $remoteVersion,
            'commitish' => $diff['commitish'] ?: ($headSha !== '' ? $headSha : $head),
            'logs'      => $logs,
            'files'     => $files,
            'meta'      => [
                'ahead_by'    => $diff['ahead_by'],
                'status'      => $diff['status'],
                'provider'    => $this->provider->name(),
                'use_archive' => $useArchive,
                'by_version'  => true,
            ],
        ]);
    }

    public function downloadFile($file)
    {
        $file = $this->normalizeRel($file);
        if ($file === '' || $this->shouldSkip($file)) {
            return Response::ok('成功');
        }

        $remote = $this->provider->getContents($file, $this->config['ref']);
        if ($remote === null) {
            return Response::ok('成功'); // 远端已删，跳过
        }

        $cacheRoot = rtrim($this->config['cache'], '/\\') . DIRECTORY_SEPARATOR . 'files';
        $target = $cacheRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file);
        $dir = dirname($file);
        if ($dir !== '.' && $dir !== '') {
            Fs::mkdir($cacheRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dir));
        } else {
            Fs::mkdir($cacheRoot);
        }

        if (!Fs::write($target, $remote['content'])) {
            return Response::fail('写入缓存失败');
        }
        return Response::ok('成功');
    }

    public function applyFiles($commitish)
    {
        $cacheRoot = rtrim($this->config['cache'], '/\\') . DIRECTORY_SEPARATOR . 'files';
        if (is_dir($cacheRoot)) {
            if (!Fs::copyDir($cacheRoot, rtrim($this->config['root'], '/\\'))) {
                return Response::fail('复制文件失败');
            }
            Fs::delete($cacheRoot);
        }
        $this->writeCommit($commitish);
        $this->maybeWriteVersionFromRemote();
        return Response::ok('success');
    }

    public function archiveInfo()
    {
        $info = $this->provider->probeArchive($this->config['ref']);
        if ($info['size'] <= 0) {
            return Response::fail('无法获取更新包信息');
        }
        return Response::ok('success', [
            'url'  => $info['url'],
            'size' => $info['size'],
        ]);
    }

    public function downloadArchive($url)
    {
        if ($url === '') {
            $url = $this->provider->getArchiveUrl($this->config['ref']);
        }
        $file = $this->archivePath();
        if (is_file($file)) {
            @unlink($file);
        }
        Fs::mkdir(dirname($file));

        $headers = [];
        if ($this->provider->name() === 'github' && $this->config['token'] !== '') {
            $headers['Authorization'] = 'Bearer ' . $this->config['token'];
            $headers['Accept'] = 'application/vnd.github+json';
        }
        $this->http->download($url, $file, $headers);
        return Response::ok('success', ['size' => Fs::fileSize($file)]);
    }

    public function archiveStatus()
    {
        return Fs::fileSize($this->archivePath()) ?: 0;
    }

    public function applyArchive($commitish)
    {
        $file = $this->archivePath();
        if (!is_file($file)) {
            return Response::fail('更新包不存在');
        }
        if (!Fs::unzip($file, rtrim($this->config['root'], '/\\'), [$this, 'shouldSkip'])) {
            return Response::fail('解压失败');
        }
        Fs::delete(dirname($file));
        $this->writeCommit($commitish);
        $this->maybeWriteVersionFromRemote();
        return Response::ok('success');
    }

    public function removeFiles(array $files)
    {
        foreach ($files as $file) {
            $rel = $this->normalizeRel($file);
            if ($rel === '' || $this->shouldSkip($rel)) {
                continue;
            }
            Fs::delete($this->absPath($rel));
        }
        foreach ($this->config['cleanup_paths'] as $p) {
            Fs::delete($this->absPath($p));
        }
        if ($this->config['reset_opcache'] && function_exists('opcache_reset') && extension_loaded('Zend OPcache')) {
            @opcache_reset();
        }
        $this->maybeAutoStar();
        if (is_callable($this->config['on_complete'])) {
            call_user_func($this->config['on_complete'], [
                'version' => $this->getLocalVersion(),
                'commit'  => $this->getLocalCommit(),
                'config'  => $this->config,
            ]);
        }
        return Response::ok('成功', $this->systemData());
    }

    /**
     * 立即尝试 Star（供前端/调试）
     */
    public function starNow()
    {
        $ret = $this->provider->starRepository();
        if (!empty($ret['ok'])) {
            return Response::ok($ret['msg'], $ret);
        }
        return Response::fail(isset($ret['msg']) ? $ret['msg'] : 'Star 失败', $ret);
    }

    protected function maybeAutoStar()
    {
        if (empty($this->config['auto_star'])) {
            return;
        }
        try {
            $this->provider->starRepository();
        } catch (\Throwable $e) {
            // Star 失败不影响升级主流程
        }
        if (empty($this->config['star_all_mirrors']) || empty($this->config['mirrors']) || !is_array($this->config['mirrors'])) {
            return;
        }
        $current = $this->provider->name();
        foreach ($this->config['mirrors'] as $name => $mirror) {
            if (!is_array($mirror)) {
                continue;
            }
            if (strtolower((string) $name) === $current) {
                continue;
            }
            try {
                $this->makeProviderForMirror($name, $mirror)->starRepository();
            } catch (\Throwable $e) {
                // 忽略
            }
        }
    }

    public function reVersion($version)
    {
        if ($this->config['version_file'] === '') {
            return Response::fail('未配置 version_file');
        }
        if (Fs::write($this->config['version_file'], $version)) {
            return Response::ok('版本号回退成功');
        }
        return Response::fail('版本号回退失败');
    }

    protected function archivePath()
    {
        return rtrim($this->config['cache'], '/\\') . DIRECTORY_SEPARATOR . $this->config['archive_name'];
    }

    protected function absPath($rel)
    {
        $rel = $this->normalizeRel($rel);
        $root = rtrim($this->config['root'], '/\\');
        return $rel === '' ? $root : $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
    }

    protected function normalizeRel($path)
    {
        $path = str_replace('\\', '/', (string) $path);
        $path = ltrim($path, '/');
        if (strpos($path, '..') !== false) {
            throw new \InvalidArgumentException('非法路径');
        }
        return $path;
    }

    protected function shouldSkip($path)
    {
        $path = $this->normalizeRel($path);
        // 与 Fs::isCriticalSkip 对齐：整包/增量都永不覆盖本机敏感文件
        if (Fs::isCriticalSkip($path)) {
            return true;
        }
        $base = basename($path);
        $skipFiles = isset($this->config['skip_files']) && is_array($this->config['skip_files'])
            ? $this->config['skip_files'] : [];
        if (in_array($base, $skipFiles, true) || in_array($path, $skipFiles, true)) {
            return true;
        }
        $skipPrefixes = isset($this->config['skip_prefixes']) && is_array($this->config['skip_prefixes'])
            ? $this->config['skip_prefixes'] : [];
        foreach ($skipPrefixes as $prefix) {
            if ($prefix !== '' && strpos($path, $prefix) === 0) {
                return true;
            }
        }
        return false;
    }

    protected function filterPaths(array $paths)
    {
        $out = [];
        foreach ($paths as $p) {
            $p = $this->normalizeRel($p);
            if ($p === '' || $this->shouldSkip($p)) {
                continue;
            }
            $out[] = $p;
        }
        return array_values(array_unique($out));
    }

    /**
     * 是否像 Git commit SHA（7~40 位十六进制）
     */
    protected function isCommitSha($value)
    {
        return (bool) preg_match('/^[0-9a-f]{7,40}$/i', trim((string) $value));
    }

    /**
     * 解析分支/标签对应的远端 HEAD 完整 SHA（写入 data/commit 用）
     */
    protected function resolveHeadSha($ref = '')
    {
        $ref = trim((string) ($ref !== '' ? $ref : $this->config['ref']));
        if ($ref === '') {
            return '';
        }
        if ($this->isCommitSha($ref) && strlen($ref) >= 40) {
            return strtolower($ref);
        }
        try {
            // getRemoteVersion(..., 'commit') 返回短 SHA；再走一次完整解析
            $short = $this->provider->getRemoteVersion($ref, 'commit', '');
            if ($this->isCommitSha($short)) {
                // 尽量取完整 SHA：GitHub/Gitee commits API 短码也可 compare
                return strtolower($short);
            }
        } catch (\Throwable $e) {
        }
        return '';
    }

    protected function writeCommit($commitish)
    {
        if (!$commitish || $this->config['commit_file'] === '') {
            return;
        }
        $sha = trim((string) $commitish);
        // 禁止把分支名 main 写进 data/commit，否则下次 compare(main...main) 永远「已是最新」
        if (!$this->isCommitSha($sha)) {
            $resolved = $this->resolveHeadSha($sha);
            if ($resolved === '') {
                $resolved = $this->resolveHeadSha($this->config['ref']);
            }
            if ($resolved === '') {
                return;
            }
            $sha = $resolved;
        }
        Fs::write($this->config['commit_file'], $sha);
    }

    protected function maybeWriteVersionFromRemote()
    {
        if ($this->config['version_file'] === '') {
            return;
        }
        try {
            $v = $this->provider->getRemoteVersion(
                $this->config['ref'],
                $this->config['version_strategy'],
                $this->config['remote_version_path']
            );
            if ($v !== '') {
                Fs::write($this->config['version_file'], $v);
            }
        } catch (\Throwable $e) {
            // 忽略版本同步失败
        }
    }

    protected function formatDate($date)
    {
        if ($date === '') {
            return date('Y-m-d');
        }
        $ts = strtotime($date);
        return $ts ? date('Y-m-d H:i:s', $ts) : $date;
    }

    /**
     * 优先 CHANGELOG.md，其次 git 提交说明
     *
     * @param array  $commitLogs
     * @param string $remoteVersion
     * @param string $localVersion
     * @param bool   $onlyNewer 有更新时只展示高于本地版本的条目；已最新则展示最近若干条
     * @return array
     */
    protected function buildDisplayLogs(array $commitLogs, $remoteVersion, $localVersion = '', $onlyNewer = true)
    {
        $fromFile = $this->fetchChangelogLogs($remoteVersion, $localVersion, $onlyNewer);
        if ($fromFile) {
            return $fromFile;
        }
        $limit = (int) $this->config['changelog_limit'];
        if ($limit > 0 && count($commitLogs) > $limit) {
            return array_slice($commitLogs, 0, $limit);
        }
        return $commitLogs;
    }

    protected function fetchChangelogLogs($remoteVersion, $localVersion = '', $onlyNewer = true)
    {
        $path = isset($this->config['changelog_path']) ? $this->config['changelog_path'] : 'CHANGELOG.md';
        if ($path === '') {
            return [];
        }
        $text = '';
        try {
            $file = $this->provider->getContents($path, $this->config['ref']);
            if ($file && isset($file['content'])) {
                $text = (string) $file['content'];
            }
        } catch (\Throwable $e) {
            $text = '';
        }
        if ($text === '') {
            $local = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->config['root']), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            if (is_file($local)) {
                $text = (string) @file_get_contents($local);
            }
        }
        if (trim($text) === '') {
            return [];
        }
        $all = $this->parseChangelog($text);
        if (!$all) {
            return [];
        }
        if ($onlyNewer && $localVersion !== '') {
            $filtered = [];
            foreach ($all as $item) {
                $ver = isset($item['title']) ? $item['title'] : '';
                if ($ver !== '' && version_compare($this->normalizeVersion($ver), $this->normalizeVersion($localVersion), '>')) {
                    $filtered[] = $item;
                }
            }
            // 本地版本文件未跟上时，至少展示当前远程版本条目
            if (!$filtered && $remoteVersion !== '') {
                foreach ($all as $item) {
                    $ver = isset($item['title']) ? $item['title'] : '';
                    if ($ver !== '' && version_compare($this->normalizeVersion($ver), $this->normalizeVersion($remoteVersion), '<=')) {
                        $filtered[] = $item;
                        break;
                    }
                }
            }
            $all = $filtered ?: $all;
        }
        $limit = (int) $this->config['changelog_limit'];
        if ($limit > 0 && count($all) > $limit) {
            $all = array_slice($all, 0, $limit);
        }
        return $all;
    }

    /**
     * 解析 CHANGELOG.md：## 1.0.1 - 2026-11-03
     */
    protected function parseChangelog($text)
    {
        $text = str_replace(["\r\n", "\r"], "\n", (string) $text);
        $lines = explode("\n", $text);
        $logs = [];
        $cur = null;
        foreach ($lines as $line) {
            if (preg_match('/^#{2,3}\s*v?(\d+(?:\.\d+){1,3})\s*(?:[\-\x{2013}\x{2014}:\(]\s*)?(\d{4}-\d{2}-\d{2})?\)?\s*$/u', trim($line), $m)) {
                if ($cur) {
                    $logs[] = $cur;
                }
                $cur = [
                    'sha'     => '',
                    'title'   => $m[1],
                    'date'    => !empty($m[2]) ? $m[2] : '',
                    'message' => '',
                ];
                continue;
            }
            if ($cur === null) {
                continue;
            }
            $trim = trim($line);
            if ($trim === '' || preg_match('/^#\s+/', $trim)) {
                continue;
            }
            $cur['message'] .= ($cur['message'] === '' ? '' : "\n") . $trim;
        }
        if ($cur) {
            $logs[] = $cur;
        }
        return $logs;
    }

    protected function normalizeVersion($ver)
    {
        $ver = trim((string) $ver);
        $ver = ltrim($ver, 'vV');
        if (preg_match('/^(\d+(?:\.\d+)*)/', $ver, $m)) {
            return $m[1];
        }
        return $ver;
    }

    /**
     * 比较版本号（支持 26.11.03.04 这类多段数字）
     * @return int 同 version_compare：a<b=-1，a=b=0，a>b=1
     */
    protected function versionCmp($a, $b)
    {
        $na = $this->normalizeVersion($a);
        $nb = $this->normalizeVersion($b);
        if ($na === '' && $nb === '') {
            return 0;
        }
        if ($na === '') {
            return -1;
        }
        if ($nb === '') {
            return 1;
        }
        return version_compare($na, $nb);
    }

    /**
     * compare 失败是否像「历史被重写 / 无共同祖先 / 基线提交已不存在」
     */
    protected function isUnrelatedHistoryError($message)
    {
        $m = strtolower((string) $message);
        if ($m === '') {
            return false;
        }
        $needles = [
            'no common ancestor',
            'not in this repository',
            'no merge base',
            'does not exist',
            'unknown revision',
            'not found',
            '404',
            'base sha',
            'is not a valid',
        ];
        foreach ($needles as $n) {
            if (strpos($m, $n) !== false) {
                return true;
            }
        }
        // 中文接口偶发文案
        if (strpos($message, '共同祖先') !== false || strpos($message, '不存在') !== false) {
            return true;
        }
        return false;
    }

    /**
     * 检查更新：整包覆盖结果
     */
    protected function fullArchiveCheckResult($remoteVersion, $localVersion, $head, $headSha, $reason, $noLocalCommit = false)
    {
        $logs = $this->buildDisplayLogs([], $remoteVersion, $localVersion, true);
        array_unshift($logs, [
            'sha'     => $headSha,
            'title'   => $remoteVersion,
            'date'    => date('Y-m-d'),
            'message' => $reason . "，将用远端最新代码整包覆盖（{$this->provider->name()}；跳过本机配置/数据）。",
        ]);
        return Response::make(1, '发现新版本 ' . $remoteVersion . '，将整包覆盖', [
            'version'   => $remoteVersion,
            'commitish' => $headSha !== '' ? $headSha : $head,
            'logs'      => $logs,
            'files'     => [
                'added'    => [],
                'modified' => [],
                'removed'  => [],
                'zip'      => [$this->config['archive_name']],
            ],
            'meta'      => [
                'ahead_by'            => 0,
                'status'              => 'full',
                'provider'            => $this->provider->name(),
                'use_archive'         => true,
                'no_local_commit'     => (bool) $noLocalCommit,
                'by_version'          => true,
                'unrelated_history'   => (strpos($reason, '共同祖先') !== false || strpos($reason, '重写') !== false),
            ],
        ]);
    }
}
