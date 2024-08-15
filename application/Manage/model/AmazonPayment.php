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

    public $orderAdjustmentWfs = [];

    public $orderFbaInventory = [];

    public $orderTransferNew = [];

    public $orderWayfairCoreRefundAdjust = [];

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

            // 过滤sku的不被压缩宽度的半角空格 asc码为 160和190的组合 0xC2 0xA0
            $sku = trim($item[4]);
            $sku_new = '';
            for ($i = 0; $i < strlen($sku); $i++) {
                if (ord($sku[$i]) != 160 && ord($sku[$i]) != 194) {
                    $sku_new .= $sku[$i];
                }
            }

            if ($item[2] == 'Order') {
                $this->orderSaleNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  trim($item[3]),
                    "sku"                       =>  trim($sku_new),
                    "description"               =>  $item[5],
                    "quantity"                  =>  $item[6],
                    "fulfillment"               =>  $item[9],
                    "postal"                    =>  $item[12],
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '', $item[14])),
                    "product_sales_tax"         =>  sprintf('%.2f', str_replace(',', '', $item[15])),
                    "shipping_credits"          =>  sprintf('%.2f', str_replace(',', '', $item[16])),
                    "shipping_credits_tax"      =>  sprintf('%.2f', str_replace(',', '', $item[17])),
                    "gift_wrap_credits"         =>  sprintf('%.2f', str_replace(',', '', $item[18])),
                    "gift_wrap_credits_tax"     =>  sprintf('%.2f', str_replace(',', '', $item[19])),
                    "regulatory_fee"            =>  sprintf('%.2f', str_replace(',', '', $item[20])),
                    "regulatory_fee_tax"        =>  sprintf('%.2f', str_replace(',', '', $item[21])),
                    "promotional_rebates"       =>  sprintf('%.2f', str_replace(',', '', $item[22])),
                    "promotional_rebates_tax"   =>  sprintf('%.2f', str_replace(',', '', $item[23])),
                    "marketplace_withheld_tax"  =>  sprintf('%.2f', str_replace(',', '', $item[24])),
                    "selling_fees"              =>  sprintf('%.2f', str_replace(',', '', $item[25])),
                    "fba_fees"                  =>  sprintf('%.2f', str_replace(',', '', $item[26])),
                    "other_transaction_fees"    =>  sprintf('%.2f', str_replace(',', '', $item[27])),
                    "other"                     =>  sprintf('%.2f', str_replace(',', '', $item[28])),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[29])),
                ];
            } elseif ($item[2] == 'Refund'
                ||  $item[2] == 'Chargeback Refund'
            ) {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  trim($item[3]),
                    "sku"                       =>  trim($sku_new),
                    "description"               =>  $item[5],
                    "quantity"                  =>  $item[6],
                    "fulfillment"               =>  $item[9],
                    "postal"                    =>  $item[12],
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '', $item[14])),
                    "product_sales_tax"         =>  sprintf('%.2f', str_replace(',', '', $item[15])),
                    "shipping_credits"          =>  sprintf('%.2f', str_replace(',', '', $item[16])),
                    "shipping_credits_tax"      =>  sprintf('%.2f', str_replace(',', '', $item[17])),
                    "gift_wrap_credits"         =>  sprintf('%.2f', str_replace(',', '', $item[18])),
                    "gift_wrap_credits_tax"     =>  sprintf('%.2f', str_replace(',', '', $item[19])),
                    "regulatory_fee"            =>  sprintf('%.2f', str_replace(',', '', $item[20])),
                    "regulatory_fee_tax"        =>  sprintf('%.2f', str_replace(',', '', $item[21])),
                    "promotional_rebates"       =>  sprintf('%.2f', str_replace(',', '', $item[22])),
                    "promotional_rebates_tax"   =>  sprintf('%.2f', str_replace(',', '', $item[23])),
                    "marketplace_withheld_tax"  =>  sprintf('%.2f', str_replace(',', '', $item[24])),
                    "selling_fees"              =>  sprintf('%.2f', str_replace(',', '', $item[25])),
                    "fba_fees"                  =>  sprintf('%.2f', str_replace(',', '', $item[26])),
                    "other_transaction_fees"    =>  sprintf('%.2f', str_replace(',', '', $item[27])),
                    "other"                     =>  sprintf('%.2f', str_replace(',', '', $item[28])),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[29])),
                ];
            } elseif ($item[2] == 'Service Fee'
                ||  $item[2] == 'Deal Fee'
            ) {
                $this->orderPromotionNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[29])),
                ];
            } elseif ($item[2] == 'Shipping Services') {
                $this->orderShippingServiceNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  trim($item[3]),
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[29])),
                ];
            } elseif ($item[2] == 'Liquidations'
                || $item[2] == 'Liquidations Adjustments') {
                $this->orderLiquidationNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "order_id"                  =>  trim($item[3]),
                    "sku"                       =>  trim($sku_new),
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '', $item[14])),
                    "transaction_fee"           =>  sprintf('%.2f', str_replace(',', '', $item[27])),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[29])),
                ];
            } elseif ($item[2] == 'Adjustment'
                ||  $item[2] == 'A-to-z Guarantee Claim'
                ||  $item[2] == 'SAFE-T reimbursement'
                ||  $item[2] == 'Fee Adjustment') {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  trim($item[3]),
                    "sku"                       =>  trim($sku_new),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[29])),
                    "is_amazon"                 =>  1,
                ];
            } elseif ($item[2] == 'FBA Inventory Fee'
                || $item[2] == 'FBA Customer Return Fee') {
                $this->orderFbaInventory[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  trim($item[3]),
                    "description"               =>  $item[5],
                    "other"                     =>  sprintf('%.2f', $item[28]),
                    "total"                     =>  sprintf('%.2f', $item[29]),
                ];
            } elseif ($item[2] == 'Transfer') {
                $this->orderTransferNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[29])),
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
            'orderFbaInventory'         =>  $this->orderFbaInventory,
            'orderTransferNew'          =>  $this->orderTransferNew
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
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '', $item[13])),
                    "product_sales_tax"         =>  sprintf('%.2f', str_replace(',', '', $item[14])),
                    "shipping_credits"          =>  sprintf('%.2f', str_replace(',', '', $item[15])),
                    "shipping_credits_tax"      =>  sprintf('%.2f', str_replace(',', '', $item[16])),
                    "gift_wrap_credits"         =>  sprintf('%.2f', str_replace(',', '', $item[17])),
                    "gift_wrap_credits_tax"     =>  sprintf('%.2f', str_replace(',', '', $item[18])),
                    "regulatory_fee"            =>  0,
                    "regulatory_fee_tax"        =>  0,
                    "promotional_rebates"       =>  sprintf('%.2f', str_replace(',', '', $item[19])),
                    "promotional_rebates_tax"   =>  sprintf('%.2f', str_replace(',', '', $item[20])),
                    "marketplace_withheld_tax"  =>  sprintf('%.2f', str_replace(',', '', $item[21])),
                    "selling_fees"              =>  sprintf('%.2f', str_replace(',', '', $item[22])),
                    "fba_fees"                  =>  sprintf('%.2f', str_replace(',', '', $item[23])),
                    "other_transaction_fees"    =>  sprintf('%.2f', str_replace(',', '', $item[24])),
                    "other"                     =>  sprintf('%.2f', str_replace(',', '', $item[25])),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[26])),
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
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '', $item[13])),
                    "product_sales_tax"         =>  sprintf('%.2f', str_replace(',', '', $item[14])),
                    "shipping_credits"          =>  sprintf('%.2f', str_replace(',', '', $item[15])),
                    "shipping_credits_tax"      =>  sprintf('%.2f', str_replace(',', '', $item[16])),
                    "gift_wrap_credits"         =>  sprintf('%.2f', str_replace(',', '', $item[17])),
                    "gift_wrap_credits_tax"     =>  sprintf('%.2f', str_replace(',', '', $item[18])),
                    "regulatory_fee"            =>  0,
                    "regulatory_fee_tax"        =>  0,
                    "promotional_rebates"       =>  sprintf('%.2f', str_replace(',', '', $item[19])),
                    "promotional_rebates_tax"   =>  sprintf('%.2f', str_replace(',', '', $item[20])),
                    "marketplace_withheld_tax"  =>  sprintf('%.2f', str_replace(',', '', $item[21])),
                    "selling_fees"              =>  sprintf('%.2f', str_replace(',', '', $item[22])),
                    "fba_fees"                  =>  sprintf('%.2f', str_replace(',', '', $item[23])),
                    "other_transaction_fees"    =>  sprintf('%.2f', str_replace(',', '', $item[24])),
                    "other"                     =>  sprintf('%.2f', str_replace(',', '', $item[25])),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[26])),
                ];
            } elseif ($item[2] == 'Service Fee') {
                $this->orderPromotionNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[26])),
                ];
            } elseif ($item[2] == 'Shipping Services') {
                $this->orderShippingServiceNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[26])),
                ];
            } elseif ($item[2] == 'Liquidations'
                || $item[2] == 'Liquidations Adjustments') {
                $this->orderLiquidationNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "order_id"                  =>  $item[3],
                    "sku"                       =>  $item[4],
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '', $item[13])),
                    "transaction_fee"           =>  sprintf('%.2f', str_replace(',', '', $item[24])),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[26])),
                ];
            } elseif ($item[2] == 'Adjustment'
                ||  $item[2] == 'A-to-z Guarantee Claim'
                ||  $item[2] == 'Fee Adjustment') {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[26])),
                    "is_amazon"                 =>  1,
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
            } elseif ($item[2] == 'Transfer') {
                $this->orderTransferNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[26])),
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
            'orderFbaInventory'         =>  $this->orderFbaInventory,
            'orderTransferNew'          =>  $this->orderTransferNew
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
            } elseif ($item[2] == 'Servicegebühr') {
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
            } elseif ($item[2] == 'Anpassungen'
                || $item[2] == 'Anpassung') {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[3],
                    "sku"                       =>  $item[4],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[26]))),
                    "is_amazon"                 =>  1,
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
            } elseif ($item[2] == 'Übertrag') {
                $this->orderTransferNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "description"               =>  $item[5],
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
            'orderFbaInventory'         =>  $this->orderFbaInventory,
            'orderTransferNew'          =>  $this->orderTransferNew
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
            } elseif ($item[2] == 'Tarifa de prestación de servicio'
                || $item[2] == 'Tarifa de Oferta flash'
            ) {
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
                    "is_amazon"                 =>  1,
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
            } elseif ($item[2] == 'Transferir') {
                $this->orderTransferNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "description"               =>  $item[5],
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
            'orderFbaInventory'         =>  $this->orderFbaInventory,
            'orderTransferNew'          =>  $this->orderTransferNew
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
                    "is_amazon"                 =>  1,
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
            } elseif ($item[2] == 'Transfert') {
                $this->orderTransferNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "description"               =>  $item[5],
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
            'orderFbaInventory'         =>  $this->orderFbaInventory,
            'orderTransferNew'          =>  $this->orderTransferNew
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
                    "is_amazon"                 =>  1,
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
            } elseif ($item[2] == 'Trasferimento') {
                $this->orderTransferNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "description"               =>  $item[5],
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
            'orderFbaInventory'         =>  $this->orderFbaInventory,
            'orderTransferNew'          =>  $this->orderTransferNew
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
                    "fulfillment"               =>  "Seller",
                    "postal"                    =>  $item[18],
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '', $item[24])),
                    "selling_fees"              =>  sprintf('%.2f', str_replace(',', '', $item[22])) * -1,
                    "shipping_credits"          =>  0,
                    "gift_wrap_credits"         =>  0,
                    "regulatory_fee"            =>  0,
                    "promotional_rebates"       =>  0,
                    "fba_fees"                  =>  0,
                ];
                if ($item[71]) {
                    $this->orderAdjustmentNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "payment_id"                =>  number_format($item[2], 0, '', ''),
                        "sku"                       =>  $item[8],
                        "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[71])) * -1,
                    ];
                }
                if ($item[31]) {
                    $this->orderAdjustmentNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "payment_id"                =>  number_format($item[2], 0, '', ''),
                        "sku"                       =>  $item[8],
                        "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[31])) * -1,
                    ];
                }
            } elseif ($item[5] == 'REFUNDED') {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  number_format($item[2], 0, '', ''),
                    "sku"                       =>  $item[8],
                    "quantity"                  =>  $item[7],
                    "fulfillment"               =>  "Seller",
                    "postal"                    =>  $item[18],
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '', $item[25])),
                    "selling_fees"              =>  sprintf('%.2f', str_replace(',', '', $item[22])) * -1,
                    "shipping_credits"          =>  0,
                    "gift_wrap_credits"         =>  0,
                    "regulatory_fee"            =>  0,
                    "promotional_rebates"       =>  0,
                    "fba_fees"                  =>  0,
                ];
                if ($item[71]) {
                    $this->orderAdjustmentNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "payment_id"                =>  number_format($item[2], 0, '', ''),
                        "sku"                       =>  $item[8],
                        "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[71])) * -1,
                    ];
                }
            } elseif ($item[5] == 'ADJMNT') {
                if ($item[66] == "Walmart-fulfilled(WFS)" && $item[55] == 'WFS Fulfillment fee') {
                    // WFS尾程
                    $this->orderAdjustmentWfs[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "payment_id"                =>  number_format($item[2], 0, '', ''),
                        "sku"                       =>  $item[8],
                        "is_fulfillment"            =>  1,
                        "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[21])),
                    ];
                } elseif ($item[66] == "Walmart-fulfilled(WFS)" && $item[55] == '"WFS Return Shipping fee "') {
                    // WFS退运费
                    $this->orderAdjustmentWfs[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "payment_id"                =>  number_format($item[2], 0, '', ''),
                        "sku"                       =>  $item[8],
                        "is_return_shipping"        =>  1,
                        "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[21])),
                    ];
                } else {
                    $this->orderAdjustmentNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "payment_id"                =>  number_format($item[2], 0, '', ''),
                        "sku"                       =>  $item[8],
                        "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[21])),
                    ];
                }
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
            'orderFbaInventory'         =>  $this->orderFbaInventory,
            'orderTransferNew'          =>  $this->orderTransferNew,
            'orderAdjustmentWfs'        =>  $this->orderAdjustmentWfs
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
            $report = FinanceReportModel::get($reportId);
            $orderObj = new OrderModel();
            $order = $orderObj->with(['details'])->where(['refNo|saleOrderCode' => $item[2]])->find();
            if ($order && $order['userAccount'] != $this->userAccount) {
                $this->userAccount = $order['userAccount'];
            }
            $orderRefund = $orderObj->with(['details'])->where(['refNo|saleOrderCode' => $item[0]])->find();
            if ($orderRefund && $orderRefund['userAccount'] != $this->userAccount) {
                $this->userAccount = $orderRefund['userAccount'];
            }

            if ($item[5] == 'PENDING PAYMENT' && !strpos($item['0'], '_CM')) {
                $this->orderSaleNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[2],
                    "quantity"                  =>  '',
                    "fulfillment"               =>  "Seller",
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '', $item[4])),
                    "shipping_credits"          =>  0,
                    "gift_wrap_credits"         =>  0,
                    "regulatory_fee"            =>  0,
                    "promotional_rebates"       =>  0,
                    "selling_fees"              =>  round(sprintf('%.4f', str_replace(',', '', $item[4])) * 0.04 * -1, 2),
                    "fba_fees"                  =>  0,
                ];
            } elseif ($item[2] == 'Return') {
                if (is_numeric($item[9])) {
                    $count = FinanceOrderRefundModel::wayfairPaymentSkuCount($item[5]);
                    $this->orderRefundNew[] = [
                        "report_id"             =>  $reportId,
                        "table_id"              =>  $tableId,
                        "payment_id"            =>  substr($item[0], 0 , 11),
                        "sku"                   =>  $item[5],
                        "quantity"              =>  $count,
                        "fulfillment"           =>  "Seller",
                        "product_sales"         =>  sprintf('%.2f', str_replace(',', '', $item[9])),
                        "shipping_credits"      =>  0,
                        "gift_wrap_credits"     =>  0,
                        "regulatory_fee"        =>  0,
                        "promotional_rebates"   =>  0,
                        "selling_fees"          =>  0,
                        "fba_fees"              =>  0,
                    ];
                    $this->orderWayfairCoreRefundAdjust[] = [
                        'table_id'              =>  $tableId,
                        'invoice_no'            =>  substr($item[0], 0 , 11),
                        'invoice_date'          =>  $item[1],
                        'payment_id'            =>  substr($item[0], 0 , 11),
                        'sale_amount'           =>  0,
                        'status'                =>  2,
                        'currency'              =>  'USD',
                        'commission_rate'       =>  0,
                        'commission'            =>  0,
                        'collection'            =>  sprintf('%.2f', str_replace(',', '', $item[9])),
                        'calculate_month'       =>  date('Ym', strtotime($report['month'] . "-00")),
                        'user_account'          =>  $this->userAccount
                    ];
                }
            } elseif ($item[2] != 'Return') {
                if (is_numeric($item[9])) {
                    $this->orderAdjustmentNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "payment_id"                =>  substr($item[0], 0, 11),
                        "sku"                       =>  $item[5],
                        "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[9])),
                    ];
                    $this->orderWayfairCoreRefundAdjust[] = [
                        'table_id'              =>  $tableId,
                        'invoice_no'            =>  substr($item[0], 0 , 11),
                        'invoice_date'          =>  $item[1],
                        'payment_id'            =>  substr($item[0], 0 , 11),
                        'sale_amount'           =>  0,
                        'status'                =>  3,
                        'currency'              =>  'USD',
                        'commission_rate'       =>  0,
                        'commission'            =>  0,
                        'collection'            =>  sprintf('%.2f', str_replace(',', '', $item[9])),
                        'calculate_month'       =>  date('Ym', strtotime($report['month'] . "-00")),
                        'user_account'          =>  $this->userAccount
                    ];
                }
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
            'orderFbaInventory'         =>  $this->orderFbaInventory,
            'orderTransferNew'          =>  $this->orderTransferNew,
            'orderWayfairCore'          =>  $this->orderWayfairCoreRefundAdjust
        ];
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function shein($excel, $tableId, $reportId): array
    {
        foreach ($excel as $item) {
            $orderObj = new OrderModel();
            $order = $orderObj->with(['details'])->where(['refNo|saleOrderCode' => $item[1]])->find();
            if ($order && $order['userAccount'] != $this->userAccount) {
                $this->userAccount = $order['userAccount'];
            }

            if ($item[2] == '订单收入') {
                $this->orderSaleNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[1],
                    "quantity"                  =>  $item[8],
                    "fulfillment"               =>  "Seller",
                    "product_sales"             =>  FinanceOrderSaleModel::sheinNumberFormat($item[9]),
                    "selling_fees"              =>  FinanceOrderSaleModel::sheinNumberFormat($item[10]) + FinanceOrderSaleModel::sheinNumberFormat($item[11]) + FinanceOrderSaleModel::sheinNumberFormat($item[12]) + FinanceOrderSaleModel::sheinNumberFormat($item[13]) + FinanceOrderSaleModel::sheinNumberFormat($item[14]) + FinanceOrderSaleModel::sheinNumberFormat($item[15]) + FinanceOrderSaleModel::sheinNumberFormat($item[16]),
                    "shipping_credits"          =>  0,
                    "gift_wrap_credits"         =>  0,
                    "regulatory_fee"            =>  0,
                    "promotional_rebates"       =>  0,
                    "fba_fees"                  =>  0,
                ];
            } elseif ($item[2] == '订单退货') {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[1],
                    "quantity"                  =>  $item[8],
                    "fulfillment"               =>  "Seller",
                    "product_sales"             =>  FinanceOrderSaleModel::sheinNumberFormat($item[9]),
                    "selling_fees"              =>  0,
                    "shipping_credits"          =>  0,
                    "gift_wrap_credits"         =>  0,
                    "regulatory_fee"            =>  0,
                    "promotional_rebates"       =>  0,
                    "fba_fees"                  =>  0,
                ];
            } elseif ($item[2] == '违规处罚扣款'
            || $item[2] == '订单调整') {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[1],
                    "total"                     =>  FinanceOrderSaleModel::sheinNumberFormat($item[18]),
                ];
            } elseif ($item[2] == '退货履约服务费') {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[1],
                    "total"                     =>  FinanceOrderSaleModel::sheinNumberFormat($item[10]) + FinanceOrderSaleModel::sheinNumberFormat($item[11]) + FinanceOrderSaleModel::sheinNumberFormat($item[12]) + FinanceOrderSaleModel::sheinNumberFormat($item[13]) + FinanceOrderSaleModel::sheinNumberFormat($item[14]) + FinanceOrderSaleModel::sheinNumberFormat($item[15]) + FinanceOrderSaleModel::sheinNumberFormat($item[16]),
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
            'orderFbaInventory'         =>  $this->orderFbaInventory,
            'orderTransferNew'          =>  $this->orderTransferNew
        ];
    }
}
