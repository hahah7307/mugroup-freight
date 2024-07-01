<?php

namespace app\Manage\model;

use think\Model;

class FinanceOrderRefundModel extends Model
{
    protected $name = 'finance_order_refund';

    protected $resultSetType = 'collection';

    public function details(): \think\model\relation\HasMany
    {
        return $this->hasMany('OrderDetailModel', 'order_id');
    }

    static public function wayfairPaymentSkuCount($payment_id)
    {
        $statisticObj = new FinanceOrderStatisticsModel();
        $list = $statisticObj->where(['payment_id' => $payment_id])->select();
        $qtyList = [];
        foreach ($list as $item) {
            $qtyList[] = $statisticObj->where(['payment_id' => $payment_id, 'warehouse_sku' => $item['warehouse_sku']])->sum('qty');
        }

        return !empty($qtyList) ? max($qtyList) : 1;
    }
}
