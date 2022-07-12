<?php

namespace root;
set_time_limit(0);
require_once __DIR__ . '/route.php';
require_once __DIR__ . '/app.php';
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

class HttpServer
{
    private $ip = '0.0.0.0';
    private $port = 8020;
    private $_socket = null;

    public function __construct()
    {
        $this->_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        @\socket_set_option($this->_socket, SOL_SOCKET, SO_REUSEADDR, 1);
        @\socket_set_option($this->_socket, SOL_SOCKET, SO_REUSEPORT, 1);
        if ($this->_socket === false) {
            die(socket_strerror(socket_last_error($this->_socket)));
        }
        global $_port;
        $this->port = $_port;
    }

    public function run()
    {
        @\socket_bind($this->_socket, $this->ip, $this->port);
        @\socket_listen($this->_socket, 5);
        while (true) {
            $socketAccept = @\socket_accept($this->_socket);

            $request='';
            $flag=true;
            while($flag){
                $_content = socket_read($socketAccept, 1024);
                if (strlen($_content)<1024){
                    $flag=false;
                }
                $request=$request.$_content;
            }
            //var_dump($request);
            if (stripos($request,'filename=')){
                $first_str=substr($request,stripos($request,'filename='));

                $second_str=substr($first_str,stripos($first_str,'Content-Type:')+26);
                //去掉结束符号
                $end_pos=stripos($second_str,'------WebKitFormBoundary');
                $end_str=substr($second_str,$end_pos);
                //var_dump($end_str);
                $s=str_replace($end_str,'',$second_str);
                file_put_contents(app_path().'/public/'.time().'_'.uniqid().'.png',$s);
                $ks="";
                for($i=0;$i< strlen($s);$i++)
                {
                    if(ord($s[$i])==32) $ks.= chr(0);
                    else $ks .= $s[$i];
                }
                $fp = @fopen(app_path().'/public/say.png', 'w');
                fwrite($fp, $ks);
                fclose($fp);
                //file_put_contents(app_path().'/public/say.png',$ks);

            }

            $_param = [];
            socket_write($socketAccept, 'HTTP/1.1 200 OK' . PHP_EOL, 1024);
            socket_write($socketAccept, 'Date:' . date('Y-m-d H:i:s') . PHP_EOL, 1024);
            $_mark = $this->getUri($request);
            //var_dump($_mark);

            $fileName = $_mark['file'];
            $_request = $_mark['request'];
            foreach ($_mark['post_param'] as $k => $v) {
                $_param[$k] = $v;
            }
            $url = $fileName;
            $fileExt = preg_replace('/^.*\.(\w+)$/', '$1', $fileName);
            switch ($fileExt) {
                case "html":
                    socket_write($socketAccept, 'Content-Type: text/html' . PHP_EOL);
                    socket_write($socketAccept, '' . PHP_EOL);
                    $fileName = dirname(__DIR__) . '/view/' . $fileName;
                    if (file_exists($fileName)) {
                        $fileContent = file_get_contents($fileName);
                    } else {
                        $fileContent = 'sorry,the file is missing!';
                    }
                    socket_write($socketAccept, $fileContent, strlen($fileContent));
                    break;
                case "jpg":
                case "js":
                case "css":
                case "gif":
                case "png":
                case "icon":
                case "jpeg":
                case "ico":
                    socket_write($socketAccept, 'Content-Type: image/jpeg' . PHP_EOL);
                    socket_write($socketAccept, '' . PHP_EOL);
                    $fileName = dirname(__DIR__) . '/public/' . $fileName;
                    if (file_exists($fileName)) {
                        $fileContent = file_get_contents($fileName);
                    } else {
                        $fileContent = 'sorry,the file is missing!';
                    }

                    socket_write($socketAccept, $fileContent, strlen($fileContent));
                    break;
                default:
                    if (($url) && strpos($url, '?')) {
                        $request_url = explode('?', $url);
                        $route = $request_url[0];
                        $params = explode('&', $request_url[1]);
                        foreach ($params as $k => $v) {
                            $_v = explode('=', $v);
                            $_param[$_v[0]] = $_v['1'];
                        }
                        $content = handle(route($route), $_param, $_request);
                    } else {
                        $content = handle(route($url), $_param, $_request);
                    }
                    socket_write($socketAccept, 'Content-Type: text/html' . PHP_EOL, 1024);
                    socket_write($socketAccept, '' . PHP_EOL, 1024);
                    if ($content) {
                        $content = is_string($content) ? $content : json_encode($content);
                        $write_length = strlen($content);
                        if ($write_length < 1024) {
                            $write_length = 1024;
                        }
                    } else {
                        $write_length = 1024;
                        $content = '';
                    }
                    socket_write($socketAccept, $content, $write_length);
            }
            socket_close($socketAccept);

        }

    }


