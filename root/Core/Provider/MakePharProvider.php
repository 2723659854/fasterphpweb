<?php

namespace Root\Core\Provider;

use Root\Queue\RedisQueueConsumer;
use Root\Xiaosongshu;
use Phar;
/**
 * @purpose 项目打包
 * @note 打包命令 php -d phar.readonly=0 songshu make:phar
 * @note 管理服务 php -d phar.readonly=0 songshu.phar start/restart/stop [-d]
 */
class MakePharProvider implements IdentifyInterface
{

    /**
     * 项目打包
     * @param Xiaosongshu $app
     * @param array $param
     * @return void
     */
    public function handle(Xiaosongshu $app,array $param){

        echo date('Y-m-d H:i:s')."\r\n";
        echo "开始打包\r\n";
        is_dir(app_path().'/build')||mkdir(app_path().'/build',0777,true);
        $phar = new Phar(app_path().'/build/songshu.phar',0,'songshu');
        echo "开始缓冲Phar写入操作，不要修改磁盘上的Phar对象\r\n";
        $phar->startBuffering();
        echo "为phar设置签名算法并应用它。\r\n";
        $phar->setSignatureAlgorithm(Phar::SHA256);
        echo "开始打包...\r\n";
        $phar->buildFromDirectory(app_path(),'#^(?!.*(composer.json|/.github/|/.idea/|/.git/|/.setting/|/runtime/|/vendor-bin/|/build/))(.*)$#');
        /** 设置引导文件 */
        $phar->setStub("#!/usr/bin/env php
<?php
define('IN_PHAR', true);
Phar::mapPhar('songshu');
require_once 'phar://songshu/songshu';
__HALT_COMPILER();
");
        echo "保存打包文件...\r\n";
        $phar->stopBuffering();
        unset($phar);
        echo "文件位置：".app_path()."/build/songshu.phar\r\n";
        echo "管理服务：php -d phar.readonly=0 songshu.phar start/restart/stop [-d]\r\n";
        echo date('Y-m-d H:i:s')."\r\n";
        echo "^-^打包完成!\r\n";
    }

}