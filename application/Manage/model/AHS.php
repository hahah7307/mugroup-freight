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
    static public function getAHSFee($storage, $zone, $product)
    {
        $ahsFee = 0;
        if ($storage == StorageModel::LIANGCANGID) {
            $ahsFee = self::AHSFeeLiang($product['productWeight'], $zone, $product['productLength'], $product['productWidth'], $product['productHeight']);
        } elseif ($storage == StorageModel::LECANGID) {
            $ahsFee = self::AHSFeeLoctek($product['productWeight'], $zone, $product['productLength'], $product['productWidth'], $product['productHeight']);
        }
        return $ahsFee;
    }

    /**
     * @throws DbException
     */
    static public function AHSFeeLiang($w, $zone, $a, $b, $c)
    {
        $storage = StorageModel::LIANGCANGID;
        $w *= self::KG2LBS;
        $weightFee = self::AHSWeight($w) ? StorageAhsRuleModel::get(['storage_id' => $storage, 'ahs_id' => 1, 'state' => 1, 'zone' => $zone])->getData('value') : 0;
        $dimensionFee = self::AHSDimension($a, $b, $c) ? StorageAhsRuleModel::get(['storage_id' => $storage, 'ahs_id' => 2, 'state' => 1, 'zone' => $zone])->getData('value') : 0;

        return max($weightFee, $dimensionFee);
    }

    /**
     * @throws DbException
     */
    static public function AHSFeeLoctek($w, $zone, $a, $b, $c)
    {
        $storage = StorageModel::LECANGID;
        $w *= self::KG2LBS;

        if ($w > 70) {
            $weightFee = StorageAhsRuleModel::get(['storage_id' => $storage, 'ahs_id' => 4, 'state' => 1, 'zone' => $zone])->getData('value');
        } elseif (self::AHSWeight($w)) {
            $weightFee = StorageAhsRuleModel::get(['storage_id' => $storage, 'ahs_id' => 3, 'state' => 1, 'zone' => $zone])->getData('value');
        } else {
            $weightFee = 0;
        }
        $dimensionFee = self::AHSDimension($a, $b, $c) ? StorageAhsRuleModel::get(['storage_id' => $storage, 'ahs_id' => 5, 'state' => 1, 'zone' => $zone])->getData('value') : 0;

        return max($weightFee, $dimensionFee);
    }

    static public function demandSurcharges($storage, $createdDate)
    {
        if ($storage == StorageModel::LIANGCANGID) {
            if (strtotime($createdDate) >= strtotime(Config::get('ahs_additional_time'))
                && strtotime($createdDate) <= strtotime(Config::get('ahs_additional_time2'))) {
                return Config::get('liang_additional_fee');
            } else {
                return 0;
            }
        } elseif ($storage == StorageModel::LECANGID) {
            if (strtotime($createdDate) >= strtotime(Config::get('ahs_additional_time3'))
                && strtotime($createdDate) <= strtotime(Config::get('ahs_additional_time4'))) {
                return Config::get('loctek_additional_fee');
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }
}
