<?php
//todo  给框架写一个自定义命令行
require dirname(__DIR__).'/vendor/autoload.php';
require_once __DIR__.'/TestCommand.php';

use Symfony\Component\Console\Application;

$application = new Application();

// ... register commands / 注册命令
$application->add(new \console\TestCommand());
$application->run();
