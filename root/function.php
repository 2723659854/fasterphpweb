<?php


/**
 * App目录
 * @return string
 */
function app_path()
{
    return dirname(__DIR__);
}

/**
 * 获取配置文件
 * @param $path_name
 * @return mixed
 */
function config($path_name)
{
    return include app_path() . '/config/' . $path_name . '.php';
}

/**
 * public目录
 * @return string
 */
function public_path()
{
    return app_path() . '/public';
}

function command_path(){
    return dirname(__DIR__).'/app/command';
}

/**
 * 遍历目录
 * @param $path
 * @return mixed
 */
function traverse($path = '.')
{
    global $filePath;
    $current_dir = opendir($path);
    while (($file = readdir($current_dir)) !== false) {
        $sub_dir = $path . DIRECTORY_SEPARATOR . $file;
        if ($file == '.' || $file == '..') {
            continue;
        } else if (is_dir($sub_dir)) {
            traverse($sub_dir);
        } else {
            $filePath[$path . '/' . $file] = $path . '/' . $file;
        }
    }
    return $filePath;
}

/**
 * 获取文件中定义的类名
 * @param $php_code
 * @return array
 */
function get_php_classes($php_code) {
    $classes = array();
    $tokens = token_get_all($php_code);
    $count = count($tokens);
    for ($i = 2; $i < $count; $i++) {
        if (   $tokens[$i - 2][0] == T_CLASS
            && $tokens[$i - 1][0] == T_WHITESPACE
            && $tokens[$i][0] == T_STRING) {

            $class_name = $tokens[$i][1];
            $classes[] = $class_name;
        }
    }
    return $classes;
}
/**
 * 启动服务
 * @param $param
 * @return void
 * @throws Exception
 */
function start_server($param)
{

    check_env();
    $daemonize = false;
    $flag      = true;
    global $_pid_file, $_port, $_listen, $_server_num, $_system, $_lock_file, $_has_epoll,$_system_command;
    $_pid_file  = __DIR__ . '/my_pid.txt';
    $_lock_file = __DIR__ . '/lock.txt';
    require_once __DIR__ . '/Timer.php';
    require_once __DIR__ . '/view.php';
    require_once __DIR__ . '/Request.php';
    require_once __DIR__ . '/BaseModel.php';
    require_once __DIR__ . '/Cache.php';
    require_once __DIR__ . '/queue/Queue.php';
    require_once __DIR__ . '/Facade.php';
    require_once __DIR__ . '/Selector.php';
    require_once __DIR__ . '/Epoll.php';
    require_once __DIR__ . '/Nginx.php';
    require_once __DIR__ . '/BaseCommand.php';
    require_once __DIR__ . '/queue/RabbitMQBase.php';

    /** @var bool $_has_epoll 默认不支持epoll模型 */
    $_has_epoll = false;
    /** @var bool $_system 是否是linux系统 */
    $_system = true;
    if (\DIRECTORY_SEPARATOR === '\\') {
        $_system = false;
    } else {
        $_has_epoll = (new EventBase())->getMethod() == 'epoll';
    }
    $httpServer = null;
    $server     = include dirname(__DIR__) . '/config/server.php';
    if (isset($server['port']) && $server['port']) {
        $_port = intval($server['port']);
    } else {
        $_port = 8020;
    }
    if (isset($server['num']) && $server['num']) {
        $_server_num = intval($server['num']);
    } else {
        $_server_num = 2;
    }
    $_listen    = "http://127.0.0.1:" . $_port;
    $httpServer = null;
    /** 装载用户的自定义命令 */
    deal_command();
    /** 装载App目录下的所有文件 */
    foreach (traverse(app_path() . '/app') as $key => $val) {
        if (file_exists($val)) {
            require_once $val;
        }
    }
    /** 分析用户输入的命令，执行业务逻辑 */
    if (count($param) > 1) {
        switch ($param[1]) {
            case "start":
                if (isset($param[2]) && ($param[2] == '-d')) {
                    if ($_system) {
                        $daemonize = true;
                    } else {
                        echo "当前环境是windows,只能在控制台运行\r\n";
                    }
                }
                echo "进程启动中...\r\n";
                break;
            case "stop":
                if ($_system) {
                    close();
                    echo "进程已关闭\r\n";
                } else {
                    echo "当前环境是windows,只能在控制台运行\r\n";
                }
                $flag = false;
                break;
            case "restart":
                if ($_system) {
                    close();
                    $daemonize = true;
                    echo "进程重启中...\r\n";
                } else {
                    echo "当前环境是windows,只能在控制台运行\r\n";
                }
                break;
            case "queue":
                echo "测试队列,你可以按CTRL+C停止\r\n";
                \cli_set_process_title("xiaosongshu_queue");
                xiaosongshu_queue();
                break;
            default:
                /** 如果是自定义命令，则执行用户的逻辑 */
                if (isset($_system_command[$param[1]])){
                    (new $_system_command[$param[1]]())->handle();
                    exit;
                }else{
                    /** 查看是否是用户自定义的命令 */
                    echo "未识别的命令\r\n";
                    $flag = false;
                }

        }
    } else {
        echo "缺少必要参数，你可以输入start,start -d,stop,restart,queue\r\n";
        $flag = false;
    }
    if ($flag == false) {
        exit("脚本退出运行\r\n");
    }
    $fd  = fopen($_lock_file, 'w');
    $res = flock($fd, LOCK_EX | LOCK_NB);
    if (!$res) {
        echo $_listen . "\r\n";
        echo "已有脚本正在运行，请勿重复启动，你可以使用stop停止运行或者使用restart重启\r\n";
        exit(0);
    }

    /** 此处需要判断是否是是Linux系统，如果是则检查是否有epoll 有则调用epoll，否则调用select */

    if ($daemonize) {
        daemon();
    } else {
        echo $_listen . "\r\n";
        echo "进程启动完成,你可以按ctrl+c停止运行\r\n";

        if ($_system && $_has_epoll) {
            /** linux系统使用epoll模型 */
            epoll();
        } else {
            /** windows系统使用select模型 */
            select();
        }

    }

}

