<?php
return [
    ['GET', '/', [App\Controller\Index\Index::class, 'index']],
    ['GET', '/index/demo/index', [\App\Controller\Admin\Index::class, 'index']],
];