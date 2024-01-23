<?php

namespace app\Manage\model;

use think\Model;

class DateStockCalculateModel extends Model
{
    protected $name = 'ecang_date_stock_calculate';

    protected $resultSetType = 'collection';

    public function receiving(): \think\model\relation\HasOne
    {
        return $this->hasOne('DateStockReceivingModel', 'id', 'date_stock_receiving_id');
    }

    public function consume(): \think\model\relation\HasOne
    {
        return $this->hasOne('DateStockConsumeModel', 'id', 'date_stock_consume_id');
    }
}
