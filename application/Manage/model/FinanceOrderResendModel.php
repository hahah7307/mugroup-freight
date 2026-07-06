<?php

namespace app\Manage\model;

use think\Model;

class FinanceOrderResendModel extends Model
{
    protected $name = 'finance_order_resend';

    protected $resultSetType = 'collection';

    public function store(): \think\model\relation\HasOne
    {
        return $this->hasOne('FinanceStoreModel', 'id', 'store_id');
    }
}
