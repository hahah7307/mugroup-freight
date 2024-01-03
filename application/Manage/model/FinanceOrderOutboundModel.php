<?php

namespace app\Manage\model;

use SoapClient;
use think\Config;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\Model;

class FinanceOrderOutboundModel extends Model
{
    protected $name = 'finance_order_outbound';

    protected $resultSetType = 'collection';

    public function orderDetails(): \think\model\relation\HasOne
    {
        return $this->hasOne('OrderModel', 'id', 'ecang_order_id');
    }

    public function orderAddress(): \think\model\relation\HasOne
    {
        return $this->hasOne('OrderModel', 'id', 'ecang_order_id');
    }
}
