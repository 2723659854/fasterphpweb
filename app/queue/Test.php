<?php
namespace App\Queue;
use Root\Queue\Queue;

//队列  必须继承 Root\Queue\Queue类
class Test extends Queue
{
    public $param=null;

    /**
     * Test constructor.
     * @param array $param 根据业务需求，传递业务参数，必须以一个数组的形式传递
     */
    public function __construct($param=[])
    {
        $this->param=$param;
    }

    /**
     * 消费者
     * 具体的业务逻辑必须写在handle里面
     */
    public function handle(){
        echo "我是";
        echo $this->param['name'];echo "，今年";
        echo $this->param['age'];echo "岁。\r\n";
    }
}