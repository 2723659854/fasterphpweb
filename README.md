
框架简介
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;socketweb是一款常驻内存的轻量级的php框架，遵循常用的mvc架构。</p>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;本框架对定时器和队列，mysql数据库,redis缓存进行了简单封装，并且保留了部分代码实例。</p>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;本框架尚处于开发测试阶段，请不要用于正式商用业务。</p>

### 项目安装

```bash
composer create-project xiaosongshu/fasterphpweb
```
### 启动项目

```bash 
php start.php start  或者 php songshu start 
```
### 目录结构

~~~
|-- app
    |-- controller               <控制层>
        |-- index               <index业务模块>
        |-- ...                 <其他业务模块>
    |-- facade                  <门面模块>
    |-- queue                  <队列任务模块>
    |-- rabbitmq                  <rabbitmq队列>
    |-- model               <模型层>
    |-- command               <自定义命令行>
|-- config                  <配置项>
    |--app.php              <项目配置>
    |--database.php              <数据库配置>
    |--redis.php              <缓存配置>
    |--server.php              <http服务配置>
    |--timer.php              <定时任务配置>
|-- mysql                 <mysql文件，非必须>
    ...
|-- public                  <公共文件>
|-- root                <系统目录，建议不要轻易改动>
    ...                     
|-- vendor                  <外部扩展包>
|-- view                     <视图层>
        ...             
|-- composer.json              <项目依赖>
|-- README.md                  <项目说明文件>
|-- start.php                  <服务启动文件>
|-- songshu                    <服务启动文件>
~~~

###  快速开始

1，导入mysql文件到你的数据库或者自己创建 <br>
2，进入项目根目录:cd /your_project_root_path<br>
3，调试模式:  php start.php start<br>
4，守护进程模式: php start.php start -d<br>
5，重启项目:  php start.php restart<br>
6，停止项目:  php start.php stop<br>
7，项目默认端口为：8080, 你可以自行修改<br>
8，项目访问地址：localhost://127.0.0.1:8000<br>
9，windows默认只开启一个http服务<br>
10，windows若需要测试队列，请单独开启一个窗口执行 php start.php queue ，监听队列<br>
11，windows不支持定时器<br>
12，本项目支持普通的redis的list队列，同时支持rabbitmq队列，如果需要使用延时队列，需要安装插件<br>
13，在windows上默认使用select的io多路复用模型，在linux上默认使用epoll的io多路复用模型<br>
14，但是在linux系统上，如果使用开启后台运行，加入不支持epoll模型，则使用的多进程同步阻塞io模型。<br>
15，系统环境搭建，默认需要php,mysql,redis，而rabbitmq不是必须的。你可以自己搭建所需要的环境，也可以
使用本项目下面的docker配置。<br>
16，假设你使用docker配置，首先要安装docker，然后执行命令：docker-compose up -d 启动环境。注意修改
docker-compose.yaml 里面的目录映射，端口映射。<br>

### 注意

1，原则上本项目只依赖socket，mysqli，redis扩展和pcntl系列函数，如需要第三方扩展，请自行安装。<br>
2，因为是常驻内存，所以每一次修改了php代码后需要重启项目。<br>
3，start.php为项目启动源码，root目录为运行源码，除非你已经完全明白代码意图，否则不要轻易修改代码。<br>
4，所有的控制器方法都必须返回一个字符串，否则连接一直占用进程，超时后系统自动断开连接。<br>
5，业务代码不要使用sleep,exit这两个方法。否则导致整个进程阻塞或者中断。<br>

## 联系开发者

2723659854@qq.com<br>

## 项目地址

https://github.com/2723659854/fasterphpweb


### 项目文档

#### 项目基本配置

```php 
# 请在config/server.php 当中配置项目的端口和进程数
<?php
return [
    //监听端口
    'num'=>4,//启动进程数,建议不要超过CPU核数的两倍，windows无效
    'port'=>8000,//http监听端口
];

```

#### 控制层

