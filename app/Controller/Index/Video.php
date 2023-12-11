<?php

namespace App\Controller\Index;

use App\Service\HahaService;
use Root\Annotation\Mapping\RequestMapping;
use Root\Request;
use Root\Response;
/**
 * @purpose 控制器
 * @author administrator
 * @time 2023-10-11 07:02:03
 */
class Video
{

    /**
     * @Inject
     * @var HahaService 测试服务注解
     */
    public HahaService $hahaService;

    /**
     * 测试注解
     * @param Request $request
     * @return Response
     */
    #[RequestMapping(methods:'get',path:'/video/inject')]
    public function testInject(Request $request):Response{
        return \response($this->hahaService->back());
    }



    /**
     * index方法
     * @param Request $request 请求类
      * @return Response
     * @note 直播演示
     */
    #[RequestMapping(methods:'get',path:'/video/play')]
    public function index(Request $request):Response{
        return view('video/play');
    }
}