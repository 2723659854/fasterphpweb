<?php

namespace App\Service;

class OtherService
{

    /**
     * 返回一个数组
     * @return array
     */
    public function talk():array{
        return ['list'=>[1,2,3],'count'=>3];
    }
}