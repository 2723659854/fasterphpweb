<?php

namespace Root;

class Command
{
    /**
     * 生成定时器数据库
     * @return void
     */
    public function makeTimeDatabase()
    {
        TimerData::first();
    }

    /**
     * 创建命令行
     * @param string $name
     * @return void
     */
    public function make_command(string $name): void
    {
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

    /**
     * 创建模型
     * @param string $name
     * @return void
     */
    public function make_model(string $name): void
    {
        if (!$name) {
            echo "请输入要创建的模型名称\r\n";
            exit;
        }

        $name       = trim($name, '/');
        $controller = strtolower(app_path() . '/app/model/' . $name . '.php');
        /**
         * 检查是否存在相同的文件
         */
        foreach (scan_dir(app_path() . '/app/model', true) as $file) {
            if (file_exists($file)) {
                $fileName = strtolower($file);
                if ($fileName == $controller) {
                    echo "存在相同名称的文件：[{$fileName}]\r\n";
                    exit;
                }
            }
        }
        $name = array_filter(explode('/', $name));

        $className = ucfirst(strtolower(array_pop($name)));
        $nameSpace = "App\Model";
        if ($name) {
            foreach ($name as $dir) {
                $dir       = ucfirst(strtolower($dir));
                $nameSpace = $nameSpace . "\\" . $dir;
            }
        }
        $filePath = app_path() . '/app/model';
        foreach ($name as $dir) {
            $filePath = $filePath . '/' . strtolower($dir);
        }
        if (!is_dir($filePath)) {
            mkdir($filePath, '0777', true);
        }
        $filePath = $filePath . "/" . $className . '.php';

        $time = date('Y-m-d H:i:s');
        /** 表名 */
        $lower_name = strtolower($className);
        $content    = <<<EOF
<?php

namespace $nameSpace;

use Root\Model;
/**
 * @purpose mysql模型
 * @author administrator
 * @time $time
 */
class $className extends Model
{
    /** @var string \$table 建议指定表名，否则系统根据模型名推断表名，可能会不准确 */
    public string \$table = "$lower_name";

}
EOF;

        file_put_contents($filePath, $content);
        echo "创建模型完成\r\n";
        exit;
    }

    /**
     * 创建sqlite模型
     * @param string $name
     * @return void
     */
    public function make_sqlite_model(string $name): void
    {
        if (!$name) {
            echo "请输入要创建的sqlite模型名称\r\n";
            exit;
        }

        $name       = trim($name, '/');
        $controller = strtolower(app_path() . '/app/sqliteModel/' . $name . '.php');
        /**
         * 检查是否存在相同的文件
         */
        foreach (scan_dir(app_path() . '/app/sqliteModel', true) as $file) {
            if (file_exists($file)) {
                $fileName = strtolower($file);
                if ($fileName == $controller) {
                    echo "存在相同名称的文件：[{$fileName}]\r\n";
                    exit;
                }
            }
        }
        $name = array_filter(explode('/', $name));

        $className = ucfirst(strtolower(array_pop($name)));
        $nameSpace = "App\SqliteModel";
        if ($name) {
            foreach ($name as $dir) {
                $dir       = ucfirst(strtolower($dir));
                $nameSpace = $nameSpace . "\\" . $dir;
            }
        }
        $filePath = app_path() . '/app/sqliteModel';
        foreach ($name as $dir) {
            $filePath = $filePath . '/' . strtolower($dir);
        }
        if (!is_dir($filePath)) {
            mkdir($filePath, '0777', true);
        }
        /** 文件名 */
        $filePath = $filePath . "/" . $className . '.php';

        /** 存放目录 */
        $location = implode('/', $name);
        /** 当前时间 */
        $time = date('Y-m-d H:i:s');
        /** 表名 */
        $lower_name = strtolower($className);
        $content    = <<<EOF
<?php

namespace $nameSpace;

use Root\SqliteBaseModel;
/**
 * @purpose sqlite 模型
 * @author administrator
 * @time $time
 */
class $className extends SqliteBaseModel
{

    /** 存放目录：请修改为你自己的字段，真实路径为config/sqlite.php里面absolute设置的路径 + \$dir ,例如：/usr/src/myapp/fasterphpweb/sqlite/datadir/hello/talk */
    public string \$dir = '$location';

    /** 表名称：请修改为你自己的表名称 */
    public string \$table = '$lower_name';

    /** 表字段：请修改为你自己的字段 */
    public string \$field ='id INTEGER PRIMARY KEY,name varhcar(24),created text(12)';

}
EOF;

        file_put_contents($filePath, $content);
        echo "创建sqlite模型完成\r\n";
        exit;
    }

    /**
     * 创建控制器
     * @param string $name 控制器名称
     * @return void
     */
    public function make_controller(string $name): void
    {
        if (!$name) {
            echo "请输入要创建的控制器名称\r\n";
            exit;
        }
        $time = date('Y-m-d H:i:s');

        $name       = trim($name, '/');
        $controller = strtolower(app_path() . '/app/controller/' . $name . '.php');
        /**
         * 检查是否存在相同的文件
         */
        foreach (scan_dir(app_path() . '/app/controller', true) as $key => $file) {
            if (file_exists($file)) {
                $fileName = strtolower($file);
                if ($fileName == $controller) {
                    echo "存在相同名称的文件：[{$fileName}]\r\n";
                    exit;
                }
            }
        }
        $name = array_filter(explode('/', $name));
        if (count($name) < 2) {
            echo "必须是模块名称/控制器名称\r\n";
            exit;
        }
        $className = ucfirst(strtolower(array_pop($name)));
        $nameSpace = "App\Controller";
        if ($name) {
            foreach ($name as $dir) {
                $dir       = ucfirst(strtolower($dir));
                $nameSpace = $nameSpace . "\\" . $dir;
            }
        }
        $filePath = app_path() . '/app/controller';
        foreach ($name as $dir) {
            $filePath = $filePath . '/' . strtolower($dir);
        }
        if (!is_dir($filePath)) {
            mkdir($filePath, '0777', true);
        }
        $filePath = $filePath . "/" . $className . '.php';
        $content  = <<<EOF
<?php

namespace $nameSpace;

use Root\Request;
use Root\Response;
/**
 * @purpose 控制器
 * @author administrator
 * @time $time
 */
class $className
{
    /**
     * index方法
     * @param Request \$request 请求类
      * @return Response
     */
    public function index(Request \$request):Response{
        return response(\$request->all());
    }
}
EOF;
        file_put_contents($filePath, $content);
        echo "创建控制器完成\r\n";
        exit;
    }
}