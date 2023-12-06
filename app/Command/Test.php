<?php
namespace App\Command;
use App\Rabbitmq\Demo;
use App\Rabbitmq\Demo2;
use phpseclib3\Net\SSH2;
use Root\Lib\BaseCommand;
class Test  extends BaseCommand
{

    /** @var string $command 命令触发字段，必填 */
    public $command = 'check:test';
    
    /** 业务逻辑 必填 */
    public function handle()
    {
        /** 助手函数 */
        $message = 'hello ,you are a student !';
        (new Demo2())->publish(['status'=>1,'msg'=>'ok']);
        var_dump("投递消息完成");
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

    /**
     * ssh远程操作服务器
     * @return void
     * @note 如果安装扩展 composer require phpseclib/phpseclib 失败，
     * @note 那就直接下载这个扩展https://github.com/phpseclib/phpseclib，然后手动拖动到vendor目录下，然后composer.json里面添加"phpseclib/phpseclib": "*"即可
     */
    public function test_ssh(){
        /** 实例化一个ssh类 */
        $ssh = new SSH2('ip地址，必须开启22端口 ');
        /** 登陆服务器 ：使用账号和密码登陆*/
        if (!$ssh->login('账户', '密码')) {
            exit('Login Failed');
        }
        /** 执行shell命令，并获取执行结果，官方建议shiyong PHP_EOL 作为分隔符，但是实际测试是不行的，或者使用分号;分隔，测试可以使用 */
        //$output=$ssh->exec( "要执行的命令");
        $output=$ssh->exec( "set -e ; cd /www ; cd server ; cd test;ls");
        /** 关闭连接 */
        $ssh->disconnect();
        print_r($output);
    }
}