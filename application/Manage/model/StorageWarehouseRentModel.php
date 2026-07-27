<?php

namespace app\Manage\model;

use think\Model;

class StorageWarehouseRentModel extends Model
{
    protected $name = 'storage_warehouse_rent';

    protected $resultSetType = 'collection';

    protected $insert = ['created_at', 'updated_at'];

    protected $update = ['updated_at'];

}
