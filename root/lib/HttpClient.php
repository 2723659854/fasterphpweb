<?php

namespace Root\Lib;
use Root\Io\Epoll;
use Root\Io\Selector;
use Root\Request;

/**
 * @purpose tcp客户端
 * @note 这个是短链接，发送同步请求
 * @note 测试thinkPHP 作为服务器  php think run --host 0.0.0.0 --port 8080
 * @note 测试laravel 作为服务器  php artisan serve --host 0.0.0.0 --port 8080
 * @note 如果是webman或者fasterphpweb或者hyperf这种常驻内存的框架，直接启动就行
 * @note 可以发送http请求和https请求
 */
class HttpClient
{

    /** http代理 */
    //public static string $httpProxy = '';
    /** https代理 */
    //public static string $httpsProxy = '';

    /**
     * 发送http/https请求
     * @param string $host
     * @param string $method
     * @param array $params
     * @param array $query
     * @param array $header
     * @return Request
     */
    public static function request(string $host, string $method='GET',array $params = [],array $query=[],array $header=[]):Request{
        /** 用户会传入ip地址，所以不用正则检测 */
        $parsUrl = parse_url($host);
        if (empty($parsUrl['host'])){
            $parsUrl = parse_Url('http://'.$host);
        }
        /** 请求的domain */
        $_host =$parsUrl['host'];
        /** 请求的路径 */
        $_path = $parsUrl['path']??'/';
        /** 协议类型 */
        $_scheme = $parsUrl['scheme']??'http';
        /** 请求方法 */
        $_method = strtoupper($method)??'GET';
        /** query 资源参数 */
        $_query = $parsUrl['query']??[];
        $query = array_merge($query,$_query);
        /** 端口 */
        $_port = $parsUrl['port']??80;
        /** 如果是https则切换到443端口，否则使用原来的端口 */
        $_port = ($_scheme=='https')?443:$_port;
        if (!$_host){
            throw new \RuntimeException("host错误");
        }
        if (!in_array($_scheme,['http','https'])) throw new \RuntimeException("不支持的协议类型【{$_scheme}】");
        /** 处理请求 */
        return self::doRequest($_host,$_port,$_path,$_method,$params,$query,$header);
    }

    /**
     * 执行请求
     * @param string $host
     * @param int $port
     * @param string $target
     * @param string $method
     * @param array $params
     * @param array $query
     * @param array $header
     * @return Request
     */
    private static function doRequest(string $host, int $port = 80, string $target = '/', string $method='GET',array $params = [],array $query=[],array $header=[]){
        /** 构建request */
        $request = self::makeRequest($host,$port,$target,$method,$params,$query,$header);
        /** 协议类型 */
        $scheme = 'tcp';
        /** 初始化客户端设置 */
        $contextOptions = [];
        if ($port==443){
            /** 不校验ssl */
            $contextOptions['ssl']=[
                'verify_peer' => false,
                'verify_peer_name' => false
            ];
            $scheme = 'ssl';
        }

        /** 设置参数 */
        $context = stream_context_create($contextOptions);
        /** 创建客户端 STREAM_CLIENT_CONNECT 同步请求，STREAM_CLIENT_ASYNC_CONNECT 异步请求*/
        $socket = stream_socket_client("{$scheme}://{$host}:{$port}", $errno, $errstr, 1, STREAM_CLIENT_CONNECT, $context);
        /** 创建连接失败 */
        if ($errno){
            throw new \RuntimeException($errstr,$errno);
        }
        /** 发送http请求 */
        fwrite($socket, $request);
        /** 获取响应类容 */
        $response = "";
        while (!feof($socket)) {
            $response .= fread($socket, 1024);
        }
        /** 关闭连接 */
        fclose($socket);
        /** 返回响应结果 */
        return self::makeResponse($response,$port,$target,$method,$params,$query,$header);
    }

    /**
     * 处理响应
     * @param string $response
     * @param int $port
     * @param string $target
     * @param string $method
     * @param array $params
     * @param array $query
     * @param array $header
     * @return Request
     */
    private static function makeResponse(string $response,int $port=80, string $target = '/',string $method='GET', array $params = [],array $query=[],array $header =[]):Request{
        /** 处理响应内容 */
        $response = new Request($response);
        /** 可能对面的域名需要重定向 */
        if (($response->getStatusCode()>299)&&($response->getStatusCode()<400)){
            /** 获取重定向的地址 */
            $location = $response->header('location');
            /** 解析域名 */
            $temporary = parse_url($location);
            /** 对方协议发生了变化 */
            $scheme = $temporary['scheme'];
            /** 对方domain域名发生变化 */
            $host = $temporary['host'];
            /** 可能对面端口也会发生变化 */
            $port = $temporary['port']??$port;
            /** 判断协议类型确定端口 */
            $port = ($scheme=='https')?443:$port;
            return self::doRequest($host,$port,$target,$method,$params,$query,$header);
        }else{
            /** 返回响应 */
            return $response;
        }
    }

