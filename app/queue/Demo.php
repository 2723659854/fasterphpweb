<?php
namespace App\Queue;
use Root\Queue\Queue;

/**
 * @purpose redis消费者
 * @author administrator
 * @time 2023-10-31 03:44:50
 */
class Demo extends Queue
{
    public $param=null;

    /**
     * Test constructor.
     * @param array $param 根据业务需求，传递业务参数，必须以一个数组的形式传递
     */
    public function __construct(array $param)
    {
        $this->param=$param;
    }

    /**
     * 消费者
     * 具体的业务逻辑必须写在handle里面
     */
    public function handle(){
        //todo 这里写你的具体的业务逻辑
        var_dump($this->param);
    }
}