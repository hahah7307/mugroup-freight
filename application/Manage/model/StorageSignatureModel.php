<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class StorageSignatureModel extends Model
{
    const STATE_ACTIVE = 1;

    protected $name = 'storage_signature';

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

    /**
     * @throws DbException
     */
    static public function getSignature($storage_id, $order)
    {
        $condition['storage_id'] = $storage_id;
        $condition['state'] = self::STATE_ACTIVE;
        $condition['start_at'] = ['lt', $order['dateWarehouseShipping']];
        $condition['end_at'] = ['egt', $order['dateWarehouseShipping']];
        return self::get($condition);
    }
}
