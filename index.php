<?php

$data = function (){
    echo "ok";
};
$object = new \stdClass();
$object->name = 'tome';
$object->handle=function (){
    var_dump(123);
};