```php
# 注意命名空间
/** 这里表示admin模块 */
namespace App\Controller\Admin;

use APP\Facade\Cache;
use APP\Facade\User;
/**
 * @purpose 类名 这里表示index控制器
 * @author 作者名称
 * @date 2023年4月27日16:05:11
 * @note 注意事项
 */
class Index
{

    /** 
      * @method get|post 本项目没有提供强制路由，自动根据模块名/控制器名/方法名 解析
      * 
      */
    public function index()
    {
        return '/admin/index/index';
    }
}
```
### 请求

```php 
 /**
     * request以及模板渲染演示
     * @param Request $request
     * @return array|false|string|string[]
     */
    public function database(Request $request)
    {
        /** 获取var参数 */
        $var      = $request->get('var');
        $name     = $request->post('name');
        $all = $request->all();
        /** 调用数据库 */
        $data     = User::where('username', '=', 'test')->first();
        /** 读取配置 */
        $app_name = config('app')['app_name'];
        /** 模板渲染 参数传递 */
        return view('index/database', ['var' => $var, 'str' => date('Y-m-d H:i:s'), 'user' => json_encode($data), 'app_name' => $app_name]);
    }
```
#### 获取get参数

```php 
/** 获取所有get参数 */
$data = $request->get();
/** 获取指定键名参数 */
$name = $request->get('name','tom');
```
#### 获取post参数

```php 
/** 获取所有post请求参数 */
$data = $request->post();
/** 获取指定键名参数 */
$name = $request->post('name','tom');
```
#### 获取所有请求参数

```php 
$data = $request->all();
```
#### 获取原始请求包体

```php 
$post = $request->rawBody();
```
#### 获取header头部信息

```php 
/** 获取所有的header */
$request->header();
/** 获取指定的header参数host */
$request->header('host');
```
#### 获取原始querystring

```php 
$request->queryString()
```
#### 获取cookie

```php 
/** 获取cookie */
$request->cookie('username');
/** 获取cookie 并设置默认值 */
$request->cookie('username', 'zhangsan');
```

### 响应

#### 设置cookie

```php 
return \response()->cookie('zhangsan','tom');
```
#### 返回视图

```php 
return view('index/database', ['var' => $var, 'str' => date('Y-m-d H:i:s'), 'user' => json_encode($data), 'app_name' => $app_name]);
```
#### 返回数据

```php
return response(['status'=>200,'msg'=>'ok','data'=>$data]);
/** 会覆盖response里面的数据 */
return response()->withBody('返回的数据');
```
#### 重定向

```php 
 return redirect('/admin/user/list');
```
#### 下载文件

```php 
 /** 直接下载 */
  return response()->file(public_path().'/favicon.ico');
 /** 设置别名 */
 return response()->download(public_path().'/favicon.ico','demo.ico');   
```
#### 设置响应头

```php 
 return \response(['status'=>200,'msg'=>'ok','data'=>$data],200,['Content-Type'=>'application/json']);
 return \response(['status'=>200,'msg'=>'ok','data'=>$data])->header('Content-Type','application/json');
 return \response(['status'=>200,'msg'=>'ok','data'=>$data])->withHeader('Content-Type','application/json');
 return \response(['status'=>200,'msg'=>'ok','data'=>$data])->withHeaders(['Content-Type'=>'application/json']);
```

#### 设置响应状态码

```php 
return response([],200);
return response([])->withStatus(200);
```

### 模板渲染

<br>默认支持html文件，变量使用花括号表示{}，暂不支持for,foreach,if等复杂模板运算 <br>
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>测试模板</title>
</head>
<body>
<h2>
    一个常驻内存的PHP轻量级框架socketweb

</h2>
<img src="/head.png" alt="头像">
<h5>{$var}</h5>
<h5>数据库查询的数据</h5>
<h5>{$user}</h5>
<h5>APP_NAME:{$app_name}</h5>
</body>
</html>

