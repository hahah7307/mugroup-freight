<?php

namespace app\Manage\model;

use think\Config;
use think\exception\DbException;
use think\Model;

class StorageOutboundModel extends Model
{
    const KG2LB = 2.204;

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
    static public function getOutbound($storage, $product, $platform)
    {
        $storageOutbound = new StorageOutboundModel();
        $outboundList = $storageOutbound->where(['state' => 1, 'storage_id' => $storage, 'platform_tag' => $platform])->order('level asc')->select();
        $price = 0;
        foreach ($outboundList as $rule) {
            $ruleCondition = json_decode($rule['condition'], true);
            foreach ($product as $item) {
                if ($ruleCondition['max'] == 0 && $item['productWeight'] * self::KG2LB > $ruleCondition['min']) {
                    $price += $rule['value'];
                    break;
                } elseif ($item['productWeight'] * self::KG2LB > $ruleCondition['min'] && $item['productWeight'] * self::KG2LB <= $ruleCondition['max']) {
                    $price += $rule['value'];
                    break;
                }
                unset($item);
            }
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
