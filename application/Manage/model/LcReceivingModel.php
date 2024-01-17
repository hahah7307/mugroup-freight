<?php

namespace app\Manage\model;

use think\Model;

class LcReceivingModel extends Model
{
    protected $name = 'lc_receiving';

    protected $resultSetType = 'collection';

    public function items(): \think\model\relation\HasMany
    {
        return $this->hasMany('LcReceivingItemModel', 'receiving_id', 'id');
    }
}
