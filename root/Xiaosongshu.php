<?php

namespace Root;

/**
 * @purpose 应用启动处理器
 * @time 2023年9月21日12:57:34
 */
class Xiaosongshu
{

    /**
     * 启动服务
     * @param $param
     * @return void
     * @throws \Exception
     */
    public function start_server($param)
    {
        $this->check_env();
        $daemonize = false;
        $flag      = true;
        global $_pid_file, $_port, $_listen, $_server_num, $_system, $_lock_file, $_has_epoll, $_system_command,$_system_table,$_color_class;
        $_pid_file  = __DIR__ . '/my_pid.txt';
        $_lock_file = __DIR__ . '/lock.txt';
        /** 加载助手函数 */
        require_once __DIR__.'/function.php';
        /** 加载必须的启动文件 */
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        /** 加载所有的必须的文件 */
        foreach (scan_dir(app_path().'/root',true) as $k){ if (pathinfo($k)['extension']=='php')require_once $k; }
        foreach (scan_dir(app_path().'/process',true) as $k){ if (pathinfo($k)['extension']=='php')require_once $k; }
        /** @var bool $_has_epoll 默认不支持epoll模型 */
        $_has_epoll = false;
        /** @var bool $_system 是否是linux系统 */
        $_system = true;
        if (\DIRECTORY_SEPARATOR === '\\') {
            $_system = false;
        } else {
            $_has_epoll = (new \EventBase())->getMethod() == 'epoll';
        }
        $httpServer = null;
        $server = config('server');
        if (isset($server['port']) && $server['port']) {
            $_port = intval($server['port']);
        } else {
            $_port = 8000;
        }
        if (isset($server['num']) && $server['num']) {
            $_server_num = intval($server['num']);
        } else {
            $_server_num = 2;
        }
        $_listen    = "http://0.0.0.0:" . $_port;
        $httpServer = null;
        /** 装载用户的自定义命令 */
        $this->deal_command();

        /** 装载App目录下的所有文件 */
        foreach (scan_dir(app_path() . '/app',true) as $key => $val) {
            if (file_exists($val)) {
                require_once $val;
            }
        }

        /** 加载表格类工具 */
        $_system_table = new \Xiaosongshu\Table\Table();
        /** 加载字体类工具 */
        $_color_class = new \Xiaosongshu\ColorWord\Transfer();

        /** 分析用户输入的命令，执行业务逻辑 */
        if (count($param) > 1) {
            switch ($param[1]) {
                case "start":
                    if (isset($param[2]) && ($param[2] == '-d')) {
                        if ($_system) {
                            $daemonize = true;
                        } else {
                            echo $_color_class->info("当前环境是windows,只能在控制台运行\r\n");
                            echo "\r\n";
                        }
                    }

                    echo $_color_class->info("进程启动中...\r\n");
                    echo "\r\n";
                    break;
                case "stop":
                    if ($_system) {
                        $this->close();
                        echo $_color_class->info("进程已关闭\r\n");
                    } else {
                        echo $_color_class->info("当前环境是windows,只能在控制台运行\r\n");

                    }
                    $flag = false;
                    break;
                case "restart":
                    if ($_system) {
                        $this->close();
                        $daemonize = true;
                        echo $_color_class->info("进程重启中\r\n");
                    } else {
                        echo $_color_class->info("当前环境是windows,只能在控制台运行\r\n");
                    }
                    break;
                case "queue":
                    echo $_color_class->info("测试redis队列,你可以按CTRL+C停止");
                    \cli_set_process_title("xiaosongshu_queue");
                    $this->xiaosongshu_queue();
                    break;
                case "make:command":
                    $this->make_command($param[2]??'');
                    break;
                case "make:model":
                    $this->make_model($param[2]??'');
                    break;
                case "make:controller":
                    $this->make_controller($param[2]??'');
                    break;
                case "make:sqlite":
                    $this->make_sqlite_model($param[2]??'');
                    break;
                default:
                    /** 如果是自定义命令，则执行用户的逻辑 */
                    if (isset($_system_command[$param[1]])) {
                        $arguments = $param;
                        unset($arguments[0]);
                        unset($arguments[1]);
                        /** 这里可能用反射更好一点，懒得改了 */
                        $specialCommandClass = (new $_system_command[$param[1]]());
                        if (method_exists($specialCommandClass, 'configure')) {
                            $specialCommandClass->configure();
                        }
                        /** 解析参数 */
                        $needFillArguments = [];
                        foreach ($arguments as  $item) {
                            /** option 参数 */
                            if (strpos($item, '=')) {
                                $value        = explode('=', $item);
                                $option_name  = str_replace('--', '', $value[0] ?? '');
                                /** 丢弃help关键字 */
                                if ($option_name=='help'){
                                    continue;
                                }
                                $option_value = $value[1] ?? null;
                                /** 只有被定义了才被赋值，这里不可使用isset，因为如果默认值为null，则不能判断 */
                                if (array_key_exists($option_name, $specialCommandClass->input['option'])) {
                                    $specialCommandClass->input['option'][$option_name] = $option_value;
                                }
                            } else {
                                /** argument参数,按顺序填充，如果类当中没有定义这个属性就丢弃 */
                                $needFillArguments[] = $item;
                            }
                        }
                        /** 赋值必填参数 */
                        if ($needFillArguments) {
                            foreach ($specialCommandClass->input['argument'] as $k => $v) {
                                $specialCommandClass->input['argument'][$k] = array_shift($needFillArguments);
                            }
                        }

                        /** 获取自定义命令的帮助 */
                        if(in_array('-h',$param)||in_array('--help',$param)){
                            $head= array_shift($specialCommandClass->help);
                            if (empty($specialCommandClass->help)){
                                echo $_color_class->info("暂无帮助信息")."\r\n";
                                exit;
                            }
                            $_system_table->table($head,$specialCommandClass->help);
                            exit;
                        }
                        /** 执行命令行逻辑 */
                        try {
                            $specialCommandClass->handle();
                        } catch (\Exception $exception) {
                            /** 捕获异常，并打印错误 */

                            echo $_color_class->error("报错：code:{$exception->getCode()},文件{$exception->getFile()}，第{$exception->getLine()}行发生错误，错误信息：{$exception->getMessage()}");
                            echo "\r\n";
                        }

                        exit;
                    } else {
                        /** 查看是否是用户自定义的命令 */
                        echo $_color_class->info("未识别的命令\r\n");
                        echo "\r\n";
                        $flag = false;
                    }

            }
        } else {
            echo $_color_class->info("缺少必要参数，你可以输入start,start -d,stop,restart,queue\r\n");
            $flag = false;
        }
        if ($flag == false) {
            echo $_color_class->info("脚本退出运行\r\n");
            exit;
        }
        $fd  = fopen($_lock_file, 'w');
        $res = flock($fd, LOCK_EX | LOCK_NB);
        if (!$res) {
            echo $_color_class->info($_listen . "\r\n");
            echo $_color_class->info("已有脚本正在运行，请勿重复启动，你可以使用stop停止运行或者使用restart重启\r\n");
            exit(0);
        }

        /** 此处需要判断是否是是Linux系统，如果是则检查是否有epoll 有则调用epoll，否则调用select */
        if ($daemonize) {
            $this->daemon();
        } else {
            $open=[
                ['http','正常','1',$_listen]
            ];
            $_system_table->table(['名称','状态','进程数','服务'],$open);
            echo $_color_class->info("进程启动完成,你可以按ctrl+c停止运行\r\n");
            if ($_system && $_has_epoll) {
                /** linux系统使用epoll模型 */
                $this->epoll();
            } else {
                /** windows系统使用select模型 */
                $this->select();
            }

        }

    }

