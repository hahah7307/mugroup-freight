<?php

namespace app\Manage\model;

use think\Config;
use think\exception\DbException;
use think\Model;

class StorageModel extends Model
{
    const STATE_ACTIVE = 1;
    const LIANGCANGID = 1;
    const LECANGID = 2;
    const WUYOUDAID = 3;

    protected $name = 'storage';

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
