#框架简介
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;socketweb是一款常驻内存的轻量级的php框架，遵循常用的mvc架构。</p>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;本框架对定时器和队列，mysql数据库,redis缓存进行了简单封装，并且保留了部分代码实例。</p>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;本框架尚处于开发测试阶段，请不要用于正式商用业务。</p>

### 项目安装
```bash
composer create-project xiaosongshu/fasterphpweb
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
#### 获取参数

```php 
 /**
     * request以及模板渲染演示
     * @param Request $request
     * @return array|false|string|string[]
     */
    public function database(Request $request)
    {
        /** 获取var参数 */
        $var      = $request->param('var');
        /** 调用数据库 */
        $user     = new User();
        $data     = $user->where('username', '=', 'test')->first();
        /** 读取配置 */
        $app_name = config('app')['app_name'];
        /** 模板渲染 参数传递 */
        return view('index/database', ['var' => $var, 'str' => date('Y-m-d H:i:s'), 'user' => json_encode($data), 'app_name' => $app_name]);
    }
```
#### 模板渲染
<br>默认支持html文件，变量使用花括号表示{}<br>
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
        /** 第一种方法，实例化模型，然后查询数据库 */
        $book=(new Book())->where('id','=',3)->first();
        /** 第二种方法：使用门面类调用模型，需要自己创建门面类 */
        $messages = Fbook::where('id', '=', 3)->first();
        /** 第三种方法:直接静态化调用  */
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
        /** 第一种方法，先实例化，在调用 */
        (new Cache())->set('fuck','you');
        /** 第二种方法，直接静态方法调用 */
        Cache::set('happy','new year');
        return ['code' => 200, 'msg' => 'ok','普通的调用'=>(new Cache())->get('fuck'),'静态调用'=>Cache::get('happy')];
    }
```
#### 文件上传

```php 
//普通上传文件
        /** 判断是否获取到文件 */
        if ($request->file('one')) {
            /** 接收文件 */
            $file    = $request->file('one');
            /** 获取文件名称 */
            $name    = $file['filename'] ? $file['filename'] : 'test.png';
            /** 获取文件内容 */
            $content = $file['content'];
            /** 打开需要保存的文件fd */
            $fp1     = fopen(app_path() . '/public/' . $name, 'wb');
            /** 写入文件 */
            fwrite($fp1, $content);
            /** 关闭文件fd */
            fclose($fp1);
        }
```
#### 下载文件到浏览器

```php 
 /** 下载文件到浏览器 */
    public function down()
    {
        /** 语法：download_file(文件路径) */
        return download_file(public_path().'/head.png');
    }

```

#### 定时器
只能在linux系统中使用定时器，或者使用docker环境。
#### 添加定时任务
```php 
/** 添加定时任务，周期，回调函数，参数，是否循环执行 */
        \root\Timer::add(5, function ($a,$b) {
            var_dump("我只执行一次额");
            var_dump($a);
            var_dump($b);
        }, [3,5], false);

//todo 需要添加删除定时器的方法，后期再来完善。
```


#### rabbitmq消息队列

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
#### elasticsearch 搜索
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
```php 
<?php
namespace App\Command;
/** 引入命令行基类 */
use Root\BaseCommand;
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
#### 命令行工具
创建命令行 php start.php make:command Test  <br>
创建控制器 php start.php make:controller a/b/c <br>
创建模型 php start.php make:model index/user <br>


