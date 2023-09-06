<?php
namespace App\Command;
use Root\BaseCommand;
class Test  extends BaseCommand
{

    /** @var string $command 命令触发字段，必填 */
    public $command = 'check:test';
    
    /** 业务逻辑 必填 */
    public function handle()
    {

        $this->test_table();
        $this->info("渲染完成");
        $this->error("错误信息");
    }

    /**
     * 测试进度条
     * @return void
     */
    public function test_bar(){
        $bar = $this->createProgressBar(10);
        for ($i=1;$i<=10;$i++){
            $bar->advance();
            sleep(1);
        }
    }

    /**
     * 测试表格
     * @return void
     */
    public function test_table(){
        /** 表头 */
        $header = ['name', 'email','age','爱好'];
        /** 内容 */
        $row = [
            ['张a.三.....','1231232dsfvsa',12,'打篮球'],
            ['李////////////////////四','xzfbgadsfvgs23',34,'基尼太美'],
            ['李////////////////////四','x啥地方',21,'说唱'],
            ['时间一去不复返，只能流去不流回','171qwerqwerqwrom',89,'rap'],
            ['韩流当道','地地道道',77,'射流风机as'],
            ['cxkk','32日3日染发',34,'鸡你太美'],
            ['234365,..','地地道道哈哈哈哈',43,'nbsp;'],
        ];
        /** 渲染表格 */
        $this->table($header,$row);
    }
}