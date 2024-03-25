<?php

namespace app\Manage\model;

use think\Model;

class FinanceOrderSaleModel extends Model
{
    protected $name = 'finance_order_sale';

    protected $resultSetType = 'collection';

    public function details(): \think\model\relation\HasMany
    {
        return $this->hasMany('OrderDetailModel', 'order_id');
    }
}
