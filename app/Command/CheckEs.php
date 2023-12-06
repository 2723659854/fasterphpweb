<?php
namespace App\Command;

use Root\ESClient;
use Root\Lib\BaseCommand;
/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2023-10-09 11:05:15
 */
class CheckEs extends BaseCommand
{

    /** @var string $command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public $command = 'check:es';
    
     /**
     * 配置参数
     * @return void
     */
    public function configure(){

    }
    
    /**
     * 清在这里编写你的业务逻辑
     * @return void
     */
    public function handle()
    {
        $this->info("请在这里编写你的业务逻辑");

        //$client = new ESClient();
        //$client->andSearch();
    }
}