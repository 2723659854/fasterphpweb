<?php

namespace Root\Core\Provider;

use Root\Command;
use Root\Xiaosongshu;

/**
 * @purpose 创建命令行工具
 */
class MakeCommandProvider implements IdentifyInterface
{

    public function handle(Xiaosongshu $app, array $param)
    {
        $name = $param[2] ?? '';
        if (!$name) {
            echo "请输入要创建的命令文件名称\r\n";
            exit;
        }
        foreach (scan_dir(command_path(), true) as $key => $file) {
            if (file_exists($file)) {
                $fileName = basename($file);
                if ($fileName == $name . '.php') {
                    echo "存在相同名称的文件：[{$fileName}]\r\n";
                    exit;
                }
            }
        }
        $time    = date('Y-m-d H:i:s');
        $content = <<<EOF
<?php
namespace App\Command;

use Root\BaseCommand;
/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time $time
 */
class $name extends BaseCommand
{

    /** @var string \$command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public \$command = '$name';
    
     /**
     * 配置参数
     * @return void
     */
    public function configure(){
        /** 必选参数 */
        \$this->addArgument('argument','这个是参数argument的描述信息');
        /** 可传参数 */
        \$this->addOption('option','这个是option参数的描述信息');
    }
    
    /**
     * 清在这里编写你的业务逻辑
     * @return void
     */
    public function handle()
    {
        /** 获取必选参数 */
        var_dump(\$this->getOption('argument'));
        /** 获取可选参数 */
        var_dump(\$this->getOption('option'));
        \$this->info("请在这里编写你的业务逻辑");
    }
}
EOF;
        @file_put_contents(app_path() . '/app/command/' . $name . '.php', $content);
        echo "创建命令行工具完成\r\n";
        exit;
    }
}