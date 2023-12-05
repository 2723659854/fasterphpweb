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
    $processFiles = [
        __DIR__ . DIRECTORY_SEPARATOR . 'start.php start'
    ];
    foreach (config('process', []) as $processName => $config) {
        $processFiles[] = write_process_file($runtimeProcessPath, $processName, $config);
    }
    foreach ($processFiles as $file){
        $resource = popen_processes([$file]);
        $status = proc_get_status($resource);
        $pid = $status['pid'];
        writeWindowsPid($pid);
    }

}
function displayServer(){
    /** 读取服务器配置 */
    $server = config('server');
    $_port = $server['port'] ?? 8000;
    $_listen = "http://0.0.0.0:" . $_port;
    /** 加载表格类工具 */
    $_system_table = G(\Xiaosongshu\Table\Table::class);
    /** 主进程退出 */
    $head = ['名称', '状态', '进程数', '服务'];
    $content = [];
    /** http */
    $http_count = config('server')['num'] ?? 4;
    $content[] = ['http', '正常', $http_count, $_listen];
    /** rabbitmq */
    $rabbitmq_config = config('rabbitmq');
    if (in_array(true, array_column(config('rabbitmqProcess') ?? [], 'enable'))) {
        $rabbitmq_count = 0;
        foreach ((config('rabbitmqProcess')) as $item) {
            if ($item['enable']??false){
                $rabbitmq_count += $item['count'];
            }
        }
        $content[] = ['rabbitmq', '正常', $rabbitmq_count, $rabbitmq_config['port']];
    }
    /** 定时器 */
    $content[] = ['定时任务', '正常', '1', 'timer'];
    /** redis队列 */
    $redis_config = config('redis');
    if ($redis_config['enable']) {
        $content[] = ['redis_queue', '正常', 1, $redis_config['port']];
    }
    /** ws 服务 */
    $ws_count = 0;
    $ws_port = [];
    foreach (config('ws') as $k => $v) {
        if ($v['enable']) {
            $ws_count++;
            $ws_port[] = $v['port'];
        }
    }
    if ($ws_count) {
        $content[] = ['ws服务', '正常', $ws_count, implode(',', $ws_port)];
    }
    /** rtmp服务 */
    $rtmp_enable = config('rtmp')['enable']??false;
    if($rtmp_enable){
        $content[] = ['rtmp-flv', '正常', 2, config('rtmp')['rtmp'].','.config('rtmp')['flv']];
    }

    $_system_table->table($head, $content);
    print_r("进程启动完成,你可以输入php start.php stop停止运行\r\n");
}

function write_process_file($runtimeProcessPath, $processName, $config): string
{
    $handle = $config['handler'].'::class';
    $fileContent = <<<EOF
<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../root/function.php';
G($handle)->handle(config('process')['$processName']);
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






