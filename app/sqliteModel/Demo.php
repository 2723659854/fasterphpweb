<?php

namespace App\SqliteModel;

use Root\SqliteBaseModel;

class Demo extends SqliteBaseModel
{

    public string $dir = 'hello/world';

    public string $table = 'user';

    public string $field ='id INTEGER PRIMARY KEY,name varhcar(24),created text(12)';

}