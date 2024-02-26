<?php

namespace app\Manage\model;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Model;

class StorageBaseModel extends Model
{
    const INCH2CM = 2.54;

    const LB2INCH = 250;

    const KG2LB = 2.204;

    const KG2CM3 = 9000;

    const STATE_ACTIVE = 1;

    protected $name = 'storage_base';

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

    //获取计费重
    static public function getProductLbs($storage, $product)
    {
        $lbs = 0;
        if ($storage == StorageModel::LIANGCANGID) {
            $volume_lbs = ceil(ceil($product['productLength'] / self::INCH2CM) * ceil($product['productWidth'] / self::INCH2CM) * ceil($product['productHeight'] / self::INCH2CM) / self::LB2INCH);
            $weight_lbs = $product['productWeight'] * self::KG2LB;
            $lbs = max($volume_lbs, $weight_lbs);
        } elseif ($storage == StorageModel::LECANGID) {
            $volume_kg = $product['productLength'] * $product['productWidth'] * $product['productHeight'] / self::KG2CM3;
            $lbs = max($volume_kg, $product['productWeight']) * self::KG2LB;
        }

        return ceil($lbs);
    }

    // 获取基础运费

    /**
     * @throws DbException
     */
    static public function getBase($storage, $lbs, $customerZone, $order)
    {
        $condition['storage_id'] = $storage;
        $condition['lbs_weight'] = $lbs;
        $condition['zone'] = $customerZone;
        $condition['state'] = self::STATE_ACTIVE;
        $condition['start_at'] = ['lt', $order['dateWarehouseShipping']];
        $condition['end_at'] = ['egt', $order['dateWarehouseShipping']];
        return self::get($condition);
    }
}
