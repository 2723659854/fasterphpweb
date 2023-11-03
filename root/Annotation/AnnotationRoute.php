<?php
namespace Root\Annotation;
use Root\Annotation\Mapping\Middleware;
use Root\Annotation\Mapping\Middlewares;
use Root\Route;
use Root\Annotation\Mapping\RequestMapping;

/**
 * @purpose 加载注解路由
 */
Class AnnotationRoute{

    /**
     * 加载路由
     * @return void
     */
    public static function loadRoute(){
        /** @var  $dir_iterator *递归遍历目录查找控制器自动设置路由 */
        $dir_iterator = new \RecursiveDirectoryIterator(app_path().'/app/Controller');
        $iterator = new \RecursiveIteratorIterator($dir_iterator);

        foreach ($iterator as $file) {

            /** 忽略目录和非php文件 */
            if (is_dir($file) || $file->getExtension() != 'php') {
                continue;
            }

            $file_path = str_replace('\\', '/', $file->getPathname());


            // 根据文件路径是被类名

            $class = substr(substr($file_path, strlen(app_path())),0,-4);
            $class_name_array = explode('/',$class);
            /** @var  $class_name *根据文件路径获取类名 */
            $class_name = [];
            foreach ($class_name_array as $value){
                $class_name []= ucfirst($value);
            }

            $class_name = implode('\\',$class_name);
            if (!class_exists($class_name)) {
                echo "Class $class_name not found, skip route for it\n";
                continue;
            }
            if (floatval(PHP_VERSION) > 8)
            {

                $controller = new \ReflectionClass($class_name);
                foreach ($controller->getMethods(\ReflectionMethod::IS_PUBLIC) as $k => $reflectionMethod) {
                    $middlewares = '';
                    $path        = "";
                    $methods     = "";

                    foreach ($reflectionMethod->getAttributes() as $kk => $attribute) {
                        if ($attribute->getName() === Middleware::class)
                        {
                            $middlewares = $attribute->getArguments();
                        }
                        if ($attribute->getName() === Middlewares::class)
                        {
                            $middlewares = $attribute->getArguments();
                        }
                        if ($attribute->getName() === RequestMapping::class)
                        {
                            $path = $attribute->getArguments()["path"]?? "";
                            $methods = $attribute->newInstance()->setMethods();
                        }
                    }


                    if (!empty($methods) and !empty($path))
                    {
                        if (!empty($middlewares))
                        {
                            Route::add($methods, $path, [$class_name, $reflectionMethod->name],$middlewares);
                        }else{
                            Route::add($methods, $path, [$class_name, $reflectionMethod->name]);
                        }
                    }
                }

            }
        }
    }
}




