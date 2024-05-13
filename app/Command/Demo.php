<?php

namespace App\Command;

use Root\Lib\BaseCommand;
use Root\Lib\HttpClient;
use Root\Request;

/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2024-05-13 02:22:46
 */
class Demo extends BaseCommand
{

    /** @var string $command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public $command = 'demo';

    /**
     * 配置参数
     * @return void
     */
    public function configure()
    {
        /** 必选参数 */
        //$this->addArgument('argument','这个是参数argument的描述信息');
        /** 可传参数 */
        //$this->addOption('option','这个是option参数的描述信息');
    }

    /**
     * 请在这里编写你的业务逻辑
     * @return void
     */
    public function handle()
    {
        $this->sendTcp('127.0.0.1:8000','GET');
    }

    /**
     * 测试并发请求
     * @param string $host 请求域名
     * @param string $method 请求方法
     * @param int $forkNumber 并发数
     * @param int $requestNumber 每个客户端请求总数
     * @return void
     * @comment 多进程，高频次，高并发，http请求
     */
    public function sendTcp(string $host,string $method = 'GET', int $forkNumber = 3,int $requestNumber = 100)
    {
        $this->info("本次高并发请求开始");
        for ($i = 0; $i < $forkNumber; $i++) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                die("无法创建子进程");
            } elseif ($pid == 0) {
                // 子进程逻辑
                for ($i = 1; $i <= $requestNumber; $i++) {
                    $response = (HttpClient::request($host, $method, ['lesson_id' => 201]));
                    echo $response->getStatusCode();echo "\r\n";
                }
                /** 每一个子进程任务执行完成后，必须exit退出，否则子进程会接着执行for循环，导致创建多个子进程 */
                exit;
            }
        }
        $this->info("本次高并发请求结束");
    }
}