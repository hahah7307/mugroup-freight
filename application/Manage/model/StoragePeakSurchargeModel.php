<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class StoragePeakSurchargeModel extends Model
{
    const STATE_ACTIVE = 1;

    protected $name = 'storage_peak_surcharge';

    protected $resultSetType = 'collection';

    protected $insert = ['created_at', 'updated_at'];

    protected $update = ['updated_at'];

    protected function setCreatedAtAttr()
    {
        return date('Y-m-d H:i:s');
    }

    protected function setUpdatedAtAttr()
    {
        return date('Y-m-d H:i:s');
    }
}
