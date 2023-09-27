<?php
return [
    /** 首页 */
    ['GET', '/', [App\Controller\Index\Index::class, 'index']],
    /** 路由测试 */
    ['GET', '/index/demo/index', [\App\Controller\Admin\Index::class, 'index']],
    /** 上传文件 */
    ['GET', '/upload', [\App\Controller\Admin\Index::class, 'upload']],
    /** 保存文件 */
    ['post', '/store', [\App\Controller\Admin\Index::class, 'store']],
];