<?php
namespace GitOnlineUpdate\Provider;

class GitHubProvider extends AbstractProvider
{
    public function name()
    {
        return 'github';
    }

    protected function headers()
    {
        $h = [
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
        if ($this->token !== '') {
            $h['Authorization'] = 'Bearer ' . $this->token;
        }
        return $h;
    }

    protected function api($path, array $query = [])
    {
        $url = 'https://api.github.com' . $path;
        if ($query) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
        }
        return $url;
    }

    public function compare($base, $head)
    {
        $url = $this->api('/repos/' . $this->owner . '/' . $this->repo . '/compare/' . rawurlencode($base) . '...' . rawurlencode($head));
        $res = $this->http->getJson($url, $this->headers());
        if ($res['status'] >= 400 || !is_array($res['data'])) {
            $msg = is_array($res['data']) && isset($res['data']['message']) ? $res['data']['message'] : ('GitHub compare 失败 HTTP ' . $res['status']);
            throw new \RuntimeException($msg);
        }
        $data = $res['data'];
        $commits = isset($data['commits']) ? $data['commits'] : [];
        $files = isset($data['files']) ? $data['files'] : [];
        $headSha = !empty($data['commits']) ? $data['commits'][count($data['commits']) - 1]['sha'] : $head;
        if (!empty($data['merge_base_commit']) && empty($commits) && ($data['status'] ?? '') === 'identical') {
            $headSha = $data['merge_base_commit']['sha'];
        }
        $result = $this->normalizeCompare($commits, $files, $headSha);
        if (isset($data['ahead_by'])) {
            $result['ahead_by'] = (int) $data['ahead_by'];
        }
        if (isset($data['behind_by'])) {
            $result['behind_by'] = (int) $data['behind_by'];
        }
        if (isset($data['status'])) {
            $result['status'] = $data['status'];
        }
        return $result;
    }

    public function getContents($path, $ref)
    {
        $url = $this->api(
            '/repos/' . $this->owner . '/' . $this->repo . '/contents/' . $this->encodePath($path),
            ['ref' => $ref]
        );
        $res = $this->http->getJson($url, $this->headers());
        if ($res['status'] == 404) {
            return null;
        }
        if ($res['status'] >= 400 || !is_array($res['data'])) {
            $msg = is_array($res['data']) && isset($res['data']['message']) ? $res['data']['message'] : '读取文件失败';
            throw new \RuntimeException($msg);
        }
        if (isset($res['data'][0])) {
            throw new \RuntimeException('路径是目录，不是文件: ' . $path);
        }
        // 大文件可能只有 download_url
        if (empty($res['data']['content']) && !empty($res['data']['download_url'])) {
            $bin = $this->http->get($res['data']['download_url'], $this->headers());
            if ($bin['status'] >= 400) {
                throw new \RuntimeException('下载大文件失败');
            }
            return [
                'path'     => $res['data']['path'],
                'content'  => $bin['body'],
                'encoding' => 'raw',
                'sha'      => isset($res['data']['sha']) ? $res['data']['sha'] : '',
                'size'     => isset($res['data']['size']) ? (int) $res['data']['size'] : strlen($bin['body']),
            ];
        }
        return $this->decodeContent($res['data']);
    }

    public function getRemoteVersion($ref, $strategy, $versionPath)
    {
        if ($strategy === 'tag') {
            $url = $this->api("/repos/{$this->owner}/{$this->repo}/tags", ['per_page' => 1]);
            $res = $this->http->getJson($url, $this->headers());
            if (!empty($res['data'][0]['name'])) {
                return $res['data'][0]['name'];
            }
            return $ref;
        }
        if ($strategy === 'commit') {
            $url = $this->api("/repos/{$this->owner}/{$this->repo}/commits/" . rawurlencode($ref));
            $res = $this->http->getJson($url, $this->headers());
            if (!empty($res['data']['sha'])) {
                return substr($res['data']['sha'], 0, 7);
            }
            return $ref;
        }
        $file = $this->getContents($versionPath ?: 'version', $ref);
        if ($file && $file['content'] !== '') {
            return trim($file['content']);
        }
        return $ref;
    }

    public function getArchiveUrl($ref)
    {
        return $this->api('/repos/' . $this->owner . '/' . $this->repo . '/zipball/' . rawurlencode($ref));
    }

    public function getReleasesUrl()
    {
        return "https://github.com/{$this->owner}/{$this->repo}/releases";
    }

    public function probeArchive($ref)
    {
        $url = $this->getArchiveUrl($ref);
        // zipball 常为 302，用 GET 不落盘探测不现实；返回 URL，size 由下载后轮询
        return ['url' => $url, 'size' => 1];
    }

    public function starRepository()
    {
        if ($this->token === '') {
            return ['ok' => false, 'msg' => '未配置 token，跳过 Star', 'status' => 0];
        }
        $url = $this->api('/user/starred/' . $this->owner . '/' . $this->repo);
        $headers = $this->headers();
        $headers['Content-Length'] = '0';
        $res = $this->http->put($url, '', $headers);
        // 204 成功；404/401 等失败；已 star 时多为 204
        if ($res['status'] === 204 || $res['status'] === 304) {
            return ['ok' => true, 'msg' => '已 Star', 'status' => $res['status']];
        }
        if ($res['status'] >= 200 && $res['status'] < 300) {
            return ['ok' => true, 'msg' => '已 Star', 'status' => $res['status']];
        }
        return ['ok' => false, 'msg' => 'Star 失败 HTTP ' . $res['status'], 'status' => $res['status']];
    }

    protected function normalizeCommit(array $c)
    {
        $msg = isset($c['commit']['message']) ? $c['commit']['message'] : '';
        $parts = preg_split("/\r\n|\n|\r/", trim($msg), 2);
        $date = '';
        if (!empty($c['commit']['committer']['date'])) {
            $date = $c['commit']['committer']['date'];
        } elseif (!empty($c['commit']['author']['date'])) {
            $date = $c['commit']['author']['date'];
        }
        return [
            'sha'     => isset($c['sha']) ? $c['sha'] : '',
            'title'   => $parts[0],
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
