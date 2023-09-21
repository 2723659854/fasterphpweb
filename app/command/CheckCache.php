<?php
namespace App\Command;

use Root\BaseCommand;
use Root\Cache;

/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2023-09-21 07:00:29
 */
class CheckCache extends BaseCommand
{

    /** @var string $command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public $command = 'check:cache';
    
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
        /** 获取必选参数 */
        //var_dump($this->getOption('argument'));
        /** 获取可选参数 */
        //var_dump($this->getOption('option'));
        $this->info("请在这里编写你的业务逻辑");
        Cache::set('foo','bar');
        var_dump(Cache::get('foo'));
        $cache = new Cache();
        var_dump($cache->get('foo'));

    }
}