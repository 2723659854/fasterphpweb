<?php

namespace Root\Core\Provider;

use Root\Xiaosongshu;

/**
 * @purpose 创建控制器
 */
class MakeControllerProvider implements IdentifyInterface
{

    public function handle(Xiaosongshu $app, array $param)
    {
        $name = $param[2] ?? '';
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