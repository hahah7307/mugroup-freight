<?php

namespace app\Manage\model;

use think\Model;

class LcInventoryBatchModel extends Model
{
    protected $name = 'lc_inventory_batch';

    protected $resultSetType = 'collection';

    public function receiving(): \think\model\relation\HasOne
    {
        return $this->hasOne('LcReceivingModel', 'receiving_code', 'receiving_code');
    }
}
