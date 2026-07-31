<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class EdaProductModel extends Model
{
    protected $name = 'eda_product';

    protected $resultSetType = 'collection';
}
