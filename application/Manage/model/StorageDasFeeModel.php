<?php

namespace app\Manage\model;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Model;

class StorageDasFeeModel extends Model
{
    const ACTIVE_STATE = 1;

    protected $name = 'storage_das_fee';

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

    // 获取偏远地址附加费

    /**
     * @throws DbException
     */
    static public function getDasFee($storage, $das, $order)
    {
        if (empty($das)) {
            return 0;
        }

        $deliverType = self::order2deliverType($order, $das['type']);
        if (!$deliverType) {
            return 0;
        }

        $condition['storage_id'] = $storage;
        $condition['type'] = $das['type'];
        $condition['deliver_type'] = $deliverType;
        $condition['state'] = self::ACTIVE_STATE;
        $condition['start_at'] = ['lt', $order['datePaidPlatform']];
        $condition['end_at'] = ['egt', $order['datePaidPlatform']];
        return self::get($condition)->getData('value');
    }

    static public function order2deliverType($order, $type)
    {
        if ($type == 3) {
            return 'ALL';
        } else {
            $storage_id = $order['area']['storage_id'];
            if ($storage_id == StorageModel::LIANGCANGID) {
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
            } elseif ($storage_id == StorageModel::LECANGID) {
                return 'ALL';
            } else {
                return false;
            }
        }
    }
}
