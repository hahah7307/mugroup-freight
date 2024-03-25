<?php

namespace app\Manage\model;

use think\Model;

class FinanceOrderShippingServiceModel extends Model
{
    protected $name = 'finance_order_shipping_service';

    protected $resultSetType = 'collection';

    public function details(): \think\model\relation\HasMany
    {
        return $this->hasMany('OrderDetailModel', 'order_id');
    }
}
