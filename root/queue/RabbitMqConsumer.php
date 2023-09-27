<?php

namespace Root\Queue;

class RabbitMqConsumer
{

    /**
     * 处理rabbitmq的消费
     * @return void
     */
    public function consume()
    {
        $enable = config('rabbitmq')['enable'];
        if ($enable) {
            $config = config('rabbitmqProcess');
            foreach ($config as $name => $value) {
                if (isset($value['handler'])) {
                    /** 创建一个子进程，在子进程里面执行消费 */
                    $count = $value['count'] ?? 1;
                    for ($i = 0; $i < $count; $i++) {
                        $rabbitmq_pid = \pcntl_fork();
                        if ($rabbitmq_pid > 0) {
                            /** 记录进程号 */
                            writePid();
                            cli_set_process_title($name . '_' . ($i + 1));
                            if (class_exists($value['handler'])) {
                                /** 切换CPU */
                                sleep(1);
                                $className = $value['handler'];
                                $queue     = new $className();
                                $queue->consume();
                            }
                        }
                    }

                }
            }
        }
    }
}