<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class OrderCaptureModel extends Model
{
    protected $name = 'ecang_order_capture';

    protected $resultSetType = 'collection';
}
