<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class OrderDetailModel extends Model
{
    protected $name = 'ecang_order_detail';

    protected $resultSetType = 'collection';

//    protected $insert = ['created_at', 'updated_at'];

//    protected $update = ['updated_at'];

    protected function setCreatedAtAttr()
    {
        return date('Y-m-d H:i:s');
    }

    protected function setUpdatedAtAttr()
    {
        return date('Y-m-d H:i:s');
    }

    public function product(): \think\model\relation\HasOne
    {
        return $this->hasOne('ProductModel', 'productSku', 'warehouseSku');
    }
}
