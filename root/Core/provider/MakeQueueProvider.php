<?php

namespace Root\Core\Provider;

use Root\Xiaosongshu;

/**
 * @purpose 创建redis队列消费者
 */
class MakeQueueProvider implements IdentifyInterface
{

    public function handle(Xiaosongshu $app, array $param)
    {
        $name = $param[2] ?? '';
        if (!$name) {
            echo "请输入要创建的redis消费者文件名称\r\n";
            exit;
        }
        foreach (scan_dir(app_path().'/app/queue', true) as $key => $file) {
            if (file_exists($file)) {
                $fileName = basename($file);
                if ($fileName == $name . '.php') {
                    echo "存在相同名称的文件：[{$fileName}]\r\n";
                    exit;
                }
            }
        }
        $name = ucwords($name);
        $time    = date('Y-m-d H:i:s');
        $content = <<<EOF
<?php
namespace App\Queue;
use Root\Queue\Queue;

/**
 * @purpose redis消费者
 * @author administrator
 * @time $time
 */
class $name extends Queue
{
    public \$param=null;

    /**
     * Test constructor.
     * @param array \$param 根据业务需求，传递业务参数，必须以一个数组的形式传递
     */
    public function __construct(array \$param)
    {
        \$this->param=\$param;
    }

    /**
     * 消费者
     * 具体的业务逻辑必须写在handle里面
     */
    public function handle(){
        //todo 这里写你的具体的业务逻辑
        var_dump(\$this->param);
    }
}
EOF;
        @file_put_contents(app_path() . '/app/queue/' . $name . '.php', $content);
        echo "创建redis队列完成\r\n";
        exit;
    }
}