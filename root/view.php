<?php
$rule=[
    'start'=>"{",
    'end'=>"}",
];

function view($path,$param=[]){
    $content=file_get_contents(app_path().'/view/'.$path.'.html');
        $preg= '/{\$[\s\S]*?}/i';
        preg_match_all($preg,$content,$res);
        $array=$res['0'];
        $new_param=[];
        foreach ($param as $k=>$v){
            $key='{$'.$k.'}';
            $new_param[$key]=$v;
        }
        foreach ($array as $k=>$v){
            if (array_key_exists($v,$new_param)){
                if ($new_param[$v]==null){
                    $new_param[$v]='';
                }
                $content=str_replace($v,$new_param[$v],$content);
            }else{
               return no_declear('index',['msg'=>"未定义的变量".$v]);
            }
        }
    return $content;
}
function no_declear($path,$param){

    $content=file_get_contents(app_path().'/root/error/'.$path.'.html');

    if ($param){

        $preg= '/{\$[\s\S]*?}/i';
        preg_match_all($preg,$content,$res);
        $array=$res['0'];
        $new_param=[];
        foreach ($param as $k=>$v){
            $key='{$'.$k.'}';
            $new_param[$key]=$v;
        }

        foreach ($array as $k=>$v){
            if (isset($new_param[$v])){
                $content=str_replace($v,$new_param[$v],$content);
            }else{

                throw new Exception("未定义的变量".$v);
            }
        }

    }
    return $content;
}
