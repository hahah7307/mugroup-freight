<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class InventoryAdjustmentModel extends Model
{
    protected $name = 'ecang_inventory_adjustment';

    protected $resultSetType = 'collection';
}
