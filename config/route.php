<?php
return [

    /** 首页 */
    ['GET', '/', [App\Controller\Index\Index::class, 'index']],
    ['POST', '/test', [App\Controller\Index\Index::class, 'json']],
    //['POST', '/', [App\Controller\Index\Index::class, 'index']],
    /** 路由测试 */
    ['GET', '/index/demo/index', [\App\Controller\Admin\Index::class, 'index']],
    ['GET', '/just1', [\App\Controller\Admin\Index::class, 'index']],

    /** 上传文件 */
    ['GET', '/upload', [\App\Controller\Admin\Index::class, 'upload']],
    /** 保存文件 */
    ['post', '/store', [\App\Controller\Admin\Index::class, 'store']],
    /** 缓存存取 */
    ['get', '/cache', [\App\Controller\Index\Index::class, 'cache']],
    /** 返回json */
    ['get', '/json', [\App\Controller\Index\Index::class, 'json']],
    /** 数据库 */
    ['get', '/database', [\App\Controller\Index\Index::class, 'database']],
    ['get', '/fuck/query', [\App\Controller\Index\Index::class, 'database']],
    /** 数据库写入 */
    ['get', '/insert', [\App\Controller\Index\Index::class, 'insert']],
    /** base64 文件上传 */
    ['get', '/base64', [\App\Controller\Index\Index::class, 'upload']],
    /** base64 文件保存 */
    ['post', '/base64_store', [\App\Controller\Index\Index::class, 'store']],
    /** 测试redis队列 */
    ['get', '/queue', [\App\Controller\Index\Index::class, 'queue']],
    /** 测试rabbitmq队列 */
    ['get', '/rabbitmq', [\App\Controller\Index\Index::class, 'rabbitmq']],
    /** 文件下载 */
    ['get', '/download', [\App\Controller\Index\Index::class, 'download']],
    /** 测试门面类facade */
    ['get', '/facade', [\App\Controller\Index\Index::class, 'facade']],
    /** 测试es搜索 */
    ['get', '/es', [\App\Controller\Index\Index::class, 'elasticsearch']],
    /** 测试中间件 */
    ['GET','/middle',[\App\Controller\Index\Index::class,'middle'],[\App\Middleware\MiddlewareA::class,\App\Middleware\MiddlewareB::class]],
    /** 直播测试页面*/
    ['GET','/play',[\App\Controller\Index\Video::class,'index']]
];