<?php

namespace App\Controller\Index;

use App\Model\User;
use App\Model\Book;
use Root\Request;
use Root\Cache;
use App\Queue\Test;
use APP\Facade\Cache as Fcache;
use APP\Facade\Book as Fbook;
use App\Rabbitmq\Demo;

class Index
{
    //todo 以下代码均为演示代码
    //如果需要渲染模板就调用view 不需要渲染模板就不调用view
    public function __construct()
    {

    }

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
        (new Cache())->set('fuck', 'you');
        /** 第二种方法，直接静态方法调用 */
        Cache::set('happy', 'new year');
        return ['code' => 200, 'msg' => 'ok', '普通的调用' => (new Cache())->get('fuck'), '静态调用' => Cache::get('happy')];
    }

    /** 纯数据 */
    public function json()
    {
        return json_encode(['status' => 1, 'msg' => 'success']);
    }

    /** 测试数据库 */
    public function query()
    {
        /** 第一种方法，实例化模型，然后查询数据库 */
        $book = (new Book())->where('id', '=', 3)->first();
        /** 第二种方法：使用门面类调用模型，需要自己创建门面类 */
        $messages = Fbook::where('id', '=', 3)->first();
        return ['status' => 1, 'data' => $messages, 'msg' => 'success', 'book' => $book];
    }

    /**
     * request以及模板渲染演示
     * @param Request $request
     * @return array|false|string|string[]
     */
    public function database(Request $request)
    {
        /** 获取var参数 */
        $var = $request->param('var');
        /** 调用数据库 */
        $user = new User();
        $data = $user->where('username', '=', 'test')->first();
        /** 读取配置 */
        $app_name = config('app')['app_name'];
        /** 模板渲染 参数传递 */
        return view('index/database', ['var' => $var, 'str' => date('Y-m-d H:i:s'), 'user' => json_encode($data), 'app_name' => $app_name]);
    }

    //测试数据写入
    public function say(Request $request)
    {

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
        return view('index/upload', ['cache' => 2]);
    }

    //测试表单提交和文件上传
    public function store(Request $request)
    {
        //普通上传文件
        if ($request->file('one')) {
            $file    = $request->file('one');
            $name    = $file['filename'] ? $file['filename'] : 'test.png';
            $content = $file['content'];
            $fp1     = fopen(app_path() . '/public/' . $name, 'wb');
            fwrite($fp1, $content);
            fclose($fp1);
        }
        if ($request->file('two')) {
            $file    = $request->file('two');
            $name    = $file['filename'] ? $file['filename'] : 'test.png';
            $content = $file['content'];
            $fp1     = fopen(app_path() . '/public/' . $name, 'wb');
            fwrite($fp1, $content);
            fclose($fp1);
        }
        //base64文件上传
        $picture = $request->param('picture');
        if ($picture && (strlen($picture) > 12)) {
            $image = base64_file_upload($picture);
        } else {
            $image = '';
        }
        $teacher = $request->param('teacher');
        return view('index/say', ['picture' => $image, 'teacher' => $teacher, 'file' => json_encode($request->file('file'))]);
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


    //测试批量写入
    public function buy_book()
    {
        $name            = [
            '语文', '数学', '英语', '物理', '化学', '政治', '美术', '体育', '生物', '历史', '地理',
        ];
        $publisher       = ['人民教育出版社', '青海教育出版社', '新华教育出版社', '重庆教育出版社', '四川教育出版社', '贵州教育出版社',];
        $str             = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $str_len         = strlen($str);
        $name_count      = count($name);
        $publisher_count = count($publisher);
        $array           = [];
        for ($i = 0; $i <= 100000; $i++) {
            $subject  = $name[rand(0, $name_count - 1)];
            $publish  = $publisher[rand(0, $publisher_count - 1)];
            $word_num = rand(0, $str_len - 1);
            $word     = '';
            for ($j = 0; $j < $word_num; $j++) {
                $word = $word . $str[rand(0, $str_len - 1)];
            }
            $array[] = ['name' => $subject, 'publisher' => $publish, 'content' => $word, 'create_time' => date('Y-m-d'), 'update_time' => date('Y-m-d')];
        }

        foreach (array_chunk($array, 500) as $v) {
            Fbook::insertAll($v);
        }

        return 'INSERT SUCCESS!';
    }

    public function compare()
    {

        return 'compare success!';
    }

    public function checkBook(Request $request)
    {
        $book = Fbook::where('id', '>', 0)->limit(10)->get();
        return json_encode($book);
    }

    /**
     * 测试rabbitmq消息队列
     * @return array
     */
    public function rabbitmq()
    {
        $queue = new Demo();
        $queue->send(['name' => '张三', 'age' => 23]);
        return ['msg' => '发送成功', 'status' => 200];
    }

    /**
     * 加法
     * @param Request $request
     * @return int
     * @note 服务提供者
     * @note 将服务注册到nacos，然后其他地方可以调用这个服务
     */
    public function add(Request $request)
    {

        $a = $request->param('a', 0);
        $b = $request->param('b', 0);
        return (int)bcadd($a, $b);
    }

    /** 下载文件到浏览器 */
    public function down()
    {
        /** 语法：download_file(文件路径) */
        return download_file(public_path().'/head.png');
    }

}
