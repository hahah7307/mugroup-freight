<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class TaskUserModel extends Model
{
    protected $name = 'task_user';

    protected $resultSetType = 'collection';
}
