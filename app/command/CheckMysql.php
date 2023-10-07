<?php
namespace App\Command;

use App\Model\User;
use Root\Lib\BaseCommand;
/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2023-09-21 06:52:09
 */
class CheckMysql extends BaseCommand
{

    /** @var string $command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public $command = 'check:mysql';
    
     /**
     * 配置参数
     * @return void
     */
    public function configure(){
        /** 必选参数 */
        //$this->addArgument('argument','这个是参数argument的描述信息');
        /** 可传参数 */
        //$this->addOption('option','这个是option参数的描述信息');
    }
    
    /**
     * 清在这里编写你的业务逻辑
     * @return void
     */
    public function handle()
    {
        $this->info("请在这里编写你的业务逻辑");

        $time = time();
        $count = 100000;

        $this->info("开始查询数据库");
        for($i=0;$i<=$count;$i++){
            //User::where('id','=',1)->first();
            User::query('select * from users where id = 1');
        }
        $time2 = time();
        $spend = $time2 - $time;
        $this->info("查询数据库{$count}次，耗时{$spend}s");
    }
}