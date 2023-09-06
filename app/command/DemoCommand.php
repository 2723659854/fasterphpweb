<?php
namespace App\Command;
use Root\BaseCommand;
class DemoCommand  extends BaseCommand
{

    /** @var string $command 命令触发字段，必填 */
    public $command = 'your:command';

    /** 业务逻辑 必填 */
    public function handle()
    {
        echo "请在这里写你的业务逻辑\r\n";
    }
}