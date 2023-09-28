<?php

namespace Root\Annotation\Mapping;

/**
 * @purpose 注解抽象类
 */
abstract class AbstractAnnotation
{

    /**
     * 格式化参数
     * @param $value
     * @return array
     */
    protected function formatParams($value): array
    {
        if (isset($value[0])) {
            $value = $value[0];
        }
        if (! is_array($value)) {
            $value = ['value' => $value];
        }
        return $value;
    }

    /**
     * 绑定属性
     * @param string $key
     * @param array $value
     * @return void
     */
    protected function bindMainProperty(string $key, array $value)
    {
        $formattedValue = $this->formatParams($value);
        if (isset($formattedValue['value'])) {
            $this->{$key} = $formattedValue['value'];
        }
    }
}