<?php

namespace Process;

class Demo
{
    /**
     * 逻辑处理函数
     * @return void
     * @note 这里面写你自己的逻辑，可以是监听端口，也可以是其他常驻内存进程
     */
    public function handle(array $param){
        while (1){
            var_dump("我是一个常驻内存的进程",date('Y-m-d H:i:s'));
            sleep(5);
        }
    }

}