    /**
     * 创建命令行
     * @param string $name
     * @return void
     */
    public function make_command(string $name):void{
        if (!$name) {
            echo "请输入要创建的命令文件名称\r\n";
            exit;
        }
        foreach (scan_dir(command_path(),true) as $key => $file) {
            if (file_exists($file)) {
                $fileName = basename($file);
                if ($fileName == $name . '.php') {
                    echo "存在相同名称的文件：[{$fileName}]\r\n";
                    exit;
                }
            }
        }
        $time = date('Y-m-d H:i:s');
        $content = <<<EOF
<?php
namespace App\Command;

use Root\BaseCommand;
/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time $time
 */
class $name extends BaseCommand
{

    /** @var string \$command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public \$command = 'your:command';
    
     /**
     * 配置参数
     * @return void
     */
    public function configure(){
        /** 必选参数 */
        \$this->addArgument('argument','这个是参数argument的描述信息');
        /** 可传参数 */
        \$this->addOption('option','这个是option参数的描述信息');
    }
    
    /**
     * 清在这里编写你的业务逻辑
     * @return void
     */
    public function handle()
    {
        /** 获取必选参数 */
        var_dump(\$this->getOption('argument'));
        /** 获取可选参数 */
        var_dump(\$this->getOption('option'));
        \$this->info("请在这里编写你的业务逻辑");
    }
}
EOF;
        @file_put_contents(app_path() . '/app/command/' . $name . '.php', $content);
        echo "创建完成\r\n";
        exit;
    }

