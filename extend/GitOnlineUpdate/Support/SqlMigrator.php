<?php
namespace GitOnlineUpdate\Support;

/**
 * 升级 SQL 自动执行器
 *
 * 约定：
 * - 脚本目录：{root}/upgrade/sql/*.sql（按文件名排序）
 * - 已执行记录：{root}/data/sql_applied.json（本机保留，不随在线更新覆盖）
 * - 表前缀：脚本内默认 `think_`，执行时替换为实际 prefix
 */
class SqlMigrator
{
    /** @var string */
    protected $root;

    /** @var string */
    protected $sqlDir;

    /** @var string */
    protected $appliedFile;

    /** @var string */
    protected $tablePrefix;

    /** @var callable|null function(string $sql): mixed */
    protected $executor;

    /**
     * @param string        $root        项目根目录
     * @param string        $tablePrefix 实际表前缀，如 think_
     * @param callable|null $executor    执行单条 SQL；默认 null 表示仅扫描不执行
     */
    public function __construct($root, $tablePrefix = 'think_', $executor = null)
    {
        $this->root = rtrim(str_replace('\\', '/', (string) $root), '/');
        $this->sqlDir = $this->root . '/upgrade/sql';
        $this->appliedFile = $this->root . '/data/sql_applied.json';
        $this->tablePrefix = (string) $tablePrefix;
        $this->executor = $executor;
    }

    /**
     * 列出全部 / 待执行脚本
     * @return array{all:string[],pending:string[],applied:string[]}
     */
    public function status()
    {
        $all = $this->listScripts();
        $applied = $this->loadApplied();
        $pending = [];
        foreach ($all as $name) {
            if (!in_array($name, $applied, true)) {
                $pending[] = $name;
            }
        }
        return [
            'all'     => $all,
            'pending' => $pending,
            'applied' => $applied,
            'sql_dir' => $this->sqlDir,
        ];
    }

    /**
     * 执行全部待跑脚本
     * @param bool $dryRun true 只预览不执行
     * @return array
     */
    public function run($dryRun = false)
    {
        $status = $this->status();
        $pending = $status['pending'];
        $ran = [];
        $skipped = [];
        $errors = [];

        if (!$pending) {
            return [
                'ok'      => true,
                'msg'     => '无待执行的升级 SQL',
                'ran'     => [],
                'skipped' => [],
                'pending' => [],
                'errors'  => [],
                'dry_run' => (bool) $dryRun,
            ];
        }

        if ($dryRun) {
            return [
                'ok'      => true,
                'msg'     => '预览：将执行 ' . count($pending) . ' 个脚本',
                'ran'     => [],
                'skipped' => [],
                'pending' => $pending,
                'errors'  => [],
                'dry_run' => true,
            ];
        }

        if (!is_callable($this->executor)) {
            return [
                'ok'      => false,
                'msg'     => '未配置 SQL 执行器',
                'ran'     => [],
                'skipped' => [],
                'pending' => $pending,
                'errors'  => [['file' => '', 'error' => 'executor missing']],
                'dry_run' => false,
            ];
        }

        $applied = $this->loadApplied();
        foreach ($pending as $name) {
            $path = $this->sqlDir . '/' . $name;
            if (!is_file($path)) {
                $errors[] = ['file' => $name, 'error' => '文件不存在'];
                break;
            }
            try {
                $sql = (string) file_get_contents($path);
                $sql = $this->normalizePrefix($sql);
                $statements = $this->splitStatements($sql);
                foreach ($statements as $stmt) {
                    call_user_func($this->executor, $stmt);
                }
                $applied[] = $name;
                $this->saveApplied($applied);
                $ran[] = $name;
            } catch (\Throwable $e) {
                $errors[] = ['file' => $name, 'error' => $e->getMessage()];
                break;
            }
        }

        $ok = empty($errors);
        $left = [];
        foreach ($this->listScripts() as $name) {
            if (!in_array($name, $applied, true)) {
                $left[] = $name;
            }
        }

        return [
            'ok'      => $ok,
            'msg'     => $ok
                ? ('已执行 ' . count($ran) . ' 个升级 SQL')
                : ('升级 SQL 执行失败：' . $errors[0]['file'] . ' — ' . $errors[0]['error']),
            'ran'     => $ran,
            'skipped' => $skipped,
            'pending' => $left,
            'errors'  => $errors,
            'dry_run' => false,
        ];
    }

    /**
     * @return string[]
     */
    protected function listScripts()
    {
        if (!is_dir($this->sqlDir)) {
            return [];
        }
        $files = [];
        $items = @scandir($this->sqlDir);
        if ($items === false) {
            return [];
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            if (!preg_match('/\.sql$/i', $item)) {
                continue;
            }
            // 忽略示例 / 说明
            if (stripos($item, 'example') !== false || stripos($item, 'readme') !== false) {
                continue;
            }
            $files[] = $item;
        }
        sort($files, SORT_STRING);
        return $files;
    }

