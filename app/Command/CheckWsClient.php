<?php
namespace App\Command;

use Root\Lib\BaseCommand;
use Root\Lib\WsClient;

/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2023-10-10 07:28:35
 */
class CheckWsClient extends BaseCommand
{

    /** @var string $command 检查websocket客户端可用性 */
    public $command = 'check:ws';
    
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
        $this->info("请在这里编写你的业务逻辑");
        /** 设置需要连接的ws服务器 */
        WsClient::setUp('127.0.0.1',9501);
        /** 发送一条数据 */
        WsClient::send(['type'=>'ping']);
        /** 读取一条数据 */
        var_dump(WsClient::get());
        /** 设置消息回调函数，负责处理接收到消息后逻辑，若不设置，则自动丢弃消息 */
        WsClient::$onMessage = function ($message){
            $message = json_decode($message,true);
            /** 消息类型 */
            $type = $message['type']??null;
            /** 消息体 */
            $content = $message['content']??'';
            /** 接收人 */
            $sendTo = $message['to']??'all';
            if ($sendTo=='all'){
                var_dump("广播的消息",$content);
            }else{
                var_dump("私聊给我的消息",$content);
            }
        };
        /** 开启客户端监听 */
        WsClient::start();
    }
}