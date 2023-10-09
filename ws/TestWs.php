<?php
namespace Ws;
use Root\Lib\WsSelectorService;

/**
 * @purpose ws服务
 * @author administrator
 * @time 2023年9月28日17:56:05
 */
class TestWs extends WsSelectorService
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
     * 发生了错误
     * @param $socket
     * @param \Exception $exception
     * @return mixed|void
     * @note 此处仅仅作为举例，实际应用请根据自己的业务需求处理报错信息
     */
    public function onError($socket, \Exception $exception)
    {
        $this->sendTo($socket,$exception->getMessage());
        $this->close($socket);
    }
}