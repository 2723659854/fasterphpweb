<?php

declare(strict_types=1);

namespace Root\Annotation\Mapping;

use Attribute;

/**
 * 单个中间件
 * @Annotation
 * @Target({"ALL"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Middleware extends AbstractAnnotation
{
    /**
     * @var string
     */
    public $middleware = '';

    public function __construct(...$value)
    {
        $this->bindMainProperty('middleware', $value);
        $this->middleware = $value;
    }
}
