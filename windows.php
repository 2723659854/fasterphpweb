<?php
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__ . '/root/function.php';

global $_pid_file;
if (!$_pid_file){
    $_pid_file = phar_app_path() . '/root/my_pid.txt';
}

/** 要执行的方法 */
$method = $argv[1]??'';
if ($method=='stop'){
    $pids = file_get_contents($_pid_file);
    foreach (explode('-',$pids) as $taskId){
        $cmd = "taskkill /F /T /PID {$taskId}";
        $descriptorspec = [STDIN, STDOUT, STDOUT];
        $resource = proc_open($cmd, $descriptorspec, $pipes, null, null, ['bypass_shell' => true]);
        $status = proc_get_status($resource);
        //print_r($status);
    }
    file_put_contents($_pid_file,null);
    sleep(1);
    echo "已关闭所有进程\r\n";
}else{

    $runtimeProcessPath = app_path() .'/runtime/windows';
    if (!is_dir($runtimeProcessPath)) {
        mkdir($runtimeProcessPath,0777,true);
    }
    /** 启动http服务 */
    $processFiles = [
        __DIR__ . DIRECTORY_SEPARATOR . 'start.php start'
    ];
    /** 启动自定义进程 */
    foreach (config('process', []) as $processName => $config) {
        if ($config['enable']){
            $handler = $config['handler'].'::class';
            $processFiles[] = write_process_file($runtimeProcessPath, $processName, $handler,'process');
        }

    }
    /** 启动rabbitmq服务 */

    /** 启动redis队列服务 */

    /** 启动ws服务 php start.php ws:start Ws.Just */
    //todo 这里 存在问题，端口接收不到消息
    foreach (config('ws') as $processName=>$config){
        if ($config['enable']){
            $handler = $config['handler'].'::class';
            $processFiles[] = write_process_file($runtimeProcessPath, $processName, $handler,'ws');
        }
    }

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
 * @param $param
 * @return string
 */
function write_process_file($runtimeProcessPath, $processName, $handle, $type): string
{
    $fileContent = <<<EOF
<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../root/function.php';
G($handle)->handle(config('$type')['$processName']);
EOF;
    $processFile = $runtimeProcessPath . DIRECTORY_SEPARATOR . "start_$processName.php";
    file_put_contents($processFile, $fileContent);
    return $processFile;
}

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

function writeWindowsPid($pid)
{
    global $_pid_file;
    /** 记录进程号 */
    $fp = fopen($_pid_file, 'a+');
    fwrite($fp, $pid . '-');
    fclose($fp);
}






