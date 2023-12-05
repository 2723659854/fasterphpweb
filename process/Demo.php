<?php

namespace Process;

class Demo
{
    /**
     * 逻辑处理函数
     * @return void
     */
    public function handle(){
        while (1){
            var_dump("我是一个常驻内存的进程",date('Y-m-d H:i:s'));
            sleep(5);
        }
    }

}