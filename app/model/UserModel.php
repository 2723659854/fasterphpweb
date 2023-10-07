<?php

namespace App\Model;
use Illuminate\Database\Eloquent\Model;
class UserModel extends  Model
{

    public $connection ='mysql';
    public $table= 'users';
}