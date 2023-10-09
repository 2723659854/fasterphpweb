<?php
namespace Ws;
use RuntimeException;
use Root\Lib\Websocket;
use Root\Lib\WsService;

/**
 * @purpose ws服务
 * @author administrator
 * @time 2023-09-28 10:47:59
 */
class Just extends WsService
{
    /** ws 监听ip */
    public string $host= '0.0.0.0';
    /** 监听端口 */
    public int $port = 9501;

    public function __construct(){
        //todo 编写可能需要的逻辑
    }

    /**
     * 建立连接事件
     * @param $socket
     * @return mixed|void
     */
    public function onConnect($socket)
    {
        // TODO: Implement onConnect() method.
    }

    /**
     * 消息事件
     * @param $socket
     * @param $message
     * @return mixed|void
     */
    public function onMessage($socket, $message)
    {
        // TODO: Implement onMessage() method.
        switch ($message){
            case 'Ping':
                $this->sendTo($socket,'Pong');
                break;
            default:
                /** 发送当前时间 ，和客户端地址 */
                $this->sendTo($socket,['data'=>$message,'time'=>date('Y-m-d H:i:s'),'ip'=>$this->getUserInfoBySocket($socket)->remote_address??'']);
        }
    }

    /**
     * 连接断开事件
     * @param $socket
     * @return mixed|void
     */
    public function onClose($socket)
    {
        // TODO: Implement onClose() method.
    }

    /**
     * 异常事件
     * @param $socket
     * @param \Exception $exception
     * @return mixed|void
     */
    public function onError($socket, \Exception $exception)
    {
        //var_dump($exception->getMessage());
        $this->close($socket);
    }
}