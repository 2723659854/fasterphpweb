<?php

namespace Root;

use Root\Core\AppFactory;
use Root\Core\Provider\IdentifyInterface;
use Root\Io\Epoll;
use Root\Io\Selector;
use Root\Lib\Container;
use Root\Queue\RabbitMqConsumer;
use Root\Queue\RedisQueueConsumer;
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
        /* Report all errors except E_NOTICE */
        /** 关闭不影响功能的警告和提醒 ，看着太烦人了 */

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
        $this->requireFile();
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
        /** 启动rtmp服务 */
        $this->start_rtmp($param);
    }

    public function start_rtmp($param){
        /** 开启一个tcp服务，监听1935端口 */
        $rtmpServer = new  \Workerman\Worker('tcp://0.0.0.0:1935');
        /** 当客户端连接服务端的时候触发 */
        $rtmpServer->onConnect = function (\Workerman\Connection\TcpConnection $connection) {
            logger()->info("connection" . $connection->getRemoteAddress() . " connected . ");
            new \MediaServer\Rtmp\RtmpStream(
                new \MediaServer\Utils\WMBufferStream($connection)
            );
        };
        /** 下面是提供flv播放资源的接口 */
        $rtmpServer->onWorkerStart = function ($worker) {
            logger()->info("rtmp server " . $worker->getSocketName() . " start . ");
            \MediaServer\Http\HttpWMServer::$publicPath = __DIR__.'/public';
            $httpServer = new \MediaServer\Http\HttpWMServer("\\MediaServer\\Http\\ExtHttpProtocol://0.0.0.0:18080");
            $httpServer->listen();
            logger()->info("rtmp推流地址：rtmp://0.0.0.0:1935/{your_app_name}/{your_live_room_name}");
            logger()->info("rtmp拉流地址：rtmp://0.0.0.0/{your_app_name}/{your_live_room_name}");
            logger()->info("http-flv地址：http://0.0.0.0:18080/{your_app_name}/{your_live_room_name}.flv");
            logger()->info("ws-flv地址：ws://0.0.0.0:18080/{your_app_name}/{your_live_room_name}.flv");
        };
        \Workerman\Worker::runAll();
    }
    /**
     * 安装项目目录
     * @return void
     * @note 如果引入外部的包,即在composer.json里面设置了autoload，请使用composer dump-autoload 更新自动加载机制
     */
    public function requireFile()
    {
        foreach (['root', 'process', 'ws', 'app'] as $name) {
            foreach (sortFiles(scan_dir(app_path() . '/' . $name, true)) as $val) {
                if (file_exists($val) && (pathinfo($val)['extension'] == 'php')) {
                    require_once $val;
                }
            }
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
        echo $_color_class->info("关闭进程中...\r\n");
        if (file_exists($_pid_file)) {
            $master_ids = file_get_contents($_pid_file);
            $master_id = explode('-', $master_ids);
            rsort($master_id);
            $master_id = array_unique($master_id);
            foreach ($master_id as $v) {
                if ($v > 0) {
                    \posix_kill($v, SIGQUIT);
                    \posix_kill($v, SIGINT);
                    \posix_kill($v, SIGKILL);
                    \posix_kill($v, SIGTERM);
                    \posix_kill($v, 0);
                }
            }
            sleep(1);
            file_put_contents($_pid_file, null);
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
        \umask(0);

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
        /** @var int $pid 再创建一个子进程，脱离主进程会话 */
        $pid = \pcntl_fork();
        writePid();
        if (-1 === $pid) {
            throw new \Exception("Fork fail");
        } elseif (0 !== $pid) {
            /** 脱离会话控制 */
            exit(0);
        }
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
                $rabbitmq_count += $item['count'];
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
        /** 创建子进程负责处理 常驻内存的进程 */
        if (getmypid()==$master_pid){
            pcntl_fork();
            writePid();
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