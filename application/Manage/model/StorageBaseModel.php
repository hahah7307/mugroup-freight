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

    static public function getProductLbs($storage, $product)
    {
        $lbs = 0;
        foreach ($product as $item) {
            $lbs = 0;
            if ($storage == StorageModel::LIANGCANGID) {
                $volume_lbs = ceil(ceil($item['productLength'] / self::INCH2CM) * ceil($item['productWidth'] / self::INCH2CM) * ceil($item['productHeight'] / self::INCH2CM) / self::LB2INCH);
                $weight_lbs = $item['productWeight'] * self::KG2LB;
                $lbs += max($volume_lbs, $weight_lbs) * $item['productQty'];
            } elseif ($storage = StorageModel::LECANGID) {
                $volume_kg = $item['productLength'] * $item['productWidth'] * $item['productHeight'] / self::KG2CM3;
                $lbs += max($volume_kg, $item['productWeight']) * self::KG2LB * $item['productQty'];
            } else {
                $lbs += 0;
            }
            unset($item);
        }

        return ceil($lbs);
    }
}
