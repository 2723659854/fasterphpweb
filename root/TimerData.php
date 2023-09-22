<?php

namespace Root;

class TimerData extends SqliteBaseModel
{

    public string $dir = 'timer/data';

    /** 表名称：请修改为你自己的表名称 */
    public string $table = 'timerDatabase';

    /** 表字段：请修改为你自己的字段 */
    public string $field ='_id INTEGER PRIMARY KEY AUTOINCREMENT,id text,data text,time varchar(24),status varchar(1)';
}