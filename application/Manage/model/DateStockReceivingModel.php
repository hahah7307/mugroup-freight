<?php

namespace app\Manage\model;

use think\Model;

class DateStockReceivingModel extends Model
{
    protected $name = 'ecang_date_stock_receiving';

    protected $resultSetType = 'collection';

    public function consume(): \think\model\relation\HasOne
    {
        return $this->hasOne('DateStockConsumeModel', 'id', 'date_stock_consume_id');
    }
}
