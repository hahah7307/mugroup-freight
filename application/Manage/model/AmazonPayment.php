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

    public $orderSubscriptionNew = [];

    public $orderWayfairCoreRefundAdjust = [];

    public $orderTemuDetails = [];

    public $orderShippingNew = [];

    public $wildberriesOrderNotify = [];

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
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[0])),
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
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[0])),
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
            } elseif (($item[2] == 'Service Fee' && strpos($item[5], 'Coupon') !== false)
                || ($item[2] == 'Service Fee' && strpos($item[5], 'Vine Enrollment Fee') !== false)
                ||  $item[2] == 'Deal Fee'
            ) {
                $this->orderPromotionNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[29])),
                ];
            } elseif ($item[2] == 'Service Fee' && $item[5] == 'Subscription') {
                $this->orderSubscriptionNew[] = [
                    "report_id"                 => $reportId,
                    "table_id"                  => $tableId,
                    "description"               => $item[5],
                    "total"                     => sprintf('%.2f', str_replace(',', '', $item[29])),
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
                ||  $item[2] == 'FBA Transaction fees'
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
            'orderTransferNew'          =>  $this->orderTransferNew,
            'orderSubscriptionNew'      =>  $this->orderSubscriptionNew
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
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[0])),
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
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[0])),
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
            } elseif (($item[2] == 'Service Fee' && strpos($item[5], 'Coupon') !== false)
                || ($item[2] == 'Service Fee' && strpos($item[5], 'Vine Enrollment Fee') !== false)
                ||  $item[2] == 'Deal Fee'
            ) {
                $this->orderPromotionNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "description"               =>  $item[5],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[26])),
                ];
            } elseif ($item[2] == 'Service Fee' && $item[5] == 'Subscription') {
                $this->orderSubscriptionNew[] = [
                    "report_id"                 => $reportId,
                    "table_id"                  => $tableId,
                    "description"               => $item[5],
                    "total"                     => sprintf('%.2f', str_replace(',', '', $item[26])),
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
            'orderTransferNew'          =>  $this->orderTransferNew,
            'orderSubscriptionNew'      =>  $this->orderSubscriptionNew
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
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[0])),
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
                if (sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))) != 0) {
                    $this->orderAdjustmentNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "payment_id"                =>  $item[3],
                        "sku"                       =>  $item[4],
                        "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                        "is_amazon"                 =>  1,
                    ];
                }
            } elseif ($item[2] == 'Erstattung') {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[0])),
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
                if (sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))) != 0) {
                    $this->orderAdjustmentNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "payment_id"                =>  $item[3],
                        "sku"                       =>  $item[4],
                        "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                        "is_amazon"                 =>  1,
                    ];
                }
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
            'orderTransferNew'          =>  $this->orderTransferNew,
            'orderSubscriptionNew'      =>  $this->orderSubscriptionNew
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
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[0])),
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
                if (sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))) != 0) {
                    $this->orderAdjustmentNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "payment_id"                =>  $item[3],
                        "sku"                       =>  $item[4],
                        "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                        "is_amazon"                 =>  1,
                    ];
                }
            } elseif ($item[2] == 'Reembolso') {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[0])),
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
                if (sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))) != 0) {
                    $this->orderAdjustmentNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "payment_id"                =>  $item[3],
                        "sku"                       =>  $item[4],
                        "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                        "is_amazon"                 =>  1,
                    ];
                }
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
            'orderTransferNew'          =>  $this->orderTransferNew,
            'orderSubscriptionNew'      =>  $this->orderSubscriptionNew
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
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[0])),
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
                if (sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))) != 0) {
                    $this->orderAdjustmentNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "payment_id"                =>  $item[3],
                        "sku"                       =>  $item[4],
                        "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                        "is_amazon"                 =>  1,
                    ];
                }
            } elseif ($item[2] == 'Remboursement') {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[0])),
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
                if (sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))) != 0) {
                    $this->orderAdjustmentNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "payment_id"                =>  $item[3],
                        "sku"                       =>  $item[4],
                        "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                        "is_amazon"                 =>  1,
                    ];
                }
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
            } elseif ($item[2] == 'Frais de service') {
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
            'orderTransferNew'          =>  $this->orderTransferNew,
            'orderSubscriptionNew'      =>  $this->orderSubscriptionNew
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
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[0])),
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
                if (sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))) != 0) {
                    $this->orderAdjustmentNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "payment_id"                =>  $item[3],
                        "sku"                       =>  $item[4],
                        "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                        "is_amazon"                 =>  1,
                    ];
                }
            } elseif ($item[2] == 'Rimborso') {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[0])),
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
                if (sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))) != 0) {
                    $this->orderAdjustmentNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "payment_id"                =>  $item[3],
                        "sku"                       =>  $item[4],
                        "total"                     =>  sprintf('%.2f', str_replace(',', '.', str_replace('.', '', $item[24]))),
                        "is_amazon"                 =>  1,
                    ];
                }
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
            'orderTransferNew'          =>  $this->orderTransferNew,
            'orderSubscriptionNew'      =>  $this->orderSubscriptionNew
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
            $orderObj = new FinanceOrderStatisticsModel();
            $order = $orderObj->where(['payment_id|saleOrderCode' => $item[10]])->find();
            if ($order && $order['user_account'] != $this->userAccount) {
                $this->userAccount = $order['user_account'];
            }

            if ($item[6] == 'Sale') {
                if ($item[13] == 'Product Price'
                    || $item[13] == 'Promo Code'
                    || $item[13] == 'Shipping'
                    || $item[13] == 'Extra Savings'
                ) {
                    $this->orderSaleNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "date"                      =>  date('Y-m-d H:i:s', strtotime($item[5])),
                        "payment_id"                =>  number_format($item[10], 0, '', ''),
                        "sku"                       =>  $item[18],
                        "quantity"                  =>  $item[14],
                        "fulfillment"               =>  "Seller",
                        "postal"                    =>  $item[24],
                        "product_sales"             =>  sprintf('%.2f', str_replace(',', '', $item[12])),
                        "selling_fees"              =>  0,
                        "shipping_credits"          =>  0,
                        "gift_wrap_credits"         =>  0,
                        "regulatory_fee"            =>  0,
                        "promotional_rebates"       =>  0,
                        "fba_fees"                  =>  0,
                    ];
                }
                if ($item[13] == 'Commission on Product' || $item[13] == 'Commission on Shipping') {
                    $this->orderSaleNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "date"                      =>  date('Y-m-d H:i:s', strtotime($item[5])),
                        "payment_id"                =>  number_format($item[10], 0, '', ''),
                        "sku"                       =>  $item[18],
                        "quantity"                  =>  $item[14],
                        "fulfillment"               =>  "Seller",
                        "postal"                    =>  $item[24],
                        "product_sales"             =>  0,
                        "selling_fees"              =>  sprintf('%.2f', str_replace(',', '', $item[12])),
                        "shipping_credits"          =>  0,
                        "gift_wrap_credits"         =>  0,
                        "regulatory_fee"            =>  0,
                        "promotional_rebates"       =>  0,
                        "fba_fees"                  =>  0,
                    ];
                }
                if ($item[13] == 'Total Walmart Funded Savings') {
                    $this->orderAdjustmentNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "payment_id"                =>  number_format($item[10], 0, '', ''),
                        "sku"                       =>  $item[18],
                        "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[12])),
                    ];
                }
            } elseif ($item[6] == 'Refund') {
                if ($item[13] == 'Product Price' || $item[13] == 'Shipping' || $item[13] == 'ExcessRefundAdjustment') {
                    $this->orderRefundNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "date"                      =>  date('Y-m-d H:i:s', strtotime($item[5])),
                        "payment_id"                =>  number_format($item[10], 0, '', ''),
                        "sku"                       =>  $item[18],
                        "quantity"                  =>  $item[14],
                        "fulfillment"               =>  "Seller",
                        "postal"                    =>  $item[24],
                        "product_sales"             =>  sprintf('%.2f', str_replace(',', '', $item[12])),
                        "selling_fees"              =>  0,
                        "shipping_credits"          =>  0,
                        "gift_wrap_credits"         =>  0,
                        "regulatory_fee"            =>  0,
                        "promotional_rebates"       =>  0,
                        "fba_fees"                  =>  0,
                    ];
                }
                if ($item[13] == 'Commission on Product' || $item[13] == 'Commission on Shipping') {
                    $this->orderRefundNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "date"                      =>  date('Y-m-d H:i:s', strtotime($item[5])),
                        "payment_id"                =>  number_format($item[10], 0, '', ''),
                        "sku"                       =>  $item[18],
                        "quantity"                  =>  $item[14],
                        "fulfillment"               =>  "Seller",
                        "postal"                    =>  $item[24],
                        "product_sales"             =>  0,
                        "selling_fees"              =>  sprintf('%.2f', str_replace(',', '', $item[12])),
                        "shipping_credits"          =>  0,
                        "gift_wrap_credits"         =>  0,
                        "regulatory_fee"            =>  0,
                        "promotional_rebates"       =>  0,
                        "fba_fees"                  =>  0,
                    ];
                }
                if ($item[13] == 'Total Walmart Funded Savings') {
                    $this->orderAdjustmentNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "payment_id"                =>  number_format($item[10], 0, '', ''),
                        "sku"                       =>  $item[18],
                        "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[12])),
                    ];
                }
            } else {
                if (!strpos($item[13], 'tax') && $item[7] != "Walmart Product Advertising" && $item[7] != "SEM Marketing") {
                    $this->orderAdjustmentNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "payment_id"                =>  number_format($item[10], 0, '', ''),
                        "sku"                       =>  $item[18],
                        "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[12])),
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

            if (gettype($item[5]) == 'string' && $item[5] == 'PENDING PAYMENT' && !strpos($item['0'], '_CM')) {
                $this->orderSaleNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[1])),
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
            } elseif (gettype($item[2]) == 'string' && $item[2] == 'Return') {
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
            } else {
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
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[7])),
                    "payment_id"                =>  $item[1],
                    "fulfillment"               =>  "Seller",
                    "product_sales"             =>  FinanceOrderSaleModel::sheinNumberFormat($item[9]),
                    "selling_fees"              =>  FinanceOrderSaleModel::sheinNumberFormat($item[10]) + FinanceOrderSaleModel::sheinNumberFormat($item[11]) + FinanceOrderSaleModel::sheinNumberFormat($item[12]),
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
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[7])),
                    "payment_id"                =>  $item[1],
                    "fulfillment"               =>  "Seller",
                    "product_sales"             =>  FinanceOrderSaleModel::sheinNumberFormat($item[9]),
                    "selling_fees"              =>  FinanceOrderSaleModel::sheinNumberFormat($item[10]) + FinanceOrderSaleModel::sheinNumberFormat($item[11]) + FinanceOrderSaleModel::sheinNumberFormat($item[12]),
                    "shipping_credits"          =>  0,
                    "gift_wrap_credits"         =>  0,
                    "regulatory_fee"            =>  0,
                    "promotional_rebates"       =>  0,
                    "fba_fees"                  =>  0,
                ];
                if ($item[14] != '/') {
                    $this->orderAdjustmentNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "payment_id"                =>  $item[1],
                        "total"                     =>  FinanceOrderSaleModel::sheinNumberFormat($item[14]),
                    ];
                }
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
                    "total"                     =>  FinanceOrderSaleModel::sheinNumberFormat($item[18]),
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
    public function shein_semi_managed($excel, $tableId, $reportId): array
    {
        foreach ($excel as $item) {
            $orderObj = new OrderModel();
            $order = $orderObj->with(['details'])->where(['refNo|saleOrderCode' => $item[1]])->find();
            if ($order && $order['userAccount'] != $this->userAccount) {
                $this->userAccount = $order['userAccount'];
            }

            if ($item[9] == '订单销售收入-订单收入') {
                $this->orderSaleNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[7])),
                    "payment_id"                =>  $item[1],
                    "quantity"                  =>  '',
                    "fulfillment"               =>  "Seller",
                    "product_sales"             =>  FinanceOrderSaleModel::sheinNumberFormat($item[10]),
                    "selling_fees"              =>  FinanceOrderSaleModel::sheinNumberFormat($item[14]),
                    "shipping_credits"          =>  0,
                    "gift_wrap_credits"         =>  0,
                    "regulatory_fee"            =>  0,
                    "promotional_rebates"       =>  0,
                    "fba_fees"                  =>  0,
                ];
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[1],
                    "total"                     =>  FinanceOrderSaleModel::sheinNumberFormat($item[12]),
                ];
            } elseif ($item[9] == '退货退款-订单退货') {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[7])),
                    "payment_id"                =>  $item[1],
                    "quantity"                  =>  '',
                    "fulfillment"               =>  "Seller",
                    "product_sales"             =>  FinanceOrderSaleModel::sheinNumberFormat($item[10]),
                    "selling_fees"              =>  FinanceOrderSaleModel::sheinNumberFormat($item[14]),
                    "shipping_credits"          =>  0,
                    "gift_wrap_credits"         =>  0,
                    "regulatory_fee"            =>  0,
                    "promotional_rebates"       =>  0,
                    "fba_fees"                  =>  0,
                ];
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[1],
                    "total"                     =>  FinanceOrderSaleModel::sheinNumberFormat($item[12]),
                ];
            } elseif ($item[9] == '平台服务费-退货单履约服务费'
                || $item[9] == '奖惩及其他-违规撤销补款'
                || $item[9] == '奖惩及其他-违规处罚扣款'
                || $item[9] == '奖惩及其他-违规撤销资金解冻'
                || $item[9] == '奖惩及其他-违规处罚资金冻结'
            ) {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[1],
                    "total"                     =>  FinanceOrderSaleModel::sheinNumberFormat($item[18]),
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
    public function temu($excel, $tableId, $reportId): array
    {
        $sheetNames = $excel->getSheetNames();
        $orderObj = new OrderModel();
        foreach ($sheetNames as $k => $sheetName) {
            if (strpos($sheetName, '结算') !== false) {
                foreach ($excel->getSheet($k)->toArray() as $key => $item) {
                    $order = $orderObj->with(['details'])->where(['refNo|saleOrderCode' => $item[2]])->find();
                    if ($order && $order['userAccount'] != $this->userAccount) {
                        $this->userAccount = $order['userAccount'];
                    }
                    if ($key > 0
                        && ($item[4] == '销售回款' || $item[4] == '运费回款')
                    ) {
                        $this->orderSaleNew[] = [
                            "report_id"                 =>  $reportId,
                            "table_id"                  =>  $tableId,
                            "payment_id"                =>  $item[2],
                            "fulfillment"               =>  "Seller",
                            "product_sales"             =>  sprintf('%.2f', str_replace(',', '', $item[5])),
                            "selling_fees"              =>  0,
                            "shipping_credits"          =>  0,
                            "gift_wrap_credits"         =>  0,
                            "regulatory_fee"            =>  0,
                            "promotional_rebates"       =>  0,
                            "fba_fees"                  =>  0,
                            "date"                      =>  $item[18]
                        ];
                    } elseif ($key > 0 && $item[4] == '销售冲回') {
                        $this->orderRefundNew[] = [
                            "report_id"                 =>  $reportId,
                            "table_id"                  =>  $tableId,
                            "payment_id"                =>  $item[2],
                            "fulfillment"               =>  "Seller",
                            "product_sales"             =>  sprintf('%.2f', str_replace(',', '', $item[5])),
                            "selling_fees"              =>  0,
                            "shipping_credits"          =>  0,
                            "gift_wrap_credits"         =>  0,
                            "regulatory_fee"            =>  0,
                            "promotional_rebates"       =>  0,
                            "fba_fees"                  =>  0,
                            "date"                      =>  $item[18]
                        ];
                    }
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
            'orderTransferNew'          =>  $this->orderTransferNew
        ];
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function temu_hk($excel, $tableId, $reportId): array
    {
        $sheetNames = $excel->getSheetNames();
        foreach ($sheetNames as $k => $sheetName) {
            if (strpos($sheetName, '账务明细列表') !== false) {
                foreach ($excel->getSheet($k)->toArray() as $key => $item) {
                    if ($key > 0 && $item[1] != '结算' && $item[1] != '提现') {
                        if ($item[4] != '推广服务费' &&  $item[4] != '发货面单费') {
                            $this->orderAdjustmentNew[] = [
                                "report_id"                 =>  $reportId,
                                "table_id"                  =>  $tableId,
                                "fulfillment"               =>  "Seller",
                                "payment_id"                =>  '',
                                "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[3])),
                            ];
                        }
                    }
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
            'orderTransferNew'          =>  $this->orderTransferNew
        ];
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function ebay($excel, $tableId, $reportId): array
    {
        foreach ($excel as $item) {
            $orderObj = new OrderModel();
            $order = $orderObj->with(['details'])->where(['refNo|saleOrderCode' => $item[2]])->find();
            if ($order && $order['userAccount'] != $this->userAccount) {
                $this->userAccount = $order['userAccount'];
            }

            if ($item[1] == '订单') {
                $this->orderSaleNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[2],
                    "fulfillment"               =>  "Seller",
                    "product_sales"             =>  round(str_replace(',', '', $item[34]), 2)
                        + round(str_replace(',', '', $item[25]), 2),
                    "selling_fees"              =>  round(str_replace(',', '', $item[26]), 2)
                        + round(str_replace(',', '', $item[27]), 2)
                        + round(str_replace(',', '', $item[31]), 2),
                    "shipping_credits"          =>  0,
                    "gift_wrap_credits"         =>  0,
                    "regulatory_fee"            =>  0,
                    "promotional_rebates"       =>  0,
                    "fba_fees"                  =>  0,
                ];
            } elseif ($item[1] == '退款') {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  $item[2],
                    "fulfillment"               =>  "Seller",
                    "product_sales"             =>  round(str_replace(',', '', $item[34]), 2)
                        + round(str_replace(',', '', $item[25]), 2),
                    "selling_fees"              =>  round(str_replace(',', '', $item[26]), 2)
                        + round(str_replace(',', '', $item[27]), 2)
                        + round(str_replace(',', '', $item[31]), 2),
                    "shipping_credits"          =>  0,
                    "gift_wrap_credits"         =>  0,
                    "regulatory_fee"            =>  0,
                    "promotional_rebates"       =>  0,
                    "fba_fees"                  =>  0,
                ];
            } elseif ($item[1] == '其他费用') {
                if (gettype(strpos($item[38], 'Promoted Listings')) == 'integer') {
                    $this->orderAdjustmentNew[] = [
                        "report_id"                 =>  $reportId,
                        "table_id"                  =>  $tableId,
                        "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[34])),
                    ];
                } elseif (gettype(strpos($item[38], 'Subscription Fee')) == 'integer') {
                    $this->orderSubscriptionNew[] = [
                        "report_id"                 => $reportId,
                        "table_id"                  => $tableId,
                        "description"               => $item[38],
                        "total"                     => sprintf('%.2f', str_replace(',', '', $item[34])),
                    ];
                }
            } elseif ($item[1] == '收费') {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[34])),
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
            'orderTransferNew'          =>  $this->orderTransferNew,
            'orderSubscriptionNew'      =>  $this->orderSubscriptionNew
        ];
    }

    public function wildberries($excel, $tableId, $reportId): array
    {
        foreach ($excel as $item) {
            $this->userAccount = "Wildberries";

            if (($item[9] == 'sale' && ($item[10] == 'Sale' || $item[10] == 'Correct sale'))
                || ($item[9] == '销售' && ($item[10] == '销售' || $item[10] == '合规销售'))) {
                $this->orderSaleNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[12])),
                    "payment_id"                =>  trim($item[0]),
                    "sku"                       =>  $item[3],
                    "description"               =>  $item[54],
                    "quantity"                  =>  $item[13],
                    "fulfillment"               =>  $item[9],
                    "postal"                    =>  '',
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '', $item[19])),
                    "product_sales_tax"         =>  0,
                    "shipping_credits"          =>  0,
                    "shipping_credits_tax"      =>  0,
                    "gift_wrap_credits"         =>  0,
                    "gift_wrap_credits_tax"     =>  0,
                    "regulatory_fee"            =>  sprintf('%.2f', str_replace(',', '', $item[28])) * -1,
                    "regulatory_fee_tax"        =>  0,
                    "promotional_rebates"       =>  0,
                    "promotional_rebates_tax"   =>  0,
                    "marketplace_withheld_tax"  =>  0,
                    "selling_fees"              =>  ceil($item[19] * $item[23]) * 0.01 * -1,
                    "fba_fees"                  =>  0,
                    "other_transaction_fees"    =>  0,
                    "other"                     =>  0,
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[33])),
                ];

                $this->wildberriesOrderNotify[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  trim($item[54])
                ];
            } elseif (($item[10] == 'Logistics' || $item[10] == 'Reversal of logistics')
                || ($item[9] == '销售' && ($item[10] == '物流' || $item[10] == '物流冲销'))) {
                $this->orderShippingNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[12])),
                    "payment_id"                =>  trim($item[0]),
                    "sku"                       =>  $item[3],
                    "description"               =>  $item[54],
                    "quantity"                  =>  $item[13],
                    "shipping_fee"              =>  sprintf('%.2f', str_replace(',', '', $item[36])) * -1,
                ];

                $this->wildberriesOrderNotify[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  trim($item[44])
                ];
            } elseif ($item[9] == 'return' || $item[9] == '退货') {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[12])),
                    "payment_id"                =>  trim($item[0]),
                    "sku"                       =>  $item[3],
                    "description"               =>  $item[54],
                    "quantity"                  =>  $item[13],
                    "fulfillment"               =>  $item[9],
                    "postal"                    =>  '',
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '', $item[19])) * -1,
                    "product_sales_tax"         =>  0,
                    "shipping_credits"          =>  0,
                    "shipping_credits_tax"      =>  0,
                    "gift_wrap_credits"         =>  0,
                    "gift_wrap_credits_tax"     =>  0,
                    "regulatory_fee"            =>  sprintf('%.2f', str_replace(',', '', $item[28])),
                    "regulatory_fee_tax"        =>  0,
                    "promotional_rebates"       =>  0,
                    "promotional_rebates_tax"   =>  0,
                    "marketplace_withheld_tax"  =>  0,
                    "selling_fees"              =>  ceil($item[19] * $item[23]) * 0.01,
                    "fba_fees"                  =>  0,
                    "other_transaction_fees"    =>  0,
                    "other"                     =>  0,
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[33])) * -1,
                ];
            } elseif ($item[9] == 'sale' && $item[10] == 'Compensation for damages') {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  trim($item[54]),
                    "sku"                       =>  $item[3],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[33])),
                    "is_amazon"                 =>  0,
                ];
            } elseif ($item[10] == 'Fines and surcharges') {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  trim($item[54]),
                    "sku"                       =>  $item[3],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[41])) * -1,
                    "is_amazon"                 =>  0,
                ];
            } elseif ($item[10] == 'Voluntary compensation upon return') {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  trim($item[54]),
                    "sku"                       =>  $item[3],
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[33])),
                    "is_amazon"                 =>  0,
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
            'orderTransferNew'          =>  $this->orderTransferNew,
            'orderSubscriptionNew'      =>  $this->orderSubscriptionNew,
            'orderShippingNew'          =>  $this->orderShippingNew,
            'wildberriesOrderNotify'    =>  $this->wildberriesOrderNotify
        ];
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function tiktok($excel, $tableId, $reportId): array
    {
        foreach ($excel as $k => $item) {
            if ($k < 1) {
                continue;
            }

            $orderObj = new OrderModel();
            $order = $orderObj->with(['details'])->where(['refNo|saleOrderCode' => $item[4]])->find();
            if ($order && $order['userAccount'] != $this->userAccount) {
                $this->userAccount = $order['userAccount'];
            }

            if (!empty($item[0])) {
                $this->orderSaleNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[0])),
                    "payment_id"                =>  $item[4],
                    "fulfillment"               =>  "Seller",
                    "product_sales"             =>  round(str_replace(',', '', $item[11]), 2),
                    "selling_fees"              =>  round(str_replace(',', '', $item[19]), 2)
                        + round(str_replace(',', '', $item[34]), 2)
                        + round(str_replace(',', '', $item[36]), 2)
                        + round(str_replace(',', '', $item[41]), 2)
                        + round(str_replace(',', '', $item[44]), 2)
                        + round(str_replace(',', '', $item[45]), 2),
                    "shipping_credits"          =>  0,
                    "gift_wrap_credits"         =>  0,
                    "regulatory_fee"            =>  0,
                    "promotional_rebates"       =>  0,
                    "fba_fees"                  =>  round(str_replace(',', '', $item[21]), 2),
                    "marketplace_withheld_tax"  =>  round(str_replace(',', '', $item[35]), 2)
                ];
            }

            if ($item[14] < 0) {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[0])),
                    "payment_id"                =>  $item[4],
                    "fulfillment"               =>  "Seller",
                    "product_sales"             =>  round(str_replace(',', '', $item[14]), 2),
                    "selling_fees"              =>  round(str_replace(',', '', $item[20]), 2),
                    "shipping_credits"          =>  0,
                    "gift_wrap_credits"         =>  0,
                    "regulatory_fee"            =>  0,
                    "promotional_rebates"       =>  0,
                    "fba_fees"                  =>  round(str_replace(',', '', $item[21]), 2),
                    "marketplace_withheld_tax"  =>  round(str_replace(',', '', $item[34]), 2)
                ];
            }

            if ($item[3] != "Order") {
                $this->orderAdjustmentNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "payment_id"                =>  trim($item[47]),
                    "total"                     =>  sprintf('%.2f', str_replace(',', '', $item[9])),
                    "is_amazon"                 =>  0,
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
            'orderTransferNew'          =>  $this->orderTransferNew,
            'orderSubscriptionNew'      =>  $this->orderSubscriptionNew
        ];
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function hd($excel, $tableId, $reportId): array
    {
        foreach ($excel as $item) {
            $orderObj = new OrderModel();
            $order = $orderObj->with(['details'])->where(['refNo|saleOrderCode' => $item[10]])->find();
            if ($order && $order['userAccount'] != $this->userAccount) {
                $this->userAccount = $order['userAccount'];
            }

            if ($item[2] == 'Z0') {
                $this->orderSaleNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[12])),
                    "payment_id"                =>  FinanceOrderSaleModel::hdPaymentFormat(intval(trim(str_replace('\'', '', $item[10])))),
                    "description"               =>  $item[7],
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '', $item[3])) * -1,
                    "shipping_credits"          =>  sprintf('%.2f', str_replace(',', '', $item[4])),
                    "gift_wrap_credits"         =>  0,
                    "regulatory_fee"            =>  0,
                    "promotional_rebates"       =>  0,
                    "marketplace_withheld_tax"  =>  0,
                    "selling_fees"              =>  0,
                    "fba_fees"                  =>  0,
                ];
            } elseif ($item[2] == 'Z1') {
                $this->orderRefundNew[] = [
                    "report_id"                 =>  $reportId,
                    "table_id"                  =>  $tableId,
                    "date"                      =>  date('Y-m-d H:i:s', strtotime($item[12])),
                    "description"               =>  $item[7],
                    "product_sales"             =>  sprintf('%.2f', str_replace(',', '', $item[3])) * -1,
                    "shipping_credits"          =>  sprintf('%.2f', str_replace(',', '', $item[4])),
                    "gift_wrap_credits"         =>  0,
                    "regulatory_fee"            =>  0,
                    "promotional_rebates"       =>  0,
                    "marketplace_withheld_tax"  =>  0,
                    "selling_fees"              =>  0,
                    "fba_fees"                  =>  0,
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
            'orderTransferNew'          =>  $this->orderTransferNew,
            'orderSubscriptionNew'      =>  $this->orderSubscriptionNew
        ];
    }
}
