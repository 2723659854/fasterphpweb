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
class Just extends WsSelectorService
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
        $message = @json_decode($message,true);
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
     * 获取随机字符串
     * @return string
     */
    public function getRandString(){
        $content = [
            'Hello,world!',
            'How are you ?',
            'I`m fine,and you ?',
            'How do you do ?',
            'What`s your name?',
            'How old are you ?',
            'Just so so !',
            'Who care ?',
            'Why ?',
            'No way !',
            'There is some trouble !',
            'Good afternoon !',
            'Happy new year !',
            'Are you kidding me ?',
            'Everything is nothing !',
            "How time flies! In the blink of an eye, it's already half a life.",
            "Is there any regret medicine in this world ?",
            "God, please grant me strength !",
            "Not old, not dead, not happy, not sad !",
            'Broken mindfulness has ruined the palace bell, and we, the master and disciple, have severed our gratitude.',
            "Is there really a fate ?",
            "Because I love you, I have always been defeated from the beginning.",
            "Life and death are not given by others. I'm waiting for you to come and make a decision with you. Without this decision, I wouldn't have put away this plaque.",
            "I have been working hard for so many years, but I never understand you. Now, there's no need to understand or want to understand.",
            "Those who love me die for me, those who love me want me to die.",
            "Don't be silly, no one's heartbreak is not worth it. Forget him, forget him, I'll take you away, don't worry about this nonsense world anymore, don't be any more demon gods, I'll take you away.",
            "I don't believe in righteousness, I don't believe in evil, I don't believe in happiness, but I believe in you.",
            "Bai Zihua, you actually never believe in me, you only believe in your own eyes.",
            "Time may fade a person's memory, but it can never pass away their grief.",
            "Don't be silly, no one's heartbreak is not worth it.",
            "People cannot excuse their sadness by not doing what they should do.",
            "What a person who is neither old nor dead, nor injured nor destroyed. A divine edict determined his eternal pain. She loves the world, but only hates him.",
            "Smile step by step, grieve step by step, and suffer step by step. Despite the sadness of my memories, I smile and refuse to forget.",
            "No one in this world can protect you. Being strong is your only way out.",
            "Yes, love is really scary. You think you just love someone, but you never realize how much disaster that love can cause to that person or even the world.",
            "So, no matter how I live, for me, it's nothing.",
            "When a person makes a decision, their heart gradually calms down, just go ahead and wait for the result.",
            "People can let go of pain, but how can they let go and abandon the happiness they once had? Although behind that happiness lies a cliff, beneath which lies a forest of white bones.",
            "Because I love you, I can never fight you.",
            "The saddest thing in the world is when the person who used to love you deeply becomes everything to you, but you no longer matter to her.",
            "Don't hate, never give up opportunities for happiness.",
            "I used to be very happy. Because it was too happy, when sadness came, it was so easy to be completely destroyed. But people cannot use excuses to avoid sadness and ignore what they should do.",
        ];
        $count = count($content)-1;
        return $content[rand(0,$count)];
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