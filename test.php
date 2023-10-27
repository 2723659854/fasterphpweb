<?php

declare(ticks = 1);
echo getmypid();//获取当前进程id
pcntl_signal(SIGINT,function(){
    echo "你给我发了SIGINT信号";
});
while(1){
    sleep(1);
}
