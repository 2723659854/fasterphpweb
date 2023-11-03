<?php

namespace APP\Facade;

use Root\Lib\Facade;

//门面类，方便静态调用其他类
class Cache extends Facade
{
    protected static function getAccessor()
    {
        return 'Root\Cache';
    }

}
