<?php

namespace app\Manage\model;

use think\Cache;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Model;

class FinanceOrderShareValidate extends Model
{
    /**
     * @throws DbException
     */
    static public function CompleteIsShare($report_id): bool
    {
        // 检测财报分摊工作是否开始
        $report = FinanceReportModel::get($report_id);
        if ($report && $report['is_share']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @throws DbException
     */
    static public function CompleteStoreImport($report_id): bool
    {
        // 检测财报分摊工作是否开始
        $financeStoreObj = new FinanceStoreModel();
        $list = $financeStoreObj->where(['report_id' => $report_id])->select();
        if (count($list) > 0) {
            return true;
        } else {
            return false;
        }
    }

    static public function CompleteWayfairOrder(): bool
    {
        // 检测wayfair订单是否校验完毕
        $wayfairOrder = Cache::get('wayfairOrder');
        if (!empty($wayfairOrder)) {
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
            return false;
        } else {
            return true;
        }
    }
}
