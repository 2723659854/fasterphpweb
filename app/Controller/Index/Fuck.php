<?php

namespace App\Controller\Index;

use Root\Request;
use Root\Response;

use App\Service\HahaService;
/**
 * @purpose 控制器
 * @author administrator
 * @time 2023-11-03 04:29:29
 */
class Fuck
{

    /**
     * @Inject
     * @var HahaService
     */
    public HahaService $hahaService;

    /**
     * index方法
     * @param Request $request 请求类
      * @return Response
     */
    public function index(Request $request):Response{

        return response(['request'=>$request->all(),'inject_service'=>$this->hahaService->back()]);
    }
}