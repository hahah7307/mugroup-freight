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

    static public function sheinNumberFormat($string)
    {
        if (!empty($string)) {
            if ($string == "/") {
                return 0;
            } else {
                $list = explode(' ', $string);
                if ($list[0] == '-USD') {
                    return  $list[1] * -1;
                } elseif ($list[0] == 'USD') {
                    return  $list[1];
                } else {
                    return 0;
                }
            }
        } else {
            return 0;
        }
    }
}
