<?php
use Root\Response;

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
        return dirname(__DIR__) . '/app/Command';
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
     * @note 这个方法需要根据自己需求补全各种文件类型
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
            case 'data:image/jpeg;base64':
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




if (!function_exists('response')){
    /**
     * Response 响应
     * @param mixed $body
     * @param int $status
     * @param array $headers
     * @return Response
     */
    function response( mixed $body = '', int $status = 200, array $headers = []): Response
    {
        if (!is_string($body)) $body = json_encode($body);
        return new Response($status, $headers, $body);
    }
}

if (!function_exists('redirect')){
    /**
     * 重定向
     * @param string $location 跳转地址
     * @param int $status 状态码
     * @param array $headers 头部信息
     * @return Response
     */
    function redirect(string $location, int $status = 302, array $headers = [])
    {
        $response = new Response($status, ['Location' => $location]);
        if (!empty($headers)) {
            $response->withHeaders($headers);
        }
        return $response;
    }
}

if(!function_exists('dump_error')){
    /**
     * 记录日志
     * @param Exception|RuntimeException $exception
     * @return void
     */
    function dump_error(Exception|RuntimeException $exception){
        global $_daemonize,$_color_class;
        $string = "[error] ";
        $string .="code: ".$exception->getCode().". ";
        $string .="file: ".$exception->getFile().". ";
        $string .="line: ".$exception->getLine().". ";
        $string .="message: ".$exception->getMessage().". \r\n";
        /** 调试模式打印错误 */
        if (!$_daemonize){
            echo $_color_class->info($string);
        }
        /** 写入到日志文件 */
        if (!is_dir(app_path().'/runtime/log/')){
            @mkdir(app_path().'/runtime/log/',0777);
        }
        $fp = fopen(app_path().'/runtime/log/'.date('Y-m-d',time()).'.log','a+');
        fwrite($fp,$string);
        fclose($fp);
    }
}

if (!function_exists('yaml_load')) {
    /**
     * 加载YAML配置文件
     * @param string $path 待加载的路径
     * @param bool $findLocal 是否额外寻找结尾为.local的本地配置文件
     * @return array
     */
    function yaml_load(string $path, bool $findLocal = true): array
    {
        $files = [$path];
        if ($findLocal) {
            $files[] = $path . '.local';
        }

        $yaml = [];
        foreach ($files as $file) {
            if (!($file = realpath($file))) {
                continue;
            }
            $yaml[] = \Symfony\Component\Yaml\Yaml::parseFile($file);
        }

        return array_deep_merge(...$yaml);
    }
}

if (!function_exists('yaml')) {
    /**
     * 从YAML配置文件中读取数据
     * @param string|null $key 配置键名，传null则返回整个配置文件的数据
     * @param mixed|null $default 读取失败时的默认值
     * @param string|null $path 配置文件路径（默认为根目录下config.yaml）
     * @return mixed 配置值
     */
    function yaml(?string $key = null, mixed $default = null, ?string $path = null): mixed
    {
        $path = $path ?? app_path() . DIRECTORY_SEPARATOR . 'config.yaml';
        $yaml = yaml_load($path);
        if (is_null($key)) {
            return $yaml;
        }

        $data = $yaml;
        $keys = explode('.', $key);
        foreach ($keys as $k) {
            if (!is_array($data)) {
                $data = $default;
                break;
            }
            $data = $data[$k] ?? $default;
        }
        return $data;
    }
}

if (!function_exists('array2yaml')) {
    /**
     * 将数组配置转换为YAML
     * @param array $data 配置项列表
     * @param string|null $path 待写入的配置文件路径，传null则不写入文件，直接返回YAML字符串
     * @return false|int|string
     */
    function array2yaml(array $data, ?string $path = null): false|int|string
    {
        $yaml = \Symfony\Component\Yaml\Yaml::dump($data);
        if ($path) {
            return file_put_contents(app_path() . $path, $yaml, LOCK_EX);
        }
        return $yaml;
    }
}

if (!function_exists('array_deep_merge')) {
    /**
     * 深度合并多个数组的内容，传入的非数组会被忽略
     * @param mixed ...$args
     * @return mixed
     */
    function array_deep_merge(mixed ...$args): array
    {
        $args = array_filter($args, fn($item) => is_array($item));
        if (count($args) === 0) return [];
        if (count($args) === 1) return $args[0];

        return array_reduce($args, function (array $prev, array $current) {
            //2个列表直接拼接合并
            if (array_is_list($prev) && array_is_list($current)) {
                return array_merge($prev, $current);
            }

            foreach ($current as $key => $value) {
                //非同时为数组时，后面的覆盖前面的
                if (!is_array($value) || !isset($prev[$key]) || !is_array($prev[$key])) {
                    $prev[$key] = $value;
                    continue;
                }

                $prev[$key] = array_deep_merge($prev[$key], $value);
            }

            return $prev;
        }, []);
    }
}





