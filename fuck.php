<?php
class A {
    public static function create() {
        return new static();
    }
    public static function say()
    {
        return new self();
    }
}
class B extends A{};
echo get_class(B::create());# B
echo "\r\n";
echo get_class(B::say());# A
// 结论：static 是返回当前类，self是返回上一级类