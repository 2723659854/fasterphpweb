<?php

namespace Root\Core\Provider;

use Root\Lib\MiddlewareInterface;
use Root\Request;
use Root\Response;
use Root\Xiaosongshu;

/**
 * @purpose 创建中间件
 */
class MakeMiddlewareProvider implements IdentifyInterface
{

    public function handle(Xiaosongshu $app, array $param)
    {
        $name =$param[2] ?? '';
        if (!$name) {
            echo "请输入要创建的中间件名称\r\n";
            exit;
        }

        $name       = trim($name, '/');
        $controller = strtolower(app_path() . '/app/Middleware/' . $name . '.php');
        /**
         * 检查是否存在相同的文件
         */
        foreach (scan_dir(app_path() . '/app/middleware', true) as $file) {
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
        $nameSpace = "App\Middleware";
        if ($name) {
            foreach ($name as $dir) {
                $dir       = ucfirst(strtolower($dir));
                $nameSpace = $nameSpace . "\\" . $dir;
            }
        }
        $filePath = app_path() . '/app/middleware';
        foreach ($name as $dir) {
            $filePath = $filePath . '/' . strtolower($dir);
        }
        if (!is_dir($filePath)) {
            mkdir($filePath, '0777', true);
        }
        $filePath = $filePath . "/" . $className . '.php';

        $time = date('Y-m-d H:i:s');

        $content    = <<<EOF
<?php
namespace $nameSpace;
use Root\Lib\MiddlewareInterface;
use Root\Request;
use Root\Response;

/**
 * @purpose 中间件
 * @author administrator
 * @time $time
 */
class $className implements MiddlewareInterface
{
    public function process(Request \$request, callable \$next):Response
    {
        //todo 这里处理你的逻辑
        
        return \$next(\$request);
    }
}
EOF;

        file_put_contents($filePath, $content);
        echo "创建中间件完成\r\n";
        exit;
    }
}