<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class StorageCommissionModel extends Model
{
    const STATE_ACTIVE = 1;

    protected $name = 'storage_commission';

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

    // 获取佣金比例
    /**
     * @throws DbException
     */
    static public function getCommission($storage, $order)
    {
        $condition['storage_id'] = $storage;
        $condition['start_at'] = ['lt', $order['dateWarehouseShipping']];
        $condition['end_at'] = ['egt', $order['dateWarehouseShipping']];
        return self::get($condition)->getData('commission_rate');
    }
}
