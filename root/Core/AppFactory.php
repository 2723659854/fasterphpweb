<?php
namespace Root\Core;
use Root\Core\Provider\MakeBinProvider;
use Root\Core\Provider\MakeCommandProvider;
use Root\Core\Provider\MakeControllerProvider;
use Root\Core\Provider\MakeMiddlewareProvider;
use Root\Core\Provider\MakeModelProvider;
use Root\Core\Provider\MakePharProvider;
use Root\Core\Provider\MakeQueueProvider;
use Root\Core\Provider\MakeRabbitmqProvider;
use Root\Core\Provider\MakeSqliteProvider;
use Root\Core\Provider\MakeWsProvider;
use Root\Core\Provider\QueueProvider;
use Root\Core\Provider\RestartProvider;
use Root\Core\Provider\RtmpProvider;
use Root\Core\Provider\StartProvider;
use Root\Core\Provider\StartWsProvider;
use Root\Core\Provider\StopProvider;


class AppFactory
{
    /** @var array|string[] 注册系统命令 */
    protected array $alias = [
        'start' => StartProvider::class,
        'stop'=>StopProvider::class,
        'restart'=>RestartProvider::class,
        'queue'=>QueueProvider::class,
        'make:command'=>MakeCommandProvider::class,
        'make:model'=>MakeModelProvider::class,
        'make:controller'=>MakeControllerProvider::class,
        'make:sqlite'=>MakeSqliteProvider::class,
        'make:middleware'=>MakeMiddlewareProvider::class,
        'make:ws'=>MakeWsProvider::class,
        'ws:start'=>StartWsProvider::class,
        'rtmp'=>RtmpProvider::class,
        'make:queue'=>MakeQueueProvider::class,
        'make:rabbitmq'=>MakeRabbitmqProvider::class,
        'make:phar'=>MakePharProvider::class,
        'make:bin'=>MakeBinProvider::class
    ];

    /** @var array */
    protected array $providers = [];

    /** @var array */
    public array $configs = [];

    /**
     * 初始化配置
     * @param array $configs
     */
    public function __construct($configs = [])
    {
        $this->configs = $configs ?? [];
    }

    /**
     * 调用提供者
     * @param string $name
     * @return object
     */
    public function __get(string $name):object
    {
        if (!isset($name) || !isset($this->alias[$name])) {
            throw new \Exception("{$name} is invalid.");
        }

        if (isset($this->providers[$name])) {
            return $this->providers[$name];
        }
        $class = $this->alias[$name];
        return $this->providers[$name] = $this->configs ?
            new $class($this, $this->configs) :
            new $class($this);
    }
}