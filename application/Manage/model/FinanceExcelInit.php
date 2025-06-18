<?php

namespace app\Manage\model;

use PHPExcel;
use think\db\exception\BindParamException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\exception\PDOException;
use think\Model;

class FinanceExcelInit extends Model
{
    private $objPHPExcel;

    private  $model;

    public function __construct($objPHPExcel)
    {
        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';

        // Create new PHPExcel object
        $this->objPHPExcel = $objPHPExcel;
        $this->model = new FinanceReportModel();

        parent::__construct($objPHPExcel);
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateSaleRefundSheet($index, $report_id)
    {
        $saleRefund = $this->model->query(FinanceReportModel::getSaleRefundSql($report_id));

        if ($index){
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('销售-退款');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '类型')
            ->setCellValue('B1', '平台')
            ->setCellValue('C1', '店铺')
            ->setCellValue('D1', '账单Payment')
            ->setCellValue('E1', '销售Payment')
            ->setCellValue('F1', '易仓订单号')
            ->setCellValue('G1', '店铺Seller Sku')
            ->setCellValue('H1', '仓库Sku')
            ->setCellValue('I1', '销售量')
            ->setCellValue('J1', '退款量')
            ->setCellValue('K1', '销售额')
            ->setCellValue('L1', '退款额')
            ->setCellValue('M1', '平台佣金')
            ->setCellValue('N1', '平台佣金退款')
            ->setCellValue('O1', '亚马逊尾程')
            ->setCellValue('P1', '海外仓预估尾程')
            ->setCellValue('Q1', '入核算尾程')
            ->setCellValue('R1', 'DDP')
            ->setCellValue('S1', '支付时间')
        ;

        $saleRefundIndex = 1;
        foreach ($saleRefund as $saleRefundItem) {
            $saleRefundIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $saleRefundIndex, $saleRefundItem['type'])
                ->setCellValue('B' . $saleRefundIndex, $saleRefundItem['platform'])
                ->setCellValue('C' . $saleRefundIndex, $saleRefundItem['userAccount'])
                ->setCellValue('D' . $saleRefundIndex, $saleRefundItem['payment'])
                ->setCellValue('E' . $saleRefundIndex, $saleRefundItem['payment_id'])
                ->setCellValue('F' . $saleRefundIndex, $saleRefundItem['saleOrderCode'])
                ->setCellValue('G' . $saleRefundIndex, $saleRefundItem['seller_sku'])
                ->setCellValue('H' . $saleRefundIndex, $saleRefundItem['warehouse_sku'])
                ->setCellValue('I' . $saleRefundIndex, $saleRefundItem['sale_qty'])
                ->setCellValue('J' . $saleRefundIndex, $saleRefundItem['refund_qty'])
                ->setCellValue('K' . $saleRefundIndex, $saleRefundItem['sale_amount'])
                ->setCellValue('L' . $saleRefundIndex, $saleRefundItem['refund_amount'])
                ->setCellValue('M' . $saleRefundIndex, $saleRefundItem['sale_selling_fees'])
                ->setCellValue('N' . $saleRefundIndex, $saleRefundItem['refund_selling_fees'])
                ->setCellValue('O' . $saleRefundIndex, $saleRefundItem['fba_fees'])
                ->setCellValue('P' . $saleRefundIndex, $saleRefundItem['tail'])
                ->setCellValue('Q' . $saleRefundIndex, $saleRefundItem['calcuRes'])
                ->setCellValue('R' . $saleRefundIndex, $saleRefundItem['ddp'])
                ->setCellValue('S' . $saleRefundIndex, $saleRefundItem['paid_time'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateFbaSheet($index, $report_id, $month)
    {
        $fbaWarehouseSku = $this->model->query(FinanceReportModel::getFbaWarehouseSkuSql($report_id, $month));

        if ($index){
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('FBA');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '月份')
            ->setCellValue('B1', '币种')
            ->setCellValue('C1', '店铺')
            ->setCellValue('D1', 'SKU')
            ->setCellValue('E1', '中文品名')
            ->setCellValue('F1', '运营人员')
            ->setCellValue('G1', '采购人员')
            ->setCellValue('H1', '销售数量')
            ->setCellValue('I1', '退款数量')
            ->setCellValue('J1', '实际销量')
            ->setCellValue('K1', '销售总额')
            ->setCellValue('L1', '销售税')
            ->setCellValue('M1', '退款总额')
            ->setCellValue('N1', '实际销售总额')
            ->setCellValue('O1', '平台佣金')
            ->setCellValue('P1', '平台佣金退款')
            ->setCellValue('Q1', 'FBA尾程')
            ->setCellValue('R1', 'FBA尾程退款')
            ->setCellValue('S1', '退款其他')
            ->setCellValue('T1', '调整费用')
            ->setCellValue('U1', '清算费用')
            ->setCellValue('V1', '促销费')
            ->setCellValue('W1', '退运费')
            ->setCellValue('X1', 'FBA仓储费')
            ->setCellValue('Y1', '平台广告费')
            ->setCellValue('Z1', '国内广告费')
            ->setCellValue('AA1', '工厂运费')
            ->setCellValue('AB1', '国内快递费')
            ->setCellValue('AC1', '产品DDP总值')
            ->setCellValue('AD1', 'DDP占比')
            ->setCellValue('AE1', '毛利')
            ->setCellValue('AF1', '毛利率')
            ->setCellValue('AG1', '广告费占比')
            ->setCellValue('AH1', '仓储费占比')
            ->setCellValue('AI1', '尾程占比')
            ->setCellValue('AJ1', '测评数量')
            ->setCellValue('AK1', '测评金额')
            ->setCellValue('AL1', '含测评毛利')
            ->setCellValue('AM1', '含测评毛利率')
        ;

        $fbaIndex = 1;
        foreach ($fbaWarehouseSku as $fbaItem) {
            $fbaIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $fbaIndex, $month)
                ->setCellValue('B' . $fbaIndex, self::getCurrencyByUserAccount($fbaItem['userAccount']))
                ->setCellValue('C' . $fbaIndex, $fbaItem['userAccount'])
                ->setCellValue('D' . $fbaIndex, $fbaItem['warehouse_sku'])
                ->setCellValue('E' . $fbaIndex, $fbaItem['product_name'])
                ->setCellValue('F' . $fbaIndex, $fbaItem['seller'])
                ->setCellValue('G' . $fbaIndex, $fbaItem['purchaser'])
                ->setCellValue('H' . $fbaIndex, $fbaItem['fba_sale_qty'])
                ->setCellValue('I' . $fbaIndex, $fbaItem['fba_refund_qty'])
                ->setCellValue('J' . $fbaIndex, $fbaItem['fba_qty_amount'])
                ->setCellValue('K' . $fbaIndex, $fbaItem['fba_sale_amount'])
                ->setCellValue('L' . $fbaIndex, $fbaItem['fba_sale_tax'])
                ->setCellValue('M' . $fbaIndex, $fbaItem['fba_refund_amount'])
                ->setCellValue('N' . $fbaIndex, $fbaItem['fba_amount'])
                ->setCellValue('O' . $fbaIndex, $fbaItem['fba_sale_selling_fees'])
                ->setCellValue('P' . $fbaIndex, $fbaItem['fba_refund_selling_fees'])
                ->setCellValue('Q' . $fbaIndex, $fbaItem['fba_fees'])
                ->setCellValue('R' . $fbaIndex, $fbaItem['fba_refund_fees'])
                ->setCellValue('S' . $fbaIndex, $fbaItem['fba_refund_other'])
                ->setCellValue('T' . $fbaIndex, $fbaItem['adjustment'])
                ->setCellValue('U' . $fbaIndex, $fbaItem['liquidation'])
                ->setCellValue('V' . $fbaIndex, $fbaItem['promotion'])
                ->setCellValue('W' . $fbaIndex, $fbaItem['shipping_service'])
                ->setCellValue('X' . $fbaIndex, $fbaItem['fba_inventory'])
                ->setCellValue('Y' . $fbaIndex, $fbaItem['fba_adCost'])
                ->setCellValue('Z' . $fbaIndex, $fbaItem['operation_expenses'])
                ->setCellValue('AA' . $fbaIndex, $fbaItem['operation_factory'])
                ->setCellValue('AB' . $fbaIndex, $fbaItem['operation_delivery'])
                ->setCellValue('AC' . $fbaIndex, $fbaItem['fba_ddp'])
                ->setCellValue('AD' . $fbaIndex, $fbaItem['ddp_percent'])
                ->setCellValue('AE' . $fbaIndex, $fbaItem['profit'])
                ->setCellValue('AF' . $fbaIndex, $fbaItem['gross_profit_margin'])
                ->setCellValue('AG' . $fbaIndex, $fbaItem['ad_percent'])
                ->setCellValue('AH' . $fbaIndex, $fbaItem['inventory_percent'])
                ->setCellValue('AI' . $fbaIndex, $fbaItem['tail_percent'])
                ->setCellValue('AJ' . $fbaIndex, $fbaItem['evaluation_qty'])
                ->setCellValue('AK' . $fbaIndex, $fbaItem['evaluation_amount'])
                ->setCellValue('AL' . $fbaIndex, $fbaItem['profit_include_evaluation'])
                ->setCellValue('AM' . $fbaIndex, $fbaItem['gross_profit_margin_include_evaluation'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateFbmSheet($index, $report_id, $month)
    {
        $fbmWarehouseSku = $this->model->query(FinanceReportModel::getFbmWarehouseSkuSql($report_id, $month));

        if ($index){
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('FBM');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '月份')
            ->setCellValue('B1', '币种')
            ->setCellValue('C1', '店铺')
            ->setCellValue('D1', 'SKU')
            ->setCellValue('E1', '中文品名')
            ->setCellValue('F1', '运营人员')
            ->setCellValue('G1', '采购人员')
            ->setCellValue('H1', '销售数量')
            ->setCellValue('I1', '退款数量')
            ->setCellValue('J1', '实际销量')
            ->setCellValue('K1', '销售总额')
            ->setCellValue('L1', '销售税')
            ->setCellValue('M1', '退款总额')
            ->setCellValue('N1', '实际销售总额')
            ->setCellValue('O1', '平台佣金')
            ->setCellValue('P1', '平台佣金退款')
            ->setCellValue('Q1', '退款其他')
            ->setCellValue('R1', '调整费用')
            ->setCellValue('S1', '清算费用')
            ->setCellValue('T1', '促销费')
            ->setCellValue('U1', '退运费')
            ->setCellValue('V1', 'FBM尾程')
            ->setCellValue('W1', '海外仓仓储费')
            ->setCellValue('X1', '良仓调整')
            ->setCellValue('Y1', '乐歌调整')
            ->setCellValue('Z1', '平台广告费')
            ->setCellValue('AA1', '国内广告费')
            ->setCellValue('AB1', '工厂运费')
            ->setCellValue('AC1', '国内快递费')
            ->setCellValue('AD1', '产品DDP总值')
            ->setCellValue('AE1', 'DDP占比')
            ->setCellValue('AF1', '毛利')
            ->setCellValue('AG1', '毛利率')
            ->setCellValue('AH1', '广告费占比')
            ->setCellValue('AI1', '仓储费占比')
            ->setCellValue('AJ1', '尾程占比')
            ->setCellValue('AK1', '测评数量')
            ->setCellValue('AL1', '测评金额')
            ->setCellValue('AM1', '含测评毛利')
            ->setCellValue('AN1', '含测评毛利率')
        ;

        $fbmIndex = 1;
        foreach ($fbmWarehouseSku as $fbmItem) {
            $fbmIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $fbmIndex, $month)
                ->setCellValue('B' . $fbmIndex, self::getCurrencyByUserAccount($fbmItem['userAccount']))
                ->setCellValue('C' . $fbmIndex, $fbmItem['userAccount'])
                ->setCellValue('D' . $fbmIndex, $fbmItem['warehouse_sku'])
                ->setCellValue('E' . $fbmIndex, $fbmItem['product_name'])
                ->setCellValue('F' . $fbmIndex, $fbmItem['seller'])
                ->setCellValue('G' . $fbmIndex, $fbmItem['purchaser'])
                ->setCellValue('H' . $fbmIndex, $fbmItem['fbm_sale_qty'])
                ->setCellValue('I' . $fbmIndex, $fbmItem['fbm_refund_qty'])
                ->setCellValue('J' . $fbmIndex, $fbmItem['fbm_qty_amount'])
                ->setCellValue('K' . $fbmIndex, $fbmItem['fbm_sale_amount'])
                ->setCellValue('L' . $fbmIndex, $fbmItem['fbm_sale_tax'])
                ->setCellValue('M' . $fbmIndex, $fbmItem['fbm_refund_amount'])
                ->setCellValue('N' . $fbmIndex, $fbmItem['fbm_amount'])
                ->setCellValue('O' . $fbmIndex, $fbmItem['fbm_sale_selling_fees'])
                ->setCellValue('P' . $fbmIndex, $fbmItem['fbm_refund_selling_fees'])
                ->setCellValue('Q' . $fbmIndex, $fbmItem['fbm_refund_other'])
                ->setCellValue('R' . $fbmIndex, $fbmItem['adjustment'])
                ->setCellValue('S' . $fbmIndex, $fbmItem['liquidation'])
                ->setCellValue('T' . $fbmIndex, $fbmItem['promotion'])
                ->setCellValue('U' . $fbmIndex, $fbmItem['shipping_service'])
                ->setCellValue('V' . $fbmIndex, $fbmItem['calcuRes'])
                ->setCellValue('W' . $fbmIndex, $fbmItem['warehouse_rent'])
                ->setCellValue('X' . $fbmIndex, $fbmItem['lc_adjustment'])
                ->setCellValue('Y' . $fbmIndex, $fbmItem['le_adjustment'])
                ->setCellValue('Z' . $fbmIndex, $fbmItem['fbm_adCost'])
                ->setCellValue('AA' . $fbmIndex, $fbmItem['operation_expenses'])
                ->setCellValue('AB' . $fbmIndex, $fbmItem['operation_factory'])
                ->setCellValue('AC' . $fbmIndex, $fbmItem['operation_delivery'])
                ->setCellValue('AD' . $fbmIndex, $fbmItem['fbm_ddp'])
                ->setCellValue('AE' . $fbmIndex, $fbmItem['ddp_percent'])
                ->setCellValue('AF' . $fbmIndex, $fbmItem['profit'])
                ->setCellValue('AG' . $fbmIndex, $fbmItem['gross_profit_margin'])
                ->setCellValue('AH' . $fbmIndex, $fbmItem['ad_percent'])
                ->setCellValue('AI' . $fbmIndex, $fbmItem['inventory_percent'])
                ->setCellValue('AJ' . $fbmIndex, $fbmItem['tail_percent'])
                ->setCellValue('AK' . $fbmIndex, $fbmItem['evaluation_qty'])
                ->setCellValue('AL' . $fbmIndex, $fbmItem['evaluation_amount'])
                ->setCellValue('AM' . $fbmIndex, $fbmItem['profit_include_evaluation'])
                ->setCellValue('AN' . $fbmIndex, $fbmItem['gross_profit_margin_include_evaluation'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateWalmartSheet($index, $report_id, $month)
    {
        $walmartWarehouseSku = $this->model->query(FinanceReportModel::getWalmartWarehouseSkuSql($report_id));

        if ($index){
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('Walmart');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '月份')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', 'SKU')
            ->setCellValue('D1', '中文品名')
            ->setCellValue('E1', '运营人员')
            ->setCellValue('F1', '采购人员')
            ->setCellValue('G1', '销售数量')
            ->setCellValue('H1', '退款数量')
            ->setCellValue('I1', '实际销量')
            ->setCellValue('J1', '销售总额')
            ->setCellValue('K1', '退款总额')
            ->setCellValue('L1', '实际销售总额')
            ->setCellValue('M1', '平台佣金')
            ->setCellValue('N1', '平台佣金退款')
            ->setCellValue('O1', '调整费用')
            ->setCellValue('P1', 'FBM尾程')
            ->setCellValue('Q1', 'WFS尾程')
            ->setCellValue('R1', 'WFS退运费')
            ->setCellValue('S1', 'WFS仓储费')
            ->setCellValue('T1', 'WFS调整费用')
            ->setCellValue('U1', '海外仓仓储费')
            ->setCellValue('V1', '良仓调整')
            ->setCellValue('W1', '乐歌调整')
            ->setCellValue('X1', '平台广告费')
            ->setCellValue('Y1', '国内广告费')
            ->setCellValue('Z1', '工厂运费')
            ->setCellValue('AA1', '国内快递费')
            ->setCellValue('AB1', '产品DDP总值')
            ->setCellValue('AC1', 'DDP占比')
            ->setCellValue('AD1', '毛利')
            ->setCellValue('AE1', '毛利率')
            ->setCellValue('AF1', '广告费占比')
            ->setCellValue('AG1', '仓储费占比')
            ->setCellValue('AH1', '尾程占比')
            ->setCellValue('AI1', '测评数量')
            ->setCellValue('AJ1', '测评金额')
            ->setCellValue('AK1', '含测评毛利')
            ->setCellValue('AL1', '含测评毛利率')
        ;

        $walmartIndex = 1;
        foreach ($walmartWarehouseSku as $walmartItem) {
            $walmartIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $walmartIndex, $month)
                ->setCellValue('B' . $walmartIndex, $walmartItem['userAccount'])
                ->setCellValue('C' . $walmartIndex, $walmartItem['warehouse_sku'])
                ->setCellValue('D' . $walmartIndex, $walmartItem['product_name'])
                ->setCellValue('E' . $walmartIndex, $walmartItem['seller'])
                ->setCellValue('F' . $walmartIndex, $walmartItem['purchaser'])
                ->setCellValue('G' . $walmartIndex, $walmartItem['sale_qty'])
                ->setCellValue('H' . $walmartIndex, $walmartItem['refund_qty'])
                ->setCellValue('I' . $walmartIndex, $walmartItem['qty_amount'])
                ->setCellValue('J' . $walmartIndex, $walmartItem['sale_amount'])
                ->setCellValue('K' . $walmartIndex, $walmartItem['refund_amount'])
                ->setCellValue('L' . $walmartIndex, $walmartItem['amount'])
                ->setCellValue('M' . $walmartIndex, $walmartItem['sale_selling_fees'])
                ->setCellValue('N' . $walmartIndex, $walmartItem['refund_selling_fees'])
                ->setCellValue('O' . $walmartIndex, $walmartItem['adjustment'])
                ->setCellValue('P' . $walmartIndex, $walmartItem['calcuRes'])
                ->setCellValue('Q' . $walmartIndex, $walmartItem['wfs_fulfillment'])
                ->setCellValue('R' . $walmartIndex, $walmartItem['wfs_return_shipping'])
                ->setCellValue('S' . $walmartIndex, $walmartItem['wfs_warehouse'])
                ->setCellValue('T' . $walmartIndex, $walmartItem['wfs_adjustment'])
                ->setCellValue('U' . $walmartIndex, $walmartItem['warehouse_rent'])
                ->setCellValue('V' . $walmartIndex, $walmartItem['lc_adjustment'])
                ->setCellValue('W' . $walmartIndex, $walmartItem['le_adjustment'])
                ->setCellValue('X' . $walmartIndex, $walmartItem['adCost'])
                ->setCellValue('Y' . $walmartIndex, $walmartItem['operation_expenses'])
                ->setCellValue('Z' . $walmartIndex, $walmartItem['operation_factory'])
                ->setCellValue('AA' . $walmartIndex, $walmartItem['operation_delivery'])
                ->setCellValue('AB' . $walmartIndex, $walmartItem['ddp'])
                ->setCellValue('AC' . $walmartIndex, $walmartItem['ddp_percent'])
                ->setCellValue('AD' . $walmartIndex, $walmartItem['profit'])
                ->setCellValue('AE' . $walmartIndex, $walmartItem['gross_profit_margin'])
                ->setCellValue('AF' . $walmartIndex, $walmartItem['ad_percent'])
                ->setCellValue('AG' . $walmartIndex, $walmartItem['warehouse_percent'])
                ->setCellValue('AH' . $walmartIndex, $walmartItem['tail_percent'])
                ->setCellValue('AI' . $walmartIndex, $walmartItem['evaluation_qty'])
                ->setCellValue('AJ' . $walmartIndex, $walmartItem['evaluation_amount'])
                ->setCellValue('AK' . $walmartIndex, $walmartItem['profit_include_evaluation'])
                ->setCellValue('AL' . $walmartIndex, $walmartItem['gross_profit_margin_include_evaluation'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateWayfairSheet($index, $report_id, $month)
    {
        $wayfairWarehouseSku = $this->model->query(FinanceReportModel::getWayfairWarehouseSkuSql($report_id));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('Wayfair');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '月份')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', 'SKU')
            ->setCellValue('D1', '中文品名')
            ->setCellValue('E1', '运营人员')
            ->setCellValue('F1', '采购人员')
            ->setCellValue('G1', '销售数量')
            ->setCellValue('H1', '退款数量')
            ->setCellValue('I1', '实际销量')
            ->setCellValue('J1', '销售总额')
            ->setCellValue('K1', '退款总额')
            ->setCellValue('L1', '实际销售总额')
            ->setCellValue('M1', '平台佣金')
            ->setCellValue('N1', '平台佣金退款')
            ->setCellValue('O1', '调整费用')
            ->setCellValue('P1', 'FBM尾程')
            ->setCellValue('Q1', '海外仓仓储费')
            ->setCellValue('R1', '良仓调整')
            ->setCellValue('S1', '乐歌调整')
            ->setCellValue('T1', '平台广告费')
            ->setCellValue('U1', '国内广告费')
            ->setCellValue('V1', '工厂运费')
            ->setCellValue('W1', '国内快递费')
            ->setCellValue('X1', '产品DDP总值')
            ->setCellValue('Y1', 'DDP占比')
            ->setCellValue('Z1', '毛利')
            ->setCellValue('AA1', '毛利率')
            ->setCellValue('AB1', '广告费占比')
            ->setCellValue('AC1', '仓储费占比')
            ->setCellValue('AD1', '尾程占比')
            ->setCellValue('AE1', '测评数量')
            ->setCellValue('AF1', '测评金额')
            ->setCellValue('AG1', '含测评毛利')
            ->setCellValue('AH1', '含测评毛利率')
        ;

        $wayfairIndex = 1;
        foreach ($wayfairWarehouseSku as $wayfairItem) {
            $wayfairIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $wayfairIndex, $month)
                ->setCellValue('B' . $wayfairIndex, $wayfairItem['userAccount'])
                ->setCellValue('C' . $wayfairIndex, $wayfairItem['warehouse_sku'])
                ->setCellValue('D' . $wayfairIndex, $wayfairItem['product_name'])
                ->setCellValue('E' . $wayfairIndex, $wayfairItem['seller'])
                ->setCellValue('F' . $wayfairIndex, $wayfairItem['purchaser'])
                ->setCellValue('G' . $wayfairIndex, $wayfairItem['sale_qty'])
                ->setCellValue('H' . $wayfairIndex, $wayfairItem['refund_qty'])
                ->setCellValue('I' . $wayfairIndex, $wayfairItem['qty_amount'])
                ->setCellValue('J' . $wayfairIndex, $wayfairItem['sale_amount'])
                ->setCellValue('K' . $wayfairIndex, $wayfairItem['refund_amount'])
                ->setCellValue('L' . $wayfairIndex, $wayfairItem['amount'])
                ->setCellValue('M' . $wayfairIndex, $wayfairItem['sale_selling_fees'])
                ->setCellValue('N' . $wayfairIndex, $wayfairItem['refund_selling_fees'])
                ->setCellValue('O' . $wayfairIndex, $wayfairItem['adjustment'])
                ->setCellValue('P' . $wayfairIndex, $wayfairItem['calcuRes'])
                ->setCellValue('Q' . $wayfairIndex, $wayfairItem['warehouse_rent'])
                ->setCellValue('R' . $wayfairIndex, $wayfairItem['lc_adjustment'])
                ->setCellValue('S' . $wayfairIndex, $wayfairItem['le_adjustment'])
                ->setCellValue('T' . $wayfairIndex, $wayfairItem['adCost'])
                ->setCellValue('U' . $wayfairIndex, $wayfairItem['operation_expenses'])
                ->setCellValue('V' . $wayfairIndex, $wayfairItem['operation_factory'])
                ->setCellValue('W' . $wayfairIndex, $wayfairItem['operation_delivery'])
                ->setCellValue('X' . $wayfairIndex, $wayfairItem['ddp'])
                ->setCellValue('Y' . $wayfairIndex, $wayfairItem['ddp_percent'])
                ->setCellValue('Z' . $wayfairIndex, $wayfairItem['profit'])
                ->setCellValue('AA' . $wayfairIndex, $wayfairItem['gross_profit_margin'])
                ->setCellValue('AB' . $wayfairIndex, $wayfairItem['ad_percent'])
                ->setCellValue('AC' . $wayfairIndex, $wayfairItem['warehouse_percent'])
                ->setCellValue('AD' . $wayfairIndex, $wayfairItem['tail_percent'])
                ->setCellValue('AE' . $wayfairIndex, $wayfairItem['evaluation_qty'])
                ->setCellValue('AF' . $wayfairIndex, $wayfairItem['evaluation_amount'])
                ->setCellValue('AG' . $wayfairIndex, $wayfairItem['profit_include_evaluation'])
                ->setCellValue('AH' . $wayfairIndex, $wayfairItem['gross_profit_margin_include_evaluation'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateTemuSheet($index, $report_id, $month)
    {
        $temuWarehouseSku = $this->model->query(FinanceReportModel::getTemuWarehouseSkuSql($report_id));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('Temu');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '月份')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', 'SKU')
            ->setCellValue('D1', '中文品名')
            ->setCellValue('E1', '运营人员')
            ->setCellValue('F1', '采购人员')
            ->setCellValue('G1', '销售数量')
            ->setCellValue('H1', '退款数量')
            ->setCellValue('I1', '实际销量')
            ->setCellValue('J1', '销售总额')
            ->setCellValue('K1', '退款总额')
            ->setCellValue('L1', '实际销售总额')
            ->setCellValue('M1', '平台佣金')
            ->setCellValue('N1', '平台佣金退款')
            ->setCellValue('O1', '调整费用')
            ->setCellValue('P1', '海外仓尾程')
            ->setCellValue('Q1', '平台面单费')
            ->setCellValue('R1', '海外仓仓储费')
            ->setCellValue('S1', '良仓调整')
            ->setCellValue('T1', '乐歌调整')
            ->setCellValue('U1', '平台广告费')
            ->setCellValue('V1', '国内广告费')
            ->setCellValue('W1', '工厂运费')
            ->setCellValue('X1', '国内快递费')
            ->setCellValue('Y1', '产品DDP总值')
            ->setCellValue('Z1', 'DDP占比')
            ->setCellValue('AA1', '毛利')
            ->setCellValue('AB1', '毛利率')
            ->setCellValue('AC1', '广告费占比')
            ->setCellValue('AD1', '仓储费占比')
            ->setCellValue('AE1', '尾程占比')
            ->setCellValue('AF1', '测评数量')
            ->setCellValue('AG1', '测评金额')
            ->setCellValue('AH1', '含测评毛利')
            ->setCellValue('AI1', '含测评毛利率')
        ;

        $temuIndex = 1;
        foreach ($temuWarehouseSku as $temuItem) {
            $temuIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $temuIndex, $month)
                ->setCellValue('B' . $temuIndex, $temuItem['userAccount'])
                ->setCellValue('C' . $temuIndex, $temuItem['warehouse_sku'])
                ->setCellValue('D' . $temuIndex, $temuItem['product_name'])
                ->setCellValue('E' . $temuIndex, $temuItem['seller'])
                ->setCellValue('F' . $temuIndex, $temuItem['purchaser'])
                ->setCellValue('G' . $temuIndex, $temuItem['sale_qty'])
                ->setCellValue('H' . $temuIndex, $temuItem['refund_qty'])
                ->setCellValue('I' . $temuIndex, $temuItem['qty_amount'])
                ->setCellValue('J' . $temuIndex, $temuItem['sale_amount'])
                ->setCellValue('K' . $temuIndex, $temuItem['refund_amount'])
                ->setCellValue('L' . $temuIndex, $temuItem['amount'])
                ->setCellValue('M' . $temuIndex, $temuItem['sale_selling_fees'])
                ->setCellValue('N' . $temuIndex, $temuItem['refund_selling_fees'])
                ->setCellValue('O' . $temuIndex, $temuItem['adjustment'])
                ->setCellValue('P' . $temuIndex, $temuItem['calcuRes'])
                ->setCellValue('Q' . $temuIndex, $temuItem['waybill'])
                ->setCellValue('R' . $temuIndex, $temuItem['warehouse_rent'])
                ->setCellValue('S' . $temuIndex, $temuItem['lc_adjustment'])
                ->setCellValue('T' . $temuIndex, $temuItem['le_adjustment'])
                ->setCellValue('U' . $temuIndex, $temuItem['adCost'])
                ->setCellValue('V' . $temuIndex, $temuItem['operation_expenses'])
                ->setCellValue('W' . $temuIndex, $temuItem['operation_factory'])
                ->setCellValue('X' . $temuIndex, $temuItem['operation_delivery'])
                ->setCellValue('Y' . $temuIndex, $temuItem['ddp'])
                ->setCellValue('Z' . $temuIndex, $temuItem['ddp_percent'])
                ->setCellValue('AA' . $temuIndex, $temuItem['profit'])
                ->setCellValue('AB' . $temuIndex, $temuItem['gross_profit_margin'])
                ->setCellValue('AC' . $temuIndex, $temuItem['ad_percent'])
                ->setCellValue('AD' . $temuIndex, $temuItem['warehouse_percent'])
                ->setCellValue('AE' . $temuIndex, $temuItem['tail_percent'])
                ->setCellValue('AF' . $temuIndex, $temuItem['evaluation_qty'])
                ->setCellValue('AG' . $temuIndex, $temuItem['evaluation_amount'])
                ->setCellValue('AH' . $temuIndex, $temuItem['profit_include_evaluation'])
                ->setCellValue('AI' . $temuIndex, $temuItem['gross_profit_margin_include_evaluation'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateEbaySheet($index, $report_id, $month)
    {
        $ebayWarehouseSku = $this->model->query(FinanceReportModel::getEbayWarehouseSkuSql($report_id));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('Ebay');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '月份')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', 'SKU')
            ->setCellValue('D1', '中文品名')
            ->setCellValue('E1', '运营人员')
            ->setCellValue('F1', '采购人员')
            ->setCellValue('G1', '销售数量')
            ->setCellValue('H1', '退款数量')
            ->setCellValue('I1', '实际销量')
            ->setCellValue('J1', '销售总额')
            ->setCellValue('K1', '税')
            ->setCellValue('L1', '退款总额')
            ->setCellValue('M1', '实际销售总额')
            ->setCellValue('N1', '平台佣金')
            ->setCellValue('O1', '平台佣金退款')
            ->setCellValue('P1', '调整费用')
            ->setCellValue('Q1', 'FBM尾程')
            ->setCellValue('R1', '海外仓仓储费')
            ->setCellValue('S1', '良仓调整')
            ->setCellValue('T1', '乐歌调整')
            ->setCellValue('U1', '平台广告费')
            ->setCellValue('V1', '国内广告费')
            ->setCellValue('W1', '工厂运费')
            ->setCellValue('X1', '国内快递费')
            ->setCellValue('Y1', '产品DDP总值')
            ->setCellValue('Z1', 'DDP占比')
            ->setCellValue('AA1', '毛利')
            ->setCellValue('AB1', '毛利率')
            ->setCellValue('AC1', '广告费占比')
            ->setCellValue('AD1', '仓储费占比')
            ->setCellValue('AE1', '尾程占比')
            ->setCellValue('AF1', '测评数量')
            ->setCellValue('AG1', '测评金额')
            ->setCellValue('AH1', '含测评毛利')
            ->setCellValue('AI1', '含测评毛利率')
        ;

        $ebayIndex = 1;
        foreach ($ebayWarehouseSku as $ebayItem) {
            $ebayIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $ebayIndex, $month)
                ->setCellValue('B' . $ebayIndex, $ebayItem['userAccount'])
                ->setCellValue('C' . $ebayIndex, $ebayItem['warehouse_sku'])
                ->setCellValue('D' . $ebayIndex, $ebayItem['product_name'])
                ->setCellValue('E' . $ebayIndex, $ebayItem['seller'])
                ->setCellValue('F' . $ebayIndex, $ebayItem['purchaser'])
                ->setCellValue('G' . $ebayIndex, $ebayItem['sale_qty'])
                ->setCellValue('H' . $ebayIndex, $ebayItem['refund_qty'])
                ->setCellValue('I' . $ebayIndex, $ebayItem['qty_amount'])
                ->setCellValue('J' . $ebayIndex, $ebayItem['sale_amount'])
                ->setCellValue('K' . $ebayIndex, $ebayItem['sale_tax'])
                ->setCellValue('L' . $ebayIndex, $ebayItem['refund_amount'])
                ->setCellValue('M' . $ebayIndex, $ebayItem['amount'])
                ->setCellValue('N' . $ebayIndex, $ebayItem['sale_selling_fees'])
                ->setCellValue('O' . $ebayIndex, $ebayItem['refund_selling_fees'])
                ->setCellValue('P' . $ebayIndex, $ebayItem['adjustment'])
                ->setCellValue('Q' . $ebayIndex, $ebayItem['calcuRes'])
                ->setCellValue('R' . $ebayIndex, $ebayItem['warehouse_rent'])
                ->setCellValue('S' . $ebayIndex, $ebayItem['lc_adjustment'])
                ->setCellValue('T' . $ebayIndex, $ebayItem['le_adjustment'])
                ->setCellValue('U' . $ebayIndex, $ebayItem['adCost'])
                ->setCellValue('V' . $ebayIndex, $ebayItem['operation_expenses'])
                ->setCellValue('W' . $ebayIndex, $ebayItem['operation_factory'])
                ->setCellValue('X' . $ebayIndex, $ebayItem['operation_delivery'])
                ->setCellValue('Y' . $ebayIndex, $ebayItem['ddp'])
                ->setCellValue('Z' . $ebayIndex, $ebayItem['ddp_percent'])
                ->setCellValue('AA' . $ebayIndex, $ebayItem['profit'])
                ->setCellValue('AB' . $ebayIndex, $ebayItem['gross_profit_margin'])
                ->setCellValue('AC' . $ebayIndex, $ebayItem['ad_percent'])
                ->setCellValue('AD' . $ebayIndex, $ebayItem['warehouse_percent'])
                ->setCellValue('AE' . $ebayIndex, $ebayItem['tail_percent'])
                ->setCellValue('AF' . $ebayIndex, $ebayItem['evaluation_qty'])
                ->setCellValue('AG' . $ebayIndex, $ebayItem['evaluation_amount'])
                ->setCellValue('AH' . $ebayIndex, $ebayItem['profit_include_evaluation'])
                ->setCellValue('AI' . $ebayIndex, $ebayItem['gross_profit_margin_include_evaluation'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateSheinSheet($index, $report_id, $month)
    {
        $sheinWarehouseSku = $this->model->query(FinanceReportModel::getSheinWarehouseSkuSql($report_id));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('Shein');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '月份')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', 'SKU')
            ->setCellValue('D1', '中文品名')
            ->setCellValue('E1', '运营人员')
            ->setCellValue('F1', '采购人员')
            ->setCellValue('G1', '销售数量')
            ->setCellValue('H1', '退款数量')
            ->setCellValue('I1', '实际销量')
            ->setCellValue('J1', '销售总额')
            ->setCellValue('K1', '退款总额')
            ->setCellValue('L1', '实际销售总额')
            ->setCellValue('M1', '平台佣金')
            ->setCellValue('N1', '平台佣金退款')
            ->setCellValue('O1', '调整费用')
            ->setCellValue('P1', 'FBM尾程')
            ->setCellValue('Q1', '海外仓仓储费')
            ->setCellValue('R1', '良仓调整')
            ->setCellValue('S1', '乐歌调整')
            ->setCellValue('T1', '平台广告费')
            ->setCellValue('U1', '国内广告费')
            ->setCellValue('V1', '工厂运费')
            ->setCellValue('W1', '国内快递费')
            ->setCellValue('X1', '产品DDP总值')
            ->setCellValue('Y1', 'DDP占比')
            ->setCellValue('Z1', '毛利')
            ->setCellValue('AA1', '毛利率')
            ->setCellValue('AB1', '广告费占比')
            ->setCellValue('AC1', '仓储费占比')
            ->setCellValue('AD1', '尾程占比')
            ->setCellValue('AE1', '测评数量')
            ->setCellValue('AF1', '测评金额')
            ->setCellValue('AG1', '含测评毛利')
            ->setCellValue('AH1', '含测评毛利率')
        ;

        $sheinIndex = 1;
        foreach ($sheinWarehouseSku as $sheinItem) {
            $sheinIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $sheinIndex, $month)
                ->setCellValue('B' . $sheinIndex, $sheinItem['userAccount'])
                ->setCellValue('C' . $sheinIndex, $sheinItem['warehouse_sku'])
                ->setCellValue('D' . $sheinIndex, $sheinItem['product_name'])
                ->setCellValue('E' . $sheinIndex, $sheinItem['seller'])
                ->setCellValue('F' . $sheinIndex, $sheinItem['purchaser'])
                ->setCellValue('G' . $sheinIndex, $sheinItem['sale_qty'])
                ->setCellValue('H' . $sheinIndex, $sheinItem['refund_qty'])
                ->setCellValue('I' . $sheinIndex, $sheinItem['qty_amount'])
                ->setCellValue('J' . $sheinIndex, $sheinItem['sale_amount'])
                ->setCellValue('K' . $sheinIndex, $sheinItem['refund_amount'])
                ->setCellValue('L' . $sheinIndex, $sheinItem['amount'])
                ->setCellValue('M' . $sheinIndex, $sheinItem['sale_selling_fees'])
                ->setCellValue('N' . $sheinIndex, $sheinItem['refund_selling_fees'])
                ->setCellValue('O' . $sheinIndex, $sheinItem['adjustment'])
                ->setCellValue('P' . $sheinIndex, $sheinItem['calcuRes'])
                ->setCellValue('Q' . $sheinIndex, $sheinItem['warehouse_rent'])
                ->setCellValue('R' . $sheinIndex, $sheinItem['lc_adjustment'])
                ->setCellValue('S' . $sheinIndex, $sheinItem['le_adjustment'])
                ->setCellValue('T' . $sheinIndex, $sheinItem['adCost'])
                ->setCellValue('U' . $sheinIndex, $sheinItem['operation_expenses'])
                ->setCellValue('V' . $sheinIndex, $sheinItem['operation_factory'])
                ->setCellValue('W' . $sheinIndex, $sheinItem['operation_delivery'])
                ->setCellValue('X' . $sheinIndex, $sheinItem['ddp'])
                ->setCellValue('Y' . $sheinIndex, $sheinItem['ddp_percent'])
                ->setCellValue('Z' . $sheinIndex, $sheinItem['profit'])
                ->setCellValue('AA' . $sheinIndex, $sheinItem['gross_profit_margin'])
                ->setCellValue('AB' . $sheinIndex, $sheinItem['ad_percent'])
                ->setCellValue('AC' . $sheinIndex, $sheinItem['warehouse_percent'])
                ->setCellValue('AD' . $sheinIndex, $sheinItem['tail_percent'])
                ->setCellValue('AE' . $sheinIndex, $sheinItem['evaluation_qty'])
                ->setCellValue('AF' . $sheinIndex, $sheinItem['evaluation_amount'])
                ->setCellValue('AG' . $sheinIndex, $sheinItem['profit_include_evaluation'])
                ->setCellValue('AH' . $sheinIndex, $sheinItem['gross_profit_margin_include_evaluation'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generatePaymentNoOutboundSheet($index, $report_id)
    {
        $paymentNoOutbound = $this->model->query(FinanceReportModel::getPaymentNoOutboundSql($report_id));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('账单未出库统计');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '平台')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', '账单Payment')
            ->setCellValue('D1', '发货方式')
            ->setCellValue('E1', '账单店铺sku')
            ->setCellValue('F1', '仓库sku')
            ->setCellValue('G1', '数量')
            ->setCellValue('H1', '账单销售')
            ->setCellValue('I1', '账单佣金')
            ->setCellValue('J1', '账单FBA费用')
            ->setCellValue('K1', '零销售出库回冲')
            ->setCellValue('L1', '零销售出库佣金')
            ->setCellValue('M1', '零销售出库FBA费用')
        ;

        $paymentNoOutboundIndex = 1;
        foreach ($paymentNoOutbound as $paymentNoOutboundItem) {
            $paymentNoOutboundIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $paymentNoOutboundIndex, $paymentNoOutboundItem['platform'])
                ->setCellValue('B' . $paymentNoOutboundIndex, $paymentNoOutboundItem['userAccount'])
                ->setCellValue('C' . $paymentNoOutboundIndex, $paymentNoOutboundItem['payment_id'])
                ->setCellValue('D' . $paymentNoOutboundIndex, $paymentNoOutboundItem['fulfillment'])
                ->setCellValue('E' . $paymentNoOutboundIndex, $paymentNoOutboundItem['sku'])
                ->setCellValue('F' . $paymentNoOutboundIndex, $paymentNoOutboundItem['warehouse_sku'])
                ->setCellValue('G' . $paymentNoOutboundIndex, $paymentNoOutboundItem['quantity'])
                ->setCellValue('H' . $paymentNoOutboundIndex, $paymentNoOutboundItem['payment_amount'])
                ->setCellValue('I' . $paymentNoOutboundIndex, $paymentNoOutboundItem['payment_selling_fees'])
                ->setCellValue('J' . $paymentNoOutboundIndex, $paymentNoOutboundItem['payment_fba_fees'])
                ->setCellValue('K' . $paymentNoOutboundIndex, $paymentNoOutboundItem['outbound_amount'])
                ->setCellValue('L' . $paymentNoOutboundIndex, $paymentNoOutboundItem['outbound_selling_fee'])
                ->setCellValue('M' . $paymentNoOutboundIndex, $paymentNoOutboundItem['outbound_fba_fee'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateAccountTransferSheet($index, $report_id)
    {
        $userAccountTransfer = $this->model->query(FinanceReportModel::getUserAccountTransfer($report_id));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('回款统计');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '平台')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', '合计')
        ;

        $userAccountTransferIndex = 1;
        foreach ($userAccountTransfer as $userAccountTransferItem) {
            $userAccountTransferIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $userAccountTransferIndex, $userAccountTransferItem['platform'])
                ->setCellValue('B' . $userAccountTransferIndex, $userAccountTransferItem['userAccount'])
                ->setCellValue('C' . $userAccountTransferIndex, $userAccountTransferItem['total'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateAccountSubscriptionSheet($index, $report_id)
    {
        $userAccountSubscription = $this->model->query(FinanceReportModel::getUserAccountSubscription($report_id));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('订阅');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '平台')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', '合计')
        ;

        $userAccountSubscriptionIndex = 1;
        foreach ($userAccountSubscription as $userAccountSubscriptionItem) {
            $userAccountSubscriptionIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $userAccountSubscriptionIndex, $userAccountSubscriptionItem['platform'])
                ->setCellValue('B' . $userAccountSubscriptionIndex, $userAccountSubscriptionItem['userAccount'])
                ->setCellValue('C' . $userAccountSubscriptionIndex, $userAccountSubscriptionItem['total'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateOrderWayfairSheet($index, $report_id)
    {
        $orderWayfair = $this->model->query(FinanceReportModel::getOrderWayfair($report_id));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('Wayfair账单销售');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '发票号')
            ->setCellValue('B1', '订单号')
            ->setCellValue('C1', '发票时间')
            ->setCellValue('D1', '账单销售')
            ->setCellValue('E1', '账单佣金')
            ->setCellValue('F1', '账单运费')
            ->setCellValue('G1', '账单其他费用')
            ->setCellValue('H1', '账单税费')
            ->setCellValue('I1', '账单应收')
            ->setCellValue('J1', '发票销售')
            ->setCellValue('K1', '发票佣金')
            ->setCellValue('L1', '发票应收')
        ;

        $orderWayfairIndex = 1;
        foreach ($orderWayfair as $orderWayfairItem) {
            $orderWayfairIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $orderWayfairIndex, $orderWayfairItem['invoice_no'])
                ->setCellValue('B' . $orderWayfairIndex, $orderWayfairItem['order_no'])
                ->setCellValue('C' . $orderWayfairIndex, $orderWayfairItem['invoice_date'])
                ->setCellValue('D' . $orderWayfairIndex, $orderWayfairItem['amount'])
                ->setCellValue('E' . $orderWayfairIndex, $orderWayfairItem['commission'])
                ->setCellValue('F' . $orderWayfairIndex, $orderWayfairItem['shipping'])
                ->setCellValue('G' . $orderWayfairIndex, $orderWayfairItem['other'])
                ->setCellValue('H' . $orderWayfairIndex, $orderWayfairItem['tax'])
                ->setCellValue('I' . $orderWayfairIndex, $orderWayfairItem['collection'])
                ->setCellValue('J' . $orderWayfairIndex, $orderWayfairItem['sale_amount_core'])
                ->setCellValue('K' . $orderWayfairIndex, $orderWayfairItem['commission_core'])
                ->setCellValue('L' . $orderWayfairIndex, $orderWayfairItem['collection_core'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateOrderResendSheet($index, $report_id, $month)
    {
        $orderResend = $this->model->query(FinanceReportModel::getOrderResend($report_id, $month));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('Resend');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '平台')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', '参考号码')
            ->setCellValue('D1', '仓库SKU')
            ->setCellValue('E1', '数量')
            ->setCellValue('F1', '订单状态')
            ->setCellValue('G1', '尾程')
            ->setCellValue('H1', '支付时间')
            ->setCellValue('I1', '运营人员')
            ->setCellValue('J1', '采购人员')
        ;

        $orderResendIndex = 1;
        foreach ($orderResend as $orderResendItem) {
            $orderResendIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $orderResendIndex, $orderResendItem['platform'])
                ->setCellValue('B' . $orderResendIndex, $orderResendItem['user_account'])
                ->setCellValue('C' . $orderResendIndex, $orderResendItem['saleOrderCode'])
                ->setCellValue('D' . $orderResendIndex, $orderResendItem['warehouse_sku'])
                ->setCellValue('E' . $orderResendIndex, $orderResendItem['qty'])
                ->setCellValue('F' . $orderResendIndex, $orderResendItem['order_status'])
                ->setCellValue('G' . $orderResendIndex, $orderResendItem['tail'])
                ->setCellValue('H' . $orderResendIndex, $orderResendItem['paid_time'])
                ->setCellValue('I' . $orderResendIndex, $orderResendItem['seller'])
                ->setCellValue('J' . $orderResendIndex, $orderResendItem['purchaser'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateOperationExpensesSheet($index, $report_id)
    {
        $operationExpenses = $this->model->query(FinanceReportModel::getOperationExpenses($report_id));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('国内广告');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '费用类型')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', '运送方式')
            ->setCellValue('D1', 'SKU')
            ->setCellValue('E1', '费用')
            ->setCellValue('F1', '备注')
        ;

        $operationExpensesIndex = 1;
        foreach ($operationExpenses as $operationExpensesItem) {
            $operationExpensesIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $operationExpensesIndex, $operationExpensesItem['type'])
                ->setCellValue('B' . $operationExpensesIndex, $operationExpensesItem['user_account'])
                ->setCellValue('C' . $operationExpensesIndex, $operationExpensesItem['fulfillment'])
                ->setCellValue('D' . $operationExpensesIndex, $operationExpensesItem['warehouse_sku'])
                ->setCellValue('E' . $operationExpensesIndex, $operationExpensesItem['total'])
                ->setCellValue('F' . $operationExpensesIndex, $operationExpensesItem['content'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateOperationFactorySheet($index, $report_id)
    {
        $operationFactory = $this->model->query(FinanceReportModel::getOperationFactory($report_id));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('工厂运费');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '费用类型')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', '运送方式')
            ->setCellValue('D1', 'SKU')
            ->setCellValue('E1', '费用')
            ->setCellValue('F1', '备注')
        ;

        $operationFactoryIndex = 1;
        foreach ($operationFactory as $operationFactoryItem) {
            $operationFactoryIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $operationFactoryIndex, $operationFactoryItem['type'])
                ->setCellValue('B' . $operationFactoryIndex, $operationFactoryItem['user_account'])
                ->setCellValue('C' . $operationFactoryIndex, $operationFactoryItem['fulfillment'])
                ->setCellValue('D' . $operationFactoryIndex, $operationFactoryItem['warehouse_sku'])
                ->setCellValue('E' . $operationFactoryIndex, $operationFactoryItem['total'])
                ->setCellValue('F' . $operationFactoryIndex, $operationFactoryItem['content'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateOperationDeliverySheet($index, $report_id)
    {
        $operationDelivery = $this->model->query(FinanceReportModel::getOperationDelivery($report_id));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('国内快递');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '运营人员')
            ->setCellValue('B1', '平台')
            ->setCellValue('C1', '店铺')
            ->setCellValue('D1', '运送方式')
            ->setCellValue('E1', 'SKU')
            ->setCellValue('F1', '费用')
            ->setCellValue('G1', '快递单号')
        ;

        $operationDeliveryIndex = 1;
        foreach ($operationDelivery as $operationDeliveryItem) {
            $operationDeliveryIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $operationDeliveryIndex, $operationDeliveryItem['seller'])
                ->setCellValue('B' . $operationDeliveryIndex, $operationDeliveryItem['platform'])
                ->setCellValue('C' . $operationDeliveryIndex, $operationDeliveryItem['user_account'])
                ->setCellValue('D' . $operationDeliveryIndex, $operationDeliveryItem['fulfillment'])
                ->setCellValue('E' . $operationDeliveryIndex, $operationDeliveryItem['warehouse_sku'])
                ->setCellValue('F' . $operationDeliveryIndex, $operationDeliveryItem['total'])
                ->setCellValue('G' . $operationDeliveryIndex, $operationDeliveryItem['tracking_number'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateSaleAmountDiffSheet($index, $report_id)
    {
        $saleAmountDiff = $this->model->query(FinanceReportModel::getSaleAmountDiffSql($report_id));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('销售差异');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '店铺')
            ->setCellValue('B1', '订单号')
            ->setCellValue('C1', '易仓参考号')
            ->setCellValue('D1', '销售SKU')
            ->setCellValue('E1', '仓库SKU')
            ->setCellValue('F1', '订单销售')
            ->setCellValue('G1', '订单统计销售')
            ->setCellValue('H1', '订单统计税')
            ->setCellValue('I1', '订单统计ID')
        ;

        $saleAmountIndex = 1;
        foreach ($saleAmountDiff as $saleAmountItem) {
            $saleAmountIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $saleAmountIndex, $saleAmountItem['order_statistic_user_account'])
                ->setCellValue('B' . $saleAmountIndex, $saleAmountItem['payment'])
                ->setCellValue('C' . $saleAmountIndex, $saleAmountItem['saleOrderCode'])
                ->setCellValue('D' . $saleAmountIndex, $saleAmountItem['seller_sku'])
                ->setCellValue('E' . $saleAmountIndex, $saleAmountItem['warehouse_sku'])
                ->setCellValue('F' . $saleAmountIndex, $saleAmountItem['payment_sale_amount'])
                ->setCellValue('G' . $saleAmountIndex, $saleAmountItem['order_statistic_sale_amount'])
                ->setCellValue('H' . $saleAmountIndex, $saleAmountItem['order_statistic_tax'])
                ->setCellValue('I' . $saleAmountIndex, $saleAmountItem['order_statistics_id'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    public function generateSellingFeeDiffSheet($index, $report_id)
    {
        $sellingFeeDiff = $this->model->query(FinanceReportModel::getSellingFeeDiffSql($report_id));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('佣金差异');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '店铺')
            ->setCellValue('B1', '订单号')
            ->setCellValue('C1', '易仓参考号')
            ->setCellValue('D1', '销售SKU')
            ->setCellValue('E1', '仓库SKU')
            ->setCellValue('F1', '订单佣金')
            ->setCellValue('G1', '订单统计佣金')
            ->setCellValue('H1', '订单统计ID')
        ;

        $sellingFeeIndex = 1;
        foreach ($sellingFeeDiff as $sellingFeeItem) {
            $sellingFeeIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $sellingFeeIndex, $sellingFeeItem['order_statistic_user_account'])
                ->setCellValue('B' . $sellingFeeIndex, $sellingFeeItem['payment'])
                ->setCellValue('C' . $sellingFeeIndex, $sellingFeeItem['saleOrderCode'])
                ->setCellValue('D' . $sellingFeeIndex, $sellingFeeItem['seller_sku'])
                ->setCellValue('E' . $sellingFeeIndex, $sellingFeeItem['warehouse_sku'])
                ->setCellValue('F' . $sellingFeeIndex, $sellingFeeItem['payment_selling_fees'])
                ->setCellValue('G' . $sellingFeeIndex, $sellingFeeItem['order_statistic_selling_fees'])
                ->setCellValue('H' . $sellingFeeIndex, $sellingFeeItem['order_statistics_id'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    public function generateFbaFeeDiffSheet($index, $report_id)
    {
        $fbaFeeDiff = $this->model->query(FinanceReportModel::getFbaFeeDiffSql($report_id));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('FBA尾程差异');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '店铺')
            ->setCellValue('B1', '订单号')
            ->setCellValue('C1', '易仓参考号')
            ->setCellValue('D1', '销售SKU')
            ->setCellValue('E1', '仓库SKU')
            ->setCellValue('F1', '订单FBA尾程')
            ->setCellValue('G1', '订单统计FBA尾程')
            ->setCellValue('H1', '订单统计ID')
        ;

        $fbaFeeIndex = 1;
        foreach ($fbaFeeDiff as $fbaFeeItem) {
            $fbaFeeIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $fbaFeeIndex, $fbaFeeItem['order_statistic_user_account'])
                ->setCellValue('B' . $fbaFeeIndex, $fbaFeeItem['payment'])
                ->setCellValue('C' . $fbaFeeIndex, $fbaFeeItem['saleOrderCode'])
                ->setCellValue('D' . $fbaFeeIndex, $fbaFeeItem['seller_sku'])
                ->setCellValue('E' . $fbaFeeIndex, $fbaFeeItem['warehouse_sku'])
                ->setCellValue('F' . $fbaFeeIndex, $fbaFeeItem['payment_fba_fees'])
                ->setCellValue('G' . $fbaFeeIndex, $fbaFeeItem['order_statistic_fba_fees'])
                ->setCellValue('H' . $fbaFeeIndex, $fbaFeeItem['order_statistics_id'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateOperationFactoryClaimSheet($index, $month)
    {
        $operationFactory = $this->model->query(FinanceReportModel::getOperationFactoryClaim($month));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('工厂索赔');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '费用类型')
            ->setCellValue('B1', '币种')
            ->setCellValue('C1', 'SKU')
            ->setCellValue('D1', '费用')
            ->setCellValue('E1', '备注')
        ;

        $operationFactoryIndex = 1;
        foreach ($operationFactory as $operationFactoryItem) {
            $operationFactoryIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $operationFactoryIndex, $operationFactoryItem['type'])
                ->setCellValue('B' . $operationFactoryIndex, $operationFactoryItem['currency'])
                ->setCellValue('C' . $operationFactoryIndex, $operationFactoryItem['sku'])
                ->setCellValue('D' . $operationFactoryIndex, $operationFactoryItem['total'])
                ->setCellValue('E' . $operationFactoryIndex, $operationFactoryItem['content'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function getTablesByFinanceReport($index, $report)
    {
        $model = new FinanceTableModel();
        $table = $model->where(['rid' => $report['id']])->select();

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('财报账单列表');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '账单ID')
            ->setCellValue('B1', '账单文件名')
            ->setCellValue('C1', '所属平台')
            ->setCellValue('D1', '所属店铺')
            ->setCellValue('E1', '所属国家')
            ->setCellValue('F1', '销售')
            ->setCellValue('G1', '退款')
            ->setCellValue('H1', '促销')
            ->setCellValue('I1', '退运')
            ->setCellValue('J1', '清算')
            ->setCellValue('K1', '调整')
            ->setCellValue('L1', '导入时间')
        ;

        $tableIndex = 1;
        foreach ($table as $tableItem) {
            $tableIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $tableIndex, $tableItem['id'])
                ->setCellValue('B' . $tableIndex, $tableItem['table_name'])
                ->setCellValue('C' . $tableIndex, $tableItem['platform'])
                ->setCellValue('D' . $tableIndex, $tableItem['userAccount'])
                ->setCellValue('E' . $tableIndex, $tableItem['country'])
                ->setCellValue('F' . $tableIndex, $tableItem['sale_amount'])
                ->setCellValue('G' . $tableIndex, $tableItem['refund_amount'])
                ->setCellValue('H' . $tableIndex, $tableItem['promotion'])
                ->setCellValue('I' . $tableIndex, $tableItem['shipping_service'])
                ->setCellValue('J' . $tableIndex, $tableItem['liquidation'])
                ->setCellValue('K' . $tableIndex, $tableItem['adjustment'])
                ->setCellValue('L' . $tableIndex, $tableItem['created_at'])
            ;
        }
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function getOutboundAccountingByReport($index, $report)
    {
        $accounting = $this->model->query(FinanceReportModel::getOutboundAccountingByReport($report['id']));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('已核算再出库列表');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '平台')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', 'Payment')
            ->setCellValue('D1', '易仓订单号')
            ->setCellValue('E1', '支付时间')
            ->setCellValue('F1', '发货时间')
            ->setCellValue('G1', '销售SKU')
            ->setCellValue('H1', '仓库SKU')
            ->setCellValue('I1', '发货方式')
            ->setCellValue('J1', '销售数量')
        ;

        $accountingIndex = 1;
        foreach ($accounting as $accountingItem) {
            $accountingIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $accountingIndex, $accountingItem['platform'])
                ->setCellValue('B' . $accountingIndex, $accountingItem['user_account'])
                ->setCellValue('C' . $accountingIndex, $accountingItem['payment_id'])
                ->setCellValue('D' . $accountingIndex, $accountingItem['saleOrderCode'])
                ->setCellValue('E' . $accountingIndex, $accountingItem['paid_time'])
                ->setCellValue('F' . $accountingIndex, $accountingItem['shipping_time'])
                ->setCellValue('G' . $accountingIndex, $accountingItem['seller_sku'])
                ->setCellValue('H' . $accountingIndex, $accountingItem['warehouse_sku'])
                ->setCellValue('I' . $accountingIndex, $accountingItem['fulfillment'])
                ->setCellValue('J' . $accountingIndex, $accountingItem['qty'])
            ;
        }
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function getFinanceOperationExpensesExport($index, $list)
    {
        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('国内广告列表');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '支付月份')
            ->setCellValue('B1', '仓库SKU')
            ->setCellValue('C1', '申请人')
            ->setCellValue('D1', '币种')
            ->setCellValue('E1', '合计金额')
            ->setCellValue('F1', '备注')
            ->setCellValue('G1', '类型')
            ->setCellValue('H1', '平台')
            ->setCellValue('I1', '核算月份')
        ;

        $expensesIndex = 1;
        foreach ($list as $expensesItem) {
            $expensesIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $expensesIndex, $expensesItem['month'])
                ->setCellValue('B' . $expensesIndex, $expensesItem['sku'])
                ->setCellValue('C' . $expensesIndex, $expensesItem['applicant'])
                ->setCellValue('D' . $expensesIndex, $expensesItem['currency'])
                ->setCellValue('E' . $expensesIndex, $expensesItem['total'])
                ->setCellValue('F' . $expensesIndex, $expensesItem['content'])
                ->setCellValue('G' . $expensesIndex, $expensesItem['type'])
                ->setCellValue('H' . $expensesIndex, $expensesItem['export_platform'])
                ->setCellValue('I' . $expensesIndex, $expensesItem['calculate_month'])
            ;
        }
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function getFinanceOperationFactoryExport($index, $list)
    {
        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('工厂运费列表');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '支付月份')
            ->setCellValue('B1', '仓库SKU')
            ->setCellValue('C1', '合计金额')
            ->setCellValue('D1', '币种')
            ->setCellValue('E1', '备注')
            ->setCellValue('F1', '类型')
            ->setCellValue('G1', '工厂费用类型')
            ->setCellValue('H1', '发票号码')
            ->setCellValue('I1', '核算月份')
        ;

        $expensesIndex = 1;
        foreach ($list as $expensesItem) {
            $expensesIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $expensesIndex, $expensesItem['month'])
                ->setCellValue('B' . $expensesIndex, $expensesItem['sku'])
                ->setCellValue('C' . $expensesIndex, $expensesItem['total'])
                ->setCellValue('D' . $expensesIndex, $expensesItem['currency'])
                ->setCellValue('E' . $expensesIndex, $expensesItem['content'])
                ->setCellValue('F' . $expensesIndex, $expensesItem['type'])
                ->setCellValue('G' . $expensesIndex, $expensesItem['factory_type'])
                ->setCellValue('H' . $expensesIndex, $expensesItem['invoice_no'])
                ->setCellValue('I' . $expensesIndex, $expensesItem['calculate_month'])
            ;
        }
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function getFinanceOperationDeliveryExport($index, $list)
    {
        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('国内快递列表');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '支付月份')
            ->setCellValue('B1', '单号')
            ->setCellValue('C1', '日期')
            ->setCellValue('D1', '发送人')
            ->setCellValue('E1', '归属人')
            ->setCellValue('F1', '平台')
            ->setCellValue('G1', '店铺')
            ->setCellValue('H1', 'SKU')
            ->setCellValue('I1', '合计金额')
            ->setCellValue('J1', '核算月份')
        ;

        $expensesIndex = 1;
        foreach ($list as $expensesItem) {
            $expensesIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $expensesIndex, $expensesItem['month'])
                ->setCellValue('B' . $expensesIndex, $expensesItem['tracking_number'])
                ->setCellValue('C' . $expensesIndex, $expensesItem['delivery_date'])
                ->setCellValue('D' . $expensesIndex, $expensesItem['sender'])
                ->setCellValue('E' . $expensesIndex, $expensesItem['seller'])
                ->setCellValue('F' . $expensesIndex, $expensesItem['platform'])
                ->setCellValue('G' . $expensesIndex, $expensesItem['user_account'])
                ->setCellValue('H' . $expensesIndex, $expensesItem['sku'])
                ->setCellValue('I' . $expensesIndex, $expensesItem['total'])
                ->setCellValue('J' . $expensesIndex, $expensesItem['calculate_month'])
            ;
        }
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function getFinanceOperationFactoryClaimExport($index, $list)
    {
        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('工厂索赔列表');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '支付月份')
            ->setCellValue('B1', '仓库SKU')
            ->setCellValue('C1', '合计金额')
            ->setCellValue('D1', '币种')
            ->setCellValue('E1', '备注')
            ->setCellValue('F1', '类型')
            ->setCellValue('G1', '工厂费用类型')
            ->setCellValue('H1', '发票号码')
        ;

        $expensesIndex = 1;
        foreach ($list as $expensesItem) {
            $expensesIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $expensesIndex, $expensesItem['month'])
                ->setCellValue('B' . $expensesIndex, $expensesItem['sku'])
                ->setCellValue('C' . $expensesIndex, $expensesItem['total'])
                ->setCellValue('D' . $expensesIndex, $expensesItem['currency'])
                ->setCellValue('E' . $expensesIndex, $expensesItem['content'])
                ->setCellValue('F' . $expensesIndex, $expensesItem['type'])
                ->setCellValue('G' . $expensesIndex, $expensesItem['factory_type'])
                ->setCellValue('H' . $expensesIndex, $expensesItem['invoice_no'])
            ;
        }
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function getWildberriesOrderSql($index, $report)
    {
        $wildberries = $this->model->query(FinanceReportModel::getWildberriesOrderSql($report['id']));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('Wildberries账单明细');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '类型')
            ->setCellValue('B1', '平台')
            ->setCellValue('C1', '店铺')
            ->setCellValue('D1', '账单唯一编号')
            ->setCellValue('E1', 'FBS标签')
            ->setCellValue('F1', 'SKU')
            ->setCellValue('G1', '销售数量')
            ->setCellValue('H1', '退款数量')
            ->setCellValue('I1', '销售金额')
            ->setCellValue('J1', '退款金额')
            ->setCellValue('K1', '平台佣金')
            ->setCellValue('L1', '退款佣金')
            ->setCellValue('M1', '收单服务费')
            ->setCellValue('N1', '收单服务费退回')
            ->setCellValue('O1', '平台运费')
            ->setCellValue('P1', '到货时间')
        ;

        $wildberriesIndex = 1;
        foreach ($wildberries as $wildberriesItem) {
            $wildberriesIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $wildberriesIndex, $wildberriesItem['type'])
                ->setCellValue('B' . $wildberriesIndex, $wildberriesItem['platform'])
                ->setCellValue('C' . $wildberriesIndex, $wildberriesItem['user_account'])
                ->setCellValue('D' . $wildberriesIndex, $wildberriesItem['payment_id'])
                ->setCellValue('E' . $wildberriesIndex, $wildberriesItem['description'])
                ->setCellValue('F' . $wildberriesIndex, $wildberriesItem['sku'])
                ->setCellValue('G' . $wildberriesIndex, $wildberriesItem['sale_qty'])
                ->setCellValue('H' . $wildberriesIndex, $wildberriesItem['refund_qty'] * -1)
                ->setCellValue('I' . $wildberriesIndex, $wildberriesItem['sale_amount'])
                ->setCellValue('J' . $wildberriesIndex, $wildberriesItem['refund_amount'])
                ->setCellValue('K' . $wildberriesIndex, $wildberriesItem['sale_selling_fees'])
                ->setCellValue('L' . $wildberriesIndex, $wildberriesItem['refund_selling_fees'])
                ->setCellValue('M' . $wildberriesIndex, $wildberriesItem['sale_regulatory_fee'])
                ->setCellValue('N' . $wildberriesIndex, $wildberriesItem['refund_regulatory_fee'])
                ->setCellValue('O' . $wildberriesIndex, $wildberriesItem['shipping_fee'])
                ->setCellValue('P' . $wildberriesIndex, $wildberriesItem['delivery_time'])
            ;
        }
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function getWildberriesWarehouseSkuSql($index, $report)
    {
        $wildberries = $this->model->query(FinanceReportModel::getWildberriesWarehouseSkuSql($report['id']));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('Wildberries');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '平台')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', 'Payment')
            ->setCellValue('D1', 'FBS标签')
            ->setCellValue('E1', '产品名称')
            ->setCellValue('F1', 'SKU')
            ->setCellValue('G1', '销售数量')
            ->setCellValue('H1', '退款数量')
            ->setCellValue('I1', '实际销售数量')
            ->setCellValue('J1', '销售金额')
            ->setCellValue('K1', '退款金额')
            ->setCellValue('L1', '实际销售金额')
            ->setCellValue('M1', '平台佣金')
            ->setCellValue('N1', '退款佣金')
            ->setCellValue('O1', '收单服务费')
            ->setCellValue('P1', '收单服务费退回')
            ->setCellValue('Q1', '调整费用')
            ->setCellValue('R1', '平台运费')
            ->setCellValue('S1', '成本合计')
            ->setCellValue('T1', '国内运费')
            ->setCellValue('U1', '毛利')
            ->setCellValue('V1', '毛利率')
        ;

        $wildberriesIndex = 1;
        foreach ($wildberries as $wildberriesItem) {
            $wildberriesIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $wildberriesIndex, $wildberriesItem['platform'])
                ->setCellValue('B' . $wildberriesIndex, $wildberriesItem['user_account'])
                ->setCellValue('C' . $wildberriesIndex, $wildberriesItem['payment_id'])
                ->setCellValue('D' . $wildberriesIndex, $wildberriesItem['description'])
                ->setCellValue('E' . $wildberriesIndex, $wildberriesItem['product_name'])
                ->setCellValue('F' . $wildberriesIndex, $wildberriesItem['sku'])
                ->setCellValue('G' . $wildberriesIndex, $wildberriesItem['sale_qty'])
                ->setCellValue('H' . $wildberriesIndex, $wildberriesItem['refund_qty'] * -1)
                ->setCellValue('I' . $wildberriesIndex, $wildberriesItem['sale_qty'] - $wildberriesItem['refund_qty'])
                ->setCellValue('J' . $wildberriesIndex, $wildberriesItem['sale_amount'])
                ->setCellValue('K' . $wildberriesIndex, $wildberriesItem['refund_amount'])
                ->setCellValue('L' . $wildberriesIndex, $wildberriesItem['sale_amount'] + $wildberriesItem['refund_amount'])
                ->setCellValue('M' . $wildberriesIndex, $wildberriesItem['sale_selling_fees'])
                ->setCellValue('N' . $wildberriesIndex, $wildberriesItem['refund_selling_fees'])
                ->setCellValue('O' . $wildberriesIndex, $wildberriesItem['sale_regulatory_fee'])
                ->setCellValue('P' . $wildberriesIndex, $wildberriesItem['refund_regulatory_fee'])
                ->setCellValue('Q' . $wildberriesIndex, $wildberriesItem['adjustment'])
                ->setCellValue('R' . $wildberriesIndex, $wildberriesItem['shipping_fee'])
                ->setCellValue('S' . $wildberriesIndex, $wildberriesItem['cost'])
                ->setCellValue('T' . $wildberriesIndex, $wildberriesItem['domestic_shipping'])
                ->setCellValue('U' . $wildberriesIndex, $wildberriesItem['profit'])
                ->setCellValue('V' . $wildberriesIndex, $wildberriesItem['gross_profit_margin'])
            ;
        }
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function getWildberriesCostSql($index)
    {
        $wildberries = $this->model->query(FinanceReportModel::getWildberriesCostSql());

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('Wildberries成本表');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '订单号')
            ->setCellValue('B1', 'SKU平台编号')
            ->setCellValue('C1', '中文品名')
            ->setCellValue('D1', '单价')
            ->setCellValue('E1', '数量')
            ->setCellValue('F1', '总价')
            ->setCellValue('G1', '创建时间')
            ->setCellValue('H1', '支付月份')
            ->setCellValue('I1', '核算月份')
        ;

        $wildberriesIndex = 1;
        foreach ($wildberries as $wildberriesItem) {
            $wildberriesIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $wildberriesIndex, $wildberriesItem['order_no'])
                ->setCellValue('B' . $wildberriesIndex, $wildberriesItem['sku'])
                ->setCellValue('C' . $wildberriesIndex, $wildberriesItem['product_name'])
                ->setCellValue('D' . $wildberriesIndex, $wildberriesItem['unit_price'])
                ->setCellValue('E' . $wildberriesIndex, $wildberriesItem['quantity'])
                ->setCellValue('F' . $wildberriesIndex, $wildberriesItem['total'])
                ->setCellValue('G' . $wildberriesIndex, $wildberriesItem['created_date'])
                ->setCellValue('H' . $wildberriesIndex, $wildberriesItem['month'])
                ->setCellValue('I' . $wildberriesIndex, $wildberriesItem['calculate_month'])
            ;
        }
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function getWildberriesExpressDeliverySql($index, $month)
    {
        $wildberries = $this->model->query(FinanceReportModel::getWildberriesExpressDeliverySql($month));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('Wildberries国内快递');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '创建时间')
            ->setCellValue('B1', '运单号')
            ->setCellValue('C1', '重量')
            ->setCellValue('D1', '出发省')
            ->setCellValue('E1', '收件人')
            ->setCellValue('F1', '目的省')
            ->setCellValue('G1', '目的市')
            ->setCellValue('H1', '收件地址')
            ->setCellValue('I1', '寄件地址')
            ->setCellValue('J1', '寄件人')
            ->setCellValue('K1', '公司')
            ->setCellValue('L1', '组别')
            ->setCellValue('M1', '总金额')
            ->setCellValue('N1', '核算月份')
        ;

        $wildberriesIndex = 1;
        foreach ($wildberries as $wildberriesItem) {
            $wildberriesIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $wildberriesIndex, $wildberriesItem['created_date'])
                ->setCellValue('B' . $wildberriesIndex, $wildberriesItem['delivery_no'])
                ->setCellValue('C' . $wildberriesIndex, $wildberriesItem['weight'])
                ->setCellValue('D' . $wildberriesIndex, $wildberriesItem['province'])
                ->setCellValue('E' . $wildberriesIndex, $wildberriesItem['receiver'])
                ->setCellValue('F' . $wildberriesIndex, $wildberriesItem['target_province'])
                ->setCellValue('G' . $wildberriesIndex, $wildberriesItem['target_city'])
                ->setCellValue('H' . $wildberriesIndex, $wildberriesItem['receiver_addr'])
                ->setCellValue('I' . $wildberriesIndex, $wildberriesItem['sender_addr'])
                ->setCellValue('J' . $wildberriesIndex, $wildberriesItem['sender'])
                ->setCellValue('K' . $wildberriesIndex, $wildberriesItem['company_name'])
                ->setCellValue('L' . $wildberriesIndex, $wildberriesItem['group_name'])
                ->setCellValue('M' . $wildberriesIndex, $wildberriesItem['total'])
                ->setCellValue('N' . $wildberriesIndex, $wildberriesItem['month'])
            ;
        }
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function getWildberriesCostReturnSql($index, $month)
    {
        $wildberries = $this->model->query(FinanceReportModel::getWildberriesCostReturnSql($month));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('Wildberries仓库退货');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '商品名称')
            ->setCellValue('B1', '卖家货号')
            ->setCellValue('C1', 'SKU')
            ->setCellValue('D1', '容量，公升')
            ->setCellValue('E1', '数量')
            ->setCellValue('F1', '月份')
        ;

        $wildberriesIndex = 1;
        foreach ($wildberries as $wildberriesItem) {
            $wildberriesIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $wildberriesIndex, $wildberriesItem['product_name'])
                ->setCellValue('B' . $wildberriesIndex, $wildberriesItem['seller_sku'])
                ->setCellValue('C' . $wildberriesIndex, $wildberriesItem['sku'])
                ->setCellValue('D' . $wildberriesIndex, $wildberriesItem['size'])
                ->setCellValue('E' . $wildberriesIndex, $wildberriesItem['quantity'])
                ->setCellValue('F' . $wildberriesIndex, $wildberriesItem['month'])
            ;
        }
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function generateTiktokWarehouseSkuSql($index, $report, $month)
    {
        $tiktok = $this->model->query(FinanceReportModel::getTiktokWarehouseSkuSql($report['id']));

        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle('Tiktok');

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', '月份')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', 'SKU')
            ->setCellValue('D1', '中文品名')
            ->setCellValue('E1', '运营人员')
            ->setCellValue('F1', '采购人员')
            ->setCellValue('G1', '销售数量')
            ->setCellValue('H1', '退款数量')
            ->setCellValue('I1', '实际销量')
            ->setCellValue('J1', '销售总额')
            ->setCellValue('K1', '税费')
            ->setCellValue('L1', '退款总额')
            ->setCellValue('M1', '实际销售总额')
            ->setCellValue('N1', '平台佣金')
            ->setCellValue('O1', '平台佣金退款')
            ->setCellValue('P1', '平台运费')
            ->setCellValue('Q1', 'FBM尾程')
            ->setCellValue('R1', '海外仓仓储费')
            ->setCellValue('S1', '良仓调整')
            ->setCellValue('T1', '乐歌调整')
            ->setCellValue('U1', '平台广告费')
            ->setCellValue('V1', '国内广告费')
            ->setCellValue('W1', '工厂运费')
            ->setCellValue('X1', '国内快递费')
            ->setCellValue('Y1', '产品DDP总值')
            ->setCellValue('Z1', 'DDP占比')
            ->setCellValue('AA1', '毛利')
            ->setCellValue('AB1', '毛利率')
            ->setCellValue('AC1', '广告费占比')
            ->setCellValue('AD1', '仓储费占比')
            ->setCellValue('AE1', '尾程占比')
            ->setCellValue('AF1', '测评数量')
            ->setCellValue('AG1', '测评金额')
            ->setCellValue('AH1', '含测评毛利')
            ->setCellValue('AI1', '含测评毛利率')
        ;

        $tiktokIndex = 1;
        foreach ($tiktok as $tiktokItem) {
            $tiktokIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $tiktokIndex, $month)
                ->setCellValue('B' . $tiktokIndex, $tiktokItem['userAccount'])
                ->setCellValue('C' . $tiktokIndex, $tiktokItem['warehouse_sku'])
                ->setCellValue('D' . $tiktokIndex, $tiktokItem['product_name'])
                ->setCellValue('E' . $tiktokIndex, $tiktokItem['seller'])
                ->setCellValue('F' . $tiktokIndex, $tiktokItem['purchaser'])
                ->setCellValue('G' . $tiktokIndex, $tiktokItem['sale_qty'])
                ->setCellValue('H' . $tiktokIndex, $tiktokItem['refund_qty'])
                ->setCellValue('I' . $tiktokIndex, $tiktokItem['qty_amount'])
                ->setCellValue('J' . $tiktokIndex, $tiktokItem['sale_amount'])
                ->setCellValue('K' . $tiktokIndex, $tiktokItem['sale_tax'])
                ->setCellValue('L' . $tiktokIndex, $tiktokItem['refund_amount'])
                ->setCellValue('M' . $tiktokIndex, $tiktokItem['amount'])
                ->setCellValue('N' . $tiktokIndex, $tiktokItem['sale_selling_fees'])
                ->setCellValue('O' . $tiktokIndex, $tiktokItem['refund_selling_fees'])
                ->setCellValue('P' . $tiktokIndex, $tiktokItem['sale_shipping'])
                ->setCellValue('Q' . $tiktokIndex, $tiktokItem['calcuRes'])
                ->setCellValue('R' . $tiktokIndex, $tiktokItem['warehouse_rent'])
                ->setCellValue('S' . $tiktokIndex, $tiktokItem['lc_adjustment'])
                ->setCellValue('T' . $tiktokIndex, $tiktokItem['le_adjustment'])
                ->setCellValue('U' . $tiktokIndex, $tiktokItem['adCost'])
                ->setCellValue('V' . $tiktokIndex, $tiktokItem['operation_expenses'])
                ->setCellValue('W' . $tiktokIndex, $tiktokItem['operation_factory'])
                ->setCellValue('X' . $tiktokIndex, $tiktokItem['operation_delivery'])
                ->setCellValue('Y' . $tiktokIndex, $tiktokItem['ddp'])
                ->setCellValue('Z' . $tiktokIndex, $tiktokItem['ddp_percent'])
                ->setCellValue('AA' . $tiktokIndex, $tiktokItem['profit'])
                ->setCellValue('AB' . $tiktokIndex, $tiktokItem['gross_profit_margin'])
                ->setCellValue('AC' . $tiktokIndex, $tiktokItem['ad_percent'])
                ->setCellValue('AD' . $tiktokIndex, $tiktokItem['warehouse_percent'])
                ->setCellValue('AE' . $tiktokIndex, $tiktokItem['tail_percent'])
                ->setCellValue('AF' . $tiktokIndex, $tiktokItem['evaluation_qty'])
                ->setCellValue('AG' . $tiktokIndex, $tiktokItem['evaluation_amount'])
                ->setCellValue('AH' . $tiktokIndex, $tiktokItem['profit_include_evaluation'])
                ->setCellValue('AI' . $tiktokIndex, $tiktokItem['gross_profit_margin_include_evaluation'])
            ;
        }
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function generateListingRankExport($index, $data, $dateList)
    {
        if ($index) {
            // create new sheet
            $this->objPHPExcel->createSheet();
        }

        // Set name sheet
        $this->objPHPExcel->setActiveSheetIndex($index)->setTitle(date('Y-m-d') . '_listing_rank');

        $columnList = ['A', 'B', 'C', 'D', 'E', 'F'];
        $fieldNameList = ['asin', 'parent_asin', 'seller_sku', 'local_sku', 'local_name', 'principal_info'];
        $recentAsc = 'F';
        foreach ($dateList as $dateItem) {
            $asc = FinanceExcelInit::getNextExcelColumn($recentAsc);
            $columnList[] = $asc;
            $fieldNameList[] = 'rank_' . $dateItem['created_date'];
            $asc = FinanceExcelInit::getNextExcelColumn($asc);
            $columnList[] = $asc;
            $fieldNameList[] = 'star_' . $dateItem['created_date'];
            $recentAsc = $asc;
        }

        // Add some data
        $this->objPHPExcel->setActiveSheetIndex($index)
            ->setCellValue('A1', 'Asin')
            ->setCellValue('B1', '父级Asin')
            ->setCellValue('C1', '销售SKU')
            ->setCellValue('D1', '仓库SKU')
            ->setCellValue('E1', '品名')
            ->setCellValue('F1', '运营人员');

        foreach ($columnList as $key => $column) {
            if ($key >= 6) {
                $this->objPHPExcel->setActiveSheetIndex($index)->setCellValue($column . '1', $fieldNameList[$key]);
            }
        }

        $listingIndex = 1;
        foreach ($data as $dataItem) {
            $listingIndex ++;
            foreach ($columnList as $k => $listingItem) {
                if ($k == 5) {
                    $cellVal = json_decode($dataItem[$fieldNameList[$k]], true)[0]['principal_name'];
                } elseif ($k % 2 == 0 && $k >= 6) {
                    $cellVal = json_decode($dataItem[$fieldNameList[$k]], true)[0]['rank'];
                } else {
                    $cellVal = $dataItem[$fieldNameList[$k]];
                }
                $this->objPHPExcel->setActiveSheetIndex($index)->setCellValue($listingItem . $listingIndex, $cellVal);
            }
        }
    }

    public function excelSheetSet()
    {
        return $this->objPHPExcel;
    }

    static public function getNextExcelColumn($currentColumn): string
    {
        $nextColumn = '';
        $carry = true; // 进位标志

        // 从右到左遍历列名
        for ($i = strlen($currentColumn) - 1; $i >= 0; $i--) {
            $char = $currentColumn[$i];

            if ($carry) {
                if ($char == 'Z') {
                    $nextColumn = 'A' . $nextColumn;
                } else {
                    $nextColumn = chr(ord($char) + 1) . $nextColumn;
                    $carry = false;
                }
            } else {
                $nextColumn = $char . $nextColumn;
            }
        }

        // 如果循环结束仍有进位，则需要在最前面加一个 'A'
        if ($carry) {
            $nextColumn = 'A' . $nextColumn;
        }

        return $nextColumn;
    }

    static public function getCurrencyByUserAccount($userAccount): string
    {
        if (in_array($userAccount,
        [
            'TOLEAD_EU_IT',
            'TOLEAD_EU_DE',
            'TOLEAD_EU_FR',
            'TOLEAD_EU_ES'
        ])) {
            return 'EUR';
        } elseif ($userAccount == 'TOLEAD_EU_UK') {
            return 'GBP';
        } else {
            return 'USD';
        }
    }
}
