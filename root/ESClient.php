<?php

namespace Root;
use Root\Lib\ElasticSearchClient;
/**
 * elasticsearch 客户端
 * Class ESClient
 * @author yanglong
 * @date 2022年11月21日 20:02:35
 * @example 此类可以当做模型的基类使用，需要把这个类里面的index和type分别改成$this->index和$this->type,然后创建新的模型继承这一个类，并在模型中设置index和type，nodes
 */
class ESClient extends ElasticSearchClient{
    public function __construct()
    {
        parent::__construct();
    }
}
