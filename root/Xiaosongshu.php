<?php

namespace Root;

use Root\Core\AppFactory;
use Root\Core\Provider\IdentifyInterface;
use Root\Io\Epoll;
use Root\Io\Selector;
use Root\Queue\RabbitMqConsumer;
use Root\Queue\RedisQueueConsumer;
use Root\Queue\TimerConsumer;

/**
 * @purpose 应用启动处理器
 * @time 2023年9月21日12:57:34
 */
class Xiaosongshu
{

    /** 定义文件类型请求返回数据 */
    protected $backContenType =[
        'html'=>'text/html; charset=UTF-8',
        'js'=>'text/javascript; charset=UTF-8',
        'css'=>'text/css; charset=UTF-8',
        'svg'=>'image/svg+xml; charset=UTF-8',
        'png'=>'image/jpeg; charset=UTF-8',
        'jpg'=>'image/jpeg; charset=UTF-8',
        'icon'=>'image/jpeg; charset=UTF-8',
        'jpeg'=>'image/jpeg; charset=UTF-8',
        'ico'=>'image/jpeg; charset=UTF-8',
        'gif'=>'image/jpeg; charset=UTF-8',
        "doc"=>'image/jpeg; charset=UTF-8',
        "docx"=>'application/octet-stream; charset=UTF-8',
        "ppt"=>'application/octet-stream; charset=UTF-8',
        "pptx"=>'application/octet-stream; charset=UTF-8',
        "xls"=>'application/octet-stream; charset=UTF-8',
        "xlsx"=>'application/octet-stream; charset=UTF-8',
        "zip"=>'application/octet-stream; charset=UTF-8',
        "rar"=>'application/octet-stream; charset=UTF-8',
        "txt"=>'application/octet-stream; charset=UTF-8',
        'mp4'=>'video/mp4; charset=UTF-8',
        'xml'=>'text/xml; charset=UTF-8',
        'flv'=>'video/x-flv; charset=UTF-8',
        'ttf'=>'font/ttf; charset=UTF-8',
        'avi'=>'video/x-msvideo; charset=UTF-8',
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
        $flag      = true;
        global $_pid_file, $_port, $_listen, $_server_num, $_system, $_lock_file, $_has_epoll, $_system_command, $_system_table, $_color_class;
        /** 进程管理文件 */
        $_pid_file  = __DIR__ . '/my_pid.txt';
        /** 状态管理文件 */
        $_lock_file = __DIR__ . '/lock.txt';
        /** 加载必须的启动文件 */
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        /** 加载助手函数 */
        require_once __DIR__ . '/function.php';
        /** 加载根文件，常驻内存文件，应用目录文件 */
        foreach (['root','process','app'] as $name){
            foreach (sortFiles(scan_dir(app_path() .'/'.$name,true)) as $val){
                if (file_exists($val)&&(pathinfo($val)['extension'] == 'php')) {  require_once $val; }
            }
        }

        /** 是否linux系统 */
        $_system = !(\DIRECTORY_SEPARATOR === '\\');
        /** 是否有epoll模型 */
        $_has_epoll = (new \EventBase())->getMethod() == 'epoll';
        /** 读取服务器配置 */
        $server     = config('server');
        $_port = $server['port']??8000;
        $_server_num = $server['num']??2;
        $_listen    = "http://0.0.0.0:" . $_port;
        /** 安装用户的自定义命令 */
        $this->installUserCommand();
        /** 加载表格类工具 */
        $_system_table = G(\Xiaosongshu\Table\Table::class);
        /** 加载字体类工具 */
        $_color_class = G(\Xiaosongshu\ColorWord\Transfer::class);
        /** 支持持linux */
        if ($_system) { /** 创建定时器数据库 */ @$this->makeTimeDatabase(); }
        /** 分析用户输入的命令，执行业务逻辑 */
        if (count($param) > 1) {
            try {
                $startAppAndCommandClass = G(AppFactory::class)->{$param[1]};
            }catch (\Exception $exception){
                $startAppAndCommandClass = null;
            }
            if ($startAppAndCommandClass instanceof IdentifyInterface){
                $startAppAndCommandClass->handle($this,$param);
            }else{
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

    public function makeTimeDatabase()
    {
        TimerData::first();
    }

     /**
     * 处理用户自定义命令
     * @param $param
     * @return void
     */
    public function handleOwnCommand($param){
        global  $_system_command, $_system_table, $_color_class;
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
                $value       = explode('=', $item);
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
        $httpServer->onMessage = function ($socketAccept, $message) use ($httpServer) {
            $this->onMessage($socketAccept, $message, $httpServer);
        };
        $httpServer->start();
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
     * 处理用户请求
     * @param $socketAccept
     * @param $message
     * @param $httpServer
     * @return mixed
     */
    public function onMessage($socketAccept, $message, &$httpServer)
    {
        $request = new Request($message);
        $method  = $request->method();
        $uri     = $request->path();

        $info           = explode('.', $request->path());
        $file_extension = end($info);
        /**  说明是资源类请求，直接返回资源 */
        if (in_array($file_extension,array_keys($this->backContenType))) {
            $fileName = $request->path();
            if ($file_extension=='html'){
                $fileName= app_path().'/view'.$fileName;
            }else{
                $fileName = public_path().$fileName;
            }
            /** 如果有这个文件 */
            if (is_file($fileName)){
                fwrite($socketAccept,response(file_get_contents($fileName),200,['Content-Type'=>$this->backContenType[$file_extension]]));
                fclose($socketAccept);
            }else{
                /** 如果没有这个文件 */
                fwrite($socketAccept,response('Not Found',404));
                fclose($socketAccept);
            }
        }else{
            /** 动态路由 */
            try {
                fwrite($socketAccept,Route::dispatch($method, $uri, $request));
                fclose($socketAccept);
            }catch (\Exception|\RuntimeException $exception){
                /** 如果出现了异常 */
                fwrite($socketAccept,response($exception->getMessage(),400));
                fclose($socketAccept);
            }
        }
        /** 清理select连接 */
        unset($httpServer->allSocket[(int)$socketAccept]);
        /** 清理epoll连接 */
        unset($httpServer->events[(int)$socketAccept]);
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
        global $_listen, $_color_class, $_system_table;
        /** @var int $pid 创建子进程 */
        $pid = \pcntl_fork();
        if (-1 === $pid) {
            /** 创建子进程失败 */
            throw new \Exception('Fork fail');
        } elseif ($pid > 0) {
            /** 主进程退出 */
            $head    = ['名称', '状态', '进程数', '服务'];
            $content = [];
            /** http */
            $http_count = config('server')['num'] ?? 4;
            $content[]  = ['http', '正常', $http_count, $_listen];
            /** rabbitmq */
            $rabbitmq_config = config('rabbitmq');
            if ($rabbitmq_config['enable']) {
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
            $_system_table->table($head, $content);
            //echo $_color_class->info($_listen . "\r\n");
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
                    G(RabbitMqConsumer::class)->consume();
                } elseif ($_small_son_id == 0) {
                    /** 主进程 */
                    $clear_task_id = \pcntl_fork();
                    if ($clear_task_id) {
                        /** 如果是主进程，则设置进程名称为master，管理定时器 */
                        cli_set_process_title("xiaosongshu_master");
                        writePid();
                        /** 在主进程里启动定时器 */
                        G(TimerConsumer::class)->consume();
                    }

                } else {
                    echo $_color_class->info("在创建rabbitmq的管理进程的时候失败了\r\t");
                    exit;
                }

            } else {
                /** 在子进程里启动队列，并设置进程名称 */
                cli_set_process_title("xiaosongshu_queue");
                writePid();
                G(RedisQueueConsumer::class)->consume();
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
}
return new Xiaosongshu();