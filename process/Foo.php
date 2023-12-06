<?php

namespace Process;

class Foo
{

    public function handle($config){
        while (1){
            //var_dump($config);
            var_dump("自定义进程");
            sleep(8);
        }
    }

}