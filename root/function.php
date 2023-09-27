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

if (!function_exists('base64_file_upload')){
    /**
     * base64文件上传
     * @param string $picture 文件内容:base64加密后的文件
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
                    new \Root\Lib\BaseModel();
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

if (!function_exists('G')){
    /**
     * 通过容器获取一个对象
     * @param string $name
     * @return object
     * @note 不会重复new对象，且一直保存在内存中，不会销毁
     */
    function G(string $name){
        return \Root\Lib\Container::get($name);
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
        return \Root\Lib\Container::make($name);
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

if (!function_exists('sortFiles')){
    /**
     * 按文件深度倒序排列文件
     * @param array $files
     * @return array
     */
    function sortFiles(array $files):array{
        $_files = [];
        foreach ($files as $v){
            $length = count(explode('/',$v));
            $_files[$length][]=$v;
        }
        ksort($_files);
        $_files = array_reverse($_files);
        $backFiles=[];
        foreach ($_files as $value){
            $backFiles = array_merge($backFiles,$value);
        }
        return $backFiles;
    }
}