```
#### 模型层

#### 默认使用mysql数据库

#### 数据库配置

```php 
# 在config/database.php 当中配置
<?php
return [

    /** 默认连接方式 */
    'default'=>'mysql',
    /** mysql配置 */
    'mysql'=>[
        'host'=>'192.168.4.105',
        'username'=>'root',
        'passwd'=>'root',
        'dbname'=>'go_gin_chat',
        'port'=>'3306'
    ],
    //todo 其他类型请自己去实现
];

```

#### 模型的定义

```php
<?php
/** 命名空间 */
namespace App\Model;
/** 引入需要继承的模型基类 */
use Root\Model;

/** 定义模型名称 并继承模型基类 */
class Book extends Model
{
    /** @var string $table 建议指定表名，否则系统根据模型名推断表名，可能会不准确 */
    public $table = 'messages';
}


```

#### 模型的使用

```php 
/** 测试数据库 */
    public function query()
    {
       
        /** 第1种方法：使用门面类调用模型，需要自己创建门面类 */
        $messages = Fbook::where('id', '=', 3)->first();
        /** 第2种方法:直接静态化调用  */
        $next = Book::where('id','=',1)->first();
        return ['status' => 1, 'data' => $messages, 'msg' => 'success','book'=>$book];
    }
```

### 缓存的使用

#### 项目默认支持redis缓存

#### 缓存的配置

```php 
# 请在config/redis.php 当中配置
<?php

return [
    /** redis队列开关 */
    'enable'=>false,
    /** redis连接基本配置 */
    //'host'     => 'redis',
    'host'     => '192.168.4.105',
    'password' => '',
    'port'     => '6379',
    'database' => 0,
];
```
#### 缓存的使用

```php 
/** 测试缓存 */
    public function cache()
    {
       
        /** 第1种方法，直接静态方法调用 */
        Cache::set('happy','new year');
        return ['code' => 200, 'msg' => 'ok','静态调用'=>Cache::get('happy')];
    }
```

### 路由

###  配置文件

```php 
# config/route.php
<?php
return [

    /** 首页 */
    ['GET', '/', [App\Controller\Index\Index::class, 'index']],
    /** 路由测试 */
    ['GET', '/index/demo/index', [\App\Controller\Admin\Index::class, 'index']],
    /** 上传文件 */
    ['GET', '/upload', [\App\Controller\Admin\Index::class, 'upload']],
    /** 保存文件 */
    ['post', '/store', [\App\Controller\Admin\Index::class, 'store']],
    /** 缓存存取 */
    ['get', '/cache', [\App\Controller\Index\Index::class, 'cache']],
    /** 返回json */
    ['get', '/json', [\App\Controller\Index\Index::class, 'json']],
    /** 数据库 */
    ['get', '/database', [\App\Controller\Index\Index::class, 'database']],
    /** 数据库写入 */
    ['get', '/insert', [\App\Controller\Index\Index::class, 'insert']],
    /** base64 文件上传 */
    ['get', '/base64', [\App\Controller\Index\Index::class, 'upload']],
    /** base64 文件保存 */
    ['post', '/base64_store', [\App\Controller\Index\Index::class, 'store']],
    /** 测试redis队列 */
    ['get', '/queue', [\App\Controller\Index\Index::class, 'queue']],
    /** 测试rabbitmq队列 */
    ['get', '/rabbitmq', [\App\Controller\Index\Index::class, 'rabbitmq']],
    /** 文件下载 */
    ['get', '/download', [\App\Controller\Index\Index::class, 'download']],
    /** 测试门面类facade */
    ['get', '/facade', [\App\Controller\Index\Index::class, 'facade']],
    /** 测试es搜索 */
    ['get', '/es', [\App\Controller\Index\Index::class, 'elasticsearch']],
    /** 测试中间件 */
    ['GET','/middle',[\App\Controller\Index\Index::class,'middle'],[\App\Middleware\MiddlewareA::class,\App\Middleware\MiddlewareB::class]]

];

