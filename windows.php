<?php
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__ . '/root/function.php';
require_once __DIR__ . '/vendor/xiaosongshu/colorword/src/Transfer.php';
/** pid 保存文件位置 */
global $_pid_file;
if (!$_pid_file){
    $_pid_file = phar_app_path() . '/root/my_pid.txt';
}
/** 判断当前的系统环境 */
$_system = !(\DIRECTORY_SEPARATOR === '\\');

/** 要执行的方法 */
$method = $argv[1]??'';
if ($method=='stop'){
    $pids = file_get_contents($_pid_file);

    foreach (explode('-',$pids) as $taskId){
        if ($_system){
            /** linux系统 */
            @\exec("kill -9 {$taskId}");
        }else{
            /** windows系统 */
            $cmd = "taskkill /F /T /PID {$taskId}";
            $descriptorspec = [STDIN, STDOUT, STDOUT];
            @\proc_open($cmd, $descriptorspec, $pipes, null, null, ['bypass_shell' => true]);
        }
    }
    file_put_contents($_pid_file,null);
    sleep(1);
    echo "已关闭所有进程\r\n";
}else{
    /** 运行路劲 */
    $runtimeProcessPath = app_path() .'/runtime/windows';
    if (!is_dir($runtimeProcessPath)) {
        mkdir($runtimeProcessPath,0777,true);
    }
    $content = [];
    /** 启动http服务 */
    $processFiles = [
        __DIR__ . DIRECTORY_SEPARATOR . 'start.php start'
    ];
    $content[] = ['http', '正常', $_system?config('server')['num']??1:1, config('server')['port']??'未设置'];
    /** 启动自定义进程 */
    foreach (config('process', []) as $processName => $config) {

        if ($config['enable']){
            $handler = $config['handler'].'::class';
            if ($count = $config['count']??1){
                $content[] = [$processName, '正常', $config['count']??1, $config['port']??''];
                /** 创建多进程 */
                while ($count){
                    $processFiles[] = write_process_file($runtimeProcessPath, $processName.'_'.$count, $handler,'process');
                    $count--;
                }
            }

        }
    }
    /** 启动rabbitmq服务 */
    foreach (config('rabbitmqProcess') as $processName=>$config){
        if ($config['enable']){
            $handler = $config['handler'].'::class';
            if ($count = $config['count']??1){
                $content[] = ['rabbitmq', '正常', $config['count']??1, config('rabbitmq')['port']];
                /** 创建多进程 */
                while ($count){
                    $processFiles[] = write_process_file($runtimeProcessPath, $processName.'_'.$count, $handler,'rabbitmqProcess');
                    $count--;
                }
            }
        }
    }

    /** 启动redis队列服务 */
    if (config('redis')['enable']??false){
        $handler = \Root\Queue\RedisQueueConsumer::class.'::class';
        if (!extension_loaded('redis')) {
            print_r("系统检测到你尚未安装redis扩展，无法启动redis队列");
        }else{
            $processFiles[] = write_process_file($runtimeProcessPath, 'redis', $handler,'redis');
            $content[] = ['ws', '正常', 1, config('redis')['port']];
        }
    }

    /** 启动ws服务 php start.php ws:start Ws.Just */
    foreach (config('ws') as $processName=>$config){
        if ($config['enable']){
            $handler = $config['handler'].'::class';
            $processFiles[] = write_process_file($runtimeProcessPath, $processName, $handler,'ws');
            $content[] = ['ws', '正常', 1, $config['port']];
        }
    }

    /** rtmp服务 Root\Queue\RtmpConsumer*/
    if (config('rtmp')['enable']??false){
        $handler = \Root\Queue\RtmpConsumer::class.'::class';
        $processFiles[] = write_process_file($runtimeProcessPath, 'rtmp', $handler,'rtmp');
        $content[] = ['rtmp/flv', '正常', 2, (config('rtmp')['rtmp']??'1935').','.(config('rtmp')['flv']??'18080')];
    }

    $head = ['名称', '状态', '进程数', '服务'];
    G(\Xiaosongshu\Table\Table::class)->table($head, $content);
    echo "你可以输入 php windows.php stop关闭所有服务\r\n";

    /** 逐个启动服务 */
    foreach ($processFiles as $file){
        $resource = popen_processes([$file]);
        $status = proc_get_status($resource);
        $pid = $status['pid'];
        writeWindowsPid($pid);
    }

}

/**
 * 创建启动文件
 * @param $runtimeProcessPath
 * @param $processName
 * @param $handle
 * @param $type
 * @return string
 */
function write_process_file($runtimeProcessPath, $processName, $handle, $type): string
{
    if ($type=='rtmp'){
        $fileContent = <<<EOF
<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../vendor/xiaosongshu/colorword/src/Transfer.php';
require_once __DIR__ . '/../../root/function.php';
G($handle)->consume(['start','-d']);
EOF;
    }
    elseif ($type=='rabbitmqProcess'){
        $fileContent = <<<EOF
<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../vendor/xiaosongshu/colorword/src/Transfer.php';
require_once __DIR__ . '/../../root/function.php';
G($handle)->consume();
EOF;
    }else{
        $fileContent = <<<EOF
<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../vendor/xiaosongshu/colorword/src/Transfer.php';
require_once __DIR__ . '/../../root/function.php';
G($handle)->handle(config('$type')['$processName']??[]);
EOF;
    }

    $processFile = $runtimeProcessPath . DIRECTORY_SEPARATOR . "start_$processName.php";
    file_put_contents($processFile, $fileContent);
    return $processFile;
}

/**
 * 运行PHP命令
 * @param $processFiles
 * @return resource|void
 */
function popen_processes($processFiles)
{
    $cmd = '"' . PHP_BINARY . '" ' . implode(' ', $processFiles);
    $descriptorspec = [STDIN, STDOUT, STDOUT];
    $resource = proc_open($cmd, $descriptorspec, $pipes, null, null, ['bypass_shell' => true]);
    if (!$resource) {
        exit("Can not execute $cmd\r\n");
    }
    return $resource;
}

/**
 * 记录进程id
 * @param $pid
 * @return void
 */
function writeWindowsPid($pid)
{
    global $_pid_file;
    /** 记录进程号 */
    $fp = fopen($_pid_file, 'a+');
    fwrite($fp, $pid . '-');
    fclose($fp);
}






