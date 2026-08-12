<?php

namespace app\Manage\model;

use think\Config;
use think\exception\DbException;
use think\Model;

class StorageInboundModel extends Model
{
    const STATE_ACTIVE = 1;

    protected $name = 'storage_inbound';

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
    static public function getInbound($storage, $detail, $order)
    {
        $platform = $order['platform'];
        $storageInbound = new StorageInboundModel();
        $condition['state'] = 1;
        $condition['storage_id'] = $storage;
        $condition['platform_tag'] = $platform;
        $condition['country_code'] = 'UK';
        // 命中生效区间
        $shippingDate = empty($order['dateWarehouseShipping']) ? $order['datePaidPlatform'] : $order['dateWarehouseShipping'];
        $condition['start_at'] = ['lt', $shippingDate];
        $condition['end_at'] = ['egt', $shippingDate];
        $inboundList = $storageInbound->where($condition)->order('level asc')->select();
        $price = 0;
        foreach ($inboundList as $rule) {
            $ruleCondition = json_decode($rule['condition'], true);
            if ($ruleCondition['max'] == 0 && $detail['product']['productWeight'] > $ruleCondition['min']) {
                $price = $rule['value'];
                break;
            } elseif ($detail['product']['productWeight'] > $ruleCondition['min'] && $detail['product']['productWeight'] <= $ruleCondition['max']) {
                $price = $rule['value'];
                break;
            }
            unset($rule);
        }
        return $price;
    }
}