    /**
     * 创建模型
     * @param string $name
     * @return void
     */
    public function make_model(string $name):void {
        if (!$name) {
            echo "请输入要创建的模型名称\r\n";
            exit;
        }

        $name =trim($name,'/');
        $controller = strtolower(app_path().'/app/model/'.$name . '.php');
        /**
         * 检查是否存在相同的文件
         */
        foreach (scan_dir(app_path().'/app/model',true) as  $file) {
            if (file_exists($file)) {
                $fileName =strtolower($file);
                if ($fileName == $controller ) {
                    echo "存在相同名称的文件：[{$fileName}]\r\n";
                    exit;
                }
            }
        }
        $name = array_filter(explode('/',$name));

        $className = ucfirst(strtolower(array_pop($name)));
        $nameSpace = "App\Model";
        if ($name){
            foreach ($name as $dir){
                $dir = ucfirst(strtolower($dir));
                $nameSpace =$nameSpace."\\".$dir;
            }
        }
        $filePath = app_path().'/app/model';
        foreach ($name as $dir){
            $filePath = $filePath.'/'.strtolower($dir);
        }
        if (!is_dir($filePath)){
            mkdir($filePath,'0777',true);
        }
        $filePath = $filePath."/".$className.'.php';

        $time = date('Y-m-d H:i:s');
        /** 表名 */
        $lower_name = strtolower($className);
        $content = <<<EOF
<?php

namespace $nameSpace;

use Root\Model;
/**
 * @purpose mysql模型
 * @author administrator
 * @time $time
 */
class $className extends Model
{
    /** @var string \$table 建议指定表名，否则系统根据模型名推断表名，可能会不准确 */
    public string \$table = "$lower_name";

}
EOF;

        file_put_contents($filePath,$content);
        echo "创建模型完成\r\n";
        exit;
    }

    /**
     * 创建sqlite模型
     * @param string $name
     * @return void
     */
    public function make_sqlite_model(string $name):void {
        if (!$name) {
            echo "请输入要创建的sqlite模型名称\r\n";
            exit;
        }

        $name =trim($name,'/');
        $controller = strtolower(app_path().'/app/sqliteModel/'.$name . '.php');
        /**
         * 检查是否存在相同的文件
         */
        foreach (scan_dir(app_path().'/app/sqliteModel',true) as  $file) {
            if (file_exists($file)) {
                $fileName =strtolower($file);
                if ($fileName == $controller ) {
                    echo "存在相同名称的文件：[{$fileName}]\r\n";
                    exit;
                }
            }
        }
        $name = array_filter(explode('/',$name));

        $className = ucfirst(strtolower(array_pop($name)));
        $nameSpace = "App\SqliteModel";
        if ($name){
            foreach ($name as $dir){
                $dir = ucfirst(strtolower($dir));
                $nameSpace =$nameSpace."\\".$dir;
            }
        }
        $filePath = app_path().'/app/sqliteModel';
        foreach ($name as $dir){
            $filePath = $filePath.'/'.strtolower($dir);
        }
        if (!is_dir($filePath)){
            mkdir($filePath,'0777',true);
        }
        /** 文件名 */
        $filePath = $filePath."/".$className.'.php';

        /** 存放目录 */
        $location = implode('/',$name);
        /** 当前时间 */
        $time = date('Y-m-d H:i:s');
        /** 表名 */
        $lower_name = strtolower($className);
        $content = <<<EOF
<?php

namespace $nameSpace;

use Root\SqliteBaseModel;
/**
 * @purpose sqlite 模型
 * @author administrator
 * @time $time
 */
class $className extends SqliteBaseModel
{

    /** 存放目录：请修改为你自己的字段，真实路径为config/sqlite.php里面absolute设置的路径 + \$dir ,例如：/usr/src/myapp/fasterphpweb/sqlite/datadir/hello/talk */
    public string \$dir = '$location';

    /** 表名称：请修改为你自己的表名称 */
    public string \$table = '$lower_name';

    /** 表字段：请修改为你自己的字段 */
    public string \$field ='id INTEGER PRIMARY KEY,name varhcar(24),created text(12)';

}
EOF;

        file_put_contents($filePath,$content);
        echo "创建sqlite模型完成\r\n";
        exit;
    }

