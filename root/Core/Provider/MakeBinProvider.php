<?php

namespace Root\Core\Provider;

use Root\Xiaosongshu;
use Phar;
/**
 * @purpose 项目打包成二进制
 * @note 打包命令 php -d phar.readonly=0 songshu make:bin
 * @note 管理服务 songshu.bin start/restart/stop [-d]
 */
class MakeBinProvider implements IdentifyInterface
{

    /**
     * 项目打包
     * @param Xiaosongshu $app
     * @param array $param
     * @return void
     */
    public function handle(Xiaosongshu $app,array $param){

        $this->makePhar();
        echo date('Y-m-d H:i:s')."\r\n";
        echo "开始打包二进制\r\n";
        /** php 文件 */
        $sfxFile = app_path().'/root/php8.1.micro.sfx';
        $binFile = app_path().'/build/songshu.bin';
        $pharFile = app_path().'/build/songshu.phar';
        // 生成二进制文件
        file_put_contents($binFile, file_get_contents($sfxFile));
        file_put_contents($binFile, file_get_contents($pharFile), FILE_APPEND);
        // 添加执行权限
        chmod($binFile, 0755);
        echo date('Y-m-d H:i:s')."\r\n";
        echo "^-^打包二进制文件完成\r\n";
    }

    /**
     * 打包phar文件
     * @return void
     */
    public function makePhar(){
        echo date('Y-m-d H:i:s')."\r\n";
        echo "开始打包phar文件...\r\n";
        is_dir(app_path().'/build')||mkdir(app_path().'/build',0777,true);
        $phar = new Phar(app_path().'/build/songshu.phar',0,'songshu');
        $phar->startBuffering();
        $phar->setSignatureAlgorithm(Phar::SHA256);
        $phar->buildFromDirectory(app_path(),'#^(?!.*(composer.json|/.github/|/.idea/|/.git/|/.setting/|/runtime/|/vendor-bin/|/build/|/public/upload/))(.*)$#');
        $phar->setStub("#!/usr/bin/env php
<?php
define('IN_PHAR', true);
Phar::mapPhar('songshu');
require_once 'phar://songshu/songshu';
__HALT_COMPILER();
");
        $phar->stopBuffering();
        unset($phar);
    }

}