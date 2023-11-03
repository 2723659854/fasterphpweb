<?php

namespace Root;

use Root\Core\AppFactory;
use Root\Core\Provider\IdentifyInterface;
use Root\Io\Epoll;
use Root\Io\Selector;
use Root\Lib\Container;
use Root\Lib\NacosConfigManager;
use Root\Queue\RabbitMqConsumer;
use Root\Queue\RedisQueueConsumer;
use Root\Queue\RtmpConsumer;
use Root\Queue\TimerConsumer;
use Root\Queue\WsConsumer;

/**
 * @purpose 应用启动处理器
 * @time 2023年9月21日12:57:34
 */
class Xiaosongshu
{

    /** 定义文件类型请求返回数据 */
    protected $backContenType = [
        'html' => 'text/html; charset=UTF-8',
        'js' => 'text/javascript; charset=UTF-8',
        'css' => 'text/css; charset=UTF-8',
        'svg' => 'image/svg+xml; charset=UTF-8',
        'png' => 'image/jpeg; charset=UTF-8',
        'jpg' => 'image/jpeg; charset=UTF-8',
        'icon' => 'image/jpeg; charset=UTF-8',
        'jpeg' => 'image/jpeg; charset=UTF-8',
        'ico' => 'image/jpeg; charset=UTF-8',
        'gif' => 'image/jpeg; charset=UTF-8',
        "doc" => 'image/jpeg; charset=UTF-8',
        "docx" => 'application/octet-stream; charset=UTF-8',
        "ppt" => 'application/octet-stream; charset=UTF-8',
        "pptx" => 'application/octet-stream; charset=UTF-8',
        "xls" => 'application/octet-stream; charset=UTF-8',
        "xlsx" => 'application/octet-stream; charset=UTF-8',
        "zip" => 'application/octet-stream; charset=UTF-8',
        "rar" => 'application/octet-stream; charset=UTF-8',
        "txt" => 'application/octet-stream; charset=UTF-8',
        'mp4' => 'video/mp4; charset=UTF-8',
        'xml' => 'text/xml; charset=UTF-8',
        'flv' => 'video/x-flv; charset=UTF-8',
        'ttf' => 'font/ttf; charset=UTF-8',
        'avi' => 'video/x-msvideo; charset=UTF-8',
    ];


    /**
     * 启动服务
     * @param $param
     * @return void
     * @throws \Exception
     */
    public function start_server($param)
    {
        /** 环境监测 */
        $this->check_env();
        /** 是否守护模式 */
        $daemonize = false;
        /** 是否运行 */
        $flag = true;
        global $_pid_file, $_port, $_listen, $_server_num, $_system, $_lock_file, $_has_epoll, $_system_command, $_system_table, $_color_class, $_daemonize;
        $_daemonize = false;
        /** 进程管理文件 */
        $_pid_file = __DIR__ . '/my_pid.txt';
        /** 状态管理文件 */
        $_lock_file = __DIR__ . '/lock.txt';
        /** 加载必须的启动文件 */
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        /** 加载助手函数 */
        require_once __DIR__ . '/function.php';
        /** 加载根文件，常驻内存文件，应用目录文件 */
        //$this->requireFile();
        /** 是否linux系统 */
        $_system = !(\DIRECTORY_SEPARATOR === '\\');
        /** 是否有epoll模型 */
        $_has_epoll = (new \EventBase())->getMethod() == 'epoll';
        //$_has_epoll = false;
        /** 读取服务器配置 */
        $server = config('server');
        $_port = $server['port'] ?? 8000;
        $_server_num = $server['num'] ?? 2;
        $_listen = "http://0.0.0.0:" . $_port;
        /** 安装用户的自定义命令 */
        $this->installUserCommand();
        /** 加载表格类工具 */
        $_system_table = G(\Xiaosongshu\Table\Table::class);
        /** 加载字体类工具 */
        $_color_class = G(\Xiaosongshu\ColorWord\Transfer::class);
        /** 支持持linux */
        if ($_system) {
            /** 创建定时器数据库 */
            @$this->makeTimeDatabase();
        }
        /** 分析用户输入的命令，执行业务逻辑 */
        if (count($param) > 1) {
            try {
                $startAppAndCommandClass = G(AppFactory::class)->{$param[1]};
            } catch (\Exception $exception) {
                $startAppAndCommandClass = null;
            }
            if ($startAppAndCommandClass instanceof IdentifyInterface) {
                $startAppAndCommandClass->handle($this, $param);
            } else {
                /** 如果是自定义命令，则执行用户的逻辑 */
                if (isset($_system_command[$param[1]])) {
                    /** 处理用户自定义命令 */
                    $this->handleOwnCommand($param);
                } else {
                    /** 查看是否是用户自定义的命令 */
                    echo $_color_class->info("未识别的命令:{$param[1]}\r\n");
                }
            }
        } else {
            echo $_color_class->info("缺少必要参数，你可以输入start,start -d,stop,restart,queue\r\n");
        }
    }

