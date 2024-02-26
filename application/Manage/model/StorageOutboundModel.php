<?php

namespace app\Manage\model;

use think\Config;
use think\exception\DbException;
use think\Model;

class StorageOutboundModel extends Model
{
    const KG2LB = 2.204;

    const STATE_ACTIVE = 1;

    protected $name = 'storage_outbound';

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
    static public function getOutbound($storage, $product, $order)
    {
        $platform = $order['platform'];
        $storageOutbound = new StorageOutboundModel();
        $condition['state'] = 1;
        $condition['storage_id'] = $storage;
        $condition['platform_tag'] = $platform;
        // 命中生效区间
        $condition['start_at'] = ['lt', $order['dateWarehouseShipping']];
        $condition['end_at'] = ['egt', $order['dateWarehouseShipping']];
        $outboundList = $storageOutbound->where($condition)->order('level asc')->select();
        $price = 0;
        foreach ($outboundList as $rule) {
            $ruleCondition = json_decode($rule['condition'], true);
            $lbs = 0;
            // 出库费良仓取计费重，乐歌取实重
            if ($storage == StorageModel::LIANGCANGID) {
                $lbs = StorageBaseModel::getProductLbs($storage, $product);
            } elseif ($storage == StorageModel::LECANGID) {
                $lbs = $product['productWeight'] * self::KG2LB;
            }
            if ($ruleCondition['max'] == 0 && $lbs > $ruleCondition['min']) {
                $price = $rule['value'];
                break;
            } elseif ($lbs > $ruleCondition['min'] && $lbs <= $ruleCondition['max']) {
                $price = $rule['value'];
                break;
            }
            unset($lbs);
            unset($rule);
        }
        return $price;
    }

    static public function outboundPlatform()
    {
        $outboundJson = Config::get('outbound_platform');
        $outboundArr = json_decode($outboundJson, true);
        return empty($outboundArr) || !is_array($outboundArr) ? [] : $outboundArr['platform'];
    }
}
