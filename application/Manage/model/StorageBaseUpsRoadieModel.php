<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class StorageBaseUpsRoadieModel extends Model
{
    const STATE_ACTIVE = 1;

    protected $name = 'storage_base_ups_roadie';

    protected $resultSetType = 'collection';

    protected $insert = ['created_at', 'updated_at'];

    protected $update = ['updated_at'];

    public function storage(): \think\model\relation\HasOne
    {
        return $this->hasOne("StorageModel", "id", "storage_id");
    }

    // 获取基础运费
    /**
     * @throws DbException
     */
    static public function getBaseUpsRoadie($storage, $lbs, $customerZone, $order, $detail)
    {
        // 当前未声明，先按原重量收基础运费
//        if (AHS::AHSDimension($detail['product']['productLength'], $detail['product']['productWidth'], $detail['product']['productHeight'])
//            && $order['dateWarehouseShipping'] >= "2025-01-13 00:00:00") {
//            $lbs = max($lbs, 40);
//        }
//
//        if (AHS::OSFedex($detail['product']['productLength'], $detail['product']['productWidth'], $detail['product']['productHeight'], $detail['product']['productWeight'])
//            && $order['dateWarehouseShipping'] >= "2026-01-05 00:00:00") {
//            $lbs = max($lbs, 90);
//        }

        $condition['storage_id'] = $storage;
        $condition['lbs_weight'] = $lbs;
        $condition['zone'] = $customerZone;
        $condition['state'] = self::STATE_ACTIVE;
        $condition['start_at'] = ['lt', $order['dateWarehouseShipping']];
        $condition['end_at'] = ['egt', $order['dateWarehouseShipping']];
        return self::get($condition);
    }
}
