<?php
function route($url){
    if ($url){
        $url=array_filter(explode('/',$url));
    }else{
        $url=[];
    }
    $new_url=[];
    foreach ($url as $k=>$v){
        $new_url[]=$v;
    }
    $num=count($new_url);
    switch ($num){
        case 0:
            return '/app/controller/index/Index.php@APP\\Controller\\Index\\Index@index';
            break;
        case 1:
            return '/app/controller/index/Index.php@App\\Controller\\Index\\Index@'.$new_url[0];
            break;
        case 2:
            return '/app/controller/index/'.ucwords($new_url[0]).'.php@'.'App\\Controller\\Index\\'.ucwords($new_url[0]).'@'.$new_url[1];
            break;
        case 3:
            return '/app/controller/'.strtolower($new_url[0]).'/'.ucwords($new_url[1]).'.php@'.'App\\Controller\\'.ucwords($new_url[0]).'\\'.ucwords($new_url[1]).'@'.$new_url[2];
            break;
        default:
            $file = '/app/controller';
            $class = 'App\\Controller';
            $method = array_pop($new_url);
            $className = ucwords(strtolower(array_pop($new_url)));
            foreach ($new_url as $k=>$v){
                $file=$file.'/'.strtolower($v);
                $class=$class.'\\'.ucwords($v);
            }
            return $file.'/'.$className.'.php@'.$class.'\\'.$className.'@'.$method;

            //return '/app/controller/index/Index.php@APP\\Index\\Controller\\Index@index';
    }
}
