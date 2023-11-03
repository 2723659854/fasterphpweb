<?php

declare(strict_types=1);
namespace Root\Annotation\Mapping;

use Attribute;

/**
 * 多个中间件
 * @Annotation
 * @Target({"ALL"})
 */
#[Attribute]
class Middlewares extends AbstractAnnotation
{
    /**
     * @var Middleware[]
     */
    public array $middlewares = [];

    public function __construct(...$value)
    {
        if (is_string($value[0])) {
            $middlewares = [];
            foreach ($value as $middlewareName) {
                $middlewares[] = new Middleware($middlewareName);
            }
            $value = ['value' => $middlewares];
        }
        $this->bindMainProperty('middlewares', $value);
    }
}
