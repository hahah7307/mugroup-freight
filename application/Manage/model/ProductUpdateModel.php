<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class ProductUpdateModel extends Model
{
    protected $name = 'product_update';

    protected $resultSetType = 'collection';
}
