<?php

namespace app\Manage\model;

use think\Model;

class FinanceOrderWayfairModel extends Model
{
    protected $name = 'finance_order_wayfair';

    protected $resultSetType = 'collection';

    public function wayfairCore(): \think\model\relation\HasMany
    {
        return $this->hasMany('FinanceWayfairCoreModel', 'invoice_no', 'invoice_no');
    }
}
