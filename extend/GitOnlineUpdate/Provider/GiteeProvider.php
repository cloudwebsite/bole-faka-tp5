<?php
namespace GitOnlineUpdate\Provider;

class GiteeProvider extends AbstractProvider
{
    public function name()
    {
        return 'gitee';
    }

    protected function api($path, array $query = [])
    {
        if ($this->token !== '') {
            $query['access_token'] = $this->token;
        }
        $url = 'https://gitee.com/api/v5' . $path;
        if ($query) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
        }
        return $url;
    }

    public function compare($base, $head)
    {
        $url = $this->api("/repos/{$this->owner}/{$this->repo}/compare/" . rawurlencode($base) . '...' . rawurlencode($head));
        $res = $this->http->getJson($url);
        if ($res['status'] >= 400 || !is_array($res['data'])) {
            $msg = is_array($res['data']) && isset($res['data']['message']) ? $res['data']['message'] : ('Gitee compare 失败 HTTP ' . $res['status']);
            throw new \RuntimeException($msg);
        }
        $data = $res['data'];
        $commits = isset($data['commits']) && is_array($data['commits']) ? $data['commits'] : [];
        $files = isset($data['files']) && is_array($data['files']) ? $data['files'] : [];
        $headSha = $head;
        if (!empty($commits)) {
            $last = end($commits);
            if (!empty($last['sha'])) {
                $headSha = $last['sha'];
            }
        }
        if (!empty($data['compare_commit']['sha'])) {
            $headSha = $data['compare_commit']['sha'];
        }
        return $this->normalizeCompare($commits, $files, $headSha);
    }

    public function getContents($path, $ref)
    {
        $url = $this->api(
            '/repos/' . $this->owner . '/' . $this->repo . '/contents/' . $this->encodePath($path),
            ['ref' => $ref]
        );
        $res = $this->http->getJson($url);
        if ($res['status'] == 404 || $res['raw'] === '[]') {
            return null;
        }
        if ($res['status'] >= 400 || !is_array($res['data'])) {
            $msg = is_array($res['data']) && isset($res['data']['message']) ? $res['data']['message'] : '读取文件失败';
            throw new \RuntimeException($msg);
        }
        // 目录
        if (isset($res['data'][0])) {
            throw new \RuntimeException('路径是目录，不是文件: ' . $path);
        }
        return $this->decodeContent($res['data']);
    }

    public function getRemoteVersion($ref, $strategy, $versionPath)
    {
        if ($strategy === 'tag') {
            $url = $this->api("/repos/{$this->owner}/{$this->repo}/tags", ['page' => 1, 'per_page' => 1]);
            $res = $this->http->getJson($url);
            if (!empty($res['data'][0]['name'])) {
                return $res['data'][0]['name'];
            }
            return $ref;
        }
        if ($strategy === 'commit') {
            $url = $this->api("/repos/{$this->owner}/{$this->repo}/commits/" . rawurlencode($ref));
            $res = $this->http->getJson($url);
            if (!empty($res['data']['sha'])) {
                return substr($res['data']['sha'], 0, 7);
            }
            return $ref;
        }
        // file
        $file = $this->getContents($versionPath ?: 'version', $ref);
        if ($file && $file['content'] !== '') {
            return trim($file['content']);
        }
        return $ref;
    }

    public function getArchiveUrl($ref)
    {
        $url = "https://gitee.com/{$this->owner}/{$this->repo}/repository/archive/" . rawurlencode($ref) . '.zip';
        if ($this->token !== '') {
            $url .= '?access_token=' . rawurlencode($this->token);
        }
        return $url;
    }

    public function getReleasesUrl()
    {
        return "https://gitee.com/{$this->owner}/{$this->repo}/releases";
    }

    public function probeArchive($ref)
    {
        $url = "https://gitee.com/{$this->owner}/{$this->repo}/repository/archive/" . rawurlencode($ref) . '.zip';
        if ($this->token !== '') {
            $url .= (strpos($url, '?') === false ? '?' : '&') . 'access_token=' . rawurlencode($this->token);
        }
        $res = $this->http->head($url);
        $size = 0;
        if (!empty($res['headers']['content-length'])) {
            $size = (int) $res['headers']['content-length'];
        }
        // 部分环境 HEAD 不返回长度，给一个可下载标记
        if ($size <= 0 && $res['status'] >= 200 && $res['status'] < 400) {
            $size = 1;
        }
        return ['url' => $url, 'size' => $size];
    }

    public function starRepository()
    {
        if ($this->token === '') {
            return ['ok' => false, 'msg' => '未配置 token，跳过 Star', 'status' => 0];
        }
        // Gitee: PUT /api/v5/user/starred/{owner}/{repo}?access_token=
        $url = $this->api('/user/starred/' . $this->owner . '/' . $this->repo);
        $res = $this->http->put($url, '');
        if ($res['status'] === 204 || ($res['status'] >= 200 && $res['status'] < 300)) {
            return ['ok' => true, 'msg' => '已 Star', 'status' => $res['status']];
        }
        return ['ok' => false, 'msg' => 'Star 失败 HTTP ' . $res['status'], 'status' => $res['status']];
    }

    protected function normalizeCommit(array $c)
    {
        $msg = isset($c['commit']['message']) ? $c['commit']['message'] : (isset($c['message']) ? $c['message'] : '');
        $parts = preg_split("/\r\n|\n|\r/", trim($msg), 2);
        $title = $parts[0];
        $date = '';
        if (!empty($c['commit']['committer']['date'])) {
            $date = $c['commit']['committer']['date'];
        } elseif (!empty($c['commit']['author']['date'])) {
            $date = $c['commit']['author']['date'];
        } elseif (!empty($c['date'])) {
            $date = $c['date'];
        }
        return [
            'sha'     => isset($c['sha']) ? $c['sha'] : '',
            'title'   => $title,
            'date'    => $date,
            'message' => $msg,
        ];
    }

    protected function encodePath($path)
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }
}