    /**
     * @return string[]
     */
    protected function loadApplied()
    {
        if (!is_file($this->appliedFile)) {
            return [];
        }
        $raw = @file_get_contents($this->appliedFile);
        if ($raw === false || trim($raw) === '') {
            return [];
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [];
        }
        $list = isset($data['applied']) && is_array($data['applied']) ? $data['applied'] : $data;
        $out = [];
        foreach ($list as $name) {
            if (is_string($name) && $name !== '') {
                $out[] = $name;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * @param string[] $applied
     */
    protected function saveApplied(array $applied)
    {
        $dir = dirname($this->appliedFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $payload = [
            'updated_at' => date('c'),
            'applied'    => array_values(array_unique($applied)),
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (@file_put_contents($this->appliedFile, $json . "\n", LOCK_EX) === false) {
            throw new \RuntimeException('无法写入 ' . $this->appliedFile);
        }
    }

    protected function normalizePrefix($sql)
    {
        $prefix = $this->tablePrefix;
        if ($prefix === '' || $prefix === 'think_') {
            return $sql;
        }
        // 仅替换反引号包裹的默认前缀，降低误伤
        return str_replace('`think_', '`' . $prefix, $sql);
    }

    /**
     * 按分号拆分语句，忽略字符串与注释中的 ;
     * @return string[]
     */
    protected function splitStatements($sql)
    {
        $sql = str_replace(["\r\n", "\r"], "\n", (string) $sql);
        $len = strlen($sql);
        $out = [];
        $buf = '';
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;
        $inLineComment = false;
        $inBlockComment = false;

        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            $next = ($i + 1 < $len) ? $sql[$i + 1] : '';

            if ($inLineComment) {
                $buf .= $ch;
                if ($ch === "\n") {
                    $inLineComment = false;
                }
                continue;
            }
            if ($inBlockComment) {
                $buf .= $ch;
                if ($ch === '*' && $next === '/') {
                    $buf .= $next;
                    $i++;
                    $inBlockComment = false;
                }
                continue;
            }

            if (!$inSingle && !$inDouble && !$inBacktick) {
                if ($ch === '-' && $next === '-') {
                    $buf .= $ch . $next;
                    $i++;
                    $inLineComment = true;
                    continue;
                }
                if ($ch === '#') {
                    $buf .= $ch;
                    $inLineComment = true;
                    continue;
                }
                if ($ch === '/' && $next === '*') {
                    $buf .= $ch . $next;
                    $i++;
                    $inBlockComment = true;
                    continue;
                }
            }

            if ($ch === "'" && !$inDouble && !$inBacktick) {
                // 处理 '' 转义
                if ($inSingle && $next === "'") {
                    $buf .= "''";
                    $i++;
                    continue;
                }
                $inSingle = !$inSingle;
                $buf .= $ch;
                continue;
            }
            if ($ch === '"' && !$inSingle && !$inBacktick) {
                if ($inDouble && $next === '"') {
                    $buf .= '""';
                    $i++;
                    continue;
                }
                $inDouble = !$inDouble;
                $buf .= $ch;
                continue;
            }
            if ($ch === '`' && !$inSingle && !$inDouble) {
                $inBacktick = !$inBacktick;
                $buf .= $ch;
                continue;
            }

            if ($ch === ';' && !$inSingle && !$inDouble && !$inBacktick) {
                $stmt = trim($this->stripCommentsOnly($buf));
                if ($stmt !== '') {
                    $out[] = $stmt;
                }
                $buf = '';
                continue;
            }

            $buf .= $ch;
        }

        $stmt = trim($this->stripCommentsOnly($buf));
        if ($stmt !== '') {
            $out[] = $stmt;
        }
        return $out;
    }

    /**
     * 去掉仅含注释的空语句
     */
    protected function stripCommentsOnly($sql)
    {
        $lines = preg_split('/\n/', (string) $sql);
        $keep = [];
        $inBlock = false;
        foreach ($lines as $line) {
            $trim = trim($line);
            if ($inBlock) {
                if (strpos($line, '*/') !== false) {
                    $inBlock = false;
                }
                continue;
            }
            if ($trim === '' || strpos($trim, '--') === 0 || strpos($trim, '#') === 0) {
                continue;
            }
            if (strpos($trim, '/*') === 0) {
                if (strpos($trim, '*/') === false) {
                    $inBlock = true;
                }
                continue;
            }
            $keep[] = $line;
        }
        return trim(implode("\n", $keep));
    }
}
