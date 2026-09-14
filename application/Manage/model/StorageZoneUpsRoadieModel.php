<?php

namespace app\Manage\model;

use think\Model;

class StorageZoneUpsRoadieModel extends Model
{
    const STATE_ACTIVE = 1;

    protected $name = 'storage_zone_ups_roadie';

    protected $resultSetType = 'collection';

    public function storage(): \think\model\relation\HasOne
    {
        return $this->hasOne("StorageModel", "id", "storage_id");
    }

    public function area(): \think\model\relation\HasOne
    {
        return $this->hasOne("StorageAreaModel", "id", "area_id");
    }
}
