<?php

namespace App\Controller\Index;

use App\Middleware\Auth;
use App\Rabbitmq\Demo2;
use App\Rabbitmq\DemoConsume;
use Root\Annotation\Mapping\Middlewares;
use Root\Annotation\Mapping\RequestMapping;
use Root\ESClient;
use App\Middleware\MiddlewareA;
use Root\Lib\HttpClient;
use Root\Request;
use Root\Cache;
use App\Queue\Test;
use App\Rabbitmq\Demo;
use Root\Response;
use Root\Xiaosongshu;


class Index
{

    /** 默认首页,测试html */
    public function index(Request $request)
    {
        //模板在根目录下的view目录里面
        return view('/index/index', ['time' => date('Y-m-d H:i:s')])->cookie('name', 'how are you !');
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
        //$data = User::where('username', '=', 'mmlady')->first();
        $data = [];
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
        return view('index/say', ['picture' => $image, 'teacher' => $teacher, 'file' => $image]);
    }


    /**
     * 测试redis队列
     * @return string
     */
    public function queue()
    {
        //普通队列
        Test::dispatch(['name' => 'hanmeimei', 'age' => '58']);
        //延迟队列
        Test::dispatch(['name' => '李磊', 'age' => '32'], 5);
        /** 普通队列消息 */
        \App\Queue\Demo::dispatch(['name' => 'hanmeimei', 'age' => '58']);
        /** 延迟队列消息 ，单位秒(s)*/
        \App\Queue\Demo::dispatch(['name' => '李磊', 'age' => '32'], 3);

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
        $queue->publish(['name' => '张三', 'age' => 23]);
        (new Demo2())->publish(['school' => 'no school']);
        (new DemoConsume())->publish(['status' => 1, 'msg' => 'ok']);
        return \response(['msg' => 'ok', 'status' => 200]);
    }

    /** 下载文件到浏览器 */
    public function download()
    {
        /** 语法：download(文件路径) */
        return response()->download(public_path() . '/head.png');
    }

    /** 测试facade门面类 */
    public function facade()
    {
        return response(\APP\Facade\Cache::get('test'));
    }

    /**
     * 测试es搜索
     * @return \Root\Response
     */
    public function elasticsearch()
    {

        $client = new ESClient();
        return response($client->all('index', '_doc'));

    }

    /**
     * 测试中间件
     * @param Request $request
     * @return \Root\Response
     */
    public function middle(Request $request)
    {
        return response("我是中间件");
    }

    /**
     * 测试注解路由
     * @param Request $request
     * @return Response
     */
    #[RequestMapping(methods: 'get', path: '/login')]
    public function login(Request $request): Response
    {
        return \response(['I am a RequestMapping !']);
    }

    /**
     * 测试注解路由和中间件
     * @param Request $request
     * @return Response
     */
    #[RequestMapping(methods: 'get,post', path: '/chat'), Middlewares(MiddlewareA::class, Auth::class)]
    public function chat(Request $request): Response
    {

        return \response('我是用的注解路由');
    }

    /**
     * 测试ws服务
     * @return array|false|Response|string|string[]
     * @throws \Exception
     */
    #[RequestMapping(methods: 'get', path: '/ws')]
    public function ws()
    {
        return view('/index/ws');
    }

    #[RequestMapping(methods: 'get', path: '/restart')]
    public function restart()
    {
        Xiaosongshu::restart();
        return 'ok';
    }

    /**
     * 扫描端口
     * @return Response
     * @note 经过检测，对方开启了端口25,587,80，465,443
     */
    #[RequestMapping(methods: 'get', path: '/scan')]
    public function scan()
    {
        echo "开始扫描端口\r\n";
        for ($i = 0; $i <= 6553; $i++) {
            $host = "http://54.77.139.23:80";
            HttpClient::requestAsync($host, 'GET', ['lesson_id' => 201], [], [], function ($response) use ($host) {
                var_dump("请求地址：" . $host);
                var_dump($response->getStatusCode());
            }, function ($error) {
                var_dump($error->getMessage());

            });
        }
        echo "端口扫描完成\r\n";
        return \response('异步端口扫描完成');
    }
}
