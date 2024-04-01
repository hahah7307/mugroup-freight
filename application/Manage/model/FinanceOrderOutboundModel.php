<?php

namespace app\Manage\model;

use think\Model;

class FinanceOrderOutboundModel extends Model
{
    protected $name = 'finance_order_outbound';

    protected $resultSetType = 'collection';

    public function store(): \think\model\relation\HasOne
    {
        return $this->hasOne('FinanceStoreModel', 'id', 'store_id');
    }
}
