<?php

namespace Root\Core\Provider;

use Root\Xiaosongshu;

/**
 * @purpose 创建sqlite模型
 */
class MakeSqliteProvider implements IdentifyInterface
{

    public function handle(Xiaosongshu $app, array $param)
    {
        $name = $param[2] ?? '';
        if (!$name) {
            echo "请输入要创建的sqlite模型名称\r\n";
            exit;
        }

        $name       = trim($name, '/');
        $controller = strtolower(app_path() . '/app/SqliteModel/' . $name . '.php');
        /**
         * 检查是否存在相同的文件
         */
        foreach (scan_dir(app_path() . '/app/SqliteModel', true) as $file) {
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
        $filePath = app_path() . '/app/SqliteModel';
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

use Root\Lib\SqliteBaseModel;
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
}