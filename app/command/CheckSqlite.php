<?php

namespace App\Command;

use App\SqliteModel\Large;
use App\SqliteModel\Talk;
use Root\BaseCommand;
use Xiaosongshu\Table\Table;

/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2023-09-20 07:18:17
 */
class CheckSqlite extends BaseCommand
{

    /** @var string $command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public $command = 'check:sqlite';

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
     * @return void
     */
    public function handle()
    {
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
        print_r($res);
        /** 表格渲染数据 */
        $head  =['id','name','time'];
        $data = [];
        foreach ($res as $v){
            $data[]=[$v['id'],$v['name'],$v['created']];
        }
        $table = new Table();
        $table->table($head,$data);
        $this->info("查询完成了");
    }
}