<?php

namespace App\Service;

class HahaService
{
    /**
     * @Inject
     * @var OtherService 测试在服务中心继续使用注解获取另外一个服务
     */
    public OtherService $otherService;

    /**
     * back 方法返回另外一个服务提供的方法
     * @param array $param
     * @return array
     */
    public function back(array $param = []):array{

        return ['status'=>1,'msg'=>'ok','data'=>$this->otherService->talk()];
    }

}