    /**
     * 安装项目目录
     * @return void
     * @note 如果引入外部的包,即在composer.json里面设置了autoload，请使用composer dump-autoload 更新自动加载机制composer dump-autoload -o
     */
    public function requireFile()
    {
        /** 这里为啥要写这个东西呢，因为某些环境下，psr0和psr4也解救不了文件的加载顺序问题，composer表示无能为力，所以只能先composer尝试加载自定义目录，然后再手动加载 */
        $interface = [];
        $abstract = [];
        $others = [];
        foreach (['root', 'process', 'ws', 'app'] as $name) {
            foreach (sortFiles(scan_dir(app_path() . '/' . $name, true)) as $val) {
                if (file_exists($val) && (pathinfo($val)['extension'] == 'php')) {
                    /** 读取文件代码 */
                    $code = file_get_contents($val);
                    /** 首先找到所有的接口类 */
                    if (stripos($code,'interface')){
                        $interface[]=$val;
                        /** 然后找到抽象类 */
                    }elseif(stripos($code,'abstract')){
                        $abstract[]=$val;
                    }else{
                        /** 最后其他类 */
                        $others[]=$val;
                    }
                }
            }
        }
        /** 先加载接口类 */
        foreach ($interface as $file){
            require_once $file;
        }
        /** 加载抽象类 */
        foreach ($abstract as $file){
            require_once $file;
        }
        /** 加载其他类 */
        foreach ($others as $file){
            require_once $file;
        }
    }

    /**
     * 创建表
     * @return void
     */
    public function makeTimeDatabase()
    {
        TimerData::first();
    }

