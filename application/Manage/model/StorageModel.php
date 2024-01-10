<?php

namespace app\Manage\model;

use think\Config;
use think\exception\DbException;
use think\Model;

class StorageModel extends Model
{
    const STATE_ACTIVE = 1;
    const LIANGCANGID = 1;
    const LECANGID = 2;

    protected $name = 'storage';

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

    // 获取住宅地址附加费
    static public function getResidential($storage_id)
    {
        if ($storage_id == self::LIANGCANGID) {
            return Config::get('liang_rdc_fee');
        } elseif ($storage_id == self::LECANGID) {
            return  Config::get('loctek_rdc_fee');
        } else {
            return 0;
        }
    }

    // 获取住宅地址旺季附加费
    static public function getDemandResidential($storage_id, $date)
    {
        if ($storage_id == self::LIANGCANGID) {
            if (strtotime($date) >= strtotime(Config::get('rdc_additional_time5'))
                && strtotime($date) <= strtotime(Config::get('rdc_additional_time6'))) {
                return Config::get('liang_drdc_fee');
            } else {
                return 0;
            }
        } elseif ($storage_id == self::LECANGID) {
            if (strtotime($date) >= strtotime(Config::get('rdc_additional_time7'))
                && strtotime($date) <= strtotime(Config::get('rdc_additional_time8'))) {
                return Config::get('loctek_drdc_fee');
            } else {
                return 0;
            }
        } else {
            return 0;
        }

    }
}
