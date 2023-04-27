<?php
namespace App\Time;

class OtherTimer
{
    //定时器的业务逻辑必须写在handle方法里面，然后需要在config/timer里面配置
    public function handle(){
        //测试写入文件
        //file_put_contents(app_path().'/public/'.time().'note.txt','搜索');
        var_dump("我是定时器".date('Y-m-d H:i:s'));
    }
}