    /**
     * 创建控制器
     * @param string $name 控制器名称
     * @return void
     */
    public function make_controller(string $name):void {
        if (!$name) {
            echo "请输入要创建的控制器名称\r\n";
            exit;
        }
        $time = date('Y-m-d H:i:s');

        $name =trim($name,'/');
        $controller = strtolower(app_path().'/app/controller/'.$name . '.php');
        /**
         * 检查是否存在相同的文件
         */
        foreach (scan_dir(app_path().'/app/controller',true) as $key => $file) {
            if (file_exists($file)) {
                $fileName =strtolower($file);
                if ($fileName == $controller ) {
                    echo "存在相同名称的文件：[{$fileName}]\r\n";
                    exit;
                }
            }
        }
        $name = array_filter(explode('/',$name));
        if (count($name)<2){
            echo "必须是模块名称/控制器名称\r\n";exit;
        }
        $className = ucfirst(strtolower(array_pop($name)));
        $nameSpace = "App\Controller";
        if ($name){
            foreach ($name as $dir){
                $dir = ucfirst(strtolower($dir));
                $nameSpace =$nameSpace."\\".$dir;
            }
        }
        $filePath = app_path().'/app/controller';
        foreach ($name as $dir){
            $filePath = $filePath.'/'.strtolower($dir);
        }
        if (!is_dir($filePath)){
            mkdir($filePath,'0777',true);
        }
        $filePath = $filePath."/".$className.'.php';
        $content =<<<EOF
<?php

namespace $nameSpace;

use Root\Request;

/**
 * @purpose 控制器
 * @author administrator
 * @time $time
 */
class $className
{
    /**
     * index方法
     * @param Request \$request 请求类
     * @return string|string[]|null
     */
    public function index(Request \$request){
        return \$request->param();
    }
}
EOF;
        file_put_contents($filePath,$content);
        echo "创建控制器完成\r\n";
        exit;
    }

    /** 队列 */
    public function _queue_xiaosongshu()
    {
        try {
            $config = config('redis');
            $host   = $config['host'] ?? '127.0.0.1';
            $port   =  $config['port'] ?? '6379';
            $client = new \Redis();
            $client->connect($host, $port);
            $client->auth($config['password']??'');
            while (true) {
                $job = json_decode($client->RPOP('xiaosongshu_queue'), true);
                $this->deal_job($job);
                $res = $client->zRangeByScore('xiaosongshu_delay_queue', 0, time(), ['limit' => 1]);
                if ($res) {
                    $value = $res[0];
                    $res1  = $client->zRem('xiaosongshu_delay_queue', $value);
                    if ($res1) {
                        $job = json_decode($value, true);
                        $this->deal_job($job);
                    }
                }
                if (empty($job) && empty($res)) {
                    sleep(1);
                }
            }
        } catch (\Exception $exception) {
            global $_color_class;
            echo $_color_class->error("\nredis连接失败,详情：{$exception->getMessage()}\n");
            echo "\r\n";
            echo $_color_class->info("系统将在3秒后重试\n");
            sleep(3);
            $this->_queue_xiaosongshu();
        }
    }

    /**
     * 队列逻辑处理
     * @param $job
     * @return void
     */
    public function deal_job($job = [])
    {
        if (!empty($job)) {
            if (class_exists($job['class'])) {
                $class = new $job['class']($job['param']);
                $class->handle();
            } else {
                echo $job['class'] . '不存在，队列任务执行失败！';
                echo "\r\n";
            }
        }
    }

    /** 执行定时器 */
    public function xiaosongshu_timer()
    {

        /** 在这里添加定时任务 ，然后发送信号 */
        foreach (config('timer') as $name=>$value){
            if ($value['enable']){
                $id = Timer::add($value['time'],$value['function'],[],$value['persist']);
            }
        }
        Timer::run();
        while (true) {
            Timer::tick();
            /** 管理定时器 */
            foreach (scan_dir(runtime_path().'/timer',true) as  $val) {
                if (is_file($val)){
                    $pid = file_get_contents($val);
                    if ($pid > 0) {
                        \posix_kill($pid, SIGKILL);
                    }
                    @unlink($val);
                    /** 杀死进程必须等待1秒 */
                    sleep(1);
                }
            }
            /** 每次执行完成后等待1秒，防止进程占用大量内存 */
            sleep(1);
        }
    }

