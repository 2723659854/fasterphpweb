<?php
namespace APP\Facade;

use Root\Facade;

class Book extends Facade
{
    protected static function getAccessor()
    {
        return 'App\Model\Book';
    }
}