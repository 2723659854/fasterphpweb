<?php

namespace App\Command;

use Root\Lib\BaseCommand;
use HaoZiTeam\ChatGPT\V1 as ChatGPTV1;
use HaoZiTeam\ChatGPT\V2 as ChatGPTV2;

/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2024-06-05 03:23:04
 * @note 对接文档：https://www.openaidoc.com.cn/api-reference/models
 * @note 插件地址：https://github.com/TheTNB/ChatGPT-PHP
 */
class CheckAi extends BaseCommand
{

    /** @var string $command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public $command = 'check:ai';
    /**
     * @var string apitoken的获取地址
     * @comment 感谢万能的网友
     */
    public string $doc = "https://docs.qq.com/sheet/DZWJQQk10a2xySGlh?tab=BB08J2";

    /** 免费的api key pool */
    public array $apiKey = [

    ];
    /** 指针 切换客户端用 */
    public int $index = 3;
    /**
     * 客户端
     * @var ChatGPTV2|null
     */
    public ?ChatGPTV2 $client;

    /** 缓存 */
    public array $cache = [];
    /** 在发送请求出现失败的情况，相同问题可以 被提问的最大次数，防止浪费资源 */
    public int $maxRequest = 5;
    /** 客户端休眠时间 ，因为chatgpt限制了请求频率 ，过快的请求频率会报错 */
    public int $sleepTime = 1;

    public array $models = [
        'gpt-3.5-turbo-0301',//聊天模型
        "gpt-3.5-turbo",//基本模型
        "text-davinci-003",//文本能力
    ];

    /**
     * 配置参数
     * @return void
     */
    public function configure()
    {
        /** 必选参数 */
        //$this->addArgument('argument','这个是参数argument的描述信息');
        /** 可传参数 */
        //$this->addOption('option','这个是option参数的描述信息');
    }

    /**
     * 获取客户端
     */
    public function getClient()
    {
        $this->client = new ChatGPTV2($this->getKey());
    }


    /**
     * 清在这里编写你的业务逻辑
     * @return void
     */
    public function handle()
    {
        /** 初始化客户端 */
        $this->getClient();
        $this->client->addMessage('开始对话', 'system');
        $this->info("system:开始对话\r\n");
        if ($this->sleepTime < 1) {
            $this->sleepTime = 1;
        }
        /** 循环交流 */
        while (true) {
            $this->requestChatGpt();
            /** 防止过快请求 导致接口限制 */
            sleep($this->sleepTime);
        }
    }

    /**
     * 发送请求，并打印
     * @param string $question 用户的问题
     * @return mixed
     */
    public function requestChatGpt(string $question = '')
    {
        /** 获取用户的问题 */
        if (!$question) {
            $question = $this->getQuestion();
        }

        try {
            /** 先问一遍 */
            $this->client->addMessage($question, 'user');
            $this->info("user:" . $question . "\r\n");
            $answers = $this->client->ask($question);
        } catch (\Exception $exception) {
            /** 打印错误信息 */
            $this->info($exception->getMessage() . "\r\n");
            /** 切换客户端 */
            $this->getClient();
            /** 统计问题失败次数 */
            if (isset($this->cache[$question])) {
                $this->cache[$question]++;
            } else {
                $this->cache[$question] = 1;
            }
            /** 判断问题提问次数 */
            if ($this->cache[$question] > $this->maxRequest) {
                $this->info("因为chatgpt服务异常，相同问题已被问了" . $this->maxRequest . "次，不要再问了\r\n");
                return $this->requestChatGpt();
            } else {
                /** 再问一遍 */
                return $this->requestChatGpt($question);
            }
        }
        /** 输出回复内容 */
        foreach ($answers as $item) {
            $this->info("system:" . $item['answer'] . "\r\n");
            $this->client->addMessage($this->encodeWord($item['answer']), 'system');
        }
        /** 问题被正常解答，清除错误计数器 */
        if (isset($this->cache[$question])) {
            $this->cache[$question] = 0;
        }
    }


    /**
     * 获取用户输入
     * @return string
     */
    public function getQuestion()
    {
        $this->info("请输入你要资讯的问题\r\n");
        /** php 在Windows环境下只有使用这个以readline方法才不会乱码 */
        $question = readline("\r\n");
        if (!$question) {
            $this->info("你输入的信息为空\r\n");
            return $this->getQuestion();
        }
        if (in_array($question, ["exit", "退出"])) {
            $this->info("系统退出\r\n");
            exit;
        }
        if (in_array($question, ["-help", "-h", "-帮助"])) {
            $this->info("你可以输入 `-h` ,`-help`,`-帮助` 获取帮助，输入`exit`或者`退出`可以退出会话。 ");
            return $this->getQuestion();
        }
        return $this->encodeWord($question);
    }


    /**
     * 将问题进行utf-8编码
     * @param string $data
     * @return string
     * @comment 防止json编码的时候遇到中文，client挂了
     */
    public function encodeWord(string $data)
    {
        if (!mb_detect_encoding($data, 'UTF-8', true)) {
            $data = mb_convert_encoding($data, 'UTF-8', 'auto');
        }
        return $data;
    }

    /**
     * 获取key
     * @return mixed|string
     */
    public function getKey()
    {
        /** 防止指针溢出 */
        if ($this->index > (count($this->apiKey) - 1)) {
            $this->index = 0;
        }
        /** 返回当前指针的key，并移动指针 */
        return $this->apiKey[$this->index++];
    }
}