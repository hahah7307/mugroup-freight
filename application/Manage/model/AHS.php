<?php

namespace app\Manage\model;

use think\Config;
use think\exception\DbException;
use think\Model;
use think\Session;

class AHS extends Model
{
    const CM2INCHES = 2.54;

    const LBS = 50;

    const KG2LBS = 2.204;

    static public function AHSWeight($w): bool
    {
        return $w >= self::LBS;
    }

    static public function AHSDimension($a, $b, $c): bool
    {
        $arr = [$a, $b, $c];
        sort($arr);
        $length = array_reverse($arr);
        return $length[0] > 48 * self::CM2INCHES || $length[1] > 30 * self::CM2INCHES || ($length[0] + ($length[1] + $length[2]) * 2) > 105 * self::CM2INCHES;
    }

    /**
     * @throws DbException
     */
    static public function getAHSFee($storage, $zone, $product, $order)
    {
        $ahsFee = 0;
        if ($storage == StorageModel::LIANGCANGID) {
            $ahsFee = self::AHSFeeLiang($product['productWeight'], $zone, $product['productLength'], $product['productWidth'], $product['productHeight'], $order);
        } elseif ($storage == StorageModel::LECANGID) {
            $ahsFee = self::AHSFeeLoctek($product['productWeight'], $zone, $product['productLength'], $product['productWidth'], $product['productHeight'], $order);
        }
        return $ahsFee;
    }

    /**
     * @throws DbException
     */
    static public function AHSFeeLiang($w, $zone, $a, $b, $c, $order)
    {
        $storage = StorageModel::LIANGCANGID;
        $w *= self::KG2LBS;
        $weightFee = self::AHSWeight($w) ? StorageAhsRuleModel::getAHSFee($storage, 1, $zone, $order) : 0;
        $dimensionFee = self::AHSDimension($a, $b, $c) ? StorageAhsRuleModel::getAHSFee($storage, 2, $zone, $order) : 0;

        return max($weightFee, $dimensionFee);
    }

    /**
     * @throws DbException
     */
    static public function AHSFeeLoctek($w, $zone, $a, $b, $c, $order)
    {
        $storage = StorageModel::LECANGID;
        $w *= self::KG2LBS;

        if ($w > 70) {
            $weightFee = StorageAhsRuleModel::getAHSFee($storage, 4, $zone, $order);
        } elseif (self::AHSWeight($w)) {
            $weightFee = StorageAhsRuleModel::getAHSFee($storage, 3, $zone, $order);
        } else {
            $weightFee = 0;
        }
        $dimensionFee = self::AHSDimension($a, $b, $c) ? StorageAhsRuleModel::getAHSFee($storage, 5, $zone, $order) : 0;

        return max($weightFee, $dimensionFee);
    }

    /**
     * @throws DbException
     */
    static public function AHSPeakSurcharge($storage, $order)
    {
        $condition['storage_id'] = $storage;
        $condition['state'] = StoragePeakSurchargeModel::STATE_ACTIVE;
        $condition['type'] = 1;
        $condition['start_at'] = ['lt', $order['dateWarehouseShipping']];
        $condition['end_at'] = ['egt', $order['dateWarehouseShipping']];
        return StoragePeakSurchargeModel::get($condition)->getData('value');
    }
}