    /** 执行队列 */
    public function xiaosongshu_queue()
    {
        $enable = config('redis')['enable'];
        if ($enable) {
            $this->_queue_xiaosongshu();
        }
    }

    /** 关闭进程 */
    public function close()
    {
        global $_pid_file,$_color_class;
        echo $_color_class->info("关闭进程中...\r\n");
        if (file_exists($_pid_file)) {
            $master_ids = file_get_contents($_pid_file);
            $master_id  = explode('-', $master_ids);
            foreach ($master_id as $k => $v) {
                if ($v > 0) {
                    \posix_kill($v, SIGKILL);
                }
            }
            file_put_contents($_pid_file, null);
            sleep(1);
        }
    }

    /** 环境监测 */
    public function check_env()
    {
        if (!extension_loaded('sockets')) {
            exit("请先安装sockets扩展，然后开启php.ini的sockets扩展");
        }
    }

    /** 普通的阻塞模式,可以自己尝试使用 */
    public function nginx()
    {
        $worker = new Nginx();
        $worker->start();
    }

    /** 异步IO之select轮询模式 */
    public function select()
    {
        $httpServer = new Selector();
        /** 消息接收  */
        $httpServer->onMessage = function ($socketAccept, $message) use ($httpServer) {
            $this->onMessage($socketAccept, $message, $httpServer);
        };
        $httpServer->start();
    }

