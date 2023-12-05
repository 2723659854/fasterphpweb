<?php

namespace Root\Annotation\Mapping;

use Attribute;

/**
 * @Annotation
 * Class RequestMapping
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class RequestMapping extends AbstractAnnotation
{


    /** @var array|false|string[]  */
    public $methods;

    /** @var mixed  */
    public $path;

    /** @var array|string[]  */
    public array $normal = ["GET", "POST", "PUT", "PATCH", "DELETE", "HEADER", "OPTIONS"];

    /**
     * @param ...$value
     */
    public function __construct(...$value)
    {

        $formattedValue = $this->formatParams($value);
        $this->path    = $formattedValue["path"];
        if (isset($formattedValue['methods'])) {
            if (is_string($formattedValue['methods'])) {
                // Explode a string to a array
                if (function_exists('mb_strtoupper')){
                    $this->methods = explode(',', mb_strtoupper(str_replace(' ', '', $formattedValue['methods'])  , 'UTF-8'));
                }else{
                    $this->methods = explode(',', (str_replace(' ', '', $formattedValue['methods'])  ));
                }

            } else {
                $methods = [];
                foreach ($formattedValue['methods'] as $method) {
                    if (function_exists('mb_strtoupper')){
                        $methods[] = mb_strtoupper(str_replace(' ', '', $method) , 'UTF-8');
                    }else{
                        $methods[] = (str_replace(' ', '', $method));
                    }

                }
                $this->methods = $methods;
            }
        }
    }

    /**
     * 设置方法
     * @return array
     */
    public function setMethods(): array
    {
        $normalMethods = [];
        foreach ($this->methods as $method)
        {
            if(in_array($method , $this->normal))
            {
                $normalMethods[] = $method;
            }
        }
        return $normalMethods;
    }
}