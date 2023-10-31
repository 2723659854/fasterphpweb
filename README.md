框架简介
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;本框架旨在让用户了解和学习PHP实现web运行原理，涉及到了相对较多的底层基础知识。</p>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;socketweb是一款常驻内存的轻量级的php框架，遵循常用的mvc架构。</p>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;本框架对timer,redis,mysql,rabbitmq,websocket,elasticsearch,nacos,sqlite 进行了简单封装，并且保留了部分代码实例。</p>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;本框架提供基本的http服务，可支持api接口或者web网页。</p>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;本框架提供基本的websocket服务，可支持长链接，适用于聊天等场景。</p>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;本框架提供rtmp流媒体服务，纯PHP开发，不需要其他依赖。支持rtmp推流，rtmp拉流和flv拉流。适用于直播场景。</p>


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

### 快速开始

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
15，系统环境搭建，默认需要php,mysql,redis，而rabbitmq不是必须的。你可以自己搭建所需要的环境，也可以 使用本项目下面的docker配置。<br>
16，假设你使用docker配置，首先要安装docker，然后执行命令：docker-compose up -d 启动环境。注意修改 docker-compose.yaml 里面的目录映射，端口映射。<br>

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

### 配置文件

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

### 注解路由

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

### 中间件

### 创建中间件

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
### redis 队列
#### redis连接配置
```php 
<?php
# /config/redis.php
return [
    /** 是否提前启动缓存连接 */
    'preStart'=>false,
    /** redis队列开关 */
    'enable'=>true,
    /** redis连接基本配置 */
    'host'     => 'redis',
    //'host'     => '192.168.4.105',
    'password' => 'xT9=123456',
    'port'     => '6379',
    'database' => 0,
];
```
创建消费者
```bash 
php songshu make:queue Demo
```
生成的消费者内容如下：
```php 
<?php
namespace App\Queue;
use Root\Queue\Queue;

/**
 * @purpose redis消费者
 * @author administrator
 * @time 2023-10-31 03:44:50
 */
class Demo extends Queue
{
    public $param=null;

    /**
     * Test constructor.
     * @param array $param 根据业务需求，传递业务参数，必须以一个数组的形式传递
     */
    public function __construct(array $param)
    {
        $this->param=$param;
    }

    /**
     * 消费者
     * 具体的业务逻辑必须写在handle里面
     */
    public function handle(){
        //todo 这里写你的具体的业务逻辑
        var_dump($this->param);
    }
}
```
投递消息
```php 
  /** 普通队列消息 */
  \App\Queue\Demo::dispatch(['name' => 'hanmeimei', 'age' => '58']);
  /** 延迟队列消息 ，单位秒(s)*/
  \App\Queue\Demo::dispatch(['name' => '李磊', 'age' => '32'], 3);
```
### rabbitmq消息队列

#### rabbitmq连接配置

```php 
<?php
return [
    /** rabbitmq基本连接配置 */
    'host'=>'faster-rabbitmq',
    'port'=>'5672',
    'user'=>'guest',
    'pass'=>'guest',
];
```

#### 创建消费者类
```bash 
php start.php make:rabbitmq DemoConsume
```
生成的消费者内容如下：
```php 
<?php
namespace App\Rabbitmq;
use Root\Queue\RabbitMQBase;

/**
 * @purpose rabbitMq消费者
 * @author administrator
 * @time 2023-10-31 05:27:48
 */
class DemoConsume extends RabbitMQBase
{

    /**
     * 自定义队列名称
     * @var string
     */
    public $queueName ="DemoConsume";

    /** @var int $timeOut 普通队列 */
    public $timeOut=0;

   /**
     * 逻辑处理
     * @param array $param
     * @return void
     */
    public function handle(array $param)
    {
        // TODO: Implement handle() method.
    }

    /**
     * 异常处理
     * @param \Exception|\RuntimeException $exception
     * @return mixed|void
     */
    public function error(\Exception|\RuntimeException $exception)
    {
        // TODO: Implement error() method.
    }
}
```

#### 开启消费者任务

