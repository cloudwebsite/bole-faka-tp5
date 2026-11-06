<?php
namespace GitOnlineUpdate\Provider;

interface ProviderInterface
{
    /** @return string gitee|github */
    public function name();

    /**
     * 对比 base...head，返回标准化变更
     * @return array{
     *   ahead_by:int,
     *   behind_by:int,
     *   status:string,
     *   commitish:string,
     *   commits:array<int,array{sha:string,title:string,date:string,message:string}>,
     *   files:array{added:string[],modified:string[],removed:string[],renamed:array}
     * }
     */
    public function compare($base, $head);

    /**
     * 读取仓库文件内容
     * @return array{path:string,content:string,encoding:string,sha?:string}|null
     */
    public function getContents($path, $ref);

    /**
     * 获取远程版本号字符串
     */
    public function getRemoteVersion($ref, $strategy, $versionPath);

    /**
     * 整包归档下载地址（zip）
     */
    public function getArchiveUrl($ref);

    /**
     * Release / 历史页地址
     */
    public function getReleasesUrl();

    /**
     * HEAD 探测归档大小等
     * @return array{url:string,size:int}
     */
    public function probeArchive($ref);

    /**
     * 给当前仓库点 Star（需 token；已 star / 无权限时静默成功）
     * @return array{ok:bool,msg:string,status:int}
     */
    public function starRepository();
}
