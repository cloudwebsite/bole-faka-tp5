<?php
namespace GitOnlineUpdate\Provider;

use GitOnlineUpdate\Http\Client;

abstract class AbstractProvider implements ProviderInterface
{
    protected $owner;
    protected $repo;
    protected $token;
    /** @var Client */
    protected $http;

    public function __construct(array $config, Client $http)
    {
        $this->owner = $config['owner'];
        $this->repo  = $config['repo'];
        $this->token = isset($config['token']) ? $config['token'] : '';
        $this->http  = $http;
    }

    protected function normalizeCompare(array $commits, array $files, $headSha)
    {
        $added = $modified = $removed = [];
        $renamed = [];
        foreach ($files as $f) {
            $status = isset($f['status']) ? $f['status'] : '';
            $filename = isset($f['filename']) ? $f['filename'] : (isset($f['path']) ? $f['path'] : '');
            if ($filename === '') {
                continue;
            }
            switch ($status) {
                case 'added':
                case 'A':
                    $added[] = $filename;
                    break;
                case 'removed':
                case 'deleted':
                case 'D':
                    $removed[] = $filename;
                    break;
                case 'renamed':
                case 'R':
                    $renamed[] = $f;
                    if (!empty($f['previous_filename'])) {
                        $removed[] = $f['previous_filename'];
                    }
                    $modified[] = $filename;
                    break;
                default:
                    $modified[] = $filename;
                    break;
            }
        }

        $commitList = [];
        foreach ($commits as $c) {
            $commitList[] = $this->normalizeCommit($c);
        }

        return [
            'ahead_by'  => count($commitList),
            'behind_by' => 0,
            'status'    => count($commitList) || count($added) || count($modified) || count($removed) ? 'ahead' : 'identical',
            'commitish' => $headSha,
            'commits'   => $commitList,
            'files'     => [
                'added'    => array_values(array_unique($added)),
                'modified' => array_values(array_unique($modified)),
                'removed'  => array_values(array_unique($removed)),
                'renamed'  => $renamed,
            ],
        ];
    }

    abstract protected function normalizeCommit(array $c);

    protected function decodeContent(array $file)
    {
        $encoding = isset($file['encoding']) ? $file['encoding'] : 'utf-8';
        $content  = isset($file['content']) ? $file['content'] : '';
        if ($encoding === 'base64') {
            $content = base64_decode(str_replace(["\n", "\r"], '', $content));
        }
        return [
            'path'     => isset($file['path']) ? $file['path'] : '',
            'content'  => $content,
            'encoding' => $encoding,
            'sha'      => isset($file['sha']) ? $file['sha'] : '',
            'size'     => isset($file['size']) ? (int) $file['size'] : strlen($content),
        ];
    }
}