```php 
# config/rabbitmqProcess.php
<?php

return [
    /** 队列名称 */
    'demoForOne'=>[
        /** 消费者名称 */
        'handler'=>App\Rabbitmq\Demo::class,
        /** 进程数 */
        'count'=>2,
        /** 是否开启消费者 */
        'enable'=>false,
    ],
    /** 队列名称 */
    'demoForTwo'=>[
        /** 消费者名称 */
        'handler'=>App\Rabbitmq\Demo2::class,
        /** 进程数 */
        'count'=>1,
        /** 是否开启消费者 */
        'enable'=>true,
    ],
    'DemoConsume'=>[
        /** 消费者名称 */
        'handler'=>App\Rabbitmq\DemoConsume::class,
        /** 进程数 */
        'count'=>1,
        /** 是否开启消费者 */
        'enable'=>true,
    ]
];
```
投递消息
```php 
(new DemoConsume())->publish(['status'=>1,'msg'=>'ok']);
```

若不满足需求，可以使用插件
```bash 
composer require xiaosongshu/rabbitmq
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
elasticsearch 支持的方法<br>
```php 
创建索引：createIndex
创建表结构：createMappings
删除索引：deleteIndex
获取索引详情：getIndex
新增一行数据：create
批量写入数据：insert
根据id批量删除数据：deleteMultipleByIds
根据Id 删除一条记录：deleteById
获取表结构：getMap
根据id查询数据：find
根据某一个关键字搜索：search
使用原生方式查询es的数据：nativeQuerySearch
多个字段并列查询，多个字段同时满足需要查询的值：andSearch
or查询  多字段或者查询：orSearch
根据条件删除数据：deleteByQuery
根据权重查询：searchByRank
获取所有数据：all
添加脚本：addScript
获取脚本：getScript
使用脚本查询：searchByScript
使用脚本更新文档：updateByScript
索引是否存在：IndexExists
根据id更新数据：updateById
```

若不满足需求，可以使用插件
```bash 
composer require xiaosongshu/elasticsearch
```
一些例子：<br>
```php 
<?php
require_once 'vendor/autoload.php';

/** 实例化客户端 */
$client = new \Xiaosongshu\Elasticsearch\ESClient([
    /** 节点列表 */
    'nodes' => ['192.168.4.128:9200'],
    /** 用户名 */
    'username' => '',
    /** 密码 */
    'password' => '',
]);
/** 删除索引 */
$client->deleteIndex('index');
/** 如果不存在index索引，则创建index索引 */
if (!$client->IndexExists('index')) {
    /** 创建索引 */
    $client->createIndex('index', '_doc');
}

/** 创建表 */
$result = $client->createMappings('index', '_doc', [
    'id' => ['type' => 'long',],
    'title' => ['type' => 'text', "fielddata" => true,],
    'content' => ['type' => 'text', 'fielddata' => true],
    'create_time' => ['type' => 'text'],
    'test_a' => ["type" => "rank_feature"],
    'test_b' => ["type" => "rank_feature", "positive_score_impact" => false],
    'test_c' => ["type" => "rank_feature"],
]);
/** 获取数据库所有数据 */
$result = $client->all('index','_doc',0,15);

/** 写入单条数据 */
$result = $client->create('index', '_doc', [
    'id' => rand(1,99999),
    'title' => '我只是一个测试呢',
    'content' => '123456789',
    'create_time' => date('Y-m-d H:i:s'),
    'test_a' => 1,
    'test_b' => 2,
    'test_c' => 3,
]);
/** 批量写入数据 */
$result = $client->insert('index','_doc',[
    [
        'id' => rand(1,99999),
        'title' => '我只是一个测试呢',
        'content' => '你说什么',
        'create_time' => date('Y-m-d H:i:s'),
        'test_a' => rand(1,10),
        'test_b' => rand(1,10),
        'test_c' => rand(1,10),
    ],
    [
        'id' => rand(1,99999),
        'title' => '我只是一个测试呢',
        'content' => '你说什么',
        'create_time' => date('Y-m-d H:i:s'),
        'test_a' => rand(1,10),
        'test_b' => rand(1,10),
        'test_c' => rand(1,10),
    ],
    [
        'id' => rand(1,99999),
        'title' => '我只是一个测试呢',
        'content' => '你说什么',
        'create_time' => date('Y-m-d H:i:s'),
        'test_a' => rand(1,10),
        'test_b' => rand(1,10),
        'test_c' => rand(1,10),
    ],
]);
/** 使用关键字搜索 */
$result = $client->search('index','_doc','title','测试')['hits']['hits'];

