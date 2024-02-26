<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class StorageResidentialModel extends Model
{
    const STATE_ACTIVE = 1;

    protected $name = 'storage_residential';

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
    static public function getResidential($storage, $order)
    {
        $deliver_type = self::order2deliverType($order);
        if (!$deliver_type) {
            return 0;
        }

        $condition['storage_id'] = $storage;
        $condition['state'] = self::STATE_ACTIVE;
        $condition['deliver_type'] = $deliver_type;
        $condition['start_at'] = ['lt', $order['dateWarehouseShipping']];
        $condition['end_at'] = ['egt', $order['dateWarehouseShipping']];
        return self::get($condition)->getData('value');
    }

    // 获取住宅地址旺季附加费

    /**
     * @throws DbException
     */
    static public function ResidentialPeakSurcharge($storage, $order)
    {
        $condition['storage_id'] = $storage;
        $condition['state'] = StoragePeakSurchargeModel::STATE_ACTIVE;
        $condition['type'] = 2;
        $condition['start_at'] = ['lt', $order['dateWarehouseShipping']];
        $condition['end_at'] = ['egt', $order['dateWarehouseShipping']];
        return StoragePeakSurchargeModel::get($condition)->getData('value');
    }

    static public function order2deliverType($order)
    {
        if (stripos($order['shippingMethod'], 'GROUND')) {
            return 'GD';
        } elseif (stripos($order['shippingMethod'], 'HOME_DELIVERY')) {
            return 'HD';
        } elseif (stripos($order['shippingMethod'], 'HOMEDELIVERY')) {
            return 'HD';
        } elseif (stripos($order['shippingMethod'], 'HOME-DELIVEY')) {
            return 'HD';
        } else {
            return false;
        }
    }
}
