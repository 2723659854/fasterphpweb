<?php

namespace Process;

use Root\Timer;

class CornTask
{

    public static function handle(array $params){

        var_dump('我是静态方法');
    }

    public  function say(){
        var_dump("我是动态方法");
    }

}