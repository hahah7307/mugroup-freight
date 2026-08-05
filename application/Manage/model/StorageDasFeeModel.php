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
        if ($order['shippingMethod'] == "UPS_ROADIE_GROUND") {
            return 3.2;
        }

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
        $condition['start_at'] = ['lt', $order['dateWarehouseShipping']];
        $condition['end_at'] = ['egt', $order['dateWarehouseShipping']];
        return self::get($condition)->getData('value');
    }

    static public function order2deliverType($order, $type): string
    {
        if ($type == 3) {
            return 'ALL';
        } else {
            if (stripos($order['shippingMethod'], 'GROUND')) {
                return 'GD';
            } elseif (stripos($order['shippingMethod'], 'HOME')) {
                return 'HD';
            } else {
                return 'ALL';
            }
        }
    }
}
