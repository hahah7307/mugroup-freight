<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class InventoryBatchCreateModel extends Model
{
    protected $name = 'ecang_inventory_batch_create';

    protected $resultSetType = 'collection';
}
