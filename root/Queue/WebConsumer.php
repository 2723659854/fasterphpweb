<?php

namespace Root\Queue;

use Root\Xiaosongshu;

/**
 * @purpose web服务商
 */
class WebConsumer
{

    public function consume()
    {
        global $_has_epoll;
        if ($_has_epoll) {
            /** 使用epoll */
            G(Xiaosongshu::class)->epoll();
        } else {
            /** 使用普通的同步io */
            G(Xiaosongshu::class)->select();
        }
    }
}