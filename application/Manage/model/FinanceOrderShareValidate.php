<?php

namespace app\Manage\model;

use think\Cache;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Model;

class FinanceOrderShareValidate extends Model
{
    static public function CompleteWayfairOrder(): bool
    {
        // 检测wayfair订单是否校验完毕
        $wayfairOrder = Cache::get('wayfairOrder');
        if (!empty($wayfairOrder)) {
            $output = new Output();
            $output->writeln("Wayfair Unready");
            return false;
        } else {
            return true;
        }
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    static public function CompleteSheinOrder(): bool
    {
        // 检测shein订单是否校验完毕
        $orderSaleObj = new FinanceOrderSaleModel();
        $sheinOrder = $orderSaleObj->where('sku', null)->where(['payment_id' => [['like', 'GSUN%']]])->select();
        $financeOrderRefundObj = new FinanceOrderRefundModel();
        $sheinRefund = $financeOrderRefundObj->where('sku', null)->where(['payment_id' => [['like', 'GSUN%']]])->select();
        $financeOrderAdjustmentObj = new FinanceOrderAdjustmentModel();
        $sheinAdjustment = $financeOrderAdjustmentObj->where('sku', null)->where(['payment_id' => [['like', 'GSUN%']]])->select();
        if (count($sheinOrder) + count($sheinRefund) + count($sheinAdjustment) > 0) {
            $output = new Output();
            $output->writeln("Shein Unready");
            return false;
        } else {
            return true;
        }
    }
}
