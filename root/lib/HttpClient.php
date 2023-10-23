<?php

namespace Root\Lib;
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
        if (!$_host){
            throw new \RuntimeException("host错误");
        }
        /** 如果是http请求 */
        if ($_scheme=='http'){
            return self::sendHttpRequest($_host,$_port,$_path,$_method,$params,$query,$header);
        }
        /** 如果是https请求 */
        if ($_scheme=='https'){
            return self::sendSslRequest($_host,443,$_path,$_method,$params,$query,$header);
        }
        throw new \RuntimeException("不支持的协议类型【{$_scheme}】");
    }
    /**
     * 发送http请求
     * @param string $host 服务器域名
     * @param int $port 端口
     * @param string $target 路径
     * @param string $method 请求方法
     * @param array $params post参数
     * @param array $query query参数
     * @param array $header header参数
     * @return Request
     * @note 暂不支持文件上传
     */
    public static function sendHttpRequest(string $host, int $port = 80, string $target = '/', string $method='GET',array $params = [],array $query=[],array $header=[])
    {
        $method = strtoupper($method)??'GET';
        if (filter_var($host, FILTER_VALIDATE_IP)) { $address = 'tcp://' . $host; } else { $address = parse_url($host)['path']; }
        $request = self::makeRequest($host,$port,$target,$method,$params,$query,$header);
        $socket = fsockopen($address, $port??80, $errno, $errstr);
        if (!$socket) {
            throw new \RuntimeException($errstr,$errno);
        } else {
            /** 发送http请求 */
            fwrite($socket, $request);
            /** 获取响应类容 */
            $response = "";
            while (!feof($socket)) {
                $response .= fgets($socket, 1024);
            }
            /** 关闭连接 */
            fclose($socket);
            /** 返回响应结果 */
            return self::makeResponse($response,$port,$target,$method,$params,$query,$header);
        }
    }

    /**
     * 发送ssl请求
     * @param string $host domain 域名
     * @param int $port port 端口
     * @param string $target 请求路径
     * @param string $method 请求方法
     * @param array $params 请求post参数
     * @param array $query 请求query参数
     * @param array $header 请求header
     * @return Request
     */
    private static function sendSslRequest(string $host = '127.0.0.1', int $port = 443, string $target = '/',string $method='GET', array $params = [],array $query=[],array $header =[]):Request
    {
        /** 构建http请求结构体 */
        $request = self::makeRequest($host,$port,$target,$method,$params,$query,$header );
        /** 构建ssl参数不验证证书 */
        $contextOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            )
        );
        /** 设置参数 */
        $context = stream_context_create($contextOptions);
        /** 创建客户端 */
        $socket = stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 1, STREAM_CLIENT_CONNECT, $context);
        /** 创建连接失败 */
        if ($errno){
            throw new \RuntimeException($errstr,$errno);
        }
        /** 发送http请求 */
        fwrite($socket, $request);
        /** 获取响应类容 */
        $response = "";
        while (!feof($socket)) {
            $response .= fgets($socket, 1024);
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
    private static function makeResponse(string $response,int $port=80, string $target = '/',string $method='GET', array $params = [],array $query=[],array $header =[]){
        /** 处理响应内容 */
        $response = new Request($response);
        /** 可能对面的域名需要重定向 */
        if (($response->getStatusCode()>299)&&($response->getStatusCode()<400)){
            $location = $response->header('location');
            $temporary = parse_url($location);
            /** 对方协议发生了变化 */
            $scheme = $temporary['scheme'];
            /** 对方domain域名发生变化 */
            $host = $temporary['host'];
            /** 可能对面端口也会发生变化 */
            $port = $temporary['port']??$port;
            /** 可能对方协议发生了变化 */
            if ($scheme=='http'){
                return self::sendHttpRequest($host,$port,$target,$method,$params,$query,$header);
            }else{
                return self::sendSslRequest($host,443,$target,$method,$params,$query,$header);
            }
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
    private static function makeRequest(string $host = '127.0.0.1', int $port = 443, string $target = '/',string $method='GET', array $params = [],array $query=[],array $header =[]):string{
        $makeIpAndDomain = self::makeUrlAndIp();
        $referer = $makeIpAndDomain['host'];
        $refererIp = $makeIpAndDomain['ip'];
        $scheme = $port==443?'https':'http';
        /** 处理请query求参数 */
        if ($query){
            $target=$target.'?'.http_build_query($query);
        }
        $end = "\r\n";
        $request = "$method $target HTTP/1.1$end";
        $request .= "Host: $host$end";
        /** 设置客户端域名，即发起请求的页面地址 */
        $request .= "Referer: $referer$end";
        /** 部分网站的https请求需要这些头部参数 */
        $request .= ":authority: $host$end";
        $request .= ":method: $method$end";
        $request .= ":path: $target$end";
        $request .= ":scheme: $scheme$end";
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
        /** 设置客户端ip，让过nginx */
        $request .= "Client-IP: $refererIp\r\n";
        /** 可接收的文件类型 */
        $request .= "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7\r\n";
        /** 设置客户端ip绕过nginx */
        $request .= "X-Forwarded-For: $refererIp\r\n";
        # 千万不要加这一行代码，加入这一行代码后，会压缩文件，不能正确解压文件
        //$out .= "Accept-Encoding: gzip, deflate\r\n";
        /** 客户端支持的语言 */
        $request .= "Accept-Language: zh-CN,zh;q=0.9\r\n";
        /** 浏览器不缓存该资源 */
        $request .= "Cache-Control: max-age=0\r\n";
        /** 伪造客户端代理 */
        $request .= "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36\r\n";
        /** 设置客户端ip绕过nginx */
        $request .= "x-real-ip: $refererIp\r\n";
        $request .= "x-client-ip: $refererIp\r\n";
        $request .= "via: $refererIp\r\n";
        $request .= "Proxy_Add_X_Forwarded_For: $refererIp\r\n";
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
     * 伪造来路ip和域名
     * @return array
     */
    private static function makeUrlAndIp(){
        /** 生成随机来路页面 */
        $refererPool = [];
        for ($i = 0; $i <= 1000; $i++) {
            $refererPool[] = 'http://www.' . self::makeUrl(rand(3, 10)) . '.com';
        }
        /** 生成随机ip */
        $ipPoolHead = [
            '43.204.109.', '202.020.105.', '202.000.104.', '202.009.064.', '202.012.026.', '202.012.086.',
            '172.87.219.', '218.173.8.', '202.020.091.', '202.020.091.', '202.020.092.', '202.020.093.',
            '202.020.106.', '202.020.110.', '202.020.112.', '202.020.119.', '202.042.000.', '202.052.224.',
            '202.047.140.', '202.046.032.', '202.046.000.', '202.043.032.', '202.042.000.', '202.058.128.',
            '202.059.000.', '202.059.032.', '202.060.000.', '202.061.000.', '202.061.192.', '202.062.224.',
            '202.000.192.', '202.000.188.', '202.001.160.', '202.002.004.', '202.003.128.', '202.004.032.',
            '202.004.096.', '202.005.128.', '202.006.005.', '202.006.095.', '202.006.098.', '202.012.090.',
            '4.240.588.', '1.260.155.', '19.443.581.', '11.252.953.', '54.263.318.', '1.864.941.', '14.195.713.',
            '4.276.167.', '1.187.197.', '89.840.951.', '1.877.093.', '73.300.874.', '27.796.749.', '1.682.699.',
            '10.427.369.', '351.406.784.', '17.563.824.', '2.296.016.', '8.740.369.', '136.027.638.', '12.924.474.',
            '1.667.574.', '4.767.051.', '2.795.883.', '1.480.083.', '24.178.460.', '35.314.327.', '14.384.451.', '83.681.375.',
            '119.423.708.', '1.263.646.', '2.272.299.', '5.736.487.', '20.844.874.', '2.369.160.', '6.004.949.', '19.982.052.',
            '14.352.975.', '8.397.068.', '46.689.344.', '12.298.389.', '56.599.716.', '198.794.915.', '6.253.002.', '114.184.476.',
            '1.947.097.', '3.332.042.', '2.857.919.', '1.913.999.', '12.273.380.', '1.245.470.', '6.713.621.', '29.570.513.',
            '6.910.682.', '3.182.260.', '53.982.007.', '15.755.299.', '7.292.719.', '1.036.234.', '1.770.833.', '3.474.519.',
            '6.298.596.', '6.121.344.', '21.408.895.', '1.266.563.', '6.800.386.', '1.257.783.', '7.667.115.', '2.391.012.',
            '45.580.863.', '10.808.959.', '1.889.454.', '29.997.599.', '18.606.540.', '2.650.046.', '1.694.855.', '28.894.918.',
            '16.272.539.', '6.938.424.', '2.459.200.', '245.065.297.', '1.449.117.', '10.953.155.', '1.090.440.', '36.518.419.',
            '16.888.423.', '7.874.248.', '9.563.733.', '1.293.084.', '2,.829.500.',
        ];
        $ipPool = [];
        foreach ($ipPoolHead as $head) {
            for ($i = 100; $i <= 225; $i++) {
                $ipPool[] = $head . $i;
            }
        }
        /** 伪造请求方域名和IP */
        $ipPoolCount = count($ipPool) - 1;
        $refererCount = count($refererPool) - 1;
        /** 伪造来路IP */
        $refererIp = $ipPool[rand(0, $ipPoolCount)];
        /** 伪造来路页面 */
        $referer = $refererPool[rand(0, $refererCount)];
        return ['host'=>$referer,'ip'=>$refererIp];
    }

    /**
     * 生成随机字符串
     * @param $length
     * @return string
     */
    private static function makeUrl($length = 8)
    {
        /** 密码字符集，可任意添加你需要的字符 */
        $chars = [
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n',
            'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B',
            'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
            'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3',
            '4', '5', '6', '7', '8', '9', '~', '!', '@', '#', '$', '%', '^', '&', '*',
        ];
        $sum = count($chars) - 1;
        /** 在 $chars 中随机取 $length 个数组元素键名 */
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            /** 将 $length 个数组元素连接成字符串 */
            $string .= $chars[rand(0, $sum)];
        }
        return $string;
    }

}