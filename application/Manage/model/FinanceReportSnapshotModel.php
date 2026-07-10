<?php

namespace app\Manage\model;

use think\db\exception\BindParamException;
use think\exception\DbException;
use think\exception\PDOException;
use think\Model;

class FinanceReportSnapshotModel extends Model
{
    protected $name = 'finance_report_snapshot';

    protected $resultSetType = 'collection';

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    static public function FbmSnapshot($report) {
        $snapshotObj = new FinanceReportSnapshotModel();
        $fbmWarehouseSku = $snapshotObj->query(FinanceReportModel::getFbmWarehouseSkuSql($report['id'], $report['month']));
        $fbmArr = [];
        foreach ($fbmWarehouseSku as $fbmItem) {
            $fbmArr[] = [
                'report_id'                                 =>  $report['id'],
                'month'                                     =>  $report['month'],
                'platform'                                  =>  $fbmItem['platform'] . '-FBM',
                'user_account'                              =>  $fbmItem['userAccount'],
                'warehouse_sku'                             =>  $fbmItem['warehouse_sku'],
                'product_name'                              =>  $fbmItem['product_name'],
                'seller'                                    =>  $fbmItem['seller'],
                'purchaser'                                 =>  $fbmItem['purchaser'],
                'sale_qty'                                  =>  $fbmItem['fbm_sale_qty'] ?: 0,
                'refund_qty'                                =>  $fbmItem['fbm_refund_qty'] ?: 0,
                'qty_amount'                                =>  $fbmItem['fbm_qty_amount'] ?: 0,
                'sale_amount'                               =>  $fbmItem['fbm_sale_amount'] ?: 0,
                'sale_tax'                                  =>  $fbmItem['fbm_sale_tax'] ?: 0,
                'refund_amount'                             =>  $fbmItem['fbm_refund_amount'] ?: 0,
                'amount'                                    =>  $fbmItem['fbm_amount'] ?: 0,
                'sale_selling_fees'                         =>  $fbmItem['fbm_sale_selling_fees'] ?: 0,
                'refund_selling_fees'                       =>  $fbmItem['fbm_refund_selling_fees'] ?: 0,
                'refund_other'                              =>  $fbmItem['fbm_refund_other'] ?: 0,
                'calcuRes'                                  =>  $fbmItem['calcuRes'] ?: 0,
                'ddp'                                       =>  $fbmItem['fbm_ddp'] ?: 0,
                'adCost'                                    =>  $fbmItem['fbm_adCost'] ?: 0,
                'warehouse_rent'                            =>  $fbmItem['warehouse_rent'] ?: 0,
                'adjustment'                                =>  $fbmItem['adjustment'] ?: 0,
                'liquidation'                               =>  $fbmItem['liquidation'] ?: 0,
                'promotion'                                 =>  $fbmItem['promotion'] ?: 0,
                'shipping_service'                          =>  $fbmItem['shipping_service'] ?: 0,
                'lc_adjustment'                             =>  $fbmItem['lc_adjustment'] ?: 0,
                'le_adjustment'                             =>  $fbmItem['le_adjustment'] ?: 0,
                'operation_expenses'                        =>  $fbmItem['operation_expenses'] ?: 0,
                'operation_factory'                         =>  $fbmItem['operation_factory'] ?: 0,
                'operation_delivery'                        =>  $fbmItem['operation_delivery'] ?: 0,
                'ad_percent'                                =>  $fbmItem['ad_percent'] ?: 0,
                'warehouse_percent'                         =>  $fbmItem['inventory_percent'] ?: 0,
                'tail_percent'                              =>  $fbmItem['tail_percent'] ?: 0,
                'ddp_percent'                               =>  $fbmItem['ddp_percent'] ?: 0,
                'profit'                                    =>  $fbmItem['profit'] ?: 0,
                'gross_profit_margin'                       =>  $fbmItem['gross_profit_margin'] ?: 0,
                'evaluation_qty'                            =>  $fbmItem['evaluation_qty'] ?: 0,
                'evaluation_amount'                         =>  $fbmItem['evaluation_amount'] ?: 0,
                'profit_include_evaluation'                 =>  $fbmItem['profit_include_evaluation'] ?: 0,
                'gross_profit_margin_include_evaluation'    =>  $fbmItem['gross_profit_margin_include_evaluation'] ?: 0,
            ];
        }

        if ($fbmArr) {
            return $snapshotObj->insertAll($fbmArr);
        } else {
            return true;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    static public function FbaSnapshot($report) {
        $snapshotObj = new FinanceReportSnapshotModel();
        $fbaWarehouseSku = $snapshotObj->query(FinanceReportModel::getFbaWarehouseSkuSql($report['id'], $report['month']));
        $fbaArr = [];
        foreach ($fbaWarehouseSku as $fbaItem) {
            $fbaArr[] = [
                'report_id'                                 =>  $report['id'],
                'month'                                     =>  $report['month'],
                'platform'                                  =>  $fbaItem['platform'] . '-FBA',
                'user_account'                              =>  $fbaItem['userAccount'],
                'warehouse_sku'                             =>  $fbaItem['warehouse_sku'],
                'product_name'                              =>  $fbaItem['product_name'],
                'seller'                                    =>  $fbaItem['seller'],
                'purchaser'                                 =>  $fbaItem['purchaser'],
                'sale_qty'                                  =>  $fbaItem['fba_sale_qty'] ?: 0,
                'refund_qty'                                =>  $fbaItem['fba_refund_qty'] ?: 0,
                'qty_amount'                                =>  $fbaItem['fba_qty_amount'] ?: 0,
                'sale_amount'                               =>  $fbaItem['fba_sale_amount'] ?: 0,
                'sale_tax'                                  =>  $fbaItem['fba_sale_tax'] ?: 0,
                'refund_amount'                             =>  $fbaItem['fba_refund_amount'] ?: 0,
                'amount'                                    =>  $fbaItem['fba_amount'] ?: 0,
                'sale_selling_fees'                         =>  $fbaItem['fba_sale_selling_fees'] ?: 0,
                'refund_selling_fees'                       =>  $fbaItem['fba_refund_selling_fees'] ?: 0,
                'fba_fees'                                  =>  $fbaItem['fba_fees'] ?: 0,
                'fba_refund_fees'                           =>  $fbaItem['fba_refund_fees'] ?: 0,
                'refund_other'                              =>  $fbaItem['fba_refund_other'] ?: 0,
                'ddp'                                       =>  $fbaItem['fba_ddp'] ?: 0,
                'adCost'                                    =>  $fbaItem['fba_adCost'] ?: 0,
                'warehouse_rent'                            =>  $fbaItem['fba_inventory'] ?: 0,
                'adjustment'                                =>  $fbaItem['adjustment'] ?: 0,
                'liquidation'                               =>  $fbaItem['liquidation'] ?: 0,
                'promotion'                                 =>  $fbaItem['promotion'] ?: 0,
                'shipping_service'                          =>  $fbaItem['shipping_service'] ?: 0,
                'lc_adjustment'                             =>  $fbaItem['lc_adjustment'] ?: 0,
                'le_adjustment'                             =>  $fbaItem['le_adjustment'] ?: 0,
                'operation_expenses'                        =>  $fbaItem['operation_expenses'] ?: 0,
                'operation_factory'                         =>  $fbaItem['operation_factory'] ?: 0,
                'operation_delivery'                        =>  $fbaItem['operation_delivery'] ?: 0,
                'ad_percent'                                =>  $fbaItem['ad_percent'] ?: 0,
                'warehouse_percent'                         =>  $fbaItem['inventory_percent'] ?: 0,
                'tail_percent'                              =>  $fbaItem['tail_percent'] ?: 0,
                'ddp_percent'                               =>  $fbaItem['ddp_percent'] ?: 0,
                'profit'                                    =>  $fbaItem['profit'] ?: 0,
                'gross_profit_margin'                       =>  $fbaItem['gross_profit_margin'] ?: 0,
                'evaluation_qty'                            =>  $fbaItem['evaluation_qty'] ?: 0,
                'evaluation_amount'                         =>  $fbaItem['evaluation_amount'] ?: 0,
                'profit_include_evaluation'                 =>  $fbaItem['profit_include_evaluation'] ?: 0,
                'gross_profit_margin_include_evaluation'    =>  $fbaItem['gross_profit_margin_include_evaluation'] ?: 0,
            ];
        }

        if ($fbaArr) {
            return $snapshotObj->insertAll($fbaArr);
        } else {
            return true;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    static public function WalmartSnapshot($report) {
        $snapshotObj = new FinanceReportSnapshotModel();
        $walmartWarehouseSku = $snapshotObj->query(FinanceReportModel::getWalmartWarehouseSkuSql($report['id']));
        $walmartArr = [];
        foreach ($walmartWarehouseSku as $walmartItem) {
            $walmartArr[] = [
                'report_id'                                 =>  $report['id'],
                'month'                                     =>  $report['month'],
                'platform'                                  =>  $walmartItem['platform'],
                'user_account'                              =>  $walmartItem['userAccount'],
                'warehouse_sku'                             =>  $walmartItem['warehouse_sku'],
                'product_name'                              =>  $walmartItem['product_name'],
                'seller'                                    =>  $walmartItem['seller'],
                'purchaser'                                 =>  $walmartItem['purchaser'],
                'sale_qty'                                  =>  $walmartItem['sale_qty'] ?: 0,
                'refund_qty'                                =>  $walmartItem['refund_qty'] ?: 0,
                'qty_amount'                                =>  $walmartItem['qty_amount'] ?: 0,
                'sale_amount'                               =>  $walmartItem['sale_amount'] ?: 0,
                'refund_amount'                             =>  $walmartItem['refund_amount'] ?: 0,
                'amount'                                    =>  $walmartItem['amount'] ?: 0,
                'sale_selling_fees'                         =>  $walmartItem['sale_selling_fees'] ?: 0,
                'refund_selling_fees'                       =>  $walmartItem['refund_selling_fees'] ?: 0,
                'calcuRes'                                  =>  $walmartItem['calcuRes'] ?: 0,
                'wfs_fulfillment'                           =>  $walmartItem['wfs_fulfillment'] ?: 0,
                'ddp'                                       =>  $walmartItem['ddp'] ?: 0,
                'adCost'                                    =>  $walmartItem['adCost'] ?: 0,
                'warehouse_rent'                            =>  $walmartItem['warehouse_rent'] ?: 0,
                'wfs_warehouse'                             =>  $walmartItem['wfs_warehouse'] ?: 0,
                'wfs_return_shipping'                       =>  $walmartItem['wfs_return_shipping'] ?: 0,
                'adjustment'                                =>  $walmartItem['adjustment'] ?: 0,
                'lc_adjustment'                             =>  $walmartItem['lc_adjustment'] ?: 0,
                'le_adjustment'                             =>  $walmartItem['le_adjustment'] ?: 0,
                'wfs_adjustment'                            =>  $walmartItem['wfs_adjustment'] ?: 0,
                'operation_expenses'                        =>  $walmartItem['operation_expenses'] ?: 0,
                'operation_factory'                         =>  $walmartItem['operation_factory'] ?: 0,
                'operation_delivery'                        =>  $walmartItem['operation_delivery'] ?: 0,
                'ad_percent'                                =>  $walmartItem['ad_percent'] ?: 0,
                'warehouse_percent'                         =>  $walmartItem['warehouse_percent'] ?: 0,
                'tail_percent'                              =>  $walmartItem['tail_percent'] ?: 0,
                'ddp_percent'                               =>  $walmartItem['ddp_percent'] ?: 0,
                'profit'                                    =>  $walmartItem['profit'] ?: 0,
                'gross_profit_margin'                       =>  $walmartItem['gross_profit_margin'] ?: 0,
                'evaluation_qty'                            =>  $walmartItem['evaluation_qty'] ?: 0,
                'evaluation_amount'                         =>  $walmartItem['evaluation_amount'] ?: 0,
                'profit_include_evaluation'                 =>  $walmartItem['profit_include_evaluation'] ?: 0,
                'gross_profit_margin_include_evaluation'    =>  $walmartItem['gross_profit_margin_include_evaluation'] ?: 0,
            ];
        }

        if ($walmartArr) {
            return $snapshotObj->insertAll($walmartArr);
        } else {
            return true;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    static public function WayfairSnapshot($report) {
        $snapshotObj = new FinanceReportSnapshotModel();
        $wayfairWarehouseSku = $snapshotObj->query(FinanceReportModel::getWayfairWarehouseSkuSql($report['id']));
        $wayfairArr = [];
        foreach ($wayfairWarehouseSku as $wayfairItem) {
            $wayfairArr[] = [
                'report_id'                                 =>  $report['id'],
                'month'                                     =>  $report['month'],
                'platform'                                  =>  $wayfairItem['platform'],
                'user_account'                              =>  $wayfairItem['userAccount'],
                'warehouse_sku'                             =>  $wayfairItem['warehouse_sku'],
                'product_name'                              =>  $wayfairItem['product_name'],
                'seller'                                    =>  $wayfairItem['seller'],
                'purchaser'                                 =>  $wayfairItem['purchaser'],
                'sale_qty'                                  =>  $wayfairItem['sale_qty'] ?: 0,
                'refund_qty'                                =>  $wayfairItem['refund_qty'] ?: 0,
                'qty_amount'                                =>  $wayfairItem['qty_amount'] ?: 0,
                'sale_amount'                               =>  $wayfairItem['sale_amount'] ?: 0,
                'refund_amount'                             =>  $wayfairItem['refund_amount'] ?: 0,
                'amount'                                    =>  $wayfairItem['amount'] ?: 0,
                'sale_selling_fees'                         =>  $wayfairItem['sale_selling_fees'] ?: 0,
                'refund_selling_fees'                       =>  $wayfairItem['refund_selling_fees'] ?: 0,
                'calcuRes'                                  =>  $wayfairItem['calcuRes'] ?: 0,
                'ddp'                                       =>  $wayfairItem['ddp'] ?: 0,
                'adCost'                                    =>  $wayfairItem['adCost'] ?: 0,
                'warehouse_rent'                            =>  $wayfairItem['warehouse_rent'] ?: 0,
                'adjustment'                                =>  $wayfairItem['adjustment'] ?: 0,
                'lc_adjustment'                             =>  $wayfairItem['lc_adjustment'] ?: 0,
                'le_adjustment'                             =>  $wayfairItem['le_adjustment'] ?: 0,
                'operation_expenses'                        =>  $wayfairItem['operation_expenses'] ?: 0,
                'operation_factory'                         =>  $wayfairItem['operation_factory'] ?: 0,
                'operation_delivery'                        =>  $wayfairItem['operation_delivery'] ?: 0,
                'ad_percent'                                =>  $wayfairItem['ad_percent'] ?: 0,
                'warehouse_percent'                         =>  $wayfairItem['warehouse_percent'] ?: 0,
                'tail_percent'                              =>  $wayfairItem['tail_percent'] ?: 0,
                'ddp_percent'                               =>  $wayfairItem['ddp_percent'] ?: 0,
                'profit'                                    =>  $wayfairItem['profit'] ?: 0,
                'gross_profit_margin'                       =>  $wayfairItem['gross_profit_margin'] ?: 0,
                'evaluation_qty'                            =>  $wayfairItem['evaluation_qty'] ?: 0,
                'evaluation_amount'                         =>  $wayfairItem['evaluation_amount'] ?: 0,
                'profit_include_evaluation'                 =>  $wayfairItem['profit_include_evaluation'] ?: 0,
                'gross_profit_margin_include_evaluation'    =>  $wayfairItem['gross_profit_margin_include_evaluation'] ?: 0,
            ];
        }

        if ($wayfairArr) {
            return $snapshotObj->insertAll($wayfairArr);
        } else {
            return true;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    static public function SheinSnapshot($report) {
        $snapshotObj = new FinanceReportSnapshotModel();
        $sheinWarehouseSku = $snapshotObj->query(FinanceReportModel::getSheinWarehouseSkuSql($report['id']));
        $sheinArr = [];
        foreach ($sheinWarehouseSku as $sheinItem) {
            $sheinArr[] = [
                'report_id'                                 =>  $report['id'],
                'month'                                     =>  $report['month'],
                'platform'                                  =>  $sheinItem['platform'],
                'user_account'                              =>  $sheinItem['userAccount'],
                'warehouse_sku'                             =>  $sheinItem['warehouse_sku'],
                'product_name'                              =>  $sheinItem['product_name'],
                'seller'                                    =>  $sheinItem['seller'],
                'purchaser'                                 =>  $sheinItem['purchaser'],
                'sale_qty'                                  =>  $sheinItem['sale_qty'] ?: 0,
                'refund_qty'                                =>  $sheinItem['refund_qty'] ?: 0,
                'qty_amount'                                =>  $sheinItem['qty_amount'] ?: 0,
                'sale_amount'                               =>  $sheinItem['sale_amount'] ?: 0,
                'refund_amount'                             =>  $sheinItem['refund_amount'] ?: 0,
                'amount'                                    =>  $sheinItem['amount'] ?: 0,
                'sale_selling_fees'                         =>  $sheinItem['sale_selling_fees'] ?: 0,
                'refund_selling_fees'                       =>  $sheinItem['refund_selling_fees'] ?: 0,
                'calcuRes'                                  =>  $sheinItem['calcuRes'] ?: 0,
                'ddp'                                       =>  $sheinItem['ddp'] ?: 0,
                'adCost'                                    =>  $sheinItem['adCost'] ?: 0,
                'warehouse_rent'                            =>  $sheinItem['warehouse_rent'] ?: 0,
                'adjustment'                                =>  $sheinItem['adjustment'] ?: 0,
                'lc_adjustment'                             =>  $sheinItem['lc_adjustment'] ?: 0,
                'le_adjustment'                             =>  $sheinItem['le_adjustment'] ?: 0,
                'operation_expenses'                        =>  $sheinItem['operation_expenses'] ?: 0,
                'operation_factory'                         =>  $sheinItem['operation_factory'] ?: 0,
                'operation_delivery'                        =>  $sheinItem['operation_delivery'] ?: 0,
                'ad_percent'                                =>  $sheinItem['ad_percent'] ?: 0,
                'warehouse_percent'                         =>  $sheinItem['warehouse_percent'] ?: 0,
                'tail_percent'                              =>  $sheinItem['tail_percent'] ?: 0,
                'ddp_percent'                               =>  $sheinItem['ddp_percent'] ?: 0,
                'profit'                                    =>  $sheinItem['profit'] ?: 0,
                'gross_profit_margin'                       =>  $sheinItem['gross_profit_margin'] ?: 0,
                'evaluation_qty'                            =>  $sheinItem['evaluation_qty'] ?: 0,
                'evaluation_amount'                         =>  $sheinItem['evaluation_amount'] ?: 0,
                'profit_include_evaluation'                 =>  $sheinItem['profit_include_evaluation'] ?: 0,
                'gross_profit_margin_include_evaluation'    =>  $sheinItem['gross_profit_margin_include_evaluation'] ?: 0,
            ];
        }

        if ($sheinArr) {
            return $snapshotObj->insertAll($sheinArr);
        } else {
            return true;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    static public function TemuSnapshot($report) {
        $snapshotObj = new FinanceReportSnapshotModel();
        $temuWarehouseSku = $snapshotObj->query(FinanceReportModel::getTemuWarehouseSkuSql($report['id']));
        $temuArr = [];
        foreach ($temuWarehouseSku as $temuItem) {
            $temuArr[] = [
                'report_id'                                 =>  $report['id'],
                'month'                                     =>  $report['month'],
                'platform'                                  =>  $temuItem['platform'],
                'user_account'                              =>  $temuItem['userAccount'],
                'warehouse_sku'                             =>  $temuItem['warehouse_sku'],
                'product_name'                              =>  $temuItem['product_name'],
                'seller'                                    =>  $temuItem['seller'],
                'purchaser'                                 =>  $temuItem['purchaser'],
                'sale_qty'                                  =>  $temuItem['sale_qty'] ?: 0,
                'refund_qty'                                =>  $temuItem['refund_qty'] ?: 0,
                'qty_amount'                                =>  $temuItem['qty_amount'] ?: 0,
                'sale_amount'                               =>  $temuItem['sale_amount'] ?: 0,
                'refund_amount'                             =>  $temuItem['refund_amount'] ?: 0,
                'amount'                                    =>  $temuItem['amount'] ?: 0,
                'sale_selling_fees'                         =>  $temuItem['sale_selling_fees'] ?: 0,
                'refund_selling_fees'                       =>  $temuItem['refund_selling_fees'] ?: 0,
                'calcuRes'                                  =>  $temuItem['calcuRes'] ?: 0,
                'waybill'                                   =>  $temuItem['waybill'] ?: 0,
                'ddp'                                       =>  $temuItem['ddp'] ?: 0,
                'adCost'                                    =>  $temuItem['adCost'] ?: 0,
                'warehouse_rent'                            =>  $temuItem['warehouse_rent'] ?: 0,
                'adjustment'                                =>  $temuItem['adjustment'] ?: 0,
                'lc_adjustment'                             =>  $temuItem['lc_adjustment'] ?: 0,
                'le_adjustment'                             =>  $temuItem['le_adjustment'] ?: 0,
                'operation_expenses'                        =>  $temuItem['operation_expenses'] ?: 0,
                'operation_factory'                         =>  $temuItem['operation_factory'] ?: 0,
                'operation_delivery'                        =>  $temuItem['operation_delivery'] ?: 0,
                'ad_percent'                                =>  $temuItem['ad_percent'] ?: 0,
                'warehouse_percent'                         =>  $temuItem['warehouse_percent'] ?: 0,
                'tail_percent'                              =>  $temuItem['tail_percent'] ?: 0,
                'ddp_percent'                               =>  $temuItem['ddp_percent'] ?: 0,
                'profit'                                    =>  $temuItem['profit'] ?: 0,
                'gross_profit_margin'                       =>  $temuItem['gross_profit_margin'] ?: 0,
                'evaluation_qty'                            =>  $temuItem['evaluation_qty'] ?: 0,
                'evaluation_amount'                         =>  $temuItem['evaluation_amount'] ?: 0,
                'profit_include_evaluation'                 =>  $temuItem['profit_include_evaluation'] ?: 0,
                'gross_profit_margin_include_evaluation'    =>  $temuItem['gross_profit_margin_include_evaluation'] ?: 0,
            ];
        }

        if ($temuArr) {
            return $snapshotObj->insertAll($temuArr);
        } else {
            return true;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    static public function EbaySnapshot($report) {
        $snapshotObj = new FinanceReportSnapshotModel();
        $ebayWarehouseSku = $snapshotObj->query(FinanceReportModel::getEbayWarehouseSkuSql($report['id']));
        $ebayArr = [];
        foreach ($ebayWarehouseSku as $ebayItem) {
            $ebayArr[] = [
                'report_id'                                 =>  $report['id'],
                'month'                                     =>  $report['month'],
                'platform'                                  =>  $ebayItem['platform'],
                'user_account'                              =>  $ebayItem['userAccount'],
                'warehouse_sku'                             =>  $ebayItem['warehouse_sku'],
                'product_name'                              =>  $ebayItem['product_name'],
                'seller'                                    =>  $ebayItem['seller'],
                'purchaser'                                 =>  $ebayItem['purchaser'],
                'sale_qty'                                  =>  $ebayItem['sale_qty'] ?: 0,
                'refund_qty'                                =>  $ebayItem['refund_qty'] ?: 0,
                'qty_amount'                                =>  $ebayItem['qty_amount'] ?: 0,
                'sale_amount'                               =>  $ebayItem['sale_amount'] ?: 0,
                'sale_tax'                                  =>  $ebayItem['sale_tax'] ?: 0,
                'refund_amount'                             =>  $ebayItem['refund_amount'] ?: 0,
                'amount'                                    =>  $ebayItem['amount'] ?: 0,
                'sale_selling_fees'                         =>  $ebayItem['sale_selling_fees'] ?: 0,
                'refund_selling_fees'                       =>  $ebayItem['refund_selling_fees'] ?: 0,
                'calcuRes'                                  =>  $ebayItem['calcuRes'] ?: 0,
                'ddp'                                       =>  $ebayItem['ddp'] ?: 0,
                'adCost'                                    =>  $ebayItem['adCost'] ?: 0,
                'warehouse_rent'                            =>  $ebayItem['warehouse_rent'] ?: 0,
                'adjustment'                                =>  $ebayItem['adjustment'] ?: 0,
                'lc_adjustment'                             =>  $ebayItem['lc_adjustment'] ?: 0,
                'le_adjustment'                             =>  $ebayItem['le_adjustment'] ?: 0,
                'wfs_adjustment'                            =>  $ebayItem['wfs_adjustment'] ?: 0,
                'operation_expenses'                        =>  $ebayItem['operation_expenses'] ?: 0,
                'operation_factory'                         =>  $ebayItem['operation_factory'] ?: 0,
                'operation_delivery'                        =>  $ebayItem['operation_delivery'] ?: 0,
                'ad_percent'                                =>  $ebayItem['ad_percent'] ?: 0,
                'warehouse_percent'                         =>  $ebayItem['warehouse_percent'] ?: 0,
                'tail_percent'                              =>  $ebayItem['tail_percent'] ?: 0,
                'ddp_percent'                               =>  $ebayItem['ddp_percent'] ?: 0,
                'profit'                                    =>  $ebayItem['profit'] ?: 0,
                'gross_profit_margin'                       =>  $ebayItem['gross_profit_margin'] ?: 0,
                'evaluation_qty'                            =>  $ebayItem['evaluation_qty'] ?: 0,
                'evaluation_amount'                         =>  $ebayItem['evaluation_amount'] ?: 0,
                'profit_include_evaluation'                 =>  $ebayItem['profit_include_evaluation'] ?: 0,
                'gross_profit_margin_include_evaluation'    =>  $ebayItem['gross_profit_margin_include_evaluation'] ?: 0,
            ];
        }

        if ($ebayArr) {
            return $snapshotObj->insertAll($ebayArr);
        } else {
            return true;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    static public function TiktokSnapshot($report) {
        $snapshotObj = new FinanceReportSnapshotModel();
        $tiktokWarehouseSku = $snapshotObj->query(FinanceReportModel::getTiktokWarehouseSkuSql($report['id']));
        $tiktokArr = [];
        foreach ($tiktokWarehouseSku as $tiktokItem) {
            $tiktokArr[] = [
                'report_id'                                 =>  $report['id'],
                'month'                                     =>  $report['month'],
                'platform'                                  =>  $tiktokItem['platform'],
                'user_account'                              =>  $tiktokItem['userAccount'],
                'warehouse_sku'                             =>  $tiktokItem['warehouse_sku'],
                'product_name'                              =>  $tiktokItem['product_name'],
                'seller'                                    =>  $tiktokItem['seller'],
                'purchaser'                                 =>  $tiktokItem['purchaser'],
                'sale_qty'                                  =>  $tiktokItem['sale_qty'] ?: 0,
                'refund_qty'                                =>  $tiktokItem['refund_qty'] ?: 0,
                'qty_amount'                                =>  $tiktokItem['qty_amount'] ?: 0,
                'sale_amount'                               =>  $tiktokItem['sale_amount'] ?: 0,
                'sale_tax'                                  =>  $tiktokItem['sale_tax'] ?: 0,
                'refund_amount'                             =>  $tiktokItem['refund_amount'] ?: 0,
                'amount'                                    =>  $tiktokItem['amount'] ?: 0,
                'sale_selling_fees'                         =>  $tiktokItem['sale_selling_fees'] ?: 0,
                'refund_selling_fees'                       =>  $tiktokItem['refund_selling_fees'] ?: 0,
                'fba_fees'                                  =>  $tiktokItem['fba_fee'] ?: 0,
                'calcuRes'                                  =>  $tiktokItem['calcuRes'] ?: 0,
                'ddp'                                       =>  $tiktokItem['ddp'] ?: 0,
                'adCost'                                    =>  $tiktokItem['adCost'] ?: 0,
                'warehouse_rent'                            =>  $tiktokItem['warehouse_rent'] ?: 0,
                'adjustment'                                =>  $tiktokItem['adjustment'] ?: 0,
                'lc_adjustment'                             =>  $tiktokItem['lc_adjustment'] ?: 0,
                'le_adjustment'                             =>  $tiktokItem['le_adjustment'] ?: 0,
                'operation_expenses'                        =>  $tiktokItem['operation_expenses'] ?: 0,
                'operation_factory'                         =>  $tiktokItem['operation_factory'] ?: 0,
                'operation_delivery'                        =>  $tiktokItem['operation_delivery'] ?: 0,
                'ad_percent'                                =>  $tiktokItem['ad_percent'] ?: 0,
                'warehouse_percent'                         =>  $tiktokItem['warehouse_percent'] ?: 0,
                'tail_percent'                              =>  $tiktokItem['tail_percent'] ?: 0,
                'ddp_percent'                               =>  $tiktokItem['ddp_percent'] ?: 0,
                'profit'                                    =>  $tiktokItem['profit'] ?: 0,
                'gross_profit_margin'                       =>  $tiktokItem['gross_profit_margin'] ?: 0,
                'evaluation_qty'                            =>  $tiktokItem['evaluation_qty'] ?: 0,
                'evaluation_amount'                         =>  $tiktokItem['evaluation_amount'] ?: 0,
                'profit_include_evaluation'                 =>  $tiktokItem['profit_include_evaluation'] ?: 0,
                'gross_profit_margin_include_evaluation'    =>  $tiktokItem['gross_profit_margin_include_evaluation'] ?: 0,
            ];
        }

        if ($tiktokArr) {
            return $snapshotObj->insertAll($tiktokArr);
        } else {
            return true;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    static public function HomeDepotSnapshot($report) {
        $snapshotObj = new FinanceReportSnapshotModel();
        $hdWarehouseSku = $snapshotObj->query(FinanceReportModel::getHomeDepotWarehouseSkuSql($report['id']));
        $hdArr = [];
        foreach ($hdWarehouseSku as $hdItem) {
            $hdArr[] = [
                'report_id'                                 =>  $report['id'],
                'month'                                     =>  $report['month'],
                'platform'                                  =>  $hdItem['platform'],
                'user_account'                              =>  $hdItem['userAccount'],
                'warehouse_sku'                             =>  $hdItem['warehouse_sku'],
                'product_name'                              =>  $hdItem['product_name'],
                'seller'                                    =>  $hdItem['seller'],
                'purchaser'                                 =>  $hdItem['purchaser'],
                'sale_qty'                                  =>  $hdItem['sale_qty'] ?: 0,
                'refund_qty'                                =>  $hdItem['refund_qty'] ?: 0,
                'qty_amount'                                =>  $hdItem['qty_amount'] ?: 0,
                'sale_amount'                               =>  $hdItem['sale_amount'] ?: 0,
                'sale_tax'                                  =>  $hdItem['sale_tax'] ?: 0,
                'refund_amount'                             =>  $hdItem['refund_amount'] ?: 0,
                'amount'                                    =>  $hdItem['amount'] ?: 0,
                'sale_selling_fees'                         =>  $hdItem['sale_selling_fees'] ?: 0,
                'refund_selling_fees'                       =>  $hdItem['refund_selling_fees'] ?: 0,
                'calcuRes'                                  =>  $hdItem['calcuRes'] ?: 0,
                'ddp'                                       =>  $hdItem['ddp'] ?: 0,
                'adCost'                                    =>  $hdItem['adCost'] ?: 0,
                'warehouse_rent'                            =>  $hdItem['warehouse_rent'] ?: 0,
                'adjustment'                                =>  $hdItem['adjustment'] ?: 0,
                'lc_adjustment'                             =>  $hdItem['lc_adjustment'] ?: 0,
                'le_adjustment'                             =>  $hdItem['le_adjustment'] ?: 0,
                'wfs_adjustment'                            =>  $hdItem['wfs_adjustment'] ?: 0,
                'operation_expenses'                        =>  $hdItem['operation_expenses'] ?: 0,
                'operation_factory'                         =>  $hdItem['operation_factory'] ?: 0,
                'operation_delivery'                        =>  $hdItem['operation_delivery'] ?: 0,
                'ad_percent'                                =>  $hdItem['ad_percent'] ?: 0,
                'warehouse_percent'                         =>  $hdItem['warehouse_percent'] ?: 0,
                'tail_percent'                              =>  $hdItem['tail_percent'] ?: 0,
                'ddp_percent'                               =>  $hdItem['ddp_percent'] ?: 0,
                'profit'                                    =>  $hdItem['profit'] ?: 0,
                'gross_profit_margin'                       =>  $hdItem['gross_profit_margin'] ?: 0,
                'evaluation_qty'                            =>  $hdItem['evaluation_qty'] ?: 0,
                'evaluation_amount'                         =>  $hdItem['evaluation_amount'] ?: 0,
                'profit_include_evaluation'                 =>  $hdItem['profit_include_evaluation'] ?: 0,
                'gross_profit_margin_include_evaluation'    =>  $hdItem['gross_profit_margin_include_evaluation'] ?: 0,
            ];
        }

        if ($hdArr) {
            return $snapshotObj->insertAll($hdArr);
        } else {
            return true;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    static public function noOutboundSnapshot($report) {
        $snapshotObj = new FinanceReportSnapshotModel();
        $noOutboundWarehouseSku = $snapshotObj->query(FinanceReportModel::getPaymentNoOutboundSql($report['id']));
        $noOutboundArr = [];
        foreach ($noOutboundWarehouseSku as $noOutboundItem) {
            $noOutboundArr[] = [
                'report_id'                                 =>  $report['id'],
                'month'                                     =>  $report['month'],
                'platform'                                  =>  $noOutboundItem['platform'],
                'user_account'                              =>  $noOutboundItem['userAccount'],
                'warehouse_sku'                             =>  $noOutboundItem['warehouse_sku'],
                'sale_qty'                                  =>  $noOutboundItem['quantity'] ?: 0,
                'qty_amount'                                =>  $noOutboundItem['quantity'] ?: 0,
                'sale_amount'                               =>  $noOutboundItem['payment_amount'] ?: 0,
                'amount'                                    =>  $noOutboundItem['payment_amount'] ?: 0,
                'sale_selling_fees'                         =>  $noOutboundItem['payment_selling_fees'] ?: 0,
                'fba_fees'                                  =>  $noOutboundItem['payment_fba_fees'] ?: 0,
                'profit'                                    =>  $noOutboundItem['payment_amount'] + $noOutboundItem['payment_selling_fees'] + $noOutboundItem['payment_fba_fees'] ?: 0,
                'profit_include_evaluation'                 =>  $noOutboundItem['payment_amount'] + $noOutboundItem['payment_selling_fees'] + $noOutboundItem['payment_fba_fees'] ?: 0,
            ];
        }

        if ($noOutboundArr) {
            return $snapshotObj->insertAll($noOutboundArr);
        } else {
            return true;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    static public function OrderResendSave($report) {
        $orderResendOnj = new FinanceOrderResendModel();
        $hdWarehouseSku = $orderResendOnj->query(FinanceReportModel::getOrderResend($report['id'], $report['month']));
        foreach ($hdWarehouseSku as $key => $resendItem) {
            $hdWarehouseSku[$key]['report_id'] = $report['id'];
        }

        if ($hdWarehouseSku) {
            return $orderResendOnj->insertAll($hdWarehouseSku);
        } else {
            return true;
        }
    }

    /**
     * @throws PDOException
     */
    static public function ResendSnapshot($report) {
        $snapshotObj = new FinanceReportSnapshotModel();
        $financeOrderResendObj = new FinanceOrderResendModel();
        $resendList = $financeOrderResendObj->with(['store'])->where(['report_id' => $report['id']])->select();
        $resendArr = [];
        if (count($resendList) > 0) {
            foreach ($resendList as $resendItem) {
                $resendArr[] = [
                    'report_id'                                 =>  $report['id'],
                    'month'                                     =>  $report['month'],
                    'platform'                                  =>  $resendItem['platform'],
                    'user_account'                              =>  $resendItem['user_account'],
                    'warehouse_sku'                             =>  $resendItem['warehouse_sku'],
                    'seller'                                    =>  $resendItem['seller'],
                    'purchaser'                                 =>  $resendItem['purchaser'],
                    'sale_qty'                                  =>  $resendItem['qty'] ?: 0,
                    'qty_amount'                                =>  $resendItem['qty'] ?: 0,
                    'sale_amount'                               =>  0,
                    'amount'                                    =>  0,
                    'calcuRes'                                  =>  $resendItem['tail'] * -1 ?: 0,
                    'ddp'                                       =>  $resendItem['store']['sku_ddp_unit'] * $resendItem['qty'] / $report['USD'] * -1 ?: 0,
                    'profit'                                    =>  $resendItem['tail'] * -1 + $resendItem['store']['sku_ddp_unit'] * $resendItem['qty'] / $report['USD'] * -1 ?: 0,
                    'profit_include_evaluation'                 =>  $resendItem['tail'] * -1 + $resendItem['store']['sku_ddp_unit'] * $resendItem['qty'] / $report['USD'] * -1 ?: 0,
                ];
            }
        }

        if ($resendArr) {
            return $snapshotObj->insertAll($resendArr);
        } else {
            return true;
        }
    }
}
