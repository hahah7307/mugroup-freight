<?php

namespace app\Manage\model;

use think\Model;

class ReceivingItemModel extends Model
{
    protected $name = 'ecang_receiving_item';

    protected $resultSetType = 'collection';

    public function receiving(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo('ReceivingModel', 'receiving_code', 'receiving_code');
    }
}