/** 队列 */
function _queue_xiaosongshu()
{
    try {
        $config = config('redis');
        $host   = isset($config['host']) ? $config['host'] : '127.0.0.1';
        $port   = isset($config['port']) ? $config['port'] : '6379';
        $client = new Redis();
        $client->connect($host, $port);
        while (true) {
            $job = json_decode($client->RPOP('xiaosongshu_queue'), true);
            deal_job($job);
            $res = $client->zRangeByScore('xiaosongshu_delay_queue', 0, time(), ['limit' => 1]);
            if ($res) {
                $value = $res[0];
                $res1  = $client->zRem('xiaosongshu_delay_queue', $value);
                if ($res1) {
                    $job = json_decode($value, true);
                    deal_job($job);
                }
            }
            if (empty($job) && empty($res)) {
                sleep(1);
            }
        }
    } catch (\Exception $exception) {
        echo $exception->getMessage();
        echo "\r\n";
        echo "redis连接失败";
        echo "\r\n";
    }
}

/**
 * 队列逻辑处理
 * @param $job
 * @return void
 */
function deal_job($job = [])
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
function xiaosongshu_timer()
{
    require_once __DIR__ . '/Timer.php';
    $timer_config = include dirname(__DIR__) . '/config/timer.php';
    if (!empty($timer_config)) {
        foreach ($timer_config as $k => $v) {
            $className = $v['handle'];
            $time      = $v['time'];
            if (class_exists($className)) {
                root\Timer::add(intval($time), function () use ($className) {
                    $class = new $className;
                    $class->handle();
                }, [], true);
            }
        }
    }
}

/** 执行队列 */
function xiaosongshu_queue()
{
    $enable=config('redis')['enable'];
    if ($enable){
        _queue_xiaosongshu();
    }
}

