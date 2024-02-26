<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class StorageAhsRuleModel extends Model
{
    const STATE_ACTIVE = 1;

    protected $name = 'storage_ahs_rule';

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

    public function ahs(): \think\model\relation\HasOne
    {
        return $this->hasOne('StorageAhsModel', 'id', 'ahs_id');
    }

    // 获取ahs费用

    /**
     * @throws DbException
     */
    static public function getAHSFee($storage, $ahs_id, $zone, $order)
    {
        $condition['storage_id'] = $storage;
        $condition['ahs_id'] = $ahs_id;
        $condition['state'] = 1;
        $condition['zone'] = $zone;
        $condition['start_at'] = ['lt', $order['dateWarehouseShipping']];
        $condition['end_at'] = ['egt', $order['dateWarehouseShipping']];
        return self::get($condition)->getData('value');
    }
}
