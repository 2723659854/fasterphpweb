<?php

namespace Xiaosongshu\Mail;

/**
 * @purpose 邮件发送
 * @note 支持附件发送，支持超大附件发送
 * @author xiaosongshu
 * @time 2023年12月21日18:52:00
 */
class Client
{
    /** 发件人 */
    protected $user;
    /** 发件人授权码 在QQ邮箱的设置，账户，smtp里面 参考：https://blog.csdn.net/weixin_60387745/article/details/129344957 */
    protected $password;
    /** 邮箱服务器地址 */
    protected $url;
    /** 客户端 */
    protected $socket;

    /**
     * 配置邮箱服务器地址和发件人信息
     * @param string $url 服务器地址
     * @param string $user 发件人邮箱
     * @param string $password 发件人授权码
     * @return bool
     * @throws \Exception
     */
    public function config(string $url, string $user, string $password): bool
    {
        if (!filter_var($user, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("发件人邮箱地址不合法");
        }
        $check = parse_url($url);
        if (empty($check['host']) || empty($check['port'])) {
            throw new \Exception("邮箱服务器地址不合法");
        }
        $this->user = $user;
        $this->password = $password;
        $this->url = $url;
        return true;
    }

    /**
     * 需要发送给邮箱服务器的命令
     * @param string $title
     * @param string $text
     * @param array $list
     * @return array
     */
    protected function command(string $title, string $text, array $list = [], array $file = []): array
    {
        /** 状态码 命令  这里是上一条命令应该返回的状态码，如果状态码不一致 则说明发生了错误 */
        $data = [
            ['250', 'HELO 0.0.0.0'],
            ['334', 'AUTH LOGIN'],
            /** 登陆邮箱和授权码都必须经过base64加密 */
            ['334', base64_encode($this->user)],
            ['235', base64_encode($this->password)],
        ];

        $boundary = null;
        /** 获取分隔符 */
        if ($file) {
            $boundary = $this->setBoundaries();
        }
        /** 生成发送给每一个用户的邮件内容 */
        foreach ($list as $email) {
            $data[] = ['250', 'MAIL FROM: <' . $this->user . '>'];
            $data[] = ['250', 'RCPT TO: <' . $email . '>'];
            $data[] = ['354', 'DATA', $email . '发送成功'];
            if ($boundary) {
                /** 组装头部 这里设置分隔符boundary必须有一个空格，真的坑死了 */
                $string = "From: {$this->user}\r\nTo: {$email}\r\nSubject:{$title}\r\nMIME-Version: 1.0\r\nContent-Type: multipart/mixed;\r\n boundary={$boundary}\r\n\r\n";
                /** 组装文本 boundary 和http协议的不一样 这里只有两根横线 */
                $string .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$text}";
                /** 追加文件 */
                foreach ($file as $v) {
                    /** 获取文件 */
                    $fileContent = file_get_contents($v);
                    /** 获取文件名称 */
                    $fileName = basename($v);
                    /** 内容加密 */
                    $fileContent = base64_encode($fileContent);
                    /** 组装需要发送的附件 */
                    $string .= "\r\n\r\n--{$boundary}\r\nContent-Type: application/octet-stream;name={$fileName}\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename={$fileName}\r\n\r\n{$fileContent}\r\n\r\n";
                }
                /** 追加万文件后，加上分隔符结束符号 */
                $string .= "--{$boundary}--\r\n\r\n.";
                /** 组装需要写入的文件 */
                $data[] = ['250', $string];
            } else {
                $data[] = ['250', "Content-Type:Text/html;charset=utf-8\r\nFrom: {$this->user}\r\nTo: {$email}\r\nSubject:{$title}\r\n\r\n{$text}\r\n\r\n."];
            }
        }
        $data[] = ['221', 'QUIT'];
        return $data;
    }

    /**
     * 连接服务器
     * @return void
     * @throws \Exception
     */
    protected function connect()
    {
        /** 解析服务器地址 */
        $hostInfo = parse_url($this->url);
        $host = empty($hostInfo['host']) ? null : $hostInfo['host'];
        $port = empty($hostInfo['port']) ? 25 : $hostInfo['port'];
        if (!$host) {
            throw new \Exception("服务器地址错误");
        }

        /** 必须使用异步的客户端 */
        if (false === ($r = stream_socket_client('tcp://' . $host . ":" . $port, $error_code, $error_message, 3, STREAM_CLIENT_ASYNC_CONNECT))) {
            throw new \Exception($error_message, $error_code);
        }
        /** 设置为非阻塞模式 */
        if (!stream_set_blocking($r, false)) {
            fclose($r);
            throw new \Exception("连接服务器失败");
        }
        $this->socket = $r;
    }

    /**
     * 发送邮件
     * @param array $users 接收人邮箱地址
     * @param string $title 主题
     * @param string $text 内容
     * @param array $file 附件
     * @return array
     * @throws \Exception
     */
    public function send(array $users, string $title, string $text, array $file = []): array
    {
        /** 检查文件是否合法 */
        if ($file) {
            foreach ($file as $v) {
                if (!is_file($v) || !file_exists($v)) {
                    throw new \Exception("{$v}不存在");
                }
            }
        }
        /** 连接服务器 */
        $this->connect();

        /** 生成要发送的邮件内容 */
        $command = $this->command($title, $text, $users, $file);
        $except = [];
        $sockets = [$this->socket];
        $code = '220';
        $message = null;
        while ($sockets) {
            $read = $write = $sockets;
            /** 遍历所有可读可写的连接 */
            $total = stream_select($read, $write, $except, 0, 1000);
            if (false === $total || $total <= 0) {
                continue;
            }
            /** 处理可读连接 */
            foreach ($read as $ready) {
                if ($ready == $this->socket) {
                    $content = fread($ready, 1024);
                    if (!empty($content)) {
                        if (strpos($content, $code) === false) {
                            fclose($this->socket);
                            throw new \Exception("发送邮件失败：{$content}");
                        }
                        /** 处理下一次需要发送的数据 */
                        $word = array_shift($command);
                        if (!empty($word)) {
                            $code = $word[0];
                            $message = $word[1];
                        }
                    }
                }
            }
            /** 处理可写连接 */
            foreach ($write as $ready) {
                if ($ready == $this->socket && $message) {
                    /** 强制一次性发送到对端服务器 */
                    @stream_socket_sendto($this->socket,$message."\r\n");
                    /** 成功发送数据到对方服务器 */
                    $message = null;
                    if ($code == '221') {
                        $sockets = [];
                        fclose($this->socket);
                    }
                }
            }
        }
        return ['status' => 1, 'msg' => '发送邮件成功'];
    }

    /**
     * 生成分隔符
     * @return array|string|string[]
     */
    public function setBoundaries()
    {
        $len = 32;
        $bytes = '';
        if (function_exists('random_bytes')) {
            try {
                $bytes = random_bytes($len);
            } catch (\Exception $e) {
            }
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($len);
        }
        if ($bytes === '') {
            $bytes = hash('sha256', uniqid((string)mt_rand(), true), true);
        }
        return str_replace(['=', '+', '/'], '', base64_encode(hash('sha256', $bytes, true)));
    }
}