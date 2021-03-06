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
    $flag = true;
    global $pid_file, $_port, $_listen, $_server_num, $_system, $lock_file;
    $pid_file = __DIR__ . '/my_pid.txt';
    $lock_file = __DIR__ . '/lock.txt';
    require_once __DIR__ . '/Timer.php';
    require_once __DIR__ . '/view.php';
    require_once __DIR__ . '/Request.php';
    require_once __DIR__ . '/BaseModel.php';
    require_once __DIR__ . '/Cache.php';
    require_once __DIR__ . '/queue/Queue.php';
    require_once __DIR__ . '/Facade.php';
    $_system = true;
    if (\DIRECTORY_SEPARATOR === '\\') {
        $_system = false;
    }
    $httpServer = null;
    $server = include dirname(__DIR__) . '/config/server.php';
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
    $_listen = "http://127.0.0.1:" . $_port;
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
                        echo "???????????????windows,????????????????????????\r\n";
                    }
                }
                echo "???????????????...\r\n";
                break;
            case "stop":
                if ($_system) {
                    close();
                    echo "???????????????\r\n";
                } else {
                    echo "???????????????windows,????????????????????????\r\n";
                }
                $flag = false;
                break;
            case "restart":
                if ($_system) {
                    close();
                    $daemonize = true;
                    echo "???????????????...\r\n";
                } else {
                    echo "???????????????windows,????????????????????????\r\n";
                }
                break;
            case "queue":
                echo "????????????,????????????CTRL+C??????\r\n";
                \cli_set_process_title("xiaosongshu_queue");
                xiaosongshu_queue();
                break;
            default:
                echo "??????????????????\r\n";
                $flag = false;
        }
    } else {
        echo "????????????????????????????????????start,start -d,stop,restart,queue\r\n";
        $flag = false;
    }
    if ($flag == false) {
        exit("??????????????????\r\n");
    }
    $fd = fopen($lock_file, 'w');
    $res = flock($fd, LOCK_EX | LOCK_NB);
    if (!$res) {
        echo $_listen . "\r\n";
        echo "???????????????????????????????????????????????????????????????stop????????????????????????restart??????\r\n";
        exit(0);
    }


    if ($daemonize) {
        daemon();
    } else {
        echo $_listen . "\r\n";
        echo "??????????????????,????????????ctrl+c????????????\r\n";

        nginx();
    }

}


function _queue_xiaosongshu()
{
    try {
        $config = config('redis');
        $host = isset($config['host']) ? $config['host'] : '127.0.0.1';
        $port = isset($config['port']) ? $config['port'] : '6379';
        $client = new Redis();
        $client->connect($host, $port);
        while (true) {
            $job = json_decode($client->RPOP('xiaosongshu_queue'), true);
            deal_job($job);
            $res = $client->zRangeByScore('xiaosongshu_delay_queue', 0, time(), ['limit' => 1]);
            if ($res) {
                $value = $res[0];
                $res1 = $client->zRem('xiaosongshu_delay_queue', $value);
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
        echo "redis????????????";
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
            echo $job['class'] . '???????????????????????????????????????';
            echo "\r\n";
        }
    }
}

function xiaosongshu_timer()
{
    require_once __DIR__ . '/Timer.php';
    $timer_config = include dirname(__DIR__) . '/config/timer.php';
    if (!empty($timer_config)) {
        foreach ($timer_config as $k => $v) {
            $className = $v['handle'];
            $time = $v['time'];
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

function xiaosongshu_queue()
{

    _queue_xiaosongshu();
}

function close()
{
    echo "???????????????...\r\n";
    global $pid_file;
    if (file_exists($pid_file)) {
        $master_ids = file_get_contents($pid_file);
        $master_id = explode('-', $master_ids);
        foreach ($master_id as $k => $v) {
            if ($v > 0) {
                \posix_kill($v, SIGKILL);
            }
        }
        file_put_contents($pid_file, null);
        sleep(1);
    }
}

function check_env()
{
    if (!extension_loaded('sockets')) {
        exit("????????????sockets?????????????????????php.ini???sockets??????");
    }
}

function nginx()
{
    require_once __DIR__ . '/explain.php';
    $httpServer = new root\HttpServer();
    $httpServer->run();
}


function daemon()
{
    ini_set('display_errors', 'off');
    \umask(0);
    $pid = \pcntl_fork();
    if (-1 === $pid) {
        throw new Exception('Fork fail');
    } elseif ($pid > 0) {
        global $_listen;
        echo $_listen . "\r\n";
        echo "??????????????????,???????????????php start.php stop????????????\r\n";
        exit(0);
    }

    global $pid_file;
    file_put_contents($pid_file, '');

    $master_pid = getmypid();
    if (-1 === \posix_setsid()) {
        throw new Exception("Setsid fail");
    }
    global $_server_num;
    if ($_server_num < 2) {
        $_server_num = 2;
    }
    for ($i = 0; $i <= $_server_num; $i++) {
        $read_log_content = file_get_contents($pid_file);
        $father = explode('-', $read_log_content);
        $mother = [];
        foreach ($father as $k => $v) {
            if (!array_search($v, $mother)) {
                $mother[] = $v;
            }
        }
        $worker_num = count($mother);
        if ($worker_num > $_server_num) {
            break;
        } else {
            pcntl_fork();
            $fp = fopen($pid_file, 'a+');
            fwrite($fp, getmypid() . '-');
            fclose($fp);
        }
    }
    $_this_pid = getmypid();
    if ($_this_pid == $master_pid) {
        pcntl_fork();
        if (getmypid() == $master_pid) {
            cli_set_process_title("xiaosongshu_master");
            xiaosongshu_timer();
        } else {
            cli_set_process_title("xiaosongshu_queue");
            $fp = fopen($pid_file, 'a+');
            fwrite($fp, getmypid() . '-');
            fclose($fp);
            xiaosongshu_queue();
        }
    } else {
        cli_set_process_title("xiaosongshu_http");
        nginx();
    }
    $pid = \pcntl_fork();
    if (-1 === $pid) {
        throw new Exception("Fork fail");
    } elseif (0 !== $pid) {
        exit(0);
    }
}


function base64_file_upload($picture){
    if (!file_exists(app_path().'/public/images/')){
        mkdir(app_path().'/public/images/',0777);
    }
    $image = explode(',', $picture);
    $type=$image[0];
    //echo $type;
    //echo "\r\n";
    switch ($type){
        case 'data:application/pdf;base64':
            $type='pdf';
            break;
        case 'data:image/png;base64':
            $type='png';
            break;
        case 'data:text/plain;base64':
            $type='txt';
            break;
        case 'data:application/msword;base64':
            $type='doc';
            break;
        case 'data:application/x-zip-compressed;base64':
            $type='zip';
            break;
        case 'data:application/octet-stream;base64':
            $type='txt';
            break;
        case 'data:application/vnd.openxmlformats-officedocument.presentationml.presentation;base64':
            $type='doc';
            break;
        case 'data:application/vnd.ms-powerpoint;base64':
            $type='ppt';
            break;
        case 'data:application/vnd.ms-excel;base64':
            $type='xls';
            break;
        default:
            $type='txt';

    }
    $image = $image[1];
    $filename=app_path().'/public/images/'.time().'_'.uniqid().'.'.$type;
    $ifp = fopen( $filename, "wb" );
    fwrite( $ifp, base64_decode( $image) );
    fclose( $ifp );
    return $filename;
}

