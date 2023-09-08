<?php
namespace App\Command;
use Root\BaseCommand;

/**
 * 创建模型
 */
class MakeModel  extends BaseCommand
{

    /** @var string $command 创建模型 */
    public $command = 'make:model';


    public function configure()
    {
        /** 设置表名称 */
        $this->addArgument('name');
    }

    /** 业务逻辑 必填 */
    public function handle()
    {
        if (!$name=$this->getArgument('name')){
            $this->error("请设置表名称");
            return;
        }
        /** 表名 */
        $lower_name = strtolower($name);
        /** 模型名称 */
        $name = ucfirst($lower_name);
        $content = <<<EOF
<?php

namespace App\Model;

use Root\Model;

class $name extends Model
{
    /** @var string \$table 建议指定表名，否则系统根据模型名推断表名，可能会不准确 */
    public string \$table = "$lower_name";

}
EOF;
        /** 文件名称 */
        $fileName = app_path().'/app/model/'.$name.'.php';
        if (file_exists($fileName)){
            $this->error("[$name]模型已存在");
            return ;
        }else{
            file_put_contents($fileName,$content);
        }
        $this->info("[$name]创建完成");

    }
}