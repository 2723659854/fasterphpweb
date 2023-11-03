<?php

namespace Root\Core\Provider;

use Root\Xiaosongshu;

/**
 * @purpose 创建rabbitmq队列消费者
 */
class MakeRabbitmqProvider implements IdentifyInterface
{

    public function handle(Xiaosongshu $app, array $param)
    {
        $name = $param[2] ?? '';
        if (!$name) {
            echo "请输入要创建的rabbitmq消费者文件名称\r\n";
            exit;
        }
        foreach (scan_dir(app_path().'/app/rabbitmq', true) as $key => $file) {
            if (file_exists($file)) {
                $fileName = basename($file);
                if ($fileName == $name . '.php') {
                    echo "存在相同名称的文件：[{$fileName}]\r\n";
                    exit;
                }
            }
        }
        /** 名称转为大写 */
        $name = ucwords($name);
        /** 时间 */
        $time    = date('Y-m-d H:i:s');
        $content = <<<EOF
<?php
namespace App\Rabbitmq;
use Root\Queue\RabbitMQBase;

/**
 * @purpose rabbitMq消费者
 * @author administrator
 * @time $time
 */
class $name extends RabbitMQBase
{

    /**
     * 自定义队列名称
     * @var string
     */
    public \$queueName ="{$name}";

    /** @var int \$timeOut 普通队列 */
    public \$timeOut=0;

   /**
     * 逻辑处理
     * @param array \$param
     * @return void
     */
    public function handle(array \$param)
    {
        // TODO: Implement handle() method.
    }

    /**
     * 异常处理
     * @param \Exception|\RuntimeException \$exception
     * @return mixed|void
     */
    public function error(\Exception|\RuntimeException \$exception)
    {
        // TODO: Implement error() method.
    }
}
EOF;
        @file_put_contents(app_path() . '/app/rabbitmq/' . $name . '.php', $content);
        echo "创建rabbitmq队列完成\r\n";
        exit;
    }
}