```

###  注解路由

```php 
   /**
     * 测试注解路由
     * @param Request $request
     * @return Response
     */
    #[RequestMapping(methods:'get',path:'/login')]
    public function login(Request $request):Response{
        return  \response(['I am a RequestMapping !']);
    }

    /**
     * 测试注解路由和中间件
     * @param Request $request
     * @return Response
     */
    #[RequestMapping(methods:'get,post',path:'/chat'),Middlewares(MiddlewareA::class)]
    public function chat(Request $request):Response{

        return \response('我是用的注解路由');
    }
```

###  中间件

###  创建中间件

```bash 
php songshu make:middleware Auth
php start.php make:middleware Auth
```
中间件内容如下：
```php 
<?php
namespace App\Middleware;
use Root\Lib\MiddlewareInterface;
use Root\Request;
use Root\Response;

/**
 * @purpose 中间件
 * @author administrator
 * @time 2023-09-28 05:51:21
 */
class Auth implements MiddlewareInterface
{
    public function process(Request $request, callable $next):Response
    {
        //todo 这里处理你的逻辑
        return $next($request);
    }
}
```

### 使用中间件

1,路由
```php 
/** 测试中间件 */
['GET','/middle',[\App\Controller\Index\Index::class,'middle'],[\App\Middleware\MiddlewareA::class,\App\Middleware\MiddlewareB::class]]
```
2,注解
```php 
/**
     * 测试注解路由和中间件
     * @param Request $request
     * @return Response
     */
    #[RequestMapping(methods:'get,post',path:'/chat'),Middlewares(MiddlewareA::class,Auth::class)]
    public function chat(Request $request):Response{

        return \response('我是用的注解路由');
    }
```

### 定时器

只能在linux系统中使用定时器，或者使用docker环境。

### 添加定时任务
```php 
//第一种方式
/** 使用回调函数投递定时任务 */
$first = Timer::add('5',function ($username){
    echo date('Y-m-d H:i:s');

    echo $username."\r\n";
},['投递的定时任务'],true);
echo "定时器id:".$first."\r\n";
/** 根据id删除定时器 */
Timer::delete($first);
/** 使用数组投递定时任务 */
Timer::add('5',[\Process\CornTask::class,'say'],['投递的定时任务'],true);
/** 获取所有正在运行的定时任务 */
print_r(Timer::getAll());
/** 清除所有定时器 */
Timer::deleteAll();

//第二种，使用配置文件config/timer.php
return [

    /** 定时器 */
    'one'=>[
        /** 是否开启 */
        'enable'=>true,
        /** 回调函数，调用静态方法 */
        'function'=>[Process\CornTask::class,'handle'],
        /** 周期 */
        'time'=>3,
        /** 是否循环执行 */
        'persist'=>true,
    ],
    'two'=>[
        'enable'=>false,
        /** 调用动态方法 */
        'function'=>[Process\CornTask::class,'say'],
        'time'=>5,
        'persist'=>true,
    ],
    'three'=>[
        'enable'=>true,
        /** 调用匿名函数 */
        'function'=>function(){$time=date('y-m-d H:i:s'); echo "\r\n {$time} 我是随手一敲的匿名函数！\r\n";},
        'time'=>5,
        'persist'=>true,
    ]

];


```


### rabbitmq消息队列

#### rabbitmq连接配置

```php 
<?php
return [
    /** rabbitmq队列开关 */
    'enable'=>false,
    /** rabbitmq基本连接配置 */
    'host'=>'faster-rabbitmq',
    'port'=>'5672',
    'user'=>'guest',
    'pass'=>'guest',
];
```

#### 定义消费者类

```php 
<?php
namespace App\Rabbitmq;
use Root\Queue\RabbitMQBase;

class Demo extends RabbitMQBase
{

    /** @var int $timeOut 延迟时间 秒 0 则不延时，延时需要安装官方插件 */
    public $timeOut = 5;

    /**
     * 业务逻辑
     * @param $param
     * @return void
     */
    public function handle($param)
    {
        var_dump("我是谁");
        var_dump($param);
    }
}
```

#### 开启消费者任务

```php 
<?php
use App\Rabbitmq\Demo;
return [
    /** 队列名称 */
    'demoForOne'=>[
        /** 消费者名称 */
        'handler'=>Demo::class
    ],
];
```

### elasticsearch 搜索
```php 
use root\ESClient;

    /** 测试elasticsearch用法 */
