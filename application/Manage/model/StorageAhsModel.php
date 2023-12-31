<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class StorageAhsModel extends Model
{
    protected $name = 'storage_ahs';

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

    public function rule()
    {
        return self::hasMany('StorageAhsRuleModel', 'ahs_id', 'id');
    }
}
