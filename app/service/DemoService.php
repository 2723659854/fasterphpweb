<?php
namespace App\Service;
use App\Model\User;

/**
 * @purpose demo服务类
 */
class DemoService
{

    /** user模型 */
    public $user;

    /** 构造函数 需要传入模型 */
    public function __construct(User $user ){
        $this->user=$user;
    }

    /**
     * 定义talk方法
     * @param int $id
     * @return mixed
     * @note 测试使用容器调用这个服务
     * @note 用法：G(DemoService::class)->talk(1)
     */
    public function talk(int $id){
        return $this->user::where('id','=',$id)->first() ;
    }
}