public function search()
{
    /** 实例化es客户端 */
    $client = new ESClient();
    /** 查询节点的所有数据 */
    return $client->all('v2_es_user3','_doc');
}
# 其他用法参照 root\ESClient::class的源码，
```

####  加入容器
解放双手，不需要每一次都去实例化需要调用的对象。使用容器简单方便。<br>
```php 
/** G方法： */
G(App\Service\DemoService::class)->talk(1);
/** M方法：*/
M(App\Service\DemoService::class)->talk(1);
```
G方法和M方法的区别是：<br>
G方法只会实例化一次对象，然后存储在内存中，下一次调用直接从内存中获取。<br>
而M方法每一次都是重新实例化一个新的对象。<br>

####  自定义命令
```php 
<?php
namespace App\Command;
/** 引入命令行基类 */
use Root\Lib\BaseCommand;
/** 创建自定义命令类 继承基类*/
class DemoCommand  extends BaseCommand
{

    /** @var string $command 命令触发字段，必填 */
    public $command = 'check:wrong';

    /** 业务逻辑 必填 */
    public function handle()
    {
        echo "请在这里写你的业务逻辑\r\n";
    }
}
```


#### sqlite数据库支持
创建模型
```bash 
php start.php make:sqlite Talk
```
或者
```bash 
php songshu make:sqlite Talk
```
模型内容如下
```php 
<?php

namespace App\SqliteModel;

use Root\Lib\SqliteBaseModel;

/**
 * @purpose sqlite数据库
 * @note 示例
 */
class Talk extends SqliteBaseModel
{

    /** 存放目录：请修改为你自己的字段，真实路径为config/sqlite.php里面absolute设置的路径 + $dir ,例如：/usr/src/myapp/fasterphpweb/sqlite/datadir/hello/talk */
    public string $dir = 'hello/talk';

    /** 表名称：请修改为你自己的表名称 */
    public string $table = 'talk';

    /** 表字段：请修改为你自己的字段 */
    public string $field ='id INTEGER PRIMARY KEY,name varhcar(24),created text(12)';

}
```
用法
```php 
<?php
use App\SqliteModel\Talk;
/** 写入数据 */
var_dump(Talk::insert(['name' => 'hello', 'created' => time()]));
/** 更新数据 */
var_dump(Talk::where([['id', '>=', 1]])->update(['name' => 'mm']));
/** 查询1条数据 */
var_dump(Talk::where([['id', '>=', 1]])->select(['name'])->first());
/** 删除数据 */
var_dump(Talk::where([['id', '=', 1]])->delete());
/** 统计 */
var_dump(Talk::where([['id', '>', 1]])->count());
/** 查询多条数据并排序分页 */
$res = Talk::where([['id', '>', 0]]) ->orderBy(['created'=>'asc']) ->page(1, 10) ->get();

?>
```
### ws服务（websocket）

创建ws服务
```bash 
php start.php make:ws Just
```
自动生成的ws服务类如下
```php 
<?php
namespace Ws;
use RuntimeException;
use Root\Lib\WsSelectorService;
use Root\Lib\WsEpollService;

/**
 * @purpose ws服务
 * @author administrator
 * @time 2023-09-28 10:47:59
 */
class Just extends WsEpollService
{
    /** ws 监听ip */
    public string $host= '0.0.0.0';
    /** 监听端口 */
    public int $port = 9501;

    public function __construct(){
        //todo 编写可能需要的逻辑
    }

    /**
     * 建立连接事件
     * @param $socket
     * @return mixed|void
     */
    public function onConnect($socket)
    {
        // TODO: Implement onConnect() method.
    }

    /**
     * 消息事件
     * @param $socket
     * @param $message
     * @return mixed|void
     */
    public function onMessage($socket, $message)
    {
        // TODO: Implement onMessage() method.
        switch ($message){
            case 'Ping':
                $this->sendTo($socket,'Pong');
                break;
            default:
                /** 发送当前时间 ，和客户端地址 */
                $this->sendTo($socket,['data'=>$message,'time'=>date('Y-m-d H:i:s'),'ip'=>$this->getUserInfoBySocket($socket)->remote_address??'']);
        }
    }

