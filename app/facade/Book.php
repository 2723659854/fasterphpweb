<?php


namespace APP\Facade;


use root\Facade;

class Book extends Facade
{
    protected static function getAccessor()
    {
        return 'App\Model\Book';
    }
}