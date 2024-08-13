<?php
namespace App\Command;
use Xiaosongshu\Progress\ProgressBar;
use Root\Lib\BaseCommand;
/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2023-09-27 07:36:14
 */
class hello extends BaseCommand
{

    /** @var string $command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public $command = 'hello';
    
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

        /** 创建进度条 */
        $bar = new ProgressBar();
        /** 总长度 */
        $bar->createBar(200);
        /** 设置颜色：紫色 （非必选 ，默认白色） */
        $bar->setColor('purple');
        /** 更新进度条 */
        for ($i=1;$i<=10;$i++){
            //your code ......
            /** 模拟业务耗时 */
            sleep(1);
            /** 更新进度条 */
            $bar->advance(2);
        }
    }
}