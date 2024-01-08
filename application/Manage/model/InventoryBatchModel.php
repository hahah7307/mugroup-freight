<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class InventoryBatchModel extends Model
{
    protected $name = 'ecang_inventory_batch';

    protected $resultSetType = 'collection';
}
