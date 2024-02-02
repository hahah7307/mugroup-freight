<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class ProductInventoryModel extends Model
{
    protected $name = 'ecang_product_inventory';

    protected $resultSetType = 'collection';
}
