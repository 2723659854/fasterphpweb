<?php

namespace App\Command;


use GuzzleHttp\Client;
use Root\Lib\BaseCommand;

/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2024-10-22 03:01:18
 */
class Post extends BaseCommand
{

    /** @var string $command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public $command = 'post';

    /**
     * 配置参数
     * @return void
     */
    public function configure()
    {
        /** 必选参数 */
        // $this->addArgument('argument','这个是参数argument的描述信息');
        /** 可传参数 */
        // $this->addOption('option','这个是option参数的描述信息');
    }

    /**
     * 清在这里编写你的业务逻辑
     * @return void
     */
    public function handle()
    {
        /** 获取必选参数 */
        //var_dump($this->getOption('argument'));
        /** 获取可选参数 */
        //var_dump($this->getOption('option'));
        $string = <<<eof
{
    "phone":"A6F03EE94E481D9C709379F401FD5073",
    "sourceCode":"666",
    "accessKey":"253C9211-E114-4267-885D-651F4CB6C1A2",
    "iv":"123",
    "data":"6J9rw85mNnjaFkPgGuRgK1tLwkBz02bZfhndX+eg22+/+XN+mdDybrZjBVbe3OiZ9da+fT0dNzB4ZnCjymfMkxvuUeJGqJzRXKrP1dEgGNrwjZA25WKpGqPCq2z/e/LQVi5WOkyRGMF9tAdv0wN056TwNQqWFsH3lEZSnu1c9Qi1Mc92KOkcf68gEoeHn1a1GrlttEAuDJc0f8dzf69h5QPuhIEokv9op4CG6uji4+MLTa8LxkYPKBHLUQcp9w3z3X459bT+mA/DuJcugAUQ3lCZVm106H3VOXAnQZwFPcM+DdaeiJEUTp8ga5YPXWjtxZp40MJJcZlRTfVAQUDx/5OLB3H6ZpJyuR3UXOc2KRLY6H2kO39MtbZmWVKYKvhkKBTC3pkznp9ASvLEC6HO/iM+tMOg5ltR1xH6KnH0Zr/5PovK7SCp39n0RW63vYLZExpd1SUQhF+Ie3SlzW72L8WXDwf308aIIOSV14Tl681jOAm1arlrdiyL7APKzLY6GhCKEBLdB86p8JoQpSTORyElj4YoCd+lkgPOtoi0IYSOvLGEowVbCOZfgESzr+eB+5SQJMQqy41hve00WaXpDFqCJ2DN7ZVnQmB/z5eZ78qzKiM04252euDUJX/8XMuGDmvzoxfIjOZHBkfBAfjwm2j8rVZwGewSp9HnWHuUmJB8114JvqWoodw66687CHOgB9EJX1HaR8GhSsj286n3BSGRIPYe27HRsF6ECOqhyAD+QJIlNlAxqMLDyHbR1zwgVX1u82UdmVTGTDNMPOFz86ARXYDG/E+3RAGJaIAt2Y41/5OlhfKgAAkrzyEz2DnLSRJIEK195OMH6VtS1A2ftMFA8fL2mcTaxIlZ2MktrDNVLTzS1ukbQ/seEGUkKbbjXjHc8w8MzcjhQNLlKJfQZzPidemCspkn1t0WiJV45i4T8ir26Qa1DQgSTH05HD94ZqGSK7cGqY8gleqG+zLb0vhHq9yttCq5TfZCkrvAdcBou1N+DB0B7V6j2eS43vtZU3l1mCakq4KoO9Akgs43OD2JcWNhU28UnaWoF71I4cXTGIonSgNui5MkPH+din/YZ8bV39GEKfWPVElqA5ozPxRW/EwKqz/aqcIKhEN74GQNGt8u2S3/6wLQnPcFmUt/0pFQT9IKH+Q+Zv/9lh7o5jxKoMwYdbD6hVqPoiicLOB0IheAW7omS1HC0PjsZ7PBHTW644uOZH9llYoEy15ZUzmX4oo88oPF/6MDgy1vVIg="
}
eof;
        $data = json_decode($string, true);
        $client = new Client();
        $response = $client->request('post', 'http://8.154.18.197:8001/admin/info/publishInfo', [
            'headers' => ['Content-Type' => 'application/json'],
            $data
        ]);
        $content = $response->getBody()->getContents();
        $file = app_path() . '/log.txt';
        //$content = str_replace("﻿","",$content);
        file_put_contents($file, $content . "\r\n", FILE_APPEND);
        $this->info("请求完成，日志位于".$file);
    }
}