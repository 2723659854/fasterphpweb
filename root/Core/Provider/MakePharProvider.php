<?php

namespace Root\Core\Provider;

use Root\Queue\RedisQueueConsumer;
use Root\Xiaosongshu;
use Phar;
/**
 * @purpose 项目打包
 */
class MakePharProvider implements IdentifyInterface
{

    public function handle(Xiaosongshu $app,array $param){
        $phar = new Phar(app_path().'/build/songshu.phar',0,'songshu');

        $phar->startBuffering();
        $phar->setSignatureAlgorithm(Phar::SHA256);
        $phar->buildFromDirectory(app_path(),'#^(?!.*(composer.json|/.github/|/.idea/|/.git/|/.setting/|/runtime/|/vendor-bin/|/build/|vendor/webman/admin))(.*)$#');

        $phar->setStub("#!/usr/bin/env php
<?php
define('IN_PHAR', true);
Phar::mapPhar('songshu');
require_once 'phar://songshu/songshu';
__HALT_COMPILER();
");
        $phar->stopBuffering();
        unset($phar);
        var_dump("生成压缩包完成");
    }

}