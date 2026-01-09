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
//                $list = explode(' ', $string);
//                if ($list[0] == '-USD') {
//                    return  $list[1] * -1;
//                } elseif ($list[0] == 'USD') {
//                    return  $list[1];
//                } else {
//                    return 0;
//                }
                return round($string, 2);
            }
        } else {
            return 0;
        }
    }

    static public function hdPaymentFormat($string): string
    {
        if (strlen($string) < 8) {
            $n = 8 - strlen($string);
            for ($i = 1; $i <= $n; $i++) {
                $string = '0' . $string;
            }

        }
        return $string;
    }
}
