<?php

namespace MediaServer;


use Evenement\EventEmitter;
use MediaServer\MediaReader\MediaFrame;
use MediaServer\PushServer\PlayStreamInterface;
use MediaServer\PushServer\PublishStreamInterface;
use MediaServer\PushServer\VerifyAuthStreamInterface;


/**
 * @purpose 这个媒体中心服务，是怎么关联上的额
 */
class MediaServer
{

    /**
     * 事件触发器
     * @var EventEmitter
     */
    static protected $eventEmitter;


    /**
     * 魔术方法，可以调用本对象的任意方法
     * @param $name
     * @param $arguments
     * @return mixed
     */
    static function __callStatic($name, $arguments)
    {
        /** 初始化事件触发器 */
        if (!self::$eventEmitter) {
            self::$eventEmitter = new EventEmitter();
        }
        return call_user_func_array([self::$eventEmitter,$name],$arguments);
    }


    /**
     * 保存本项目下的所有推流资源
     * @var PublishStreamInterface[]
     * @comment 从代码中可以看出，所有的推流资源都存放在内存中，所以直播比较消耗内存
     */
    static public $publishStream = [];

    /**
     * 调用本对象的api
     * @param $name
     * @param $args
     * @return array|false
     */
    static public function callApi($name,$args = []){
        switch ($name){
            case 'listPushStream':
                return self::listPushStream(...$args);
            default:
                return false;
        }
    }

    /**
     * 列出路径下的推流资源
     * @param $path
     * @return array
     */
    static public function  listPushStream($path = null){
        if($path){
            return isset(self::$publishStream[$path])?[
                self::$publishStream[$path]->getPublishStreamInfo()
            ]:[];
        }
        return array_map(function($stream){
            return $stream->getPublishStreamInfo();
        },array_values(self::$publishStream));
    }

    /**
     * 是否某一路推流资源
     * @param $path
     * @return bool
     */
    static public function hasPublishStream($path)
    {
        return isset(self::$publishStream[$path]);
    }

    /**
     * 获取某一路推流资源
     * @param $path
     * @return PublishStreamInterface
     */
    static public function getPublishStream($path)
    {
        return self::$publishStream[$path];
    }

    /**
     * 添加某一路推流资源
     * @param $stream PublishStreamInterface
     */
    static protected function addPublishStream($stream)
    {
        $path = $stream->getPublishPath();
        self::$publishStream[$path] = $stream;
    }

    /**
     * 删除某一路资源
     * @param $path
     * @return void
     */
    static protected function delPublishStream($path)
    {
        unset(self::$publishStream[$path]);
    }

    /**
     * 播放资源
     * @var PlayStreamInterface[][]
     */
    static public $playerStream = [];

    /**
     * 获取某一路播放资源
     * @param $path
     * @return array|PlayStreamInterface[]
     */
    static public function getPlayStreams($path)
    {
        return self::$playerStream[$path] ?? [];
    }


    /**
     * 删除某一路播放资源
     * @param $path
     * @param $objId
     * @comment  从这里的代码逻辑可以知道，只要有播放设备接入，才会转发数据
     */
    static protected function delPlayerStream($path, $objId)
    {
        unset(self::$playerStream[$path][$objId]);
        //一个播放设备都没有
        if (self::hasPublishStream($path) && count(self::getPlayStreams($path)) == 0) {
            /** 获取这个路径下的推流资源 */
            $p_stream = self::getPublishStream($path);
            /** 移除事件 */
            $p_stream->removeListener('on_frame', self::class . '::publisherOnFrame');
            $p_stream->is_on_frame = false;
            //todo 不知道怎么表达？有播放设备才会转发流量？没有播放设备的时候不转发，转发是绑定的事件完成的。如果不移除事件会有什么影响呢？
        }
    }

    /**
     * 有播放设备接入，添加播放流媒体源
     * @param $playerStream PlayStreamInterface
     */
    static protected function addPlayerStream($playerStream)
    {
        /** 获取播放路径 */
        $path = $playerStream->getPlayPath();
        /** 获取对象id 获取这个播放源的hash值 */
        $objIndex = spl_object_id($playerStream);

        /** 初始化这个路径下的播放设备数据 */
        if (!isset(self::$playerStream[$path])) {
            self::$playerStream[$path] = [];
        }
        /** 加入当前的播放设备 */
        self::$playerStream[$path][$objIndex] = $playerStream;

        /** 如果这一路媒体已经推流了 */
        if (self::hasPublishStream($path)) {
            /** 获取推流的流媒体资源 */
            $p_stream = self::getPublishStream($path);
            if (!$p_stream->is_on_frame) {
                /** 这一路流媒体资源开始推流 转发流量数据 */
                $p_stream->on('on_frame', self::class . '::publisherOnFrame');
                $p_stream->is_on_frame = true;
            }
        }

    }


    /**
     * 转发流媒体数据
     * @param $publisher PublishStreamInterface 发布者 可以是音频，可以是视频
     * @param $frame MediaFrame 这个是流媒体数据包，比如音频或者视频
     * @comment rtmp服务端转发数据的关键就是这个方法
     */
    static function publisherOnFrame($frame, $publisher)
    {
        /** 获取这个媒体路径下的所有播放设备 */
        foreach (self::getPlayStreams($publisher->getPublishPath()) as $playStream) {
            /** 如果播放器不是空闲状态 */
            if (!$playStream->isPlayerIdling()) {
                /** 转发数据包给播放器 */
                $playStream->frameSend($frame);
            }
        }
    }


    /**
     * 添加推流
     * @param PublishStreamInterface $stream
     * @return mixed
     * @comment 有推流数据加入进来
     */
    static public function addPublish($stream)
    {
        /** 获取推流路径  */
        $path = $stream->getPublishPath();
        /** 初始化尚未开始推流 */
        $stream->is_on_frame = false;
        /** 绑定事件推流准备事件  */
        $stream->on('on_publish_ready', function () use ($path) {
            /** 获取所有的播放设备 */
            foreach (self::getPlayStreams($path) as $playStream) {
                /** 如果设备出于空闲状态 */
                if ($playStream->isPlayerIdling()) {
                    /** 通知设备开始播放，发送播放命令 */
                    $playStream->startPlay();
                }
            }
        });

        /** 如果当前已有播放设备链接 */
        if (count(self::getPlayStreams($path)) > 0) {
            /** 绑定推流事件 */
            $stream->on('on_frame', self::class . '::publisherOnFrame');
            $stream->is_on_frame = true;
        }

        /** 绑定关闭事件 当推流设备关闭后，给所有的播放客户端发送关闭命令 */
        $stream->on('on_close', function () use ($path) {
            foreach (self::getPlayStreams($path) as $playStream) {
                $playStream->playClose();
            }
            /** 删除本路推流资源 */
            self::delPublishStream($path);

        });
        /** 保存当前推流资源 */
        self::addPublishStream($stream);

        logger()->info(" add publisher {path}", ['path' => $path]);

        return true;

    }

    /**
     * 添加播放器
     * @param PlayStreamInterface $playerStream
     * @comment 有播放器接入
     */
    static public function addPlayer($playerStream)
    {
        /** 获取流媒体对象的hash值 */
        $objIndex = spl_object_id($playerStream);
        /** 获取播放路径 */
        $path = $playerStream->getPlayPath();
        /** 播放器绑定关闭事件 */
        //on close event
        $playerStream->on("on_close", function () use ($path, $objIndex) {
            /** 删除播放器媒体资源 */
            //echo "play on close", PHP_EOL;
            self::delPlayerStream($path, $objIndex);
        });
        /** 保存播放器资源 */
        self::addPlayerStream($playerStream);

        /** 判断当前是否有对应的推流设备 */
        if (self::hasPublishStream($path)) {
            $playerStream->startPlay();
        }

        logger()->info(" add player {path}", ['path' => $path]);

    }

    /**
     * @param $stream VerifyAuthStreamInterface
     * @return bool
     * @comment 就很离谱，没有鉴权
     */
    static public function verifyAuth($stream)
    {
        return true;
    }


}
