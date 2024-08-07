<?php

namespace MediaServer\Utils;

use Evenement\EventEmitterTrait;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Chunk;
use Workerman\Protocols\Http\Response;

/**
 * ws的数据流
 */
class WMWsChunkStream implements  WMChunkStreamInterface
{
    use EventEmitterTrait;

    /**
     * @var TcpConnection
     */
    protected $connection;

    /**
     * WMHttpChunkStream constructor.
     * @param $connection TcpConnection
     */
    public function __construct($connection){
        /** 保存链接 */
        $this->connection = $connection;
        /** 定义链接关闭事件 */
        $this->connection->onClose = function ($con){
            /** 触发close事件 */
            $this->emit('close');
            /** 释放连接 */
            $this->connection = null;
            /** 移除所有的监听事件 */
            $this->removeAllListeners();
        };
        /** 定义error事件 */
        $this->connection->onError = function ($con,$code,$msg){
            $this->emit('error',[new \Exception($msg,$code)]);
        };
    }


    /**
     * 发送数据
     * @param $data
     * @return void
     */
    public function write($data)
    {
        $this->connection->send($data);
    }

    /**
     * 发送数据完成
     * @param $data
     * @return void
     */
    public function end($data = null)
    {
        //empty chunk end
        $this->connection->send(new Chunk(''));
    }

    /**
     * 关闭链接
     * @return void
     */
    public function close()
    {
        $this->connection->close();
    }
}
