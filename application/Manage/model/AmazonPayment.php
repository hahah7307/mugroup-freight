<?php

namespace app\Manage\model;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Model;

class AmazonPayment extends Model
{
    public $userAccount = '';

    public $orderSaleNew = [];

    public $orderRefundNew = [];

    public $orderPromotionNew = [];

    public $orderShippingServiceNew = [];

    public $orderLiquidationNew = [];

    public $orderAdjustmentNew = [];

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function amazon_us($excel, $tableId, $reportId): array
    {
        foreach ($excel as $item) {
            $orderObj = new OrderModel();
            $order = $orderObj->with(['details'])->where(['refNo|saleOrderCode' => $item[3]])->find();
            if ($order && $order['userAccount'] != $this->userAccount) {
                $this->userAccount = $order['userAccount'];
            }

            if ($item[2] == 'Order') {
                $this->orderSaleNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "description"               =>  $item[5],
                    "quantity"                  =>  $item[6],
                    "fulfillment"               =>  $item[9],
                    "postal"                    =>  $item[12],
                    "product_sales"             =>  sprintf('%.2f',$item[14]),
                    "product_sales_tax"         =>  sprintf('%.2f',$item[15]),
                    "shipping_credits"          =>  sprintf('%.2f',$item[16]),
                    "shipping_credits_tax"      =>  sprintf('%.2f',$item[17]),
                    "gift_wrap_credits"         =>  sprintf('%.2f',$item[18]),
                    "gift_wrap_credits_tax"     =>  sprintf('%.2f',$item[19]),
                    "regulatory_fee"            =>  sprintf('%.2f',$item[20]),
                    "regulatory_fee_tax"        =>  sprintf('%.2f',$item[21]),
                    "promotional_rebates"       =>  sprintf('%.2f',$item[22]),
                    "promotional_rebates_tax"   =>  sprintf('%.2f',$item[23]),
                    "marketplace_withheld_tax"  =>  sprintf('%.2f',$item[24]),
                    "selling_fees"              =>  sprintf('%.2f',$item[25]),
                    "fba_fees"                  =>  sprintf('%.2f',$item[26]),
                    "other_transaction_fees"    =>  sprintf('%.2f',$item[27]),
                    "other"                     =>  sprintf('%.2f',$item[28]),
                    "total"                     =>  sprintf('%.2f',$item[29]),
                ];
            } elseif ($item[2] == 'Refund') {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "description"               =>  $item[5],
                    "quantity"                  =>  $item[6],
                    "fulfillment"               =>  $item[9],
                    "postal"                    =>  $item[12],
                    "product_sales"             =>  sprintf('%.2f',$item[14]),
                    "product_sales_tax"         =>  sprintf('%.2f',$item[15]),
                    "shipping_credits"          =>  sprintf('%.2f',$item[16]),
                    "shipping_credits_tax"      =>  sprintf('%.2f',$item[17]),
                    "gift_wrap_credits"         =>  sprintf('%.2f',$item[18]),
                    "gift_wrap_credits_tax"     =>  sprintf('%.2f',$item[19]),
                    "regulatory_fee"            =>  sprintf('%.2f',$item[20]),
                    "regulatory_fee_tax"        =>  sprintf('%.2f',$item[21]),
                    "promotional_rebates"       =>  sprintf('%.2f',$item[22]),
                    "promotional_rebates_tax"   =>  sprintf('%.2f',$item[23]),
                    "marketplace_withheld_tax"  =>  sprintf('%.2f',$item[24]),
                    "selling_fees"              =>  sprintf('%.2f',$item[25]),
                    "fba_fees"                  =>  sprintf('%.2f',$item[26]),
                    "other_transaction_fees"    =>  sprintf('%.2f',$item[27]),
                    "other"                     =>  sprintf('%.2f',$item[28]),
                    "total"                     =>  sprintf('%.2f',$item[29]),
                ];
            } elseif ($item[2] == 'Service Fee') {
                $this->orderPromotionNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f',$item[29]),
                ];
            } elseif ($item[2] == 'Shipping Services') {
                $this->orderShippingServiceNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"
                    =>  $item[3],
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f',$item[29]),
                ];
            } elseif ($item[2] == 'Liquidations') {
                $this->orderLiquidationNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "order_id"                  =>  $item[3],
                    "sku"                       =>  $item[4],
                    "product_sales"             =>  sprintf('%.2f',$item[14]),
                    "transaction_fee"           =>  sprintf('%.2f',$item[27]),
                    "total"                     =>  sprintf('%.2f',$item[29]),
                ];
            } elseif ($item[2] == 'Adjustment') {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "total"                     =>  sprintf('%.2f',$item[29]),
                ];
            }
        }

        return [
            'userAccount'               =>  $this->userAccount,
            'orderSaleNew'              =>  $this->orderSaleNew,
            'orderRefundNew'            =>  $this->orderRefundNew,
            'orderPromotionNew'         =>  $this->orderPromotionNew,
            'orderShippingServiceNew'   =>  $this->orderShippingServiceNew,
            'orderLiquidationNew'       =>  $this->orderLiquidationNew,
            'orderAdjustmentNew'        =>  $this->orderAdjustmentNew
        ];
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function amazon_uk($excel, $tableId, $reportId): array
    {
        foreach ($excel as $item) {
            $orderObj = new OrderModel();
            $order = $orderObj->with(['details'])->where(['refNo|saleOrderCode' => $item[3]])->find();
            if ($order && $order['userAccount'] != $this->userAccount) {
                $this->userAccount = $order['userAccount'];
            }

            if ($item[2] == 'Order') {
                $this->orderSaleNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "description"               =>  $item[5],
                    "quantity"                  =>  $item[6],
                    "fulfillment"               =>  $item[8],
                    "postal"                    =>  $item[11],
                    "product_sales"             =>  sprintf('%.2f',$item[13]),
                    "product_sales_tax"         =>  sprintf('%.2f',$item[14]),
                    "shipping_credits"          =>  sprintf('%.2f',$item[15]),
                    "shipping_credits_tax"      =>  sprintf('%.2f',$item[16]),
                    "gift_wrap_credits"         =>  sprintf('%.2f',$item[17]),
                    "gift_wrap_credits_tax"     =>  sprintf('%.2f',$item[18]),
                    "regulatory_fee"            =>  0,
                    "regulatory_fee_tax"        =>  0,
                    "promotional_rebates"       =>  sprintf('%.2f',$item[19]),
                    "promotional_rebates_tax"   =>  sprintf('%.2f',$item[20]),
                    "marketplace_withheld_tax"  =>  sprintf('%.2f',$item[21]),
                    "selling_fees"              =>  sprintf('%.2f',$item[22]),
                    "fba_fees"                  =>  sprintf('%.2f',$item[23]),
                    "other_transaction_fees"    =>  sprintf('%.2f',$item[24]),
                    "other"                     =>  sprintf('%.2f',$item[25]),
                    "total"                     =>  sprintf('%.2f',$item[26]),
                ];
            } elseif ($item[2] == 'Refund') {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "description"               =>  $item[5],
                    "quantity"                  =>  $item[6],
                    "fulfillment"               =>  $item[8],
                    "postal"                    =>  $item[11],
                    "product_sales"             =>  sprintf('%.2f',$item[13]),
                    "product_sales_tax"         =>  sprintf('%.2f',$item[14]),
                    "shipping_credits"          =>  sprintf('%.2f',$item[15]),
                    "shipping_credits_tax"      =>  sprintf('%.2f',$item[16]),
                    "gift_wrap_credits"         =>  sprintf('%.2f',$item[17]),
                    "gift_wrap_credits_tax"     =>  sprintf('%.2f',$item[18]),
                    "regulatory_fee"            =>  0,
                    "regulatory_fee_tax"        =>  0,
                    "promotional_rebates"       =>  sprintf('%.2f',$item[19]),
                    "promotional_rebates_tax"   =>  sprintf('%.2f',$item[20]),
                    "marketplace_withheld_tax"  =>  sprintf('%.2f',$item[21]),
                    "selling_fees"              =>  sprintf('%.2f',$item[22]),
                    "fba_fees"                  =>  sprintf('%.2f',$item[23]),
                    "other_transaction_fees"    =>  sprintf('%.2f',$item[24]),
                    "other"                     =>  sprintf('%.2f',$item[25]),
                    "total"                     =>  sprintf('%.2f',$item[26]),
                ];
            } elseif ($item[2] == 'Service Fee') {
                $this->orderPromotionNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f',$item[26]),
                ];
            } elseif ($item[2] == 'Shipping Services') {
                $this->orderShippingServiceNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f',$item[26]),
                ];
            } elseif ($item[2] == 'Liquidations') {
                $this->orderLiquidationNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "order_id"                  =>  $item[3],
                    "sku"                       =>  $item[4],
                    "product_sales"             =>  sprintf('%.2f',$item[13]),
                    "transaction_fee"           =>  sprintf('%.2f',$item[24]),
                    "total"                     =>  sprintf('%.2f',$item[26]),
                ];
            } elseif ($item[2] == 'Adjustment') {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "total"                     =>  sprintf('%.2f',$item[26]),
                ];
            }
        }

        return [
            'userAccount'               =>  $this->userAccount,
            'orderSaleNew'              =>  $this->orderSaleNew,
            'orderRefundNew'            =>  $this->orderRefundNew,
            'orderPromotionNew'         =>  $this->orderPromotionNew,
            'orderShippingServiceNew'   =>  $this->orderShippingServiceNew,
            'orderLiquidationNew'       =>  $this->orderLiquidationNew,
            'orderAdjustmentNew'        =>  $this->orderAdjustmentNew
        ];
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function amazon_eu($excel, $tableId, $reportId): array
    {
        foreach ($excel as $item) {
            $orderObj = new OrderModel();
            $order = $orderObj->with(['details'])->where(['refNo|saleOrderCode' => $item[3]])->find();
            if ($order && $order['userAccount'] != $this->userAccount) {
                $this->userAccount = $order['userAccount'];
            }

            if ($item[2] == 'Order') {
                $this->orderSaleNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "description"               =>  $item[5],
                    "quantity"                  =>  $item[6],
                    "fulfillment"               =>  $item[8],
                    "postal"                    =>  $item[11],
                    "product_sales"             =>  sprintf('%.2f',$item[13]),
                    "product_sales_tax"         =>  sprintf('%.2f',$item[14]),
                    "shipping_credits"          =>  sprintf('%.2f',$item[15]),
                    "shipping_credits_tax"      =>  sprintf('%.2f',$item[16]),
                    "gift_wrap_credits"         =>  sprintf('%.2f',$item[17]),
                    "gift_wrap_credits_tax"     =>  sprintf('%.2f',$item[18]),
                    "regulatory_fee"            =>  0,
                    "regulatory_fee_tax"        =>  0,
                    "promotional_rebates"       =>  sprintf('%.2f',$item[19]),
                    "promotional_rebates_tax"   =>  sprintf('%.2f',$item[20]),
                    "marketplace_withheld_tax"  =>  sprintf('%.2f',$item[21]),
                    "selling_fees"              =>  sprintf('%.2f',$item[22]),
                    "fba_fees"                  =>  sprintf('%.2f',$item[23]),
                    "other_transaction_fees"    =>  sprintf('%.2f',$item[24]),
                    "other"                     =>  sprintf('%.2f',$item[25]),
                    "total"                     =>  sprintf('%.2f',$item[26]),
                ];
            } elseif ($item[2] == 'Refund') {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "description"               =>  $item[5],
                    "quantity"                  =>  $item[6],
                    "fulfillment"               =>  $item[8],
                    "postal"                    =>  $item[11],
                    "product_sales"             =>  sprintf('%.2f',$item[13]),
                    "product_sales_tax"         =>  sprintf('%.2f',$item[14]),
                    "shipping_credits"          =>  sprintf('%.2f',$item[15]),
                    "shipping_credits_tax"      =>  sprintf('%.2f',$item[16]),
                    "gift_wrap_credits"         =>  sprintf('%.2f',$item[17]),
                    "gift_wrap_credits_tax"     =>  sprintf('%.2f',$item[18]),
                    "regulatory_fee"            =>  0,
                    "regulatory_fee_tax"        =>  0,
                    "promotional_rebates"       =>  sprintf('%.2f',$item[19]),
                    "promotional_rebates_tax"   =>  sprintf('%.2f',$item[20]),
                    "marketplace_withheld_tax"  =>  sprintf('%.2f',$item[21]),
                    "selling_fees"              =>  sprintf('%.2f',$item[22]),
                    "fba_fees"                  =>  sprintf('%.2f',$item[23]),
                    "other_transaction_fees"    =>  sprintf('%.2f',$item[24]),
                    "other"                     =>  sprintf('%.2f',$item[25]),
                    "total"                     =>  sprintf('%.2f',$item[26]),
                ];
            } elseif ($item[2] == 'Service Fee') {
                $this->orderPromotionNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f',$item[26]),
                ];
            } elseif ($item[2] == 'Shipping Services') {
                $this->orderShippingServiceNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f',$item[26]),
                ];
            } elseif ($item[2] == 'Liquidations') {
                $this->orderLiquidationNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "order_id"                  =>  $item[3],
                    "sku"                       =>  $item[4],
                    "product_sales"             =>  sprintf('%.2f',$item[13]),
                    "transaction_fee"           =>  sprintf('%.2f',$item[24]),
                    "total"                     =>  sprintf('%.2f',$item[26]),
                ];
            } elseif ($item[2] == 'Adjustment') {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "total"                     =>  sprintf('%.2f',$item[26]),
                ];
            }
        }

        return [
            'userAccount'               =>  $this->userAccount,
            'orderSaleNew'              =>  $this->orderSaleNew,
            'orderRefundNew'            =>  $this->orderRefundNew,
            'orderPromotionNew'         =>  $this->orderPromotionNew,
            'orderShippingServiceNew'   =>  $this->orderShippingServiceNew,
            'orderLiquidationNew'       =>  $this->orderLiquidationNew,
            'orderAdjustmentNew'        =>  $this->orderAdjustmentNew
        ];
    }
}
