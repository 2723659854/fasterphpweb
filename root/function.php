<?php

if (!function_exists('app_path')){
    /**
     * App目录
     * @return string
     */
    function app_path()
    {
        return dirname(__DIR__);
    }
}

if (!function_exists('config')){
    /**
     * 获取配置文件
     * @param $path_name
     * @return mixed
     */
    function config($path_name)
    {
        return include app_path() . '/config/' . $path_name . '.php';
    }
}

if (!function_exists('public_path')){
    /**
     * public目录
     * @return string
     */
    function public_path()
    {
        return app_path() . '/public';
    }
}

if (!function_exists('command_path')){
    /**
     * 命令行目录
     * @return string
     */
    function command_path()
    {
        return dirname(__DIR__) . '/app/command';
    }
}

if(!function_exists('timer_log_path')){
    /**
     * 定时器pid目录
     * @return string
     */
    function timer_log_path()
    {
        return runtime_path().'/timer';
    }
}

if (!function_exists('runtime_path')){
    /**
     * 运行目录
     * @return string
     */
    function runtime_path( ){
        return app_path().'/runtime';
    }
}



if (!function_exists('scan_dir')){
    /**
     * 扫描目录的文件
     * @param string $path 目录
     * @param bool $force 首次调用：true返回本目录的文件，false返回包含上一次的数据
     * @return array
     */
    function scan_dir(string $path = '.',bool $force=false)
    {
        /** 这里必须使用静态变量 ，否则递归调用的时候不能正确保存数据 */
        static $filePath    = [];
        if ($force){
            $filePath =[];
        }
        $current_dir = opendir($path);
        while (($file = readdir($current_dir)) !== false) {
            $sub_dir = $path . DIRECTORY_SEPARATOR . $file;
            if ($file == '.' || $file == '..') {
                continue;
            } else if (is_dir($sub_dir)) {
                scan_dir($sub_dir);
            } else {
                $filePath[$path . '/' . $file] = $path . '/' . $file;
            }
        }
        return $filePath;
    }
}

if (!function_exists('get_php_classes')){
    /**
     * 解析文件中定义的类名
     * @param string $php_code 文件代码
     * @return array
     */
    function get_php_classes(string $php_code)
    {
        $classes = array();
        $tokens  = token_get_all($php_code);
        $count   = count($tokens);
        for ($i = 2; $i < $count; $i++) {
            if ($tokens[$i - 2][0] == T_CLASS
                && $tokens[$i - 1][0] == T_WHITESPACE
                && $tokens[$i][0] == T_STRING) {

                $class_name = $tokens[$i][1];
                $classes[]  = $class_name;
            }
        }
        return $classes;
    }
}

if (!function_exists('writePid')){
    /**
     * 记录pid到文件
     * @return void
     */
    function writePid()
    {
        global $_pid_file;
        /** 记录进程号 */
        $fp = fopen($_pid_file, 'a+');
        fwrite($fp, getmypid() . '-');
        fclose($fp);
    }
}

if (!function_exists('writeTimerPid')){
    /**
     * 记录定时器的pid
     * @return void
     */
    function writeTimerPid()
    {
        /** 记录进程号 */
       $myPid = getmypid();
       file_put_contents(runtime_path() .'/timer/'. $myPid . '.txt', $myPid);
    }
}

