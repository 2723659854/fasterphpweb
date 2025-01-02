<?php

require_once __DIR__.'/consume.php';


try {
    for ($i = 0; $i <= 100; $i++) {
        (new Consume())->publish(['time' => date('Y-m-d H:i:s'), 'num' => $i]);
        echo "投递{$i}\n";
        sleep(1);
    }
}catch (Exception $e){
    echo $e->getMessage();
}
var_dump('投递完成');

