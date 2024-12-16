<?php
namespace App\Command;

use phpseclib3\Net\SSH2;
use Root\Lib\BaseCommand;
/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2024-12-16 08:57:51
 * @note 使用php实现链接ssh服务器，但是这个只做了简单的操作，没有实现命令行实时交互
 * @note 如果使用默认的ssh2扩展，不能正常链接，所以使用了第三方扩展：phpseclib/phpseclib
 */
class CheckSSH extends BaseCommand
{

    /** @var string $command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public $command = 'check:ssh';
    
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

        $this->info("请在这里编写你的业务逻辑");
        /** 实例化一个ssh类 */
        $ssh = new SSH2('your server host',22);
        /** 登陆服务器 ：使用账号和密码登陆*/
        if (!$ssh->login('root', 'your password')) {
            exit('Login Failed');
        }
        /** 发送pty命令 比如执行docker命令 */
        //$ssh->enablePTY();
        /** 执行shell命令，并获取执行结果，官方建议shiyong PHP_EOL 作为分隔符，但是实际测试是不行的，或者使用分号;分隔，测试可以使用 */
        //$output=$ssh->exec( "set -e && cd /www && cd server && ls");
        $output=$ssh->exec( "set -e ; cd /www ; cd wwwroot ; ls");
        print_r($output);
        $content = $ssh->exec("sudo docker exec -it php8 /bin/sh");
        print_r($content);
        $ssh->disconnect();
    }
}