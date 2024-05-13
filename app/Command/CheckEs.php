<?php
namespace App\Command;

use Root\ESClient;
use Root\Lib\BaseCommand;
/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2023-10-09 11:05:15
 */
class CheckEs extends BaseCommand
{

    /** @var string $command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public $command = 'check:es';


     /**
     * 配置参数
     * @return void
     */
    public function configure(){

    }
    
    /**
     * 清在这里编写你的业务逻辑
     * @return void
     */
    public function handle()
    {
        $this->info("请在这里编写你的业务逻辑");

        $client = new ESClient();
        /** 删除索引 */
       // $client->deleteIndex('index');
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
        var_dump($result);


//        /** 使用id 删除数据 */
//        $result = $client->deleteById('index','_doc',$result[0]['_id']);
//        /** 使用条件删除 */
//        $client->deleteByQuery('index','_doc','title','测试');
//        /** 使用关键字搜索 */
//        $result = $client->search('index','_doc','title','测试')['hits']['hits'];
//
//
//        /** 使用id查询 */
//        $result = $client->find('index','_doc','7fitkYkBktWURd5Uqckg');
//        /** 原生查询 */
//        $result = $client->nativeQuerySearch('index',[
//            'query'=>[
//                'bool'=>[
//                    'must'=>[
//                        [
//                            'match_phrase'=>[
//                                'title'=>'测试'
//                            ],
//                        ],
//                        [
//                            'script'=>[
//                                'script'=>"doc['content'].value.length()>2"
//                            ]
//                        ]
//                    ]
//                ]
//            ]
//
//        ]);
//        /** and并且查询 */
//        $result = $client->andSearch('index','_doc',['title','content'],'测试');
//        /** or或者查询 */
//        $result = $client->orSearch('index','_doc',['title','content'],'今天');
    }
}