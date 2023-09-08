<?php
namespace App\Command;

use Root\BaseCommand;

class DemoCommand  extends BaseCommand
{

    /** @var string $command 命令触发字段，必填 */
    public $command = 'check:wrong';

    /** 业务逻辑 必填 */
    public function handle()
    {
        throw new \Exception("发生了错误啦");
    }
}