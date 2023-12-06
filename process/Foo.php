<?php

namespace Process;

class Foo
{

    public function handle($config){
        while (1){
            //var_dump($config);
            var_dump(date('Y-m-d H:i:s'));
            sleep(8);
        }
    }

}