<?php
namespace App\Command;

use Root\Lib\BaseCommand;
/**
 * @purpose 测试发送邮件
 * @author administrator
 * @time 2023-11-03 04:29:53
 */
class Email extends BaseCommand
{

    /** @var string $command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public $command = 'check:mail';
    
     /**
     * 配置参数
     * @return void
     */
    public function configure(){

    }
    
    /**
     * 清在这里编写你的业务逻辑
     * @return void
     */
    public function handle()
    {
        /** 网易邮箱 填写你使用的邮箱服务smtp服务地址 */
        $url = 'smtp.163.com:25';
        /** 填写你的授权码 */
        $password = 'xxxxx';
        /** 填写你的邮箱 */
        $user = 'xxxxxx@163.com';

        try {
            /** 实例化客户端 */
            $client = new \Xiaosongshu\Mail\Client();
            /** 配置服务器地址 ，发件人信息 */
            $client->config($url, $user, $password);
            /** 发送邮件 语法：收件人邮箱，  邮件主题， 邮件正文， 附件 */
            $res = $client->send( ['xxxx@qq.com'],'请填写邮件主题', '请填写邮件内容',[public_path().'/head.png',]);
            print_r($res);
            $this->info("发送邮件完成");
        } catch (\Exception $exception) {
            $this->info("发送邮件失败");
            print_r($exception->getMessage());
        }
    }
}