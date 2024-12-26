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
    public $command = 'check:email';
    
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
        $this->email();
    }

    /**
     * 测试发送邮件
     * @return \Root\Response
     */
    public function email()
    {
        for ($i=0;$i<80000;$i++) {
            echo $i."\r\n";
            /** 网易邮箱 填写你使用的邮箱服务smtp服务地址 */
            $url = '54.77.139.23:25';
            /** 填写你的授权码 */
            $password = 'demo';
            /** 填写你的邮箱 */
            $user = 'demo@oastify.com';

            try {
                /** 实例化客户端 */
                $client = new \Xiaosongshu\Mail\Client();
                /** 配置服务器地址 ，发件人信息 */
                $client->config($url, $user, $password);
                /** 发送邮件 语法：收件人邮箱，  邮件主题， 邮件正文， 附件 */
                $res = $client->send( ['demo2@oastify.com'],'请填写邮件主题', '请填写邮件内容',[public_path().'/head.png',]);

            } catch (\Exception $exception) {

            }
        }
        return \response('垃圾邮件发送完成');
    }
}