    protected function getUri($request = '')
    {
        $arrayRequest = explode(PHP_EOL, $request);
        //var_dump($arrayRequest);
        $line = $arrayRequest[0];
        $str = $line . ' ';
        $length = strlen($str);
        static $fuck = '';
        $array = [];
        for ($i = 0; $i < $length; $i++) {
            if (trim($str[$i])) {
                $fuck = $fuck . $str[$i];
            } else {
                $array[] = $fuck;
                $fuck = '';
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
            $now = $arrayRequest;
            $param = array_pop($now);
            if (strpos($param, '&')) {
                $many = explode('&', $param);
                foreach ($many as $a => $b) {
                    $dou = explode('=', $b);
                    $post_param[$dou[0]] = isset($dou[1]) ? $dou[1] : null;
                }
            }
            //var_dump($now);
            foreach ($now as $a => $b) {
                if (stripos($b, 'form-data; name="')) {
                    //获取分隔符
                    $fengexian=$now[$a-1];//找到两个分割线，分割线之间的除了空格就是值
                    //获取所有的分割线下标
                    $fenge_array=array_keys($now,$fengexian,true);
                    $fenge_end=array_keys($now,$fengexian.'--',true);
                    $value_key_stop=0;
                    foreach ($fenge_array as $m=>$n){
                        if ($n>$a){
                            $value_key_stop=$n;
                            break;
                        }
                    }

                    $value='';
                    $now_count=count($now);
                    if (!empty($fenge_end)){
                        $end=$fenge_end[0];
                    }else{
                        $end=$now_count;
                    }
                    if ($value_key_stop==0){

                        $value_key_stop=$now_count;
                    }
                    //todo 文件处理有错误,生成的图片丢失了宽高等属性，无法法制
                    if (strstr($now[$a+1],'image')){
                        $start=$a+3;
                        $computer=1;
                        if ($value_key_stop>$end){
                            $value_key_stop=$end;
                        }
                        for($ii=$start;$ii<=$value_key_stop;$ii++){
                            $value=$value.$now[$ii];
                            if ($computer==2){
                                $value=$value."\r";
                            }
                            $computer++;
                        }
                    }else{
                        $start=$a+2;
                        for($ii=$start;$ii<$value_key_stop;$ii++){
                            $value=$value.$now[$ii];
                        }
                    }

                    /*$start=$a+2;
                    for($ii=$start;$ii<$value_key_stop;$ii++){
                        $value=$value.$now[$ii];
                    }*/

                    $str1 = substr($b, stripos($b, 'form-data; name="'));
                    $arr = explode('"', $str1);
                    $key = $arr[1];

                    $post_param[$key] = $value;
                    if (stripos($b, '; filename="')) {
                        $str1 = substr($b, stripos($b, '; filename="'));
                        $arr = explode('"', $str1);
                        $_filename = $arr[1];
                        //$_filecontent = isset($now[$a + 3]) ? $now[$a + 3] : null;
                        $post_param['file'] = ['filename' => $_filename, 'content' => $value];
                        $post_param[$key] = ['filename' => $_filename, 'content' => $value];
                    }
                }
            }
        }

        $arrayRequest[] = "method: " . $method;
        $arrayRequest[] = "path: /" . $url;
        return ['file' => $url, 'request' => $arrayRequest, 'post_param' => $post_param];
    }

    public function close()
    {
        socket_close($this->_socket);
    }


}

