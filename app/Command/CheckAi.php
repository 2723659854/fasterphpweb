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

    /**
     * 免费的api key pool
     * @var array|string[]
     * @note 因为apiKey不能上传，所以进行了特殊处理
     */
    public array $apiKey = [
        's,k,-,t,m,J,K,U,I,I,F,C,s,1,m,v,T,I,m,F,s,4,v,T,3,B,l,b,k,F,J,V,3,W,o,a,6,E,E,g,Y,K,M,j,4,o,V,k,y,t,q',
        's,k,-,o,4,C,0,o,K,X,2,a,C,C,f,Y,h,f,B,J,v,p,B,T,3,B,l,b,k,F,J,8,7,C,2,5,D,h,o,Q,B,a,S,P,F,h,2,j,H,g,L',
        's,k,-,8,7,j,k,b,7,3,E,6,a,U,F,d,2,D,v,x,z,g,U,T,3,B,l,b,k,F,J,n,r,Q,p,i,g,B,O,s,y,w,y,c,G,Z,P,6,s,y,M',
        's,k,-,G,v,C,0,V,x,g,p,N,b,l,J,J,t,k,K,a,M,z,J,T,3,B,l,b,k,F,J,x,U,R,G,d,R,K,w,0,J,j,8,d,x,c,A,f,9,u,F',
        's,k,-,0,d,j,9,w,x,q,b,1,2,0,z,u,2,A,v,3,6,H,9,T,3,B,l,b,k,F,J,l,1,u,S,g,a,k,Z,3,U,i,y,y,t,S,B,e,L,o,9',
        's,k,-,G,K,E,K,5,e,o,P,f,W,r,X,1,N,1,G,B,h,2,Y,T,3,B,l,b,k,F,J,D,v,c,H,N,g,q,7,X,A,t,G,Z,3,6,4,z,1,f,G',
        's,k,-,c,B,T,z,H,E,2,0,i,S,P,f,S,4,X,9,9,h,7,h,T,3,B,l,b,k,F,J,2,2,z,M,g,V,8,j,C,w,z,T,1,y,0,T,J,H,N,L',
        's,k,-,b,B,l,v,X,q,6,5,4,q,g,w,W,S,6,q,E,3,Z,v,T,3,B,l,b,k,F,J,A,9,S,G,R,C,t,h,u,i,S,1,S,B,3,S,w,h,s,q',
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

    /** 支持的模型 */
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
     * 清在这里编写你的业务逻辑
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle()
    {
        /** 初始化客户端 */
        try {
            $this->getClient();
        }catch (\Exception $exception){
            var_dump($exception->getMessage());
        }

        $this->client->addMessage('开始对话', 'system');
        $this->info("系统：对话开始，请输入你要资讯的问题\r\n");
        if ($this->sleepTime < 1) {
            /** 防止过快请求 导致接口限制 */
            $this->sleepTime = 1;
        }
        /** 循环交流 */
        while (true) {
            /** 请求chatgpt */
            try {
                $this->requestChatGpt();
            }catch (\Exception $exception){
                /** 发生了错误，可能key不可用了，切换客户端 */
                $this->getClient();
            }

            /** 模拟自然人交流，不可访问频率过高 */
            sleep($this->sleepTime);
        }
    }

    /**
     * 获取客户端
     */
    public function getClient()
    {
        $this->client = new ChatGPTV2($this->getKey());
    }

    /**
     * 发送请求，并打印
     * @param string $question
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
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
            $this->info("用户：" . $question . "\r\n");
            $answers = $this->client->ask($question);
        } catch (\Exception $exception) {
            /** 打印错误信息 */
            $this->error($exception->getMessage() . "\r\n");
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
                $this->error("系统：因为chatgpt服务异常，相同问题已被问了" . $this->maxRequest . "次，不要再问了\r\n");
                return $this->requestChatGpt();
            } else {
                /** 再问一遍 */
                return $this->requestChatGpt($question);
            }
        }
        /** 输出回复内容 */
        foreach ($answers as $item) {
            $this->info("机器人：" . $item['answer'] . "\r\n");
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
        /** php 在Windows环境下只有使用这个以readline方法才不会乱码 */
        $question = readline("");
        if (!$question) {
            $this->info("系统：你输入的信息为空，请重新输入\r\n");
            return $this->getQuestion();
        }
        if (in_array($question, ["exit", "退出"])) {
            $this->info("系统：拜拜\r\n");
            exit;
        }
        if (in_array($question, ["-help", "-h", "-帮助"])) {
            $this->info("系统：你可以输入 `-h` ,`-help`,`-帮助` 获取帮助，输入`exit`或者`退出`可以退出会话。 ");
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
        //return "sk-6pmw6yav430eL5l1sF30fGsadjVksjPWrTtLBFiDBHgeLHrB";
        /** 防止指针溢出 */
        if ($this->index > (count($this->apiKey) - 1)) {
            $this->index = 0;
        }
        /** 返回当前指针的key，并移动指针，逐个使用每一个客户端，不使用随机客户端 */
        $key = implode('',explode(',',$this->apiKey[$this->index++]));
        return $key;
    }
}