<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class OrderAddressPostalModel extends Model
{
    protected $name = 'ecang_order_address_postal';

    protected $resultSetType = 'collection';
}
