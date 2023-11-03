<?php

namespace Root\Core\Provider;

use Root\Queue\RtmpConsumer;
use Root\Xiaosongshu;

/**
 * @purpose  rtmp服务器
 */
class RtmpProvider implements IdentifyInterface
{

    public function handle(Xiaosongshu $app, array $param)
    {
        G(RtmpConsumer::class)->consume($param);
    }

}