/** 使用id更新数据 */
$result1 = $client->updateById('index','_doc',$result[0]['_id'],['content'=>'今天你测试了吗']);
/** 使用id 删除数据 */
$result = $client->deleteById('index','_doc',$result[0]['_id']);
/** 使用条件删除 */
$client->deleteByQuery('index','_doc','title','测试');
/** 使用关键字搜索 */
$result = $client->search('index','_doc','title','测试')['hits']['hits'];
/** 使用条件更新 */
$result = $client->updateByQuery('index','_doc','title','测试',['content'=>'哇了个哇，这么大的种子，这么大的花']);
/** 添加脚本 */
$result = $client->addScript('update_content',"doc['content'].value+'_'+'谁不说按家乡好'");
/** 添加脚本 */
$result = $client->addScript('update_content2',"(doc['content'].value)+'_'+'abcdefg'");
/** 获取脚本内容 */
$result = $client->getScript('update_content');
/** 使用脚本搜索 */
$result = $client->searchByScript('index', '_doc', 'update_content', 'title', '测试');
/** 删除脚本*/
$result = $client->deleteScript('update_content2');
/** 使用id查询 */
$result = $client->find('index','_doc','7fitkYkBktWURd5Uqckg');
/** 原生查询 */
$result = $client->nativeQuerySearch('index',[
    'query'=>[
        'bool'=>[
            'must'=>[
                [
                    'match_phrase'=>[
                        'title'=>'测试'
                    ],
                ],
                [
                    'script'=>[
                        'script'=>"doc['content'].value.length()>2"
                    ]
                ]
            ]
        ]
    ]

]);
/** and并且查询 */
$result = $client->andSearch('index','_doc',['title','content'],'测试');
/** or或者查询 */
$result = $client->orSearch('index','_doc',['title','content'],'今天');

```
你可能需要一键搭建elasticsearch服务，仅供参考：
```bash 
docker run -d --name my-es -p 9200:9200 -p 9300:9300 -e "discovery.type=single-node"  elasticsearch:7.9.2
```
elasticsearch属于内存数据库，启动服务后会占用很大的系统内存(redis和sqlite这两个和elasticsearch不是一个数量级)，导致服务器卡顿，影响其他服务正常运行，所以将elasticsearch独立搭建服务。
正式生成环境建议单独部署一台服务器用于部署elasticsearch，如果需要多个节点，那就需要多部署几台服务器。<br>
如果有特殊的分词需求，建议安装扩展ik分词器，参照<a href="https://blog.csdn.net/weixin_44364444/article/details/125758975">Docker中的elasticsearch安装ik分词器</a>
#### 加入容器

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

#### 自定义命令
创建
```bash 
php start.php make:command Demo
```
生成的自定义命令类如下：
```php 
<?php
namespace App\Command;

use Root\Lib\BaseCommand;
/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2023-10-10 09:11:34
 */
class Demo extends BaseCommand
{

    /** @var string $command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public $command = 'Demo';
    
     /**
     * 配置参数
     * @return void
     */
    public function configure(){
        /** 必选参数 */
        $this->addArgument('argument','这个是参数argument的描述信息');
        /** 可传参数 */
        $this->addOption('option','这个是option参数的描述信息');
    }
    
