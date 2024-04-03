<?php

namespace app\Manage\model;

use think\Model;

class SkuModel extends Model
{
    protected $name = 'ecang_sku';

    protected $resultSetType = 'collection';

    public function warehouseSku(): \think\model\relation\HasMany
    {
        return $this->hasMany('SkuRelationModel', 'sku_id', 'id');
    }
}
