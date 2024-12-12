<?php

namespace app\Manage\model;

use think\Model;

class FinanceWildberriesOrderModel extends Model
{
    protected $name = 'finance_wildberries_order';

    protected $resultSetType = 'collection';

    public function fee(): \think\model\relation\HasOne
    {
        return $this->hasOne('FinanceWildberriesFeeModel', 'order_no', 'order_no');
    }
}
