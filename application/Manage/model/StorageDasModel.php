<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class StorageDasModel extends Model
{
    const STATE_ACTIVE = 1;

    protected $name = 'storage_das';

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

    public function storage(): \think\model\relation\HasOne
    {
        return $this->hasOne("StorageModel", "id", "storage_id");
    }

    /**
     * @throws DbException
     */
    static public function getDASType($storage, $postalCode, $order)
    {
        $condition['storage_id'] = $storage;
        $condition['zip_code'] = $postalCode;
        $condition['state'] = self::STATE_ACTIVE;
        $condition['start_at'] = ['lt', $order['dateWarehouseShipping']];
        $condition['end_at'] = ['egt', $order['dateWarehouseShipping']];
        return self::get($condition);
    }
}
