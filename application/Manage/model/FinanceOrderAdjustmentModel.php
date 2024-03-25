<?php

namespace app\Manage\model;

use think\Model;

class FinanceOrderAdjustmentModel extends Model
{
    protected $name = 'finance_order_adjustment';

    protected $resultSetType = 'collection';

    public function details(): \think\model\relation\HasMany
    {
        return $this->hasMany('OrderDetailModel', 'order_id');
    }
}