    /**
     * 连接断开事件
     * @param $socket
     * @return mixed|void
     */
    public function onClose($socket)
    {
        // TODO: Implement onClose() method.
    }

    /**
     * 异常事件
     * @param $socket
     * @param \Exception $exception
     * @return mixed|void
     */
    public function onError($socket, \Exception $exception)
    {
        //var_dump($exception->getMessage());
        $this->close($socket);
    }
}
```

###  开启服务 config/ws.php
```php 
<?php
return [
    'ws1'=>[
        /** 是否开启 */
        'enable'=>true,
        /** 服务类 */
        'handler'=>\Ws\TestWs::class,
        /** 监听ip */
        'host'=>'0.0.0.0',
        /** 监听端口 */
        'port'=>'9502'
    ],
    'ws2'=>[
        'enable'=>true,
        'handler'=>\Ws\TestWs2::class,
        'host'=>'0.0.0.0',
        'port'=>'9504'
    ],
    'ws3'=>[
        'enable'=>true,
        'handler'=>\Ws\Just::class,
        'host'=>'0.0.0.0',
        'port'=>'9501'
    ]
];
```
为方便测试，可以仅开启某一个ws服务，
```bash 
php songshu ws:start Ws.Just
```
或者
```bash 
php start.php ws:start Ws.Just
```
需注意命名空间大小写。须严格匹配。

#### 客户端测试代码
```html 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ws服务演示</title>
</head>
<body>
<center><h3>ws服务演示</h3></center>
<p>本页面仅作为演示，请根据自己的业务需求调整逻辑。</p>
<input type="text" name = "content" id = "say"/>
<button onclick="send()">发送消息</button>
<div id = 'content'></div>
<script>
    var connection = null;
    var ping = null;
    /** 连接ws服务*/
    window.onload = function() {
        console.log('页面加载完成了！连接ws服务器');
        connect();
    };
    /** 连接ws */
    function connect(){
        console.log("连接服务器")
        /** 连接服务器 */
        connection = new WebSocket('ws://localhost:9501');
        /** 设置回调事件 */
        connection.onopen = onopen;
        connection.onerror = onerror;
        connection.onclose = onclose;
        connection.onmessage = onmessage;
    }

    /** 发送消息*/
    function send(){
        var content = document.getElementById('say').value;
        connection.send(content);
    }

     function onopen () {
        connection.send('hi');
        console.log("连接成功，发送数据")
        /** 发送心跳 */
        ping = setInterval(function() {
            connection.send('Ping');
        }, 5000);
    }
    /** 错误 */
      function onerror (error) {
        console.log(error)
    }
    /** 连接断开了 */
    function onclose (){
        /** 重连服务器 */
        console.log("重新连接服务器")
        /** 清除心跳 */
        clearInterval(ping)
        /** 3秒后重连 */
        setTimeout(function (){
            connect();
        },3000)


    }
    /** 接收到消息 */
    function onmessage (e) {
        console.log('Server: ' + e.data);
        /** 将接收到的消息追加到页面 */
        var own =document.getElementById('content')
        var content  = "<p>"+e.data+"</p>"
        own.innerHTML = content + own.innerHTML;
    }
</script>
</body>
</html>
```
#### 命令行工具
创建自定义命令行： php start.php make:command Test  <br>
创建控制器： php start.php make:controller a/b/c <br>
创建mysql模型: php start.php make:model index/user <br>
创建sqlite模型: php start.php make:sqlite Demo<br>
创建中间件: php start.php make:middleware Auth<br>
创建ws服务：php start.php make:ws Just<br>
#### 其他
现在的网站都已经发展到前后端分离了，默认是无状态请求，cookie几乎没有用了。
所以没有编写cookie和session操作类了。
你可以使用token来识别用户，而不是cookie或者session。

