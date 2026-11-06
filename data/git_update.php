<?php
/**
 * Git 在线升级配置（直接使用，无需复制）
 *
 * 仓库/分支等以本文件为准。Token 请在后台「在线更新」弹窗填写，
 * 会写回本文件；勿把带 Token 的版本提交到公开仓库。
 *
 * Token 获取：
 * - GitHub: https://github.com/settings/tokens
 *   新建 Fine-grained 或 classic PAT；公开库读可不勾选特殊权限，
 *   需要 Star 时 classic 勾选 public_repo（或 fine-grained 勾选 Starring）。
 * - Gitee:  https://gitee.com/profile/personal_access_tokens
 *   勾选 projects、user_info 等；Star 需要对应写权限。
 */
return [
    // 默认更新源：github | gitee（后台页面仍可切换）
    'provider' => 'github',
    'ref'      => 'main',

    'mirrors' => [
        'github' => [
            'owner' => 'cloudwebsite',
            'repo'  => 'bole-faka-tp5',
            'token' => '', // 后台弹窗填写
        ],
        'gitee' => [
            'owner' => 'lailiyun',
            'repo'  => 'bole-faka-tp5',
            'token' => '', // 后台弹窗填写
        ],
    ],

    'auto_star'        => true,
    'star_all_mirrors' => true,

    'version_strategy'    => 'file',
    'remote_version_path' => 'version', // 仓库根目录 version 文件
    'archive_threshold'   => 80,
];
