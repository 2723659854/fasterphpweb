##框架简介
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;socketweb是一款常驻内存的轻量级的php框架，遵循常用的mvc架构。</p>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;本框架对定时器和队列，mysql数据库,redis缓存进行了简单封装，并且保留了部分代码实例。</p>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;本框架尚处于开发测试阶段，请不要用于正式商用业务。</p>

### 项目安装
composer require xiaosongshu/fasterphpweb

### 目录结构
~~~
|-- app
    |-- controller               <控制层>
        |-- index               <index业务模块>
        |-- ...                 <其他业务模块>
    |-- facade                  <门面模块>
    |-- queue                  <队列任务模块>
    |-- timer                  <定时任务模块>
    |-- model               <模型层>
|-- config                  <配置项>
    |--app.php              <项目配置>
    |--database.php              <数据库配置>
    |--redis.php              <缓存配置>
    |--server.php              <http服务配置>
    |--timer.php              <定时任务配置>
|-- mysql                 <mysql文件>
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

1，导入mysql文件到你的数据库 <br>
2，进入项目根目录:cd /your_project_root_path<br>
3，调试模式:  php start.php start<br>
4，守护进程模式: php start.php start -d<br>
5，重启项目:  php start.php restart<br>
6，停止项目:  php start.php stop<br>
7，项目默认端口为：8082, 你可以自行修改<br>
8，项目访问地址：localhost://127.0.0.1:8082<br>
9，windows默认只开启一个http服务<br>
10，windows若需要测试队列，请单独开启一个窗口执行 php start.php queue ，监听队列<br>
11，windows不支持定时器<br>


### 注意
1，原则上本项目只依赖socket，mysqli，redis扩展和pcntl系列函数，如需要第三方扩展，请自行安装。<br>
2，因为是常驻内存，所以每一次修改了php代码后需要重启项目。<br>
3，start.php为项目启动源码，root目录为运行源码，除非你已经完全明白代码意图，否则不要轻易修改代码。<br>
4，所有的控制器方法都必须返回一个字符串，否则连接一直占用进程，超时后系统自动断开连接。<br>
5，业务代码不要使用sleep,exit这两个方法。否则导致整个进程阻塞或者中断。<br>
## 联系开发者
2723659854@qq.com<br>
171892716@qq.com
## 项目地址
https://github.com/2723659854/fasterphpweb

