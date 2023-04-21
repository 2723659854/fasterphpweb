<?php
function app_path()
{
    return dirname(__DIR__);
}

function config($path_name)
{
    return include app_path() . '/config/' . $path_name . '.php';
}

function public_path()
{
    return app_path() . '/public';
}


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

function start_server($param)
{
    check_env();
    $daemonize = false;
    $flag      = true;
    global $_pid_file, $_port, $_listen, $_server_num, $_system, $_lock_file, $_has_epoll;
    $_pid_file  = __DIR__ . '/my_pid.txt';
    $_lock_file = __DIR__ . '/lock.txt';
    require_once __DIR__ . '/Timer.php';
    require_once __DIR__ . '/view.php';
    require_once __DIR__ . '/Request.php';
    require_once __DIR__ . '/BaseModel.php';
    require_once __DIR__ . '/Cache.php';
    require_once __DIR__ . '/queue/Queue.php';
    require_once __DIR__ . '/Facade.php';
    require_once __DIR__ . '/Worker.php';

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
    foreach (traverse(app_path() . '/app') as $key => $val) {
        if (file_exists($val)) {
            require_once $val;
        }
    }
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
                echo "未识别的命令\r\n";
                $flag = false;
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
    root\Timer::run();
    while (true) {
        pcntl_signal_dispatch();
        sleep(10);
    }

}

/** 执行队列 */
function xiaosongshu_queue()
{

    _queue_xiaosongshu();
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

/** 普通的阻塞模式 */
function nginx()
{
    echo "启用普通同步阻塞IO模式\r\n";
    require_once __DIR__ . '/explain.php';
    $worker = new root\HttpServer();
    $worker->run();
}

/** 异步IO之select轮询模式 */
function select_copy()
{
    echo "启动select异步IO模型\r\n";
    require_once __DIR__ . '/Worker.php';
    $httpServer = new \Root\Worker();
    /** 消息接收  */
    $httpServer->onMessage = function ($socketAccept, $message) use ($httpServer) {
        if (strpos($message, 'HTTP/1.1')) {
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
                    fclose($socketAccept);
                    unset($httpServer->allSocket[(int)$socketAccept]);
                    unset($socketAccept);
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
                    fclose($socketAccept);
                    unset($httpServer->allSocket[(int)$socketAccept]);
                    unset($socketAccept);
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

                    fwrite($socketAccept, $fileContent, strlen($fileContent));
                    fclose($socketAccept);
                    unset($httpServer->allSocket[(int)$socketAccept]);
                    unset($socketAccept);
                    break;
                default:

                    fwrite($socketAccept, 'HTTP/1.1 200 OK' . PHP_EOL);
                    fwrite($socketAccept, 'Date:' . date('Y-m-d H:i:s') . PHP_EOL);
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


                    /** 这里必须关闭才能够给cli模式正常的返回数据，但是这个会影响需要长连接的浏览器或者其他服务，还不知道怎么处理 */
                    fclose($socketAccept);
                    unset($httpServer->allSocket[(int)$socketAccept]);
                    unset($socketAccept);
            }
        } else {
            //事件回调当中写业务逻辑
            $content      = $message;
            $http_resonse = "HTTP/1.1 200 OK\r\n";
            $http_resonse .= "Content-Type: application/json;charset=UTF-8\r\n";
            $http_resonse .= "Connection: keep-alive\r\n"; //连接保持
            $http_resonse .= "Server: php socket server\r\n";
            $http_resonse .= "Content-length: " . strlen($content) . "\r\n\r\n";
            $http_resonse .= $content;
            fwrite($socketAccept, $http_resonse);
        }

    };
    $httpServer->start();
}

function select()
{
    echo "启动select异步IO模型\r\n";
    require_once __DIR__ . '/Worker.php';
    $httpServer = new \Root\Worker();
    /** 消息接收  */
    $httpServer->onMessage = function ($socketAccept, $message) use ($httpServer) {
        onMessage($socketAccept, $message, $httpServer);
    };
    $httpServer->start();
}


function onMessage($socketAccept, $message, &$httpServer)
{
    if (strpos($message, 'HTTP/1.1')) {
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
            case "ico":
            case "jpg":
            case "js":
            case "css":
            case "gif":
            case "png":
            case "icon":
            case "jpeg":
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
    echo "启动epoll异步IO模型\r\n";
    /** 加载epoll模型类 */
    require_once __DIR__ . '/Fucker.php';
    /** @var object $httpServer 将对象加载到内存 */
    $httpServer            = new Fucker();
    /** @var callable onMessage 设置消息处理函数 */
    $httpServer->onMessage = function ($socketAccept, $message) use ($httpServer) {
        onMessage($socketAccept, $message, $httpServer);
    };
    /** 启动服务 */
    $httpServer->run();
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
    /** 初始化工作进程数 */
    global $_server_num;
    if ($_server_num < 2) {
        $_server_num = 2;
    }
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
            pcntl_fork();
            /** 将创建好的进程id存入文件 */
            $fp = fopen($_pid_file, 'a+');
            fwrite($fp, getmypid() . '-');
            fclose($fp);
        }
    }
    /** @var int $_this_pid  获取当前进程id */
    $_this_pid = getmypid();
    /** 如果是主进程 */
    if ($_this_pid == $master_pid) {
        /** 在主进程里再创建一个子进程 */
        pcntl_fork();
        if (getmypid() == $master_pid) {
            /** 如果是主进程，则设置进程名称为master */
            cli_set_process_title("xiaosongshu_master");
            /** 在主进程里启动定时器 */
            xiaosongshu_timer();
        } else {
            /** 在子进程里启动队列，并设置进程名称 */
            cli_set_process_title("xiaosongshu_queue");
            $fp = fopen($_pid_file, 'a+');
            fwrite($fp, getmypid() . '-');
            fclose($fp);
            xiaosongshu_queue();
        }
    } else {
        /** 如果不是主进程，则开启http服务，并设置进程名称 */
        cli_set_process_title("xiaosongshu_http");
        global $_has_epoll;
        /** 如果linux支持epoll模型则使用epoll */
        if ($_has_epoll) {
            epoll();
        } else {
            /** 否则使用select */
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
        $url = '/';
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

