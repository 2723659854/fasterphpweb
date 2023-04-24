<?php
namespace Root;
abstract class BaseCommand
{
    /** @var string $command 命令触发字段 必填 */
    public $command='check:wrong';

    public function __construct(){

    }

    /**
     * 业务逻辑 必填
     * @return void
     */
    public function handle(){

    }

}