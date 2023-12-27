<?php

namespace app\Manage\model;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Model;

class StorageZoneModel extends Model
{
    protected $name = 'storage_zone';

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
     * @param $storage
     * @param $type
     * @param $postalCode
     * @return int
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    static public function getCustomZone($storage, $type, $postalCode): int
    {
        if ($storage == StorageModel::LIANGCANGID) {
            $zip_code = substr($postalCode, 0, 3) . "00";
            $storageZone = new StorageZoneModel();
            $zone = $storageZone->where(['storage_id' => $storage, 'type' => $type])->where('zip_code', '<=', $zip_code)->order('id desc')->find();
            // TODO:邮编不在范围内无法得到分区
            return intval($postalCode) >= $zone['zip_code'] && intval($postalCode) <= $zone['zip_code_bak'] ? $zone['zone'] : 0;
        } elseif ($storage == StorageModel::LECANGID) {
            $zone = StorageZoneModel::get(['storage_id' => $storage, 'type' => $type, 'zip_code' => $postalCode]);
            return $zone ? $zone['zone'] : 0;
        } else {
            return 0;
        }
    }
}
