<?php

require_once __DIR__.'/consume.php';



var_dump('开始消费');

(new Consume())->consume();