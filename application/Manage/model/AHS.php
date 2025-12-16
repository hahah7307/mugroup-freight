<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

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
        return ceil($length[0] / self::CM2INCHES) > 48
            || ceil($length[1] / self::CM2INCHES) > 30
            || (ceil($length[0] / self::CM2INCHES) + (ceil($length[1]/ self::CM2INCHES) + ceil($length[2] / self::CM2INCHES)) * 2) > 105
            || ceil($length[0] / self::CM2INCHES) * ceil($length[1] / self::CM2INCHES) * ceil($length[2] / self::CM2INCHES) > 10368;
    }

    static public function OSFedex($a, $b, $c, $w): bool
    {
        $arr = [$a, $b, $c];
        sort($arr);
        $length = array_reverse($arr);
        return ceil($length[0] / self::CM2INCHES) > 96
            || (ceil($length[0] / self::CM2INCHES) + (ceil($length[1]/ self::CM2INCHES) + ceil($length[2] / self::CM2INCHES)) * 2) > 130
            || ceil($length[0] / self::CM2INCHES) * ceil($length[1] / self::CM2INCHES) * ceil($length[2] / self::CM2INCHES) > 17280
            || ceil($w * self::CM2INCHES) > 110;
    }

    /**
     * @throws DbException
     */
    static public function getAHSFee($storage, $zone, $detail, $order)
    {
        $ahsFee = 0;
        if ($storage == StorageModel::LIANGCANGID) {
            $ahsFee = self::AHSFeeLiang($detail['product']['productWeight'], $zone, $detail['product']['productLength'], $detail['product']['productWidth'], $detail['product']['productHeight'], $order);
        } elseif ($storage == StorageModel::LECANGID) {
            $ahsFee = self::AHSFeeLoctek($detail['product']['productWeight'], $zone, $detail['product']['productLength'], $detail['product']['productWidth'], $detail['product']['productHeight'], $order);
        } elseif ($storage == StorageModel::WUYOUDAID) {
            $ahsFee = self::AHSFeeWuyouda($detail['product']['productWeight'], $zone, $detail['product']['productLength'], $detail['product']['productWidth'], $detail['product']['productHeight'], $order);
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
    static public function AHSFeeWuyouda($w, $zone, $a, $b, $c, $order)
    {
        $storage = StorageModel::WUYOUDAID;
        $w *= self::KG2LBS;

        if (self::AHSWeight($w)) {
            $weightFee = StorageAhsRuleModel::getAHSFee($storage, 6, $zone, $order);
        } else {
            $weightFee = 0;
        }
        $dimensionFee = self::AHSDimension($a, $b, $c) ? StorageAhsRuleModel::getAHSFee($storage, 7, $zone, $order) : 0;

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
