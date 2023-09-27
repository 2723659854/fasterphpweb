<?php

namespace Root\Core\Provider;

use Root\Xiaosongshu;

interface IdentifyInterface
{
    public function handle(Xiaosongshu $app,array $param);
}