    /**
     * 处理用户自定义命令
     * @param $param
     * @return void
     */
    public function handleOwnCommand($param)
    {
        global $_system_command, $_system_table, $_color_class;
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
        foreach ($arguments as $item) {
            /** option 参数 */
            if (strpos($item, '=')) {
                $value = explode('=', $item);
                $option_name = str_replace('--', '', $value[0] ?? '');
                /** 丢弃help关键字 */
                if ($option_name == 'help') {
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
        if (in_array('-h', $param) || in_array('--help', $param)) {
            $head = array_shift($specialCommandClass->help);
            if (empty($specialCommandClass->help)) {
                echo $_color_class->info("暂无帮助信息") . "\r\n";
                exit;
            }
            $_system_table->table($head, $specialCommandClass->help);
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
    }

    /** 关闭进程 */
    public function close()
    {
        global $_pid_file, $_color_class;
        echo $_color_class->info("服务关闭中...\r\n");
        if (file_exists($_pid_file)) {
            $master_ids = file_get_contents($_pid_file);
            $master_id = explode('-', $master_ids);
            rsort($master_id);
            $master_id = array_unique($master_id);
            foreach ($master_id as $v) {
                if ($v > 0) {
                    \posix_kill($v, SIGKILL);
                }
            }
            file_put_contents($_pid_file, null);
            sleep(1);
            self::close_rtmp();
            sleep(2);
            Xiaosongshu::closeWorker();
        }
        echo $_color_class->info("服务已关闭\r\n");
    }

    /** 自动化重启项目 */
    public static function restart(){
        (function(){
            global $_pid_file, $_start_server_file_lock;
            /** 关闭除主进程 以外的所有子进程 */
            if (file_exists($_pid_file)) {
                $master_ids = file_get_contents($_pid_file);
                $master_id = explode('-', $master_ids);
                rsort($master_id);
                $master_id = array_unique($master_id);
                foreach ($master_id as $v) {
                    if (($v > 0)&&(int)$v!=getmypid()) {
                        \posix_kill($v, SIGKILL);
                    }
                }
                file_put_contents($_pid_file, null);
                sleep(1);
            }
            /** 释放文件锁 */
            if ($_start_server_file_lock){
                flock($_start_server_file_lock,LOCK_UN);
                fclose($_start_server_file_lock);
            }
            /** 采用杀死进程的方式杀死worker以及所有子进程，因为普通的命令可能不能关闭worker服务 */
            Xiaosongshu::closeWorker();
            /** 重新启动服务，守护模式运行 */
            G(Xiaosongshu::class)->start_server(['start.php','start','-d']);
        })();
    }

    /**
     * 强制关闭worker
     * @return void
     * @note 通过杀死进程的方式杀死worker
     */
    public static function closeWorker(){
        $rtmpId = Xiaosongshu::getWorkerPid();
        if ($rtmpId['pid']){
            $pids = Xiaosongshu::getSubprocesses($rtmpId['pid']);
            foreach ($pids as $id){
                \posix_kill($id, SIGKILL);
            }
            /** 清空pid 否则无法重启worker */
            file_put_contents($rtmpId['file'],null);
            sleep(1);
        }
    }

    /**
     * 获取rtmp masterId
     * @return array
     * @note 获取worker的主进程id
     */
    public static function getWorkerPid(){
        $path = explode('/',app_path());
        $file ='';
        foreach ($path as $v){
            if ($file){ $file .="_".$v; }else{ $file = $v; }
        }
        $file='_'.$file."_start.php.pid";
        $file = app_path().'/vendor/workerman/'.$file;

        if (is_file($file)){
            return ['pid'=>file_get_contents($file),'file'=>$file];
        }else{
            return ['pid'=>null,'file'=>$file];
        }
    }

    /**
     * 通过pid查询所有下级进程的pid
     * @param $pid
     * @return array
     * @note 通过worker的主进程id获取所有子进程id
     */
    public static function getSubprocesses($pid) {
        @exec("pstree -p {$pid}", $result, $returnCode);
        /** 发生了错误 */
        if ($returnCode!== 0) {
            return [];
        }
        /** 拼接pid */
        $string  = $result[0]??"";
        $pid = [];
        if ($string){
            $count  = strlen($string);
            $temp = '';
            for ($i=0;$i<$count;$i++){
                if (is_numeric($string[$i])){
                    $temp .=$string[$i];
                }else{
                    $pid[] = $temp;
                    $temp = '';
                }
            }
        }
        return array_filter($pid);
    }

    /**
     * 关闭rtmp服务
     * @return void
     */
    public static function close_rtmp(){
        /** 关闭rtmp服务 */
        $rtmp_enable = config('rtmp')['enable']??false;
        if ($rtmp_enable){
            /** workman是单独的一个框架，需要单独开启一个进程处理业务 */
            $rtmp_pid = pcntl_fork();
            if ($rtmp_pid>0){
                G(RtmpConsumer::class)->consume(['stop']);
            }
        }
    }

    /** 运行环境监测 */
    public function check_env()
    {
        if (!extension_loaded('sockets')) {
            exit("请先安装sockets扩展，然后开启php.ini的sockets扩展");
        }
    }

    /** 异步IO之select轮询模式 */
    public function select()
    {
        $httpServer = new Selector();
        /** 消息接收  */
        $httpServer->onMessage = function ($socketAccept, $message, $remote_address) use ($httpServer) {
            $this->onMessage($socketAccept, $message, $httpServer, $remote_address);
        };
        $httpServer->start();
    }

    /** 使用epoll异步io模型 */
    public function epoll()
    {
        /** @var object $httpServer 将对象加载到内存 */
        $httpServer = new Epoll();
        /** @var callable onMessage 设置消息处理函数 */
        $httpServer->onMessage = function ($socketAccept, $message, $remote_address) use ($httpServer) {
            $this->onMessage($socketAccept, $message, $httpServer, $remote_address);
        };
        /** 启动服务 */
        $httpServer->start();
    }

    /**
     * 处理用户请求
     * @param $socketAccept
     * @param $message
     * @param $httpServer
     * @return mixed
     */
    public function onMessage($socketAccept, $message, &$httpServer, $remote_address)
    {
        /** 这里不能这么用request ，用法应该是使用set方法 */
        $request = Container::set(Request::class, [$message, $remote_address]);
        $method = $request->method();
        $uri = $request->path();
        $info = explode('.', $request->path());
        $file_extension = end($info);
        /**  说明是资源类请求，直接返回资源 */
        if (in_array($file_extension, array_keys($this->backContenType))) {
            $fileName = $request->path();
            if ($file_extension == 'html') {
                $fileName = app_path() . '/view' . $fileName;
            } else {
                $fileName = public_path() . $fileName;
            }
            /** 如果有这个文件 */
            if (is_file($fileName)) {
                fwrite($socketAccept, response(file_get_contents($fileName), 200, ['Content-Type' => $this->backContenType[$file_extension]]));
                fclose($socketAccept);
            } else {
                /** 如果没有这个文件 */
                fwrite($socketAccept, response('<h1>Not Found</h1>', 404));
                fclose($socketAccept);
            }
        } else {
            /** 动态路由 */
            try {
                $content = Route::dispatch($method, $uri, $request);
                /** 用户返回的不是对象 */
                if (!is_object($content)) {
                    $content = response($content);
                }
                /** 用户返回的不是response对象 */
                if (!($content instanceof Response)) {
                    $content = response($content);
                }
                fwrite($socketAccept, $content);
                fclose($socketAccept);

            } catch (\Exception|\RuntimeException $exception) {
                /** 如果出现了异常 */
                fwrite($socketAccept, response($exception->getMessage(), 400));
                fclose($socketAccept);
            }
        }
        /** 清理select连接 */
        unset(Selector::$allSocket[(int)$socketAccept]);
        /** 清理epoll连接 */
        unset(Epoll::$events[(int)$socketAccept]);
        /** 释放客户端连接 */
        unset($socketAccept);
    }


    /** 守护进程模式 */
    public function daemon()
    {
        /** 关闭错误 */
        ini_set('display_errors', 'off');
        /** 设置文件权限掩码为0 就是最大权限 可读写 防止操作文件权限不够出错 */
        @\umask(0);
        /** @var int $pid 创建子进程 */
        $pid = \pcntl_fork();
        writePid();
        if (-1 === $pid) {
            /** 创建子进程失败 */
            throw new \Exception('Fork fail');
        } elseif ($pid > 0) {
            /** 打印当前服务 */
           $this->displayServer();
        }

        /** 子进程开始工作 */
        global $_has_epoll;
        /** @var int $master_pid 获取当前进程id */
        $master_pid = getmypid();
        /** 将当前进程升级为主进程 */
        if (-1 === \posix_setsid()) {
            throw new \Exception("Setsid fail");
        }
        /** select 是先创建进程，再开启服务 */
        if (!$_has_epoll) {
            /** 创建子进程 */
            $this->create_process($master_pid);
        } else {
            /** epoll 是在自己的进程中开启服务，*/
            \pcntl_fork();
            writePid();
        }

        /** @var int $_this_pid 获取当前进程id */
        $_this_pid = getmypid();
        /** 一个主进程和6个子进程 ，6个子进程负责http，主进程负责 */
        /** 如果是主进程 */
        if ($_this_pid != $master_pid) {
            /** 在子进程里启动队列，并设置进程名称 */
            cli_set_process_title("xiaosongshu_http");
            writePid();
            /** 如果linux支持epoll模型则使用epoll */
            if ($_has_epoll) {
                /** 使用epoll */
                $this->epoll();
            } else {
                /** 使用普通的同步io */
                $this->select();
            }
        }
        if ($_this_pid == $master_pid) {
            /** 开启其他常驻内存的服务进程 */
            $this->makeConsumeProcess($master_pid);
        }
        exit;
//        /** @var int $pid 再创建一个子进程，脱离主进程会话 */
//        $pid = \pcntl_fork();
//        writePid();
//        if (-1 === $pid) {
//            throw new \Exception("Fork fail");
//        } elseif (0 !== $pid) {
//            /** 脱离会话控制 */
//            exit(0);
//        }
    }

    /**
     * 打印当前提供的服务
     * @return void
     */
    public function displayServer(){
        global $_listen, $_color_class, $_system_table;
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
        echo $_color_class->info("进程启动完成,你可以输入php start.php stop停止运行\r\n");
        exit(0);
    }

    /**
     * 主进程决定是否开启子进程
     * @return void
     */
    public function makeConsumeProcess($master_pid)
    {
        $redis_enable = config('redis')['enable'] ?? false;
        $rabbitmq_enable = in_array(true, array_column(config('rabbitmqProcess') ?? [], 'enable'));
        $ws_enable = in_array(true, array_column(config('ws') ?? [], 'enable'));
        $rtmp_enable = config('rtmp')['enable']??false;
        /** 创建子进程负责处理 常驻内存的进程 */
        if (getmypid()==$master_pid){
            pcntl_fork();
            writePid();
        }
        if ($rtmp_enable&& (getmypid() != $master_pid)){
            /** 这里开启的是workman的进程 ，必须单独开进程*/
            $_rtmp_pid = pcntl_fork();
            if ($_rtmp_pid>0){
                writePid();
                G(RtmpConsumer::class)->consume(['start','-d']);
            }
        }

        /** 开启redis队列 */
        if ($redis_enable && (getmypid() != $master_pid)) {
            G(RedisQueueConsumer::class)->consume();
        }
        /** 开启rabbitmq队列 */
        if ($rabbitmq_enable && (getmypid() != $master_pid)) {
            G(RabbitMqConsumer::class)->consume();
        }
        /** 开启ws服务 */
        if ($ws_enable && (getmypid() != $master_pid)) {
            G(WsConsumer::class)->consume();
        }
        /** 开启主进程，定时任务 */
        if (getmypid() == $master_pid) {

            /** 这里存在问题，不同的进程先后执行问题 */
            Timer::deleteAll();
            /** 在这里添加定时任务 ，然后发送信号 */
            foreach (config('timer') as $name => $value) {
                if ($value['enable']) {
                    Timer::add($value['time'], $value['function'], [], $value['persist']);
                }
            }
//            /** 主进程内加一个定时器负责处理 */
//            $nacos_enable = config('nacos')['enable']??false;/** 先清空所有的定时器 */
//
//            /** 如果开起了nacos配置管理，则添加到定时任务中 */
//            if ($nacos_enable){
//                $add  = Timer::add(30,[NacosConfigManager::class,'sync'],[],true);
//                var_dump("加入nacos配置管理服务结果",$add);
//            }
            G(TimerConsumer::class)->consume();
        }

    }

    /** 创建子进程 */
    public function create_process($master_pid)
    {
        /** 初始化工作进程数 */
        global $_server_num;
        if ($_server_num<2) $_server_num =2;
        for ($i = 0; $i < $_server_num; $i++) {
            /** 只有主进程才可以创建子进程 */
            if (getmypid()==$master_pid){
                pcntl_fork();
                writePid();
            }
        }
    }

    /**
     * 装载用户自定义的命令
     * @return void
     */
    public function installUserCommand()
    {
        global $_system_command;
        /** 加载所有自定义的命令 */
        foreach (scan_dir(command_path(), true) as $key => $file) {
            if (file_exists($file)) {
                require_once $file;
                $php_code = file_get_contents($file);
                $classes = get_php_classes($php_code);
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
}

return new Xiaosongshu();