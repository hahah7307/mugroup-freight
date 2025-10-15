<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class StorageSfpModel extends Model
{
    const STATE_ACTIVE = 1;

    protected $name = 'storage_sfp';

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

    // 获取住宅地址附加费

    /**
     * @throws DbException
     */
    static public function getSFP($storage, $order)
    {
        $condition['storage_id'] = $storage;
        $condition['state'] = self::STATE_ACTIVE;
        $condition['start_at'] = ['lt', $order['dateWarehouseShipping']];
        $condition['end_at'] = ['egt', $order['dateWarehouseShipping']];
        $shippingMethod = self::where($condition)->column('shipping_method');
        if (in_array($order['shippingMethod'], $shippingMethod)) {
            $condition['shipping_method'] = $order['shippingMethod'];
            return self::get($condition)->getData('value');
        } else {
            return 0;
        }
    }
}
