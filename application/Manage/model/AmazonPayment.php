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

    public $orderFbaInventory = [];

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
                    "payment_id"                =>  $item[3],
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f',$item[29]),
                ];
            } elseif ($item[2] == 'Liquidations'
                || $item[2] == 'Liquidations Adjustments') {
                $this->orderLiquidationNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "order_id"                  =>  $item[3],
                    "sku"                       =>  $item[4],
                    "product_sales"             =>  sprintf('%.2f',$item[14]),
                    "transaction_fee"           =>  sprintf('%.2f',$item[27]),
                    "total"                     =>  sprintf('%.2f',$item[29]),
                ];
            } elseif ($item[2] == 'Adjustment'
                ||  $item[2] == 'A-to-z Guarantee Claim'
                ||  $item[2] == 'Fee Adjustment') {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "total"                     =>  sprintf('%.2f',$item[29]),
                ];
            } elseif ($item[2] == 'FBA Inventory Fee'
                || $item[2] == 'FBA Customer Return Fee') {
                $this->orderFbaInventory[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "description"               =>  $item[5],
                    "other"                     =>  sprintf('%.2f', $item[28]),
                    "total"                     =>  sprintf('%.2f', $item[29]),
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
            'orderAdjustmentNew'        =>  $this->orderAdjustmentNew,
            'orderFbaInventory'         =>  $this->orderFbaInventory
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
            } elseif ($item[2] == 'Liquidations'
                || $item[2] == 'Liquidations Adjustments') {
                $this->orderLiquidationNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "order_id"                  =>  $item[3],
                    "sku"                       =>  $item[4],
                    "product_sales"             =>  sprintf('%.2f',$item[13]),
                    "transaction_fee"           =>  sprintf('%.2f',$item[24]),
                    "total"                     =>  sprintf('%.2f',$item[26]),
                ];
            } elseif ($item[2] == 'Adjustment'
                ||  $item[2] == 'A-to-z Guarantee Claim'
                ||  $item[2] == 'Fee Adjustment') {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "total"                     =>  sprintf('%.2f',$item[26]),
                ];
            } elseif ($item[2] == 'FBA Inventory Fee'
                || $item[2] == 'FBA Customer Return Fee') {
                $this->orderFbaInventory[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "description"               =>  $item[5],
                    "other"                     =>  sprintf('%.2f', $item[25]),
                    "total"                     =>  sprintf('%.2f', $item[26]),
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
            'orderAdjustmentNew'        =>  $this->orderAdjustmentNew,
            'orderFbaInventory'         =>  $this->orderFbaInventory
        ];
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function amazon_de($excel, $tableId, $reportId): array
    {
        foreach ($excel as $item) {
            $orderObj = new OrderModel();
            $order = $orderObj->with(['details'])->where(['refNo|saleOrderCode' => $item[3]])->find();
            if ($order && $order['userAccount'] != $this->userAccount) {
                $this->userAccount = $order['userAccount'];
            }

            if ($item[2] == 'Bestellung') {
                $this->orderSaleNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "description"               =>  $item[5],
                    "quantity"                  =>  $item[6],
                    "fulfillment"               =>  $item[8],
                    "postal"                    =>  $item[11],
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[13]))),
                    "product_sales_tax"         =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[14]))),
                    "shipping_credits"          =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[15]))),
                    "shipping_credits_tax"      =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[16]))),
                    "gift_wrap_credits"         =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[17]))),
                    "gift_wrap_credits_tax"     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[18]))),
                    "regulatory_fee"            =>  0,
                    "regulatory_fee_tax"        =>  0,
                    "promotional_rebates"       =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[19]))),
                    "promotional_rebates_tax"   =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[20]))),
                    "marketplace_withheld_tax"  =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[21]))),
                    "selling_fees"              =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[22]))),
                    "fba_fees"                  =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[23]))),
                    "other_transaction_fees"    =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                    "other"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[25]))),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Erstattung') {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "description"               =>  $item[5],
                    "quantity"                  =>  $item[6],
                    "fulfillment"               =>  $item[8],
                    "postal"                    =>  $item[11],
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[13]))),
                    "product_sales_tax"         =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[14]))),
                    "shipping_credits"          =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[15]))),
                    "shipping_credits_tax"      =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[16]))),
                    "gift_wrap_credits"         =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[17]))),
                    "gift_wrap_credits_tax"     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[18]))),
                    "regulatory_fee"            =>  0,
                    "regulatory_fee_tax"        =>  0,
                    "promotional_rebates"       =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[19]))),
                    "promotional_rebates_tax"   =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[20]))),
                    "marketplace_withheld_tax"  =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[21]))),
                    "selling_fees"              =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[22]))),
                    "fba_fees"                  =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[23]))),
                    "other_transaction_fees"    =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                    "other"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[25]))),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Servicegebuhr') {
                $this->orderPromotionNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Shipping Services') {
                $this->orderShippingServiceNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Liquidationen'
                || $item[2] == 'Liquidationsanpassungen') {
                $this->orderLiquidationNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "order_id"                  =>  $item[3],
                    "sku"                       =>  $item[4],
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[13]))),
                    "transaction_fee"           =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Anpassungen') {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Versand durch Amazon Lagergebühr') {
                $this->orderFbaInventory[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "description"               =>  $item[5],
                    "other"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[25]))),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
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
            'orderAdjustmentNew'        =>  $this->orderAdjustmentNew,
            'orderFbaInventory'         =>  $this->orderFbaInventory
        ];
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function amazon_es($excel, $tableId, $reportId): array
    {
        foreach ($excel as $item) {
            $orderObj = new OrderModel();
            $order = $orderObj->with(['details'])->where(['refNo|saleOrderCode' => $item[3]])->find();
            if ($order && $order['userAccount'] != $this->userAccount) {
                $this->userAccount = $order['userAccount'];
            }

            if ($item[2] == 'Pedido') {
                $this->orderSaleNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "description"               =>  $item[5],
                    "quantity"                  =>  $item[6],
                    "fulfillment"               =>  $item[8],
                    "postal"                    =>  $item[11],
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[13]))),
                    "product_sales_tax"         =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[14]))),
                    "shipping_credits"          =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[15]))),
                    "shipping_credits_tax"      =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[16]))),
                    "gift_wrap_credits"         =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[17]))),
                    "gift_wrap_credits_tax"     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[18]))),
                    "regulatory_fee"            =>  0,
                    "regulatory_fee_tax"        =>  0,
                    "promotional_rebates"       =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[19]))),
                    "promotional_rebates_tax"   =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[20]))),
                    "marketplace_withheld_tax"  =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[21]))),
                    "selling_fees"              =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[22]))),
                    "fba_fees"                  =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[23]))),
                    "other_transaction_fees"    =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                    "other"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[25]))),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Reembolso') {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "description"               =>  $item[5],
                    "quantity"                  =>  $item[6],
                    "fulfillment"               =>  $item[8],
                    "postal"                    =>  $item[11],
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[13]))),
                    "product_sales_tax"         =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[14]))),
                    "shipping_credits"          =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[15]))),
                    "shipping_credits_tax"      =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[16]))),
                    "gift_wrap_credits"         =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[17]))),
                    "gift_wrap_credits_tax"     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[18]))),
                    "regulatory_fee"            =>  0,
                    "regulatory_fee_tax"        =>  0,
                    "promotional_rebates"       =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[19]))),
                    "promotional_rebates_tax"   =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[20]))),
                    "marketplace_withheld_tax"  =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[21]))),
                    "selling_fees"              =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[22]))),
                    "fba_fees"                  =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[23]))),
                    "other_transaction_fees"    =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                    "other"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[25]))),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Tarifa de prestación de servicio') {
                $this->orderPromotionNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Shipping Services') {
                $this->orderShippingServiceNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Liquidaciónes') {
                $this->orderLiquidationNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "order_id"                  =>  $item[3],
                    "sku"                       =>  $item[4],
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[13]))),
                    "transaction_fee"           =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Ajuste') {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Tarifas de inventario de Logística de Amazon') {
                $this->orderFbaInventory[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "description"               =>  $item[5],
                    "other"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[25]))),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
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
            'orderAdjustmentNew'        =>  $this->orderAdjustmentNew,
            'orderFbaInventory'         =>  $this->orderFbaInventory
        ];
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function amazon_fr($excel, $tableId, $reportId): array
    {
        foreach ($excel as $item) {
            $orderObj = new OrderModel();
            $order = $orderObj->with(['details'])->where(['refNo|saleOrderCode' => $item[3]])->find();
            if ($order && $order['userAccount'] != $this->userAccount) {
                $this->userAccount = $order['userAccount'];
            }

            if ($item[2] == 'Commande') {
                $this->orderSaleNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "description"               =>  $item[5],
                    "quantity"                  =>  $item[6],
                    "fulfillment"               =>  $item[8],
                    "postal"                    =>  $item[11],
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[13]))),
                    "product_sales_tax"         =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[14]))),
                    "shipping_credits"          =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[15]))),
                    "shipping_credits_tax"      =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[16]))),
                    "gift_wrap_credits"         =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[17]))),
                    "gift_wrap_credits_tax"     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[18]))),
                    "regulatory_fee"            =>  0,
                    "regulatory_fee_tax"        =>  0,
                    "promotional_rebates"       =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[19]))),
                    "promotional_rebates_tax"   =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[20]))),
                    "marketplace_withheld_tax"  =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[21]))),
                    "selling_fees"              =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[22]))),
                    "fba_fees"                  =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[23]))),
                    "other_transaction_fees"    =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                    "other"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[25]))),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Remboursement') {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "description"               =>  $item[5],
                    "quantity"                  =>  $item[6],
                    "fulfillment"               =>  $item[8],
                    "postal"                    =>  $item[11],
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[13]))),
                    "product_sales_tax"         =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[14]))),
                    "shipping_credits"          =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[15]))),
                    "shipping_credits_tax"      =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[16]))),
                    "gift_wrap_credits"         =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[17]))),
                    "gift_wrap_credits_tax"     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[18]))),
                    "regulatory_fee"            =>  0,
                    "regulatory_fee_tax"        =>  0,
                    "promotional_rebates"       =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[19]))),
                    "promotional_rebates_tax"   =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[20]))),
                    "marketplace_withheld_tax"  =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[21]))),
                    "selling_fees"              =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[22]))),
                    "fba_fees"                  =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[23]))),
                    "other_transaction_fees"    =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                    "other"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[25]))),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Frais de service') {
                $this->orderPromotionNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Shipping Services') {
                $this->orderShippingServiceNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Liquidations') {
                $this->orderLiquidationNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "order_id"                  =>  $item[3],
                    "sku"                       =>  $item[4],
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[13]))),
                    "transaction_fee"           =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Ajustement') {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Frais de stock Expédié par Amazon') {
                $this->orderFbaInventory[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "description"               =>  $item[5],
                    "other"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[25]))),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
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
            'orderAdjustmentNew'        =>  $this->orderAdjustmentNew,
            'orderFbaInventory'         =>  $this->orderFbaInventory
        ];
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function amazon_it($excel, $tableId, $reportId): array
    {
        foreach ($excel as $item) {
            $orderObj = new OrderModel();
            $order = $orderObj->with(['details'])->where(['refNo|saleOrderCode' => $item[3]])->find();
            if ($order && $order['userAccount'] != $this->userAccount) {
                $this->userAccount = $order['userAccount'];
            }

            if ($item[2] == 'Ordine') {
                $this->orderSaleNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "description"               =>  $item[5],
                    "quantity"                  =>  $item[6],
                    "fulfillment"               =>  $item[8],
                    "postal"                    =>  $item[11],
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[13]))),
                    "product_sales_tax"         =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[14]))),
                    "shipping_credits"          =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[15]))),
                    "shipping_credits_tax"      =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[16]))),
                    "gift_wrap_credits"         =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[17]))),
                    "gift_wrap_credits_tax"     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[18]))),
                    "regulatory_fee"            =>  0,
                    "regulatory_fee_tax"        =>  0,
                    "promotional_rebates"       =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[19]))),
                    "promotional_rebates_tax"   =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[20]))),
                    "marketplace_withheld_tax"  =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[21]))),
                    "selling_fees"              =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[22]))),
                    "fba_fees"                  =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[23]))),
                    "other_transaction_fees"    =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                    "other"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[25]))),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Rimborso') {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "description"               =>  $item[5],
                    "quantity"                  =>  $item[6],
                    "fulfillment"               =>  $item[8],
                    "postal"                    =>  $item[11],
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[13]))),
                    "product_sales_tax"         =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[14]))),
                    "shipping_credits"          =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[15]))),
                    "shipping_credits_tax"      =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[16]))),
                    "gift_wrap_credits"         =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[17]))),
                    "gift_wrap_credits_tax"     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[18]))),
                    "regulatory_fee"            =>  0,
                    "regulatory_fee_tax"        =>  0,
                    "promotional_rebates"       =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[19]))),
                    "promotional_rebates_tax"   =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[20]))),
                    "marketplace_withheld_tax"  =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[21]))),
                    "selling_fees"              =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[22]))),
                    "fba_fees"                  =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[23]))),
                    "other_transaction_fees"    =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                    "other"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[25]))),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Commissione di servizio') {
                $this->orderPromotionNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Shipping Services') {
                $this->orderShippingServiceNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Tariffe del Programma di liquidazione') {
                $this->orderLiquidationNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "order_id"                  =>  $item[3],
                    "sku"                       =>  $item[4],
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[13]))),
                    "transaction_fee"           =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Modifica') {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                ];
            } elseif ($item[2] == 'Costo di stoccaggio Logistica di Amazon') {
                $this->orderFbaInventory[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "description"               =>  $item[5],
                    "other"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[25]))),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
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
            'orderAdjustmentNew'        =>  $this->orderAdjustmentNew,
            'orderFbaInventory'         =>  $this->orderFbaInventory
        ];
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function walmart($excel, $tableId, $reportId): array
    {
        foreach ($excel as $item) {
            $orderObj = new OrderModel();
            $order = $orderObj->with(['details'])->where(['refNo|saleOrderCode' => $item[2]])->find();
            if ($order && $order['userAccount'] != $this->userAccount) {
                $this->userAccount = $order['userAccount'];
            }

            if ($item[5] == 'SALE') {
                $this->orderSaleNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  number_format($item[2], 0, '', ''),
                    "sku"                       =>  $item[8],
                    "quantity"                  =>  $item[7],
                    "postal"                    =>  $item[18],
                    "product_sales"             =>  sprintf('%.2f',$item[24]),
                    "selling_fees"              =>  sprintf('%.2f',$item[22]),
                    "fba_fees"                  =>  0,
                ];
            } elseif ($item[5] == 'REFUNDED') {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  number_format($item[2], 0, '', ''),
                    "sku"                       =>  $item[8],
                    "quantity"                  =>  $item[7],
                    "postal"                    =>  $item[18],
                    "product_sales"             =>  sprintf('%.2f',$item[24]),
                    "selling_fees"              =>  sprintf('%.2f',$item[22]),
                    "fba_fees"                  =>  0,
                ];
            } elseif ($item[5] == 'Adjustment') {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  number_format($item[2], 0, '', ''),
                    "sku"                       =>  $item[8],
                    "total"                     =>  sprintf('%.2f',$item[24]),
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
            'orderAdjustmentNew'        =>  $this->orderAdjustmentNew,
            'orderFbaInventory'         =>  $this->orderFbaInventory
        ];
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function wayfair($excel, $tableId, $reportId): array
    {
        foreach ($excel as $item) {
            $orderObj = new OrderModel();
            $order = $orderObj->with(['details'])->where(['refNo|saleOrderCode' => $item[1]])->find();
            if ($order && $order['userAccount'] != $this->userAccount) {
                $this->userAccount = $order['userAccount'];
            }

            if ($item[11] == 'Order') {
                $this->orderSaleNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[1],
                    "sku"                       =>  $item[12],
                    "quantity"                  =>  $item[13],
                    "product_sales"             =>  sprintf('%.2f',$item[3]),
                    "selling_fees"              =>  sprintf('%.2f',$item[4]),
                    "fba_fees"                  =>  0,
                ];
            } elseif ($item[11] == 'Return') {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[1],
                    "sku"                       =>  $item[12],
                    "quantity"                  =>  $item[13],
                    "product_sales"             =>  sprintf('%.2f',$item[3]),
                    "selling_fees"              =>  0,
                    "fba_fees"                  =>  0,
                ];
            } else {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[1],
                    "sku"                       =>  $item[12],
                    "total"                     =>  sprintf('%.2f',$item[3]),
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
            'orderAdjustmentNew'        =>  $this->orderAdjustmentNew,
            'orderFbaInventory'         =>  $this->orderFbaInventory
        ];
    }
}
