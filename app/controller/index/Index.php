<?php

namespace App\Controller\Index;

use App\Model\User;
use App\Rabbitmq\Demo2;
use Root\ESClient;
use Root\Request;
use Root\Cache;
use App\Queue\Test;
use App\Rabbitmq\Demo;

class Index
{
    //todo 以下代码均为演示代码
    //如果需要渲染模板就调用view 不需要渲染模板就不调用view

    /** 默认首页,测试html */
    public function index()
    {
        //模板在根目录下的view目录里面
        return view('/index/index', ['time' => date('Y-m-d H:i:s')]);
    }

    /** 测试缓存 */
    public function cache()
    {
        /** 第一种方法，先实例化，在调用 */
        (new Cache())->set('test', 'you');
        /** 第二种方法，直接静态方法调用 */
        Cache::set('happy', 'new year');
        return response(['code' => 200, 'msg' => 'ok', '普通的调用' => (new Cache())->get('test'), '静态调用' => Cache::get('happy')]);
    }

    /** 纯数据 */
    public function json()
    {
        return response(json_encode(['status' => 1, 'msg' => 'success']));
    }


    /**
     * request以及模板渲染演示
     * @param Request $request
     * @return array|false|string|string[]
     */
    public function database(Request $request)
    {
        /** 获取var参数 */
        $var = $request->input('var');
        /** 调用数据库 */
        $data = User::where('username', '=', 'mmlady')->first();
        /** 读取配置 */
        $app_name = config('app')['app_name'];
        /** 模板渲染 参数传递 */
        return view('index/database', ['var' => $var, 'str' => date('Y-m-d H:i:s'), 'user' => json_encode($data), 'app_name' => $app_name]);
    }

    /**
     * 测试文件base64上传
     * @return array|false|string|string[]
     * @throws \Exception
     */
    public function upload()
    {
        return view('index/upload', ['cache' => 2]);
    }

    /**
     * 测试base64文件保存
     * @param Request $request
     * @return array|false|string|string[]
     * @throws \Exception
     */
    public function store(Request $request)
    {
        //base64文件上传
        $picture = $request->input('picture');
        if ($picture && (strlen($picture) > 12)) {
            $image = base64_file_upload($picture);
        } else {
            $image = 'no file';
        }
        $teacher = $request->input('teacher');
        return view('index/say', ['picture' => $image, 'teacher' => $teacher,'file'=>$image]);
    }


    /**
     * 测试redis队列
     * @return string
     */
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
        return response('push message success!');
    }

    /**
     * 测试rabbitmq消息队列
     * @return \Root\Response
     * @throws \Exception
     */
    public function rabbitmq()
    {
        $queue = new Demo();
        $queue->send(['name' => '张三', 'age' => 23]);
        (new Demo2())->send(['school'=>'no school']);
        return \response(['msg' => 'ok', 'status' => 200]);
    }

    /** 下载文件到浏览器 */
    public function download()
    {
        /** 语法：download(文件路径) */
        return response()->download(public_path().'/head.png');
    }
    /** 测试facade门面类 */
    public function facade(){
        return response(\APP\Facade\Cache::get('test'));
    }

    /**
     * 测试es搜索
     * @return \Root\Response
     */
    public function elasticsearch(){

        $client = new ESClient();
        return response($client->all('index','_doc'));

    }
}