    /**
     * 构建请求体
     * @param string $host host domain
     * @param int $port port 端口
     * @param string $target 请求路径path
     * @param string $method 请求方法
     * @param array $params 请求参数
     * @param array $query query参数
     * @param array $header 头部信息
     * @return string 请求体
     */
    public static function makeRequest(string $host = '127.0.0.1', int $port = 443, string $target = '/',string $method='GET', array $params = [],array $query=[],array $header =[]):string{

        /** 处理请query求参数 */
        if ($query){
            $target=$target.'?'.http_build_query($query);
        }
        $end = "\r\n";
        /** 定义请求头 */
        $request = "$method $target HTTP/1.1$end";
        $request .= "Host: $host:$port$end";
        /** 如果用户设置了header参数 */
        if ($header){
            foreach ($header as $k=>$v){
                $request .="{$k}: {$v}{$end}";
            }
        }
        /** 以下都是绕过nginx的配置，模拟正常的谷歌浏览器发送请求 */
        /** 不确定当前设备是否是移动设备 */
        $request .= "Sec-Ch-Ua-Mobile: ?0\r\n";
        /** 客户端操作品台为windows */
        $request .= "Sec-Ch-Ua-Platform: Windows\r\n";
        /** 资源下载后存放到本地文档 */
        $request .= "Sec-Fetch-Dest: document\r\n";
        /** 导航模式加载资源，并立即渲染 */
        $request .= "Sec-Fetch-Mode: navigate\r\n";
        /**  浏览器使用同源策略 */
        $request .= "Sec-Fetch-Site: same-origin\r\n";
        /** 未知身份的浏览器用户 */
        $request .= "Sec-Fetch-User: ?1\r\n";
        /** 搞对对方服务器请升级http协议为https协议 ，但是本客户端并不校验https证书 ，只为了欺骗nginx */
        $request .= "Upgrade-Insecure-Requests: 1\r\n";
        /** 可接收的文件类型 */
        $request .= "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7\r\n";

        # 建议不要加这一行代码，否则会压缩文件，目前尚不能正确解压文件，若你能处理压缩文件，可以取消注释，然后自己处理压缩代码
        //$request .= "Accept-Encoding: gzip, deflate\r\n";
        /** 客户端支持的语言 */
        $request .= "Accept-Language: zh-CN,zh;q=0.9\r\n";
        /** 浏览器不缓存该资源 */
        $request .= "Cache-Control: max-age=0\r\n";
        /** 伪造客户端代理 */
        $request .= "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36\r\n";
        /** 设置为短链接，告诉服务器处理完本次请求后就断开连接 */
        $request .= "Connection: Close$end";

        /** 如果有post要传输数据 ，则将数据装载到body */
        if ($method=='POST'){
            $data = empty($params)?json_encode(new \stdClass()):json_encode($params);
            $request .= "Content-Type:application/json;charset=utf-8\r\n";
            $request .= "Content-Length: ".strlen($data)."\r\n";
            $request .= $data."\r\n";
        }
        $request .= "$end";
        return $request;
    }

    /**
     * 发送异步请求
     * @param string $host
     * @param string $method
     * @param array $params
     * @param array $query
     * @param array $header
     * @param $success
     * @param $fail
     * @return Request
     */
    public static function requestAsync(string $host, string $method='GET',array $params = [],array $query=[],array $header=[],$success=null,$fail=null){
        /** 保存原始数据 */
        $oldParams = [$host,$method,$params,$query,$header,$success,$fail];
        /** 用户会传入ip地址，所以不用正则检测 */
        $parsUrl = parse_url($host);
        if (empty($parsUrl['host'])){
            $parsUrl = parse_Url('http://'.$host);
        }
        /** 请求的domain */
        $_host =$parsUrl['host'];
        /** 请求的路径 */
        $_path = $parsUrl['path']??'/';
        /** 协议类型 */
        $_scheme = $parsUrl['scheme']??'http';
        /** 请求方法 */
        $_method = strtoupper($method)??'GET';
        /** query 资源参数 */
        $_query = $parsUrl['query']??[];
        $query = array_merge($query,$_query);
        /** 端口 */
        $_port = $parsUrl['port']??80;
        /** 如果是https则切换到443端口，否则使用原来的端口 */
        $_port = ($_scheme=='https')?443:$_port;
        if (!$_host){
            throw new \RuntimeException("host错误");
        }
        if (!in_array($_scheme,['http','https'])) throw new \RuntimeException("不支持的协议类型【{$_scheme}】");
        /** 这里要改成投递异步请求 */
        return self::doAsyncRequest($_host,$_port,$_path,$_method,$params,$query,$header,$success,$fail,$oldParams);
    }

    /**
     * 执行请求
     * @param string $host
     * @param int $port
     * @param string $target
     * @param string $method
     * @param array $params
     * @param array $query
     * @param array $header
     * @return Request
     */
    private static function doAsyncRequest(string $host, int $port = 80, string $target = '/', string $method='GET',array $params = [],array $query=[],array $header=[],callable $success=null,callable $fail=null,array $oldParams =[]){
        /** 构建request */
        $request = self::makeRequest($host,$port,$target,$method,$params,$query,$header);

        /** 协议类型 */
        $scheme = 'tcp';
        /** 初始化客户端设置 */
        $contextOptions = [];
        if ($port==443){
            /** 不校验ssl */
            $contextOptions['ssl']=[
                'verify_peer' => false,
                'verify_peer_name' => false
            ];
            $scheme = 'ssl';
        }
        /** 设置参数 */
        $context = stream_context_create($contextOptions);
        /** 创建客户端 STREAM_CLIENT_CONNECT 同步请求，STREAM_CLIENT_ASYNC_CONNECT 异步请求*/
        $socket = stream_socket_client("{$scheme}://{$host}:{$port}", $errno, $errstr, 1, STREAM_CLIENT_ASYNC_CONNECT, $context);
        /** 设置位非阻塞状态 */
        stream_set_blocking($socket,false);
        /** 添加到异步模型 */
        Selector::sendRequest($socket,$request,$success,$fail,$host.':'.$port,$oldParams);
        /** 如果开启了epoll读写模型 */
        if (Epoll::$event_base){
            Epoll::sendRequest($socket,$request,$success,$fail,$host.':'.$port,$oldParams);
        }

    }
}