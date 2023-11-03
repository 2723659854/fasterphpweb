<?php

namespace Root\Core\Provider;

use Root\Lib\WsSelectorService;
use Root\Xiaosongshu;

/**
 * @purpose 创建ws模型
 */
class MakeWsProvider implements IdentifyInterface
{

    public function handle(Xiaosongshu $app, array $param)
    {
        $name =$param[2] ?? '';
        if (!$name) {
            echo "请输入要创建的ws服务名称\r\n";
            exit;
        }

        $name       = trim($name, '/');
        $controller = strtolower(app_path() . '/ws/' . $name . '.php');
        /**
         * 检查是否存在相同的文件
         */
        foreach (scan_dir(app_path() . '/ws', true) as $file) {
            if (file_exists($file)) {
                $fileName = strtolower($file);
                if ($fileName == $controller) {
                    echo "存在相同名称的文件：[{$fileName}]\r\n";
                    exit;
                }
            }
        }
        $name = array_filter(explode('/', $name));

        $className = ucfirst(strtolower(array_pop($name)));
        $nameSpace = "Ws";
        if ($name) {
            foreach ($name as $dir) {
                $dir       = ucfirst(strtolower($dir));
                $nameSpace = $nameSpace . "\\" . $dir;
            }
        }
        $filePath = app_path() . '/ws';
        foreach ($name as $dir) {
            $filePath = $filePath . '/' . strtolower($dir);
        }
        if (!is_dir($filePath)) {
            mkdir($filePath, '0777', true);
        }
        $filePath = $filePath . "/" . $className . '.php';

        $time = date('Y-m-d H:i:s');

        $content    = <<<EOF
<?php
namespace Ws;

/** 最大连接数1240，默认支持selector模型 */
use Root\Lib\WsSelectorService;
/** 最大连接无上限，需系统支持epoll模型 */
use Root\Lib\WsEpollService;

/**
 * @purpose ws服务
 * @author administrator
 * @time $time
 * @note 默认使用WsSelectorService，若系统支持event事件，支持epoll模型，则可以继承WsEpollService
 */
class $className extends WsSelectorService
{
    /** ws 监听ip */
    public string \$host= '0.0.0.0';
    /** 监听端口 */
    public int \$port = 9501;

    public function __construct(){
        //todo 编写可能需要的逻辑
    }

    /**
     * 建立连接事件
     * @param \$socket
     * @return mixed|void
     */
    public function onConnect(\$socket)
    {
        // TODO: Implement onConnect() method.
    }

    /**
     * 消息事件
     * @param \$socket
     * @param \$message
     * @return mixed|void
     */
    public function onMessage(\$socket, \$message)
    {
        // TODO: Implement onMessage() method.
        switch (\$message){
            case 'Ping':
                \$this->sendTo(\$socket,'Pong');
                break;
            default:
                \$this->sendTo(\$socket,['data'=>\$message,'time'=>date('Y-m-d H:i:s')]);
        }
    }

    /**
     * 连接断开事件
     * @param \$socket
     * @return mixed|void
     */
    public function onClose(\$socket)
    {
        // TODO: Implement onClose() method.
    }
    
    /**
     * 异常处理
     * @param \$socket
     * @param \Exception \$exception
     * @return mixed|void 
     */
    public function onError(\$socket, \Exception \$exception)
    {
        // TODO: Implement onError() method.
    }
}
EOF;

        file_put_contents($filePath, $content);
        echo "创建ws服务完成,请将服务配置到config/ws.php中。\r\n";
        exit;
    }
}