    /**
     * 清在这里编写你的业务逻辑
     * @return void
     */
    public function handle()
    {
        /** 获取必选参数 */
        var_dump($this->getOption('argument'));
        /** 获取可选参数 */
        var_dump($this->getOption('option'));
        $this->info("请在这里编写你的业务逻辑");
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
###  nacos服务
#### 安装客户端
```bash 
composer require xiaosongshu/nacos
```
#### nacos提供的方法
```php 
require_once __DIR__.'/vendor/autoload.php';
$dataId      = 'CalculatorService';
$group       = 'api';
$serviceName = 'mother';
$namespace   = 'public';
$client      = new \Xiaosongshu\Nacos\Client('http://127.0.0.1:8848','nacos','nacos');
/** 发布配置 */
print_r($client->publishConfig($dataId, $group, json_encode(['name' => 'fool', 'bar' => 'ha'])));
/** 获取配置 */
print_r($client->getConfig($dataId, $group,'public'));
/** 监听配置 */
print_r($client->listenerConfig($dataId, $group, json_encode(['name' => 'fool', 'bar' => 'ha'])));
/** 删除配置 */
print_r($client->deleteConfig($dataId, $group));
/** 创建服务 */
print_r($client->createService($serviceName, $namespace, json_encode(['name' => 'tom', 'age' => 15])));
/** 创建实例 */
print_r($client->createInstance($serviceName, "192.168.4.110", '9504', $namespace, json_encode(['name' => 'tom', 'age' => 15]), 99, 1, false));
/** 获取服务列表 */
print_r($client->getServiceList($namespace));
/** 服务详情 */
print_r($client->getServiceDetail($serviceName, $namespace));
/** 获取实例列表 */
print_r($client->getInstanceList($serviceName, $namespace));
/** 获取实例详情 */
print_r($client->getInstanceDetail($serviceName, false, '192.168.4.110', '9504'));
/** 更新实例健康状态 */
print_r($client->updateInstanceHealthy($serviceName, $namespace, '192.168.4.110', '9504',false));
/** 发送心跳 */
print_r($client->sendBeat($serviceName, '192.168.4.110', 9504, $namespace, false, 'beat'));
/** 移除实例*/
print_r($client->removeInstance($serviceName, '192.168.4.110', 9504, $namespace, false));
/** 删除服务 */
print_r($client->removeService($serviceName, $namespace));
        
```
可以根据自己的需求，给项目添加配置检测，微服务管理。<br>
配置检测：创建一个常驻内存进程，每隔30秒，读取一次nacos服务器上的配置，配置发生了变化，则修改配置，并重启服务。<br>
微服务管理：创建一个常驻内存进程，进程启动的时候注册服务。
<br>
你可能需要一键搭建nacos服务，仅供参考：
```bash 
docker run --name nacos -e MODE=standalone --env NACOS_AUTH_ENABLE=true -p 8848:8848 31181:31181 -d nacos/nacos-server:1.3.1
```
nacos这种负责管理配置和服务，安全性要求很高，一般不会销毁和重建，故没有将nacos服务绑定到基础容器里面。
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
 * @note 这是一个websocket服务端示例
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
        $allClients = $this->getAllUser();
        $clients = [];
        foreach ($allClients as $client){
            $clients[]=$client->id;
        }
        $this->sendTo($socket,['type'=>'getAllClients','content'=>$clients,'from'=>'server','to'=>$this->getUserInfoBySocket($socket)->id]);
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

        /** 消息格式 */
        # type:[ping,message,getAllClients],content:[string,array,json],to:[uid,all]
        $message = json_decode($message,true);
        /** 消息类型 */
        $type = $message['type']??null;
        /** 消息体 */
        $content = $message['content']??'';
        /** 接收人 */
        $sendTo = $message['to']??'all';
        /** 处理消息 */
        switch ($type){
            /** 心跳 */
            case 'ping':
                $this->sendTo($socket,['type'=>'pong','content'=>'pong','from'=>'sever','to'=>$this->getUserInfoBySocket($socket)->id??0]);
                break;
                /** 消息 */
            case 'message':
                if ($sendTo=='all'){
                    $this->sendToAll(['type'=>'message','content'=>$content,'to'=>'all','from'=>$this->getUserInfoBySocket($socket)->id??0]);
                }else{
                    $to = $this->getUserInfoByUid($sendTo);
                    $from = $this->getUserInfoBySocket($socket);
                    if ($to){
                        $this->sendTo($to->socket,['type'=>'message','content'=>$content,'to'=>$to->id??0,'from'=>$from->id??0]);
                    }else{
                        $this->sendTo($socket,['type'=>'message','content'=>'send message fail,the client is off line !','to'=>$from->id??0,'from'=>'server']);
                    }
                }
                break;
                /** 获取所有的客户端 */
            case "getAllClients":
                $allClients = $this->getAllUser();
                $clients = [];
                foreach ($allClients as $client){
                    $clients[]=$client->id;
                }
                $this->sendTo($socket,['type'=>'getAllClients','content'=>$clients,'from'=>'server','to'=>$this->getUserInfoBySocket($socket)->id]);
                break;
            default:
                /** 未识别的消息类型 */
                $this->sendTo($socket,['type'=>'error','content'=>'wrong message type !','from'=>'server','to'=>$this->getUserInfoBySocket($socket)->id]);
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

### 开启服务 config/ws.php

```php 
<?php
return [
    'ws1'=>[
        /** 是否开启 */
        'enable'=>false,
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
        'port'=>'9503'
    ],
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

#### javaScript客户端测试代码

```html 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>websocket服务演示</title>
</head>
<body>
<center><h3>ws服务演示</h3></center>
<p>本页面仅作为演示，请根据自己的业务需求调整逻辑页面展示效果。</p>
<input type="text" name="content" id="say" placeholder="请输入消息内容"/>
<input type="text" name="uuid" id="uuid" placeholder="请输入消息接收人UUID"/>
<button onclick="send()">发送广播消息</button>
<button onclick="sendToOne()">发送私聊消息</button>
<button onclick="getUser()">刷新在线用户</button>
<div style="border: black solid 1px;width: 300px">
    <h3>用户列表区</h3>
    <div id="user"></div>
</div>
<div>
    <h3>消息内容区</h3>
    <div id='content'></div>
</div>

<script>
    var connection = null;
    var ping = null;
    /** 连接ws服务*/
    window.onload = function () {
        console.log('页面加载完成了！连接ws服务器');
        connect();
    };

    /** 连接ws */
    function connect() {
        console.log("连接服务器")
        /** 连接服务器 */
        connection = new WebSocket('ws://localhost:9503');
        /** 设置回调事件 */
        connection.onopen = onopen;
        connection.onerror = onerror;
        connection.onclose = onclose;
        connection.onmessage = onmessage;
    }

    /** 发送消息*/
    function send() {
        var content = document.getElementById('say').value;
        let msg = {
            type: 'message',
            content: content,
            to: 'all'
        };
        connection.send(JSON.stringify(msg));
    }

    /**
     * 发送私聊信息
     */
    function sendToOne() {
        var content = document.getElementById('say').value;
        var uuid = document.getElementById('uuid').value;
        let msg = {
            type: 'message',
            content: content,
            to: uuid
        };
        connection.send(JSON.stringify(msg));
    }

    /** 连接成功 */
    function onopen() {
        let msg = {
            type: "ping",
        };
        connection.send(JSON.stringify(msg));
        console.log("连接成功，发送数据")
        /** 发送心跳 */
        ping = setInterval(function () {
            let msg = {
                type: "ping",
            };
            connection.send(JSON.stringify(msg));
        }, 10000);
    }

    /** 错误 */
    function onerror(error) {
        console.log(error)
    }

    /** 连接断开了 */
    function onclose() {
        /** 重连服务器 */
        console.log("重新连接服务器")
        /** 清除心跳 */
        clearInterval(ping)
        /** 3秒后重连 */
        setTimeout(function () {
            connect();
        }, 10000)
    }

    /** 接收到消息 */
    function onmessage(e) {
        var data = JSON.parse(e.data);
        /** 获取的在线用户列表 */
        if (data.type == 'getAllClients') {
            var string = '';
            data.content.forEach(function (item, index) {
                string = string + "<p>" + item + "</p>"
            })
            document.getElementById('user').innerHTML = string
        }else{
            /** 将接收到的普通聊天消息追加到页面 */
            var own = document.getElementById('content')
            var content = "<p>" + e.data + "</p>"
            own.innerHTML = content + own.innerHTML;
        }
    }

    /**
     * 获取在线用户
     */
    function getUser() {
        let msg = {
            type: 'getAllClients',
        };
        connection.send(JSON.stringify(msg));
    }
</script>
</body>
</html>
```

#### php版本的websocket客户端
以下是客户端使用示例。<br>
首次使用需要初始化，调用setUp()设置服务端ip和port。<br>
回调函数onMessage()方法负责处理用户的业务逻辑。<br>
start()方法是阻塞函数，负责监听服务端消息。<br>
send()函数负责发送消息，可以在任意地方调用。<br>
get()方法负责读取一条消息，可以在任意地方调用。<br>
```php 
 use Root\Lib\WsClient;

 /** 初始化 设置需要连接的ws服务器 */
 WsClient::setUp('127.0.0.1',9503);
 /** 发送一条数据 */
 WsClient::send(['type'=>'ping']);
 /** 读取一条数据 */
 var_dump(WsClient::get());
 /** 设置消息回调函数，负责处理接收到消息后逻辑，若不设置，则自动丢弃消息 */
 WsClient::$onMessage = function ($message){
            $message = json_decode($message,true);
            /** 消息类型 */
            $type = $message['type']??null;
            /** 消息体 */
            $content = $message['content']??'';
            /** 接收人 */
            $sendTo = $message['to']??'all';
            if ($sendTo=='all'){
                var_dump("广播的消息",$content);
            }else{
                var_dump("私聊给我的消息",$content);
            }
        };
 /** 开启客户端监听 */
 WsClient::start();
```
###  流媒体服务rtmp
####  流媒体服务配置 config/rtmp.php
```php 
<?php
return [
    /** 是否开启直播服务，该配置仅后台守护进程模式有效 */
    'enable'=>true,
    /** rmtp 服务端口 守护进程模式和开发模式均有效 */
    'rtmp'=>1935,
    /** flv端口 守护进程模式和开发模式均有效 */
    'flv'=>18080
];
```

开启流媒体服务
```bash 
php start.php rtmp start 
php start.php rtmp start -d 
php start.php rtmp restart
```
开启流媒体服务守护进程模式
```bash
php start.php check:rtmp start -d
```
关闭流媒体服务
```bash 
php start.php check:rtmp stop 
```
直播推流地址：rtmp://127.0.0.1:1935/a/b<br>
rtmp 拉流地址：rtmp://127.0.0.1:1935/a/b<br>
http-flv播放地址: http://127.0.0.1:18080/a/b.flv<br>
ws-flv播放地址: ws://127.0.0.1:18080/a/b.flv<br>
推流工具 ：obs,ffmpeg<br>
拉流工具 ：vlc播放器，web拉流<br>
本框架提供web拉流，详见示例：http://localhost:8000/video/play
###  Http客户端
####   支持 http/https协议
使用方法如下
```php 
use Root\Lib\HttpClient;
/** 同步请求 请求百度 */
$response = (HttpClient::request('www.baidu.com',  'GET',['lesson_id'=>201]));
var_dump($response->header());
/** 异步请求 请求百度 */
/** 发送异步请求 */
HttpClient::requestAsync('127.0.0.1:9501', 'GET', ['lesson_id' => 201], [], [], function (Request $message) {
    if ($message->rawBuffer()){
        var_dump("成功回调方法有数据");
    }
    
}, function (\RuntimeException $exception) {
    var_dump($exception->getMessage());

});
```
注意：在使用http异步请求客户端的时候 ，不要在成功回调和失败回调函数中抛出任何异常，如果需要抛出异常，一定要手动捕获。因为
在回调里面抛出异常，是没有其他服务来接管这个异常的，可能会导致进程摆烂。虽然本系统已经做了容错进行兜底，但是还是强烈建议，如果
一定要抛出异常，请自行捕获并处理异常。<br>
若该http客户端不满足你的需求，你可以使用第三方http客户端，比如Guzzle。或者使用curl函数自己构建请求。
#### 命令行工具

创建自定义命令行： php start.php make:command Test  <br>
创建控制器： php start.php make:controller a/b/c <br>
创建mysql模型: php start.php make:model index/user <br>
创建sqlite模型: php start.php make:sqlite Demo<br>
创建中间件: php start.php make:middleware Auth<br>
创建ws服务：php start.php make:ws Just<br>

#### 其他

现在的网站都已经发展到前后端分离了，默认是无状态请求，cookie几乎没有用了。 所以没有编写cookie和session操作类了。 你可以使用token来识别用户，而不是cookie或者session。

