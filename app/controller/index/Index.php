<?php

namespace App\Controller\Index;

use App\Model\User;
use App\Model\Book;
use Root\Request;
use Root\Cache;
use App\Queue\Test;
use APP\Facade\Cache as Fcache;

class Index
{
    //todo 以下代码均为演示代码
    //如果需要渲染模板就调用view 不需要渲染模板就不调用view
    public function __construct()
    {

    }

    //默认首页
    public function index()
    {
        //return "hello world!";
        //模板在根目录下的view目录里面
        return view('/index/index', ['time' => date('Y-m-d H:i:s')]);
    }

    //数据查询
    public function database(Request $request)
    {
        //print_r($request);
        $var = $request->param('var');
        $str = $request->param('str');
        $user = new User();
        $data = $user->where('username', '=', 'test')->first();
        $app_name = config('app')['app_name'];
        //模板渲染 参数传递
        return view('index/database', ['var' => $var, 'str' => date('Y-m-d H:i:s'), 'user' => json_encode($data), 'app_name' => $app_name]);
    }

    //测试数据写入
    public function say(Request $request)
    {

        //var_dump($request);
        $book = new Book();
        $book->insert([
            'name' => '哈利波特',
            'price' => 15.23,
            'create_time' => time(),
            'update_time' => time(),
        ]);
        return view('index/say');
    }

    //测试文件上传，以及缓存用法
    public function upload()
    {
        //Fcache::set('name', '小松鼠');
        return view('index/upload', ['cache' => 2]);
    }

    //测试表单提交和文件上传
    public function store(Request $request)
    {
        //var_dump($request->file());
        //普通上传文件
        if ($request->file('one')){
            $file=$request->file('one');
            $name=$file['filename']?$file['filename']:'test.png';
            $content=$file['content'];
            $fp1=fopen(app_path().'/public/'.$name,'wb');
            fwrite($fp1,$content);
            fclose($fp1);
        }
        if ($request->file('two')){
            $file=$request->file('two');
            $name=$file['filename']?$file['filename']:'test.png';
            $content=$file['content'];
            $fp1=fopen(app_path().'/public/'.$name,'wb');
            fwrite($fp1,$content);
            fclose($fp1);
        }
        //base64文件上传
        $picture=$request->param('picture');
        if ($picture&&(strlen($picture)>12)){
            $image=base64_file_upload($picture);
        }else{
            $image='';
        }
        $teacher = $request->param('teacher');
        return view('index/say', ['picture' => $image, 'teacher' => $teacher,'file'=>json_encode($request->file('file'))]);
    }

    //测试缓存
    public function book()
    {
        //设置缓存
        Cache::getInstance()->set('fuck', 'fuck you');
        //获取缓存
        Cache::getInstance()->get('fuck');
        return ['code' => 200, 'msg' => 'ok'];
    }

    //测试接收数据并直接返回数据
    public function back_url(Request $request)
    {
        //var_dump($request);
        return ['code' => 200, 'msg' => 'ok'];
    }

    //测试队列
    public function queue()
    {
        //普通队列
        Test::dispatch(['name' => 'hanmeimei', 'age' => '58']);
        Test::dispatch(['name' => 'hanmeimei', 'age' => '58']);
        Test::dispatch(['name' => 'hanmeimei', 'age' => '58']);
        Test::dispatch(['name' => 'hanmeimei', 'age' => '58']);
        Test::dispatch(['name' => 'hanmeimei', 'age' => '58']);
        //延迟队列
        Test::dispatch(['name' => '李磊', 'age' => '32'], 5);
        Test::dispatch(['name' => '李磊', 'age' => '32'], 3);
        Test::dispatch(['name' => '李磊', 'age' => '32'], 4);
        Test::dispatch(['name' => '李磊', 'age' => '32'], 15);
        Test::dispatch(['name' => '李磊', 'age' => '32'], 10);
        Test::dispatch(['name' => '李磊', 'age' => '32'], 8);
        return 'push message success!';
    }

    public function query()
    {
        $user = new User();
        //$res=$user->query('select * from user  ');
        //$res=$user->query('update user set age =55 where username ="test"');
        //$res=$user->query('delete from user where username ="test123"');
        $res = $user->query('insert into user (`username`,`sex`)  values("牛魔王",2)');
        //var_dump($res);
        return 123;
    }

}
