<?php
namespace Root;

use Root\Lib\RedisCache;

/**
 * @purpose 缓存操作客户端
 */
class Cache extends RedisCache{

    public function __construct(){
        parent::__construct();
    }
}
