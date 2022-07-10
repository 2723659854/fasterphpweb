<?php
namespace App\Time;

class Test{
    //定时器的业务逻辑必须写在handle方法里面，然后需要在config/timer里面配置
    public function handle(){
        file_put_contents(app_path().'/public/book/'.time().'book.txt','搜索');
    }

}
