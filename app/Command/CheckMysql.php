<?php
namespace App\Command;

use App\Model\Admin;
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
        $this->info("测试mysql事务");
        /** 开启事务 */
        $transAction = Admin::startTransaction();
        try {
            /** 如果查询报错，那么写入就会失败 */
            $query = Admin::where('id','=',1398)->first();
            var_dump($query);
            Admin::insert([
                'phone'=>'125896325',
                'nickname'=>'tom',
                'password'=>md5(time())
            ]);
            /** 提交事务 */
            $transAction->commit();
        }catch (\Exception $exception){
            /** 发生了异常，事务回滚 */
            $transAction->rollback();
            /** 打印报错信息 */
            var_dump($exception->getMessage());
        }

    }
}