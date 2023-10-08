<?php
namespace Ws;
use http\Exception\RuntimeException;
use Root\Lib\Websocket;

/**
 * @purpose ws服务
 * @author administrator
 * @time 2023-09-28 10:47:59
 */
class Just extends Websocket
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
                $this->sendTo($socket,['data'=>$message,'time'=>date('Y-m-d H:i:s')]);
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