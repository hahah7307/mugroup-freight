<?php

namespace app\Manage\model;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Model;

class StorageZoneModel extends Model
{
    const STATE_ACTIVE = 1;

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

    public function storage(): \think\model\relation\HasOne
    {
        return $this->hasOne("StorageModel", "id", "storage_id");
    }

    public function area(): \think\model\relation\HasOne
    {
        return $this->hasOne("StorageAreaModel", "id", "area_id");
    }

    /**
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    static public function getCustomZone($order, $postalCode): int
    {
        $storageAreaObj = new StorageAreaModel();
        $area = $storageAreaObj->where(['storage_code' => $order['warehouseCode']])->find();
        if ($order['area']['storage_id'] == StorageModel::LIANGCANGID) {
            $zip_code = substr($postalCode, 0, 3) . "00";
            $storageZone = new StorageZoneModel();
            $zone = $storageZone->where(['storage_id' => $order['area']['storage_id'], 'type' => $order['area']['type'], 'area_id' => $area['id']])->where('zip_code', '<=', $zip_code)->order('id desc')->find();
            // TODO:邮编不在范围内无法得到分区
            return intval($postalCode) >= $zone['zip_code'] && intval($postalCode) <= $zone['zip_code_bak'] ? $zone['zone'] : 0;
        } elseif ($order['area']['storage_id'] == StorageModel::LECANGID) {
            $zone = StorageZoneModel::get(['storage_id' => $order['area']['storage_id'], 'type' => $order['area']['type'], 'area_id' => $area['id'], 'zip_code' => $postalCode]);
            return $zone ? $zone['zone'] : 0;
        } else {
            return 0;
        }
    }
}