if (!function_exists('base64_file_upload')){
    /**
     * base64文件上传
     * @param string $picture 文件内容
     */
    function base64_file_upload(string $picture)
    {
        if (!file_exists(app_path() . '/public/images/')) {
            mkdir(app_path() . '/public/images/', 0777);
        }
        $image = explode(',', $picture);
        $type  = $image[0];
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
}

if (!function_exists('getUri')){
    /**
     * 解析路由和参数
     * @param string $request
     * @return array
     */
    function getUri(string $request = '')
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
}

if (!function_exists('prepareMysqlAndRedis')){
    /**
     * 提前加载MySQL和redis
     * @return void
     */
    function prepareMysqlAndRedis()
    {
        /** 使用匿名函数提前连接数据库 */
        (function () {
            try {
                $startMysql = config('database')['mysql']['preStart'] ?? false;
                if ($startMysql) {
                    new \Root\BaseModel();
                }
                $startRedis = config('redis')['preStart'] ?? false;
                if ($startRedis) {
                    new \Root\Cache();
                }
            } catch (\RuntimeException $exception) {
                echo "\r\n";
                echo $exception->getMessage();
                echo "\r\n";
            }
        })();
    }
}


if (!function_exists('download_file')){
    /**
     * 下载文件到浏览器
     * @param string $path 文件路径
     * @param string $name 文件名称
     * @return array
     */
    function download_file(string $path, string $name = '')
    {
        if (!is_file($path)) {
            throw new RuntimeException("[" . $path . "] 不是可用的文件 ！");
        }
        if (!file_exists($path)) {
            throw new RuntimeException("[" . $path . "] 不是可用的文件 ！");
        }
        if (!is_readable($path)) {
            throw new RuntimeException("[" . $path . "] 不可读 ！");
        }
        $file    = $path;
        $fd      = fopen($file, 'r');
        $content = fread($fd, filesize($file));
        fclose($fd);
        if (!trim($name)) $name = basename($path);
        return ['content' => $content, 'type' => md5('_byte_for_down_load_file_'), 'name' => $name];
    }
}

if (!function_exists('G')){
    /**
     * 通过容器获取一个对象
     * @param string $name
     * @return object
     * @note 不会重复new对象，且一直保存在内存中，不会销毁
     */
    function G(string $name){
        return \Root\Container::get($name);
    }
}

if (!function_exists('M')){
    /**
     * 通过容器获取一个对象
     * @param string $name
     * @return mixed
     * @note 每一次返回一个新的对象，用完自动销毁
     */
    function M(string $name){
        return \Root\Container::make($name);
    }
}

if (!function_exists('view')){
    /**
     * 渲染模板 使用{}中括号作为变脸分隔符号
     * @param string $path 路径
     * @param array $param 参数
     * @return array|false|string|string[]
     * @throws Exception
     * @note 模板渲染这个不完善，需要处理循环，布尔判断等等，不过这个主要是做后端服务，前端的事情就交给前端去做吧，
     */
    function view(string $path,array $param=[]){
        $path = trim($path,'/');/** 去掉多余的目录分隔符 */
        $content=file_get_contents(app_path().'/view/'.$path.'.html');
        $preg= '/{\$[\s\S]*?}/i';
        preg_match_all($preg,$content,$res);
        $array=$res['0'];
        $new_param=[];
        foreach ($param as $k=>$v){
            $key='{$'.$k.'}';
            $new_param[$key]=$v;
        }
        foreach ($array as $v){
            if (array_key_exists($v,$new_param)){
                if ($new_param[$v]==null){
                    $new_param[$v]='';
                }
                $content=str_replace($v,$new_param[$v],$content);
            }else{
                return no_declear('index',['msg'=>"文件：/view/$path.html，存在未定义的变量：".$v]);
            }
        }
        return response($content,200,['Content-Type'=>'text/html; charset=UTF-8']);
    }
}
if (!function_exists('no_declear')){
    /**
     * 未定义变量
     * @param $path
     * @param $param
     * @return array|false|string|string[]
     * @throws Exception
     */
    function no_declear($path,$param){
        $content=file_get_contents(app_path().'/root/error/'.$path.'.html');
        if ($param){
            $preg= '/{\$[\s\S]*?}/i';
            preg_match_all($preg,$content,$res);
            $array=$res['0'];
            $new_param=[];
            foreach ($param as $k=>$v){
                $key='{$'.$k.'}';
                $new_param[$key]=$v;
            }
            foreach ($array as $k=>$v){
                if (isset($new_param[$v])){
                    $content=str_replace($v,$new_param[$v],$content);
                }else{
                    throw new Exception("未定义的变量".$v);
                }
            }
        }
        return response($content,200,['Content-Type'=>'text/html; charset=UTF-8']);
    }
}

if (!function_exists('route')){
    /**
     * 解析路由
     * @param string $url 路由地址
     * @return string
     */
    function route(string $url){
        if ($url){
            $url=array_filter(explode('/',$url));
        }else{
            $url=[];
        }
        $new_url=[];
        foreach ($url as $k=>$v){
            $new_url[]=$v;
        }
        $num=count($new_url);
        switch ($num){
            case 0:
                return '/app/controller/index/Index.php@APP\\Controller\\Index\\Index@index';
                break;
            case 1:
                return '/app/controller/index/Index.php@App\\Controller\\Index\\Index@'.$new_url[0];
                break;
            case 2:
                return '/app/controller/index/'.ucwords($new_url[0]).'.php@'.'App\\Controller\\Index\\'.ucwords($new_url[0]).'@'.$new_url[1];
                break;
            case 3:
                return '/app/controller/'.strtolower($new_url[0]).'/'.ucwords($new_url[1]).'.php@'.'App\\Controller\\'.ucwords($new_url[0]).'\\'.ucwords($new_url[1]).'@'.$new_url[2];
                break;
            default:
                $file = '/app/controller';
                $class = 'App\\Controller';
                $method = array_pop($new_url);
                $className = ucwords(strtolower(array_pop($new_url)));
                foreach ($new_url as $k=>$v){
                    $file=$file.'/'.strtolower($v);
                    $class=$class.'\\'.ucwords($v);
                }
                return $file.'/'.$className.'.php@'.$class.'\\'.$className.'@'.$method;
        }
    }
}









