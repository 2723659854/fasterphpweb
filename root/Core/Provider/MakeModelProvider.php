<?php

namespace Root\Core\Provider;

use Root\Xiaosongshu;

/**
 * @purpose 创建mysql模型
 */
class MakeModelProvider implements IdentifyInterface
{

    public function handle(Xiaosongshu $app, array $param)
    {
        $name =$param[2] ?? '';
        if (!$name) {
            echo "请输入要创建的模型名称\r\n";
            exit;
        }

        $name       = trim($name, '/');
        $controller = strtolower(app_path() . '/app/Model/' . $name . '.php');
        /**
         * 检查是否存在相同的文件
         */
        foreach (scan_dir(app_path() . '/app/Model', true) as $file) {
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
        $filePath = app_path() . '/app/Model';
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
}