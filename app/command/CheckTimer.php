<?php
namespace App\Command;

use Root\Lib\BaseCommand;
use Root\Timer;
use Root\TimerData;

/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2023-09-22 06:23:27
 */
class CheckTimer extends BaseCommand
{

    /** @var string $command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public $command = 'check:timer';
    
     /**
     * 配置参数
     * @return void
     */
    public function configure(){
        /** 必选参数 */
        $this->addArgument('argument','这个是参数argument的描述信息');
        /** 可传参数 */
        $this->addOption('option','这个是option参数的描述信息');
    }
    
    /**
     * 清在这里编写你的业务逻辑
     * @return void
     */
    public function handle()
    {
        /** 使用回调函数投递定时任务 */
        $first = Timer::add('5',function ($username){
            echo date('Y-m-d H:i:s');

            echo $username."\r\n";
        },['投递的定时任务'],true);
        echo "定时器id:".$first."\r\n";
        /** 根据id删除定时器 */
        Timer::delete($first);
        /** 使用数组投递定时任务 */
        Timer::add('5',[\Process\CornTask::class,'say'],['投递的定时任务'],true);
        /** 获取所有正在运行的定时任务 */
        print_r(Timer::getAll());
        /** 清除所有定时器 */
        Timer::deleteAll();

    }
}