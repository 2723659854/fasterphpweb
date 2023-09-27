<?php
namespace App\Command;

use Root\Lib\BaseCommand;

class DemoCommand  extends BaseCommand
{

    /** @var string $command 命令触发字段，必填 */
    public $command = 'check:wrong';

    public function configure()
    {
        $this->addArgument('name','请输入姓名');
        $this->addArgument('age','请输入年龄');
        $this->addOption('location','请输入定位');
    }

    /** 业务逻辑 必填 */
    public function handle()
    {
        throw new \Exception("发生了错误啦");
    }
}