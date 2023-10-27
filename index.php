<?php
$parentId = getmypid();
var_dump("主进程" . $parentId);
$pid = pcntl_fork();
var_dump("创建的子进程" . $pid);

$count = 0;

/** 第一个 子进程 */
if ($pid == 0) {
    /** 第一个子进程内部  */
    /** 升级为主进程 */
    if (posix_setsid() == -1) {
        var_dump("升级主进程失败");
        exit;
    }
    /** 创建 第二个子进程 */
    $secondPid = pcntl_fork();

    if ($secondPid == 0) {
        /** 第二个子进程内部，负责打印消息 */
        while(1){
            var_dump(getmypid() . "进程负责打印" . $count++);
        }

    } else {
        /** 升级后的主进程 负责监听 第二个子进程 */
        pcntl_waitpid($secondPid, $status);
        var_dump("子进程{$secondPid}的状态是{$status}");
        var_dump("子进程已经退出，我也要退出了");
        exit;
    }

} else {
    /** 主进程退出 */
    var_dump("我是主进程{$parentId}，我退出了 ");
    exit;
}
