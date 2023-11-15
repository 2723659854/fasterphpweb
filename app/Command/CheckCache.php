<?php

namespace App\Command;

use Root\Lib\AsyncHttpClient;
use Root\Lib\BaseCommand;
use Root\Cache;
use Root\Lib\HttpClient;
use Root\Lib\NacosConfigManager;
use Root\Request;
use Root\Timer;
use Root\Xiaosongshu;
use Workerman\Worker;

/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2023-09-21 07:00:29
 * @note 测试请求代理服务器
 */
class CheckCache extends BaseCommand
{

    /** @var string $command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public $command = 'check:cache';

    /**
     * 配置参数
     * @return void
     */
    public function configure()
    {

    }

    /**
     * 清在这里编写你的业务逻辑
     * @return void
     */
    public function handle()
    {
       //$request = $this->sendTcp('156.236.71.182',8989,'156.236.71.182');
       //$request = $this->sendTcp('156.236.71.182',8989,'156.236.71.182:8080');
       $request = $this->sendTcp('156.236.71.182',8989,'www.baidu.com');
       file_put_contents(public_path().'/test.html',$request->rawBody());
       var_dump("请求完成");
        /** 现在要做的事情是， 服务端调整后，可以代理任意的ip */
    }

    /**
     * 发送tcp请求
     * @param string $url
     * @param int $port
     * @param string $host
     * @param string $path
     * @param int $number
     * @return Request
     */
    public function sendTcp(string $url, int $port = 80, string $host= '',string $path = '/')
    {
        if ($this->is_ip($url)) {
            $address = 'tcp://' . $url;
        } else {
            $address = parse_url($url)['path'];
        }
        /** 提前构建http请求 */
        $request = "GET {$path} HTTP/1.1
Host: {$host}
X-Real-IP: 14.104.142.26
X-Forwarded-For: 14.104.142.26
REMOTE-HOST: 14.104.142.26
Connection: close

";
        $socket = fsockopen($address, $port, $errno, $errstr);
        if (!$socket) {
            throw new \RuntimeException("连接失败：$errno-$errstr");
        } else {
            echo "连接成功\r\n";
            /** 发送http请求 */
            fwrite($socket, $request,strlen($request));
            /** 获取响应类容 */
            $response = "";
            while (!feof($socket)) {
                $response .= fgets($socket, 1024);
            }
            /** 关闭连接 */
            fclose($socket);
            $response =  new Request($response);
            if ($response->getStatusCode()>=300&&$response->getStatusCode()<400){
                $address = $response->header('location');
                $parsUrl = parse_url($address);
                if (empty($parsUrl['host'])){
                    return $this->sendTcp($url,$port,$host,$address);
                }else{
                    return $this->sendTcp($url,$port,$parsUrl['host'],$parsUrl['path']);
                }
            }
            return $response;
        }
    }

    /**
     * 检测是否是ip
     * @param $ip
     * @return bool
     */
    public function is_ip($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        } else {
            return false;
        }
    }

}