    /**
     * select和epoll消息处理事件
     * @param $socketAccept
     * @param $message
     * @param $httpServer
     * @return void
     */
    public function onMessage($socketAccept, $message, &$httpServer)
    {
        if (strpos($message, 'HTTP/')) {
            $_param = [];
            $_mark  = getUri($message);

            $fileName = $_mark['file'];
            $_request = $_mark['request'];
            foreach ($_mark['post_param'] as $k => $v) {
                $_param[$k] = $v;
            }
            $url     = $fileName;
            $fileExt = preg_replace('/^.*\.(\w+)$/', '$1', $fileName);

            switch ($fileExt) {
                case "html":
                    fwrite($socketAccept, 'HTTP/1.1 200 OK' . PHP_EOL);
                    fwrite($socketAccept, 'Date:' . date('Y-m-d H:i:s') . PHP_EOL);
                    fwrite($socketAccept, 'Content-Type: text/html' . PHP_EOL);
                    fwrite($socketAccept, '' . PHP_EOL);
                    $fileName = dirname(__DIR__) . '/view/' . $fileName;
                    if (file_exists($fileName)) {
                        $fileContent = file_get_contents($fileName);
                    } else {
                        $fileContent = 'sorry,the file is missing!';
                    }
                    fwrite($socketAccept, "Content-Length: " . strlen($fileContent) . "\r\n\r\n");
                    fwrite($socketAccept, $fileContent, strlen($fileContent));
                    break;
                case "ico":
                case "jpg":
                case "js":
                case "css":
                case "gif":
                case "png":
                case "icon":
                case "jpeg":
                    fwrite($socketAccept, 'HTTP/1.1 200 OK' . PHP_EOL);
                    fwrite($socketAccept, 'Date:' . date('Y-m-d H:i:s') . PHP_EOL);
                    fwrite($socketAccept, 'Content-Type: image/jpeg' . PHP_EOL);
                    $fileName = dirname(__DIR__) . '/public/' . $fileName;
                    if (file_exists($fileName)) {
                        $fileContent = file_get_contents($fileName);
                    } else {
                        $fileContent = 'sorry,the file is missing!';
                    }
                    fwrite($socketAccept, "Content-Length: " . strlen($fileContent) . "\r\n\r\n");
                    fwrite($socketAccept, $fileContent, strlen($fileContent));
                    break;
                case "doc":
                case "docx":
                case "ppt":
                case "pptx":
                case "xls":
                case "xlsx":
                case "zip":
                case "rar":
                case "txt":
                    fwrite($socketAccept, 'HTTP/1.1 200 OK' . PHP_EOL);
                    fwrite($socketAccept, 'Date:' . date('Y-m-d H:i:s') . PHP_EOL);
                    fwrite($socketAccept, 'Content-Type: application/octet-stream' . PHP_EOL);
                    fwrite($socketAccept, '' . PHP_EOL);
                    $fileName = dirname(__DIR__) . '/public/' . $fileName;
                    if (file_exists($fileName)) {
                        $fileContent = file_get_contents($fileName);
                    } else {
                        $fileContent = 'sorry,the file is missing!';
                    }
                    fwrite($socketAccept, "Content-Length: " . strlen($fileContent) . "\r\n\r\n");
                    fwrite($socketAccept, $fileContent, strlen($fileContent));
                    break;
                default:
                    if (($url) && strpos($url, '?')) {
                        $request_url = explode('?', $url);
                        $route       = $request_url[0];
                        $params      = explode('&', $request_url[1]);
                        foreach ($params as $k => $v) {
                            $_v             = explode('=', $v);
                            $_param[$_v[0]] = $_v['1'];
                        }
                        $content = $this->handle(route($route), $_param, $_request);
                    } else {
                        $content = $this->handle(route($url), $_param, $_request);
                    }
                    /** 文件下载 */
                    if (isset($content['type']) && $content['type'] == md5('_byte_for_down_load_file_')) {
                        fwrite($socketAccept, 'HTTP/1.1 200 OK' . PHP_EOL);
                        fwrite($socketAccept, 'Date:' . date('Y-m-d H:i:s') . PHP_EOL);
                        fwrite($socketAccept, 'Content-Type: application/octet-stream' . PHP_EOL);
                        fwrite($socketAccept, 'Accept-Ranges: bytes' . PHP_EOL);
                        fwrite($socketAccept, 'Accept-Length:' . strlen($content['content']) . PHP_EOL);
                        fwrite($socketAccept, 'Content-Disposition: attachment; filename=' . $content['name'] . "\r\n\r\n");
                        fwrite($socketAccept, $content['content'], strlen($content['content']));
                    } else {
                        /** 其他的暂时都做html处理 */
                        if (!is_string($content)) {
                            $content = json_encode($content);
                        }
                        fwrite($socketAccept, 'HTTP/1.1 200 OK' . PHP_EOL);
                        fwrite($socketAccept, 'Date:' . date('Y-m-d H:i:s') . PHP_EOL);
                        fwrite($socketAccept, 'Content-Type: text/html' . PHP_EOL);
                        fwrite($socketAccept, "Content-Length: " . strlen($content) . "\r\n\r\n");
                        fwrite($socketAccept, $content, strlen($content));
                    }

            }
            /** 这里必须关闭才能够给cli模式正常的返回数据，但是这个会影响需要长连接的浏览器或者其他服务，还不知道怎么处理 */
            fclose($socketAccept);
            /** 清理select连接 */
            unset($httpServer->allSocket[(int)$socketAccept]);
            /** 清理epoll连接 */
            unset($httpServer->events[(int)$socketAccept]);
            /** 释放客户端连接 */
            unset($socketAccept);
        }

    }

    /** 使用epoll异步io模型 */
    public function epoll()
    {
        /** @var object $httpServer 将对象加载到内存 */
        $httpServer = new Epoll();
        /** @var callable onMessage 设置消息处理函数 */
        $httpServer->onMessage = function ($socketAccept, $message) use ($httpServer) {
            $this->onMessage($socketAccept, $message, $httpServer);
        };
        /** 启动服务 */
        $httpServer->start();
    }

    /**
     * 处理request请求
     * @param string $url
     * @param array $param
     * @param array $_request
     * @return array|false|mixed|string|string[]|null
     * @throws \Exception
     */
    public function handle(string $url, array $param, array $_request)
    {

        list($file, $class, $method) = explode('@', $url);
        $file = app_path() . $file;
        if (!file_exists($file)) {
            return $this->dispay('index', ['msg' => $file . '文件不存在123123']);
        }
        require_once $file;
        if (!class_exists($class)) {
            return $this->dispay('index', ['msg' => $class . '类不存在']);
        }
        $class = new $class;
        if (!method_exists($class, $method)) {
            return $this->dispay('index', ['msg' => $method . '方法不存在']);
        }
        global $fuck;
        $fuck = new Request();
        foreach ($_request as $k => $v) {
            $v = trim($v);
            if ($v) {
                $_pos = strripos($v, ": ");
                $key = substr($v, 0, $_pos);
                $value = substr($v, $_pos + 1, strlen($v));
                if ($key) {
                    $fuck->header($key, $value);
                }
            }
        }
        foreach ($param as $k => $v) {
            $fuck->set($k, $v);
        }
        /** 这里必须捕获异常 */
        try {
            $response = $class->$method($fuck);
        }catch (\Exception|\RuntimeException $e){
            $fuck->_error=no_declear('index',['msg'=>"错误码：".$e->getCode()."<br>文件：".$e->getFile()."<br>行数：".$e->getLine().PHP_EOL."<br>错误详情：".$e->getMessage()]);
        }
        if ($fuck->_error) {

            return $fuck->_error;
        } else {
            return $response;
        }

    }