/** 关闭进程 */
function close()
{
    echo "关闭进程中...\r\n";
    global $_pid_file;
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
function check_env()
{
    if (!extension_loaded('sockets')) {
        exit("请先安装sockets扩展，然后开启php.ini的sockets扩展");
    }
}

/** 普通的阻塞模式,可以自己尝试使用 */
function nginx()
{
    require_once __DIR__ . '/Nginx.php';
    $worker = new Nginx();
    $worker->start();
}

/** 异步IO之select轮询模式 */
function select()
{
    //echo "启动select异步IO模型\r\n";
    require_once __DIR__ . '/Selector.php';
    $httpServer = new Selector();
    /** 消息接收  */
    $httpServer->onMessage = function ($socketAccept, $message) use ($httpServer) {
        onMessage($socketAccept, $message, $httpServer);
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
function onMessage($socketAccept, $message, &$httpServer)
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
        fwrite($socketAccept, 'HTTP/1.1 200 OK' . PHP_EOL);
        fwrite($socketAccept, 'Date:' . date('Y-m-d H:i:s') . PHP_EOL);
        switch ($fileExt) {
            case "html":
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
            case "ico": case "jpg": case "js": case "css": case "gif": case "png": case "icon": case "jpeg":
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
            case "doc": case "docx": case "ppt": case "pptx": case "xls": case "xlsx": case "zip": case "rar": case "txt":
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
                    $content = handle(route($route), $_param, $_request);
                } else {
                    $content = handle(route($url), $_param, $_request);
                }

                if (!is_string($content)) {
                    $content = json_encode($content);
                }
                fwrite($socketAccept, 'Content-Type: text/html' . PHP_EOL);
                fwrite($socketAccept, "Content-Length: " . strlen($content) . "\r\n\r\n");
                fwrite($socketAccept, $content, strlen($content));
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
function epoll()
{
    //echo "启动epoll异步IO模型\r\n";
    /** 加载epoll模型类 */
    require_once __DIR__ . '/Epoll.php';
    /** @var object $httpServer 将对象加载到内存 */
    $httpServer            = new Epoll();
    /** @var callable onMessage 设置消息处理函数 */
    $httpServer->onMessage = function ($socketAccept, $message) use ($httpServer) {
        onMessage($socketAccept, $message, $httpServer);
    };
    /** 启动服务 */
    $httpServer->start();
}


/** 守护进程模式 */
function daemon()
{
    /** 关闭错误 */
    ini_set('display_errors', 'off');
    /** 设置文件权限掩码为0 就是最大权限 可读写 防止操作文件权限不够出错 */
    \umask(0);
    /** @var int $pid 创建子进程 */
    $pid = \pcntl_fork();
    if (-1 === $pid) {
        /** 创建子进程失败 */
        throw new Exception('Fork fail');
    } elseif ($pid > 0) {
        /** 主进程退出 */
        global $_listen;
        echo $_listen . "\r\n";
        echo "进程启动完成,你可以输入php start.php stop停止运行\r\n";
        exit(0);
    }

    /** 子进程开始工作 */
    global $_pid_file;
    file_put_contents($_pid_file, '');

    /** @var int $master_pid 获取当前进程id */
    $master_pid = getmypid();
    /** 将当前进程升级为主进程 */
    if (-1 === \posix_setsid()) {
        throw new Exception("Setsid fail");
    }
    /** 创建子进程 */
    create_process();
    /** @var int $_this_pid  获取当前进程id */
    $_this_pid = getmypid();
    /** 如果是主进程 */
    if ($_this_pid == $master_pid) {
        /** 在主进程里再创建一个子进程 */
       \pcntl_fork();
        if (getmypid() == $master_pid) {
            /** 在主进程里面创建一个子进程负责处理rabbitmq的队列 */
            $_small_son_id=\pcntl_fork();
            if ($_small_son_id>0){
                /** 记录进程号 */
                writePid();
                /** 子进程 */
                rabbitmqConsume();
            }elseif($_small_son_id==0){
                /** 主进程 */
                /** 如果是主进程，则设置进程名称为master */
                cli_set_process_title("xiaosongshu_timer_and_master");
                writePid();
                /** 在主进程里启动定时器 */
                xiaosongshu_timer();
            }else{
                echo "在创建rabbitmq的管理进程的时候失败了\r\t";
                exit;
            }

        } else {
            /** 在子进程里启动队列，并设置进程名称 */
            cli_set_process_title("xiaosongshu_queue");
            writePid();
            xiaosongshu_queue();
        }
    } else {
        /** 在子进程里启动队列，并设置进程名称 */
        cli_set_process_title("xiaosongshu_http");
        writePid();
        global $_has_epoll;
        /** 如果linux支持epoll模型则使用epoll */
        if ($_has_epoll) {
            /** 使用epoll */
            epoll();
        } else {
            /** 使用普通的同步io */
            select();
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

/** 记录pid到文件 */
function writePid(){
    global $_pid_file;
    /** 记录进程号 */
    $fp = fopen($_pid_file, 'a+');
    fwrite($fp, getmypid() . '-');
    fclose($fp);
}

/** 创建子进程 */
function create_process(){
    /** 初始化工作进程数 */
    global $_server_num, $_pid_file;
    /** 至少要开启一个子进程才能开启http服务 */
    if ($_server_num<2) $_server_num = 2;
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

/** 文件上传 */
function base64_file_upload($picture)
{
    if (!file_exists(app_path() . '/public/images/')) {
        mkdir(app_path() . '/public/images/', 0777);
    }
    $image = explode(',', $picture);
    $type  = $image[0];
    //echo $type;
    //echo "\r\n";
    switch ($type) {
        case 'data:application/pdf;base64':
            $type = 'pdf';
            break;
        case 'data:image/png;base64':
            $type = 'png';
            break;
        case 'data:text/plain;base64':
            $type = 'txt';
            break;
        case 'data:application/msword;base64':
            $type = 'doc';
            break;
        case 'data:application/x-zip-compressed;base64':
            $type = 'zip';
            break;
        case 'data:application/octet-stream;base64':
            $type = 'txt';
            break;
        case 'data:application/vnd.openxmlformats-officedocument.presentationml.presentation;base64':
            $type = 'doc';
            break;
        case 'data:application/vnd.ms-powerpoint;base64':
            $type = 'ppt';
            break;
        case 'data:application/vnd.ms-excel;base64':
            $type = 'xls';
            break;
        default:
            $type = 'txt';

    }
    $image    = $image[1];
    $filename = app_path() . '/public/images/' . time() . '_' . uniqid() . '.' . $type;
    $ifp      = fopen($filename, "wb");
    fwrite($ifp, base64_decode($image));
    fclose($ifp);
    return $filename;
}

/**
 * 解析路由和参数
 * @param $request
 * @return array
 */
function getUri($request = '')
{
    $arrayRequest = explode(PHP_EOL, $request);
    $line         = $arrayRequest[0];
    $str          = $line . ' ';
    $url_length   = strlen($str);
    static $fuck = '';
    $array = [];
    for ($i = 0; $i < $url_length; $i++) {
        if (trim($str[$i]) != null) {
            $fuck = $fuck . $str[$i];
        } else {
            $array[] = $fuck;
            $fuck    = '';
        }
    }
    $fuck = '';
    if (isset($array[1])) {
        $url = $array[1];
    } else {
        $url = '/index/query';
    }
    if (isset($array[0])) {
        $method = $array[0];
    } else {
        $method = 'GET';
    }
    unset($arrayRequest[0]);
    foreach ($arrayRequest as $k => $v) {
        if ($v == null || $v == '') {
            unset($arrayRequest[$k]);
        }
    }
    $post_param = [];
    if ($method == 'POST' || $method == 'post') {
        $now   = $arrayRequest;
        $param = array_pop($now);
        if (strpos($param, '&')) {
            $many = explode('&', $param);
            foreach ($many as $a => $b) {
                $dou                 = explode('=', $b);
                $post_param[$dou[0]] = isset($dou[1]) ? $dou[1] : null;
            }
        }
        $length    = 0;
        $fengexian = '';
        foreach ($now as $a => $b) {
            if (stripos($b, 'ength:')) {
                $_vaka  = explode(':', $b);
                $length = (int)$_vaka[1];
            }
            if (stripos($b, 'form-data; name="')) {
                if ($now[$a - 1]) {
                    $fengexian = $now[$a - 1];
                }
                $fenge_array    = array_keys($now, $fengexian, true);
                $value_key_stop = 0;
                foreach ($fenge_array as $m => $n) {
                    if ($n > $a) {
                        $value_key_stop = $n;
                        break;
                    }
                }
                $value     = '';
                $now_count = count($now);
                if ($value_key_stop == 0) {
                    $value_key_stop = $now_count;
                }
                if (strstr($now[$a + 1], 'Type:')) {
                    $small_str = substr($request, stripos($request, $b));
                    $pos1      = stripos($small_str, $now[$a + 3]);
                    $pos2      = stripos($small_str, $now[$value_key_stop]);
                    if ($value_key_stop == $now_count) {
                        if (strstr($now[$a + 1], 'image')) {
                            $value = substr($small_str, $pos1, ($pos2 - $pos1) + strlen($now[$value_key_stop]) + $length);
                        } else {
                            $value = substr($small_str, $pos1, ($pos2 - $pos1) + strlen($now[$value_key_stop]));
                        }
                    } else {
                        $value = substr($small_str, $pos1, ($pos2 - $pos1));
                    }
                } else {
                    $start = $a + 2;
                    for ($ii = $start; $ii < $value_key_stop; $ii++) {
                        $value = $value . $now[$ii];
                    }
                }
                $str1 = substr($b, stripos($b, 'form-data; name="'));
                $arr  = explode('"', $str1);
                $key  = $arr[1];

                $post_param[$key] = $value;
                if (stripos($b, '; filename="')) {
                    $str1                     = substr($b, stripos($b, '; filename="'));
                    $arr                      = explode('"', $str1);
                    $_filename                = $arr[1];
                    $post_param['file'][$key] = ['filename' => $_filename, 'content' => $value];
                    $post_param[$key]         = ['filename' => $_filename, 'content' => $value];
                }
            }
        }
    }

    $arrayRequest[] = "method: " . $method;
    $arrayRequest[] = "path: /" . $url;
    $header         = [];
    foreach ($arrayRequest as $k => $v) {
        $v = trim($v);
        if ($v) {
            $_pos  = strripos($v, ": ");
            $key   = trim(substr($v, 0, $_pos));
            $value = trim(substr($v, $_pos + 1, strlen($v)));
            if ($key) {
                $header[$key] = $value;
            }
        }
    }

    return ['file' => $url, 'request' => $arrayRequest, 'post_param' => $post_param, 'header' => $header];
}

/**
 * 处理自定义的命令
 * @return void
 */
function deal_command(){
    global $_system_command;
    /** 加载所有自定义的命令 */
    foreach (traverse(command_path()) as $key => $file) {
        if (file_exists($file)) {
            require_once $file;
            $php_code = file_get_contents($file);
            $classes = get_php_classes($php_code);
            foreach ($classes as $class){
                /** @var string $_class_name 拼接完整的路径 */
                $_class_name='App\Command\\'.$class;
                /** @var object $object 通过反射获取这个类 */
                $object=new ReflectionClass(new $_class_name());
                /** 如果这个类有command属性，并且有handle方法，则将这个方法和类名注册到全局命令行中 */
                if ($object->hasMethod('handle')&&$object->hasProperty('command')){
                    foreach ($object->getDefaultProperties() as $property => $command){
                        if ($property=='command'){
                            $_system_command[$command]=$_class_name;
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
function rabbitmqConsume(){
    $enable=config('rabbitmq')['enable'];
    if ($enable){
        $config=config('rabbitmqProcess');
        foreach ($config as $name=>$value){
            if (isset($value['handler'])){
                /** 创建一个子进程，在子进程里面执行消费 */
                $rabbitmq_pid=\pcntl_fork();
                if ($rabbitmq_pid>0) {
                    /** 记录进程号 */
                    writePid();
                    cli_set_process_title($name);
                    if (class_exists($value['handler'])) {
                        $className=$value['handler'];
                        $queue = new $className();
                        $queue->consume();
                    }
                }
            }
        }
    }
}


function prepareMysqlAndRedis(){
    /** 使用匿名函数提前连接数据库 */
    (function(){
        try {
            $startMysql=config('database')['mysql']['preStart']??false;
            if ($startMysql){
                new BaseModel();
            }
            $startRedis=config('redis')['preStart']??false;
            if ($startRedis){
                new \Root\Cache();
            }
        }catch (RuntimeException $exception){
            echo "\r\n";
            echo $exception->getMessage();
            echo "\r\n";
        }
    })();
}


