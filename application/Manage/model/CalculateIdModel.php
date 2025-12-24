<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class CalculateIdModel extends Model
{
    protected $name = 'calculate_id';

    protected $resultSetType = 'collection';
}