    /**
     * 渲染模板
     * @param string $path 模板路径
     * @param array $param 变量
     * @return array|false|string|string[]
     * @throws \Exception
     */
    public function dispay(string $path, array $param = [])
    {
        $content = file_get_contents(app_path() . '/root/error/' . $path . '.html');
        if ($param) {
            $preg = '/{\$[\s\S]*?}/i';
            preg_match_all($preg, $content, $res);
            $array = $res['0'];
            $new_param = [];
            foreach ($param as $k => $v) {
                $key = '{$' . $k . '}';
                $new_param[$key] = $v;
            }
            foreach ($array as $k => $v) {
                if (isset($new_param[$v])) {
                    $content = str_replace($v, $new_param[$v], $content);
                } else {
                    throw new \Exception("未定义的变量" . $v);
                }
            }
        }
        return $content;
    }


    /** 守护进程模式 */
    public function daemon()
    {
        /** 关闭错误 */
        ini_set('display_errors', 'off');
        /** 设置文件权限掩码为0 就是最大权限 可读写 防止操作文件权限不够出错 */
        \umask(0);
        global $_listen,$_color_class,$_system_table;
        /** @var int $pid 创建子进程 */
        $pid = \pcntl_fork();
        if (-1 === $pid) {
            /** 创建子进程失败 */
            throw new Exception('Fork fail');
        } elseif ($pid > 0) {
            /** 主进程退出 */
            $head =  ['名称','状态','进程数','服务'];
            $content =[];
            /** http */
            $http_count = config('server')['num']??4;
            $content[]=['http','正常',$http_count,$_listen];
            /** rabbitmq */
            $rabbitmq_config = config('rabbitmq');
            if ($rabbitmq_config['enable']){
                $rabbitmq_count = 0;
                foreach ((config('rabbitmqProcess')) as $item){
                    $rabbitmq_count+=$item['count'];
                }
                $content[]=['rabbitmq','正常',$rabbitmq_count,$rabbitmq_config['port']];
            }
            /** 定时器 */
            $content[]=['timer','正常','--','--'];
            /** redis队列 */
            $redis_config = config('redis');
            if ($redis_config['enable']){
                $content[]=['redis_queue','正常',1,$redis_config['port']];
            }
            $_system_table->table($head,$content);
            echo $_color_class->info($_listen . "\r\n");
            echo $_color_class->info("进程启动完成,你可以输入php start.php stop停止运行\r\n");
            exit(0);
        }

        /** 子进程开始工作 */
        global $_pid_file;
        file_put_contents($_pid_file, '');

        /** @var int $master_pid 获取当前进程id */
        $master_pid = getmypid();
        /** 将当前进程升级为主进程 */
        if (-1 === \posix_setsid()) {
            throw new \Exception("Setsid fail");
        }
        /** 创建子进程 */
        $this->create_process();
        /** @var int $_this_pid 获取当前进程id */
        $_this_pid = getmypid();
        /** 如果是主进程 */
        if ($_this_pid == $master_pid) {
            /** 在主进程里再创建一个子进程 */
            \pcntl_fork();
            if (getmypid() == $master_pid) {
                /** 在主进程里面创建一个子进程负责处理rabbitmq的队列 */
                $_small_son_id = \pcntl_fork();
                if ($_small_son_id > 0) {
                    /** 记录进程号 */
                    writePid();
                    /** 子进程 */
                    $this->rabbitmqConsume();
                } elseif ($_small_son_id == 0) {
                    /** 主进程 */
                    $clear_task_id = \pcntl_fork();
                    if ($clear_task_id){
                        /** 如果是主进程，则设置进程名称为master，管理定时器 */
                        cli_set_process_title("xiaosongshu_master");
                        writePid();
                        /** 在主进程里启动定时器 */
                        $this->xiaosongshu_timer();
                    }

                } else {
                    echo $_color_class->info("在创建rabbitmq的管理进程的时候失败了\r\t");
                    exit;
                }

            } else {
                /** 在子进程里启动队列，并设置进程名称 */
                cli_set_process_title("xiaosongshu_queue");
                writePid();
                $this->xiaosongshu_queue();
            }
        } else {
            /** 在子进程里启动队列，并设置进程名称 */
            cli_set_process_title("xiaosongshu_http");
            writePid();
            global $_has_epoll;
            /** 如果linux支持epoll模型则使用epoll */
            if ($_has_epoll) {
                /** 使用epoll */
                $this->epoll();
            } else {
                /** 使用普通的同步io */
                $this->select();
            }
        }
        /** @var int $pid 再创建一个子进程，脱离主进程会话 */
        $pid = \pcntl_fork();
        if (-1 === $pid) {
            throw new Exception("Fork fail");
        } elseif (0 !== $pid) {
            /** 脱离会话控制 */
            exit(0);
        }
    }

    /** 创建子进程 */
    public function create_process()
    {
        /** 初始化工作进程数 */
        global $_server_num, $_pid_file;
        /** 至少要开启一个子进程才能开启http服务 */
        if ($_server_num < 2) $_server_num = 2;
        /** 创建子进程，因为是多进程，所以会有以下的操作 */
        for ($i = 0; $i <= $_server_num; $i++) {
            /** @var string $read_log_content 读取已经开启的进程 */
            $read_log_content = file_get_contents($_pid_file);
            $father           = explode('-', $read_log_content);
            $mother           = [];
            /** 读取已有的进程 */
            foreach ($father as $k => $v) {
                /** 将进程id复制到新的数组 */
                if (!array_search($v, $mother)) {
                    $mother[] = $v;
                }
            }
            /** @var array $mother 进程id去重 */
            $mother = array_unique($mother);
            /** @var int $worker_num 统计当前已有的进程总数 */
            $worker_num = count($mother);
            /** 如果当前已开启的进程数大于设置的进程总数，则不再创建子进程，这里是多进程，所以会有反复的读写和比较操作，不能按单进程的思想理解 */
            if ($worker_num > $_server_num) {
                break;
            } else {
                /** 否则创建子进程 */
                \pcntl_fork();
                /** 将创建好的进程id存入文件 */
                writePid();
            }
        }
    }
    
    /**
     * 处理自定义的命令
     * @return void
     */
    public function deal_command()
    {
        global $_system_command;
        /** 加载所有自定义的命令 */
        foreach (scan_dir(command_path(),true) as $key => $file) {
            if (file_exists($file)) {
                require_once $file;
                $php_code = file_get_contents($file);
                $classes  = get_php_classes($php_code);
                foreach ($classes as $class) {
                    /** @var string $_class_name 拼接完整的路径 */
                    $_class_name = 'App\Command\\' . $class;
                    /** @var object $object 通过反射获取这个类 */
                    $object = new \ReflectionClass(new $_class_name());
                    /** 如果这个类有command属性，并且有handle方法，则将这个方法和类名注册到全局命令行中 */
                    if ($object->hasMethod('handle') && $object->hasProperty('command')) {
                        foreach ($object->getDefaultProperties() as $property => $command) {
                            if ($property == 'command') {
                                $_system_command[$command] = $_class_name;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 处理rabbitmq的消费
     * @return void
     */
    public function rabbitmqConsume()
    {
        $enable = config('rabbitmq')['enable'];
        if ($enable) {
            $config = config('rabbitmqProcess');
            foreach ($config as $name => $value) {
                if (isset($value['handler'])) {
                    /** 创建一个子进程，在子进程里面执行消费 */
                    $count = $value['count']??1;
                    for($i=0;$i<$count;$i++){
                        $rabbitmq_pid = \pcntl_fork();
                        if ($rabbitmq_pid > 0) {
                            /** 记录进程号 */
                            writePid();
                            cli_set_process_title($name.'_'.($i+1));
                            if (class_exists($value['handler'])) {
                                /** 切换CPU */
                                sleep(1);
                                $className = $value['handler'];
                                $queue     = new $className();
                                $queue->consume();
                            }
                        }
                    }

                }
            }
        }
    }



}

return new Xiaosongshu();