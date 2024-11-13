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
            ->setCellValue('P1', '海外仓尾程')
            ->setCellValue('Q1', 'DDP')
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
                ->setCellValue('Q' . $saleRefundIndex, $saleRefundItem['ddp'])
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
            ->setCellValue('J1', '销售总额')
            ->setCellValue('K1', '销售税')
            ->setCellValue('L1', '退款总额')
            ->setCellValue('M1', '平台佣金')
            ->setCellValue('N1', '平台佣金退款')
            ->setCellValue('O1', 'FBA尾程')
            ->setCellValue('P1', 'FBA尾程退款')
            ->setCellValue('Q1', '退款其他')
            ->setCellValue('R1', '调整费用')
            ->setCellValue('S1', '清算费用')
            ->setCellValue('T1', '促销费')
            ->setCellValue('U1', '退运费')
            ->setCellValue('V1', 'FBA仓储费')
            ->setCellValue('W1', '平台广告费')
            ->setCellValue('X1', '国内广告费')
            ->setCellValue('Y1', '工厂运费')
            ->setCellValue('Z1', '国内快递费')
            ->setCellValue('AA1', '产品DDP总值')
            ->setCellValue('AB1', 'DDP占比')
            ->setCellValue('AC1', '毛利')
            ->setCellValue('AD1', '毛利率')
            ->setCellValue('AE1', '广告费占比')
            ->setCellValue('AF1', '仓储费占比')
            ->setCellValue('AG1', '尾程占比')
            ->setCellValue('AH1', '测评数量')
            ->setCellValue('AI1', '测评金额')
            ->setCellValue('AJ1', '含测评毛利')
            ->setCellValue('AK1', '含测评毛利率')
        ;

        $fbaIndex = 1;
        foreach ($fbaWarehouseSku as $fbaItem) {
            $fbaIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $fbaIndex, $month)
                ->setCellValue('B' . $fbaIndex, 'USD')
                ->setCellValue('C' . $fbaIndex, $fbaItem['userAccount'])
                ->setCellValue('D' . $fbaIndex, $fbaItem['warehouse_sku'])
                ->setCellValue('E' . $fbaIndex, $fbaItem['product_name'])
                ->setCellValue('F' . $fbaIndex, $fbaItem['seller'])
                ->setCellValue('G' . $fbaIndex, $fbaItem['purchaser'])
                ->setCellValue('H' . $fbaIndex, $fbaItem['fba_sale_qty'])
                ->setCellValue('I' . $fbaIndex, $fbaItem['fba_refund_qty'])
                ->setCellValue('J' . $fbaIndex, $fbaItem['fba_sale_amount'])
                ->setCellValue('K' . $fbaIndex, $fbaItem['fba_sale_tax'])
                ->setCellValue('L' . $fbaIndex, $fbaItem['fba_refund_amount'])
                ->setCellValue('M' . $fbaIndex, $fbaItem['fba_sale_selling_fees'])
                ->setCellValue('N' . $fbaIndex, $fbaItem['fba_refund_selling_fees'])
                ->setCellValue('O' . $fbaIndex, $fbaItem['fba_fees'])
                ->setCellValue('P' . $fbaIndex, $fbaItem['fba_refund_fees'])
                ->setCellValue('Q' . $fbaIndex, $fbaItem['fba_refund_other'])
                ->setCellValue('R' . $fbaIndex, $fbaItem['adjustment'])
                ->setCellValue('S' . $fbaIndex, $fbaItem['liquidation'])
                ->setCellValue('T' . $fbaIndex, $fbaItem['promotion'])
                ->setCellValue('U' . $fbaIndex, $fbaItem['shipping_service'])
                ->setCellValue('V' . $fbaIndex, $fbaItem['fba_inventory'])
                ->setCellValue('W' . $fbaIndex, $fbaItem['fba_adCost'])
                ->setCellValue('X' . $fbaIndex, $fbaItem['operation_expenses'])
                ->setCellValue('Y' . $fbaIndex, $fbaItem['operation_factory'])
                ->setCellValue('Z' . $fbaIndex, $fbaItem['operation_delivery'])
                ->setCellValue('AA' . $fbaIndex, $fbaItem['fba_ddp'])
                ->setCellValue('AB' . $fbaIndex, $fbaItem['ddp_percent'])
                ->setCellValue('AC' . $fbaIndex, $fbaItem['profit'])
                ->setCellValue('AD' . $fbaIndex, $fbaItem['gross_profit_margin'])
                ->setCellValue('AE' . $fbaIndex, $fbaItem['ad_percent'])
                ->setCellValue('AF' . $fbaIndex, $fbaItem['inventory_percent'])
                ->setCellValue('AG' . $fbaIndex, $fbaItem['tail_percent'])
                ->setCellValue('AH' . $fbaIndex, $fbaItem['evaluation_qty'])
                ->setCellValue('AI' . $fbaIndex, $fbaItem['evaluation_amount'])
                ->setCellValue('AJ' . $fbaIndex, $fbaItem['profit_include_evaluation'])
                ->setCellValue('AK' . $fbaIndex, $fbaItem['gross_profit_margin_include_evaluation'])
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
            ->setCellValue('J1', '销售总额')
            ->setCellValue('K1', '销售税')
            ->setCellValue('L1', '退款总额')
            ->setCellValue('M1', '平台佣金')
            ->setCellValue('N1', '平台佣金退款')
            ->setCellValue('O1', '退款其他')
            ->setCellValue('P1', '调整费用')
            ->setCellValue('Q1', '清算费用')
            ->setCellValue('R1', '促销费')
            ->setCellValue('S1', '退运费')
            ->setCellValue('T1', 'FBM尾程')
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

        $fbmIndex = 1;
        foreach ($fbmWarehouseSku as $fbmItem) {
            $fbmIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $fbmIndex, $month)
                ->setCellValue('B' . $fbmIndex, 'USD')
                ->setCellValue('C' . $fbmIndex, $fbmItem['userAccount'])
                ->setCellValue('D' . $fbmIndex, $fbmItem['warehouse_sku'])
                ->setCellValue('E' . $fbmIndex, $fbmItem['product_name'])
                ->setCellValue('F' . $fbmIndex, $fbmItem['seller'])
                ->setCellValue('G' . $fbmIndex, $fbmItem['purchaser'])
                ->setCellValue('H' . $fbmIndex, $fbmItem['fbm_sale_qty'])
                ->setCellValue('I' . $fbmIndex, $fbmItem['fbm_refund_qty'])
                ->setCellValue('J' . $fbmIndex, $fbmItem['fbm_sale_amount'])
                ->setCellValue('K' . $fbmIndex, $fbmItem['fbm_sale_tax'])
                ->setCellValue('L' . $fbmIndex, $fbmItem['fbm_refund_amount'])
                ->setCellValue('M' . $fbmIndex, $fbmItem['fbm_sale_selling_fees'])
                ->setCellValue('N' . $fbmIndex, $fbmItem['fbm_refund_selling_fees'])
                ->setCellValue('O' . $fbmIndex, $fbmItem['fbm_refund_other'])
                ->setCellValue('P' . $fbmIndex, $fbmItem['adjustment'])
                ->setCellValue('Q' . $fbmIndex, $fbmItem['liquidation'])
                ->setCellValue('R' . $fbmIndex, $fbmItem['promotion'])
                ->setCellValue('S' . $fbmIndex, $fbmItem['shipping_service'])
                ->setCellValue('T' . $fbmIndex, $fbmItem['calcuRes'])
                ->setCellValue('U' . $fbmIndex, $fbmItem['warehouse_rent'])
                ->setCellValue('V' . $fbmIndex, $fbmItem['lc_adjustment'])
                ->setCellValue('W' . $fbmIndex, $fbmItem['le_adjustment'])
                ->setCellValue('X' . $fbmIndex, $fbmItem['fbm_adCost'])
                ->setCellValue('Y' . $fbmIndex, $fbmItem['operation_expenses'])
                ->setCellValue('Z' . $fbmIndex, $fbmItem['operation_factory'])
                ->setCellValue('AA' . $fbmIndex, $fbmItem['operation_delivery'])
                ->setCellValue('AB' . $fbmIndex, $fbmItem['fbm_ddp'])
                ->setCellValue('AC' . $fbmIndex, $fbmItem['ddp_percent'])
                ->setCellValue('AD' . $fbmIndex, $fbmItem['profit'])
                ->setCellValue('AE' . $fbmIndex, $fbmItem['gross_profit_margin'])
                ->setCellValue('AF' . $fbmIndex, $fbmItem['ad_percent'])
                ->setCellValue('AG' . $fbmIndex, $fbmItem['inventory_percent'])
                ->setCellValue('AH' . $fbmIndex, $fbmItem['tail_percent'])
                ->setCellValue('AI' . $fbmIndex, $fbmItem['evaluation_qty'])
                ->setCellValue('AJ' . $fbmIndex, $fbmItem['evaluation_amount'])
                ->setCellValue('AK' . $fbmIndex, $fbmItem['profit_include_evaluation'])
                ->setCellValue('AL' . $fbmIndex, $fbmItem['gross_profit_margin_include_evaluation'])
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
            ->setCellValue('I1', '销售总额')
            ->setCellValue('J1', '退款总额')
            ->setCellValue('K1', '平台佣金')
            ->setCellValue('L1', '平台佣金退款')
            ->setCellValue('M1', '调整费用')
            ->setCellValue('N1', 'FBM尾程')
            ->setCellValue('O1', 'WFS尾程')
            ->setCellValue('P1', 'WFS退运费')
            ->setCellValue('Q1', 'WFS仓储费')
            ->setCellValue('R1', 'WFS调整费用')
            ->setCellValue('S1', '海外仓仓储费')
            ->setCellValue('T1', '良仓调整')
            ->setCellValue('U1', '乐歌调整')
            ->setCellValue('V1', '平台广告费')
            ->setCellValue('W1', '国内广告费')
            ->setCellValue('X1', '工厂运费')
            ->setCellValue('Y1', '国内快递费')
            ->setCellValue('Z1', '产品DDP总值')
            ->setCellValue('AA1', 'DDP占比')
            ->setCellValue('AB1', '毛利')
            ->setCellValue('AC1', '毛利率')
            ->setCellValue('AD1', '广告费占比')
            ->setCellValue('AE1', '仓储费占比')
            ->setCellValue('AF1', '尾程占比')
            ->setCellValue('AG1', '测评数量')
            ->setCellValue('AH1', '测评金额')
            ->setCellValue('AI1', '含测评毛利')
            ->setCellValue('AJ1', '含测评毛利率')
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
                ->setCellValue('I' . $walmartIndex, $walmartItem['sale_amount'])
                ->setCellValue('J' . $walmartIndex, $walmartItem['refund_amount'])
                ->setCellValue('K' . $walmartIndex, $walmartItem['sale_selling_fees'])
                ->setCellValue('L' . $walmartIndex, $walmartItem['refund_selling_fees'])
                ->setCellValue('M' . $walmartIndex, $walmartItem['adjustment'])
                ->setCellValue('N' . $walmartIndex, $walmartItem['calcuRes'])
                ->setCellValue('O' . $walmartIndex, $walmartItem['wfs_fulfillment'])
                ->setCellValue('P' . $walmartIndex, $walmartItem['wfs_return_shipping'])
                ->setCellValue('Q' . $walmartIndex, $walmartItem['wfs_warehouse'])
                ->setCellValue('R' . $walmartIndex, $walmartItem['wfs_adjustment'])
                ->setCellValue('S' . $walmartIndex, $walmartItem['warehouse_rent'])
                ->setCellValue('T' . $walmartIndex, $walmartItem['lc_adjustment'])
                ->setCellValue('U' . $walmartIndex, $walmartItem['le_adjustment'])
                ->setCellValue('V' . $walmartIndex, $walmartItem['adCost'])
                ->setCellValue('W' . $walmartIndex, $walmartItem['operation_expenses'])
                ->setCellValue('X' . $walmartIndex, $walmartItem['operation_factory'])
                ->setCellValue('Y' . $walmartIndex, $walmartItem['operation_delivery'])
                ->setCellValue('Z' . $walmartIndex, $walmartItem['ddp'])
                ->setCellValue('AA' . $walmartIndex, $walmartItem['ddp_percent'])
                ->setCellValue('AB' . $walmartIndex, $walmartItem['profit'])
                ->setCellValue('AC' . $walmartIndex, $walmartItem['gross_profit_margin'])
                ->setCellValue('AD' . $walmartIndex, $walmartItem['ad_percent'])
                ->setCellValue('AE' . $walmartIndex, $walmartItem['warehouse_percent'])
                ->setCellValue('AF' . $walmartIndex, $walmartItem['tail_percent'])
                ->setCellValue('AG' . $walmartIndex, $walmartItem['evaluation_qty'])
                ->setCellValue('AH' . $walmartIndex, $walmartItem['evaluation_amount'])
                ->setCellValue('AI' . $walmartIndex, $walmartItem['profit_include_evaluation'])
                ->setCellValue('AJ' . $walmartIndex, $walmartItem['gross_profit_margin_include_evaluation'])
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
            ->setCellValue('I1', '销售总额')
            ->setCellValue('J1', '退款总额')
            ->setCellValue('K1', '平台佣金')
            ->setCellValue('L1', '平台佣金退款')
            ->setCellValue('M1', '调整费用')
            ->setCellValue('N1', 'FBM尾程')
            ->setCellValue('O1', '海外仓仓储费')
            ->setCellValue('P1', '良仓调整')
            ->setCellValue('Q1', '乐歌调整')
            ->setCellValue('R1', '平台广告费')
            ->setCellValue('S1', '国内广告费')
            ->setCellValue('T1', '工厂运费')
            ->setCellValue('U1', '国内快递费')
            ->setCellValue('V1', '产品DDP总值')
            ->setCellValue('W1', 'DDP占比')
            ->setCellValue('X1', '毛利')
            ->setCellValue('Y1', '毛利率')
            ->setCellValue('Z1', '广告费占比')
            ->setCellValue('AA1', '仓储费占比')
            ->setCellValue('AB1', '尾程占比')
            ->setCellValue('AC1', '测评数量')
            ->setCellValue('AD1', '测评金额')
            ->setCellValue('AE1', '含测评毛利')
            ->setCellValue('AF1', '含测评毛利率')
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
                ->setCellValue('I' . $wayfairIndex, $wayfairItem['sale_amount'])
                ->setCellValue('J' . $wayfairIndex, $wayfairItem['refund_amount'])
                ->setCellValue('K' . $wayfairIndex, $wayfairItem['sale_selling_fees'])
                ->setCellValue('L' . $wayfairIndex, $wayfairItem['refund_selling_fees'])
                ->setCellValue('M' . $wayfairIndex, $wayfairItem['adjustment'])
                ->setCellValue('N' . $wayfairIndex, $wayfairItem['calcuRes'])
                ->setCellValue('O' . $wayfairIndex, $wayfairItem['warehouse_rent'])
                ->setCellValue('P' . $wayfairIndex, $wayfairItem['lc_adjustment'])
                ->setCellValue('Q' . $wayfairIndex, $wayfairItem['le_adjustment'])
                ->setCellValue('R' . $wayfairIndex, $wayfairItem['adCost'])
                ->setCellValue('S' . $wayfairIndex, $wayfairItem['operation_expenses'])
                ->setCellValue('T' . $wayfairIndex, $wayfairItem['operation_factory'])
                ->setCellValue('U' . $wayfairIndex, $wayfairItem['operation_delivery'])
                ->setCellValue('V' . $wayfairIndex, $wayfairItem['ddp'])
                ->setCellValue('W' . $wayfairIndex, $wayfairItem['ddp_percent'])
                ->setCellValue('X' . $wayfairIndex, $wayfairItem['profit'])
                ->setCellValue('Y' . $wayfairIndex, $wayfairItem['gross_profit_margin'])
                ->setCellValue('Z' . $wayfairIndex, $wayfairItem['ad_percent'])
                ->setCellValue('AA' . $wayfairIndex, $wayfairItem['warehouse_percent'])
                ->setCellValue('AB' . $wayfairIndex, $wayfairItem['tail_percent'])
                ->setCellValue('AC' . $wayfairIndex, $wayfairItem['evaluation_qty'])
                ->setCellValue('AD' . $wayfairIndex, $wayfairItem['evaluation_amount'])
                ->setCellValue('AE' . $wayfairIndex, $wayfairItem['profit_include_evaluation'])
                ->setCellValue('AF' . $wayfairIndex, $wayfairItem['gross_profit_margin_include_evaluation'])
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
            ->setCellValue('I1', '销售总额')
            ->setCellValue('J1', '退款总额')
            ->setCellValue('K1', '平台佣金')
            ->setCellValue('L1', '平台佣金退款')
            ->setCellValue('M1', '调整费用')
            ->setCellValue('N1', 'FBM尾程')
            ->setCellValue('O1', '海外仓仓储费')
            ->setCellValue('P1', '良仓调整')
            ->setCellValue('Q1', '乐歌调整')
            ->setCellValue('R1', '平台广告费')
            ->setCellValue('S1', '国内广告费')
            ->setCellValue('T1', '工厂运费')
            ->setCellValue('U1', '国内快递费')
            ->setCellValue('V1', '产品DDP总值')
            ->setCellValue('W1', 'DDP占比')
            ->setCellValue('X1', '毛利')
            ->setCellValue('Y1', '毛利率')
            ->setCellValue('Z1', '广告费占比')
            ->setCellValue('AA1', '仓储费占比')
            ->setCellValue('AB1', '尾程占比')
            ->setCellValue('AC1', '测评数量')
            ->setCellValue('AD1', '测评金额')
            ->setCellValue('AE1', '含测评毛利')
            ->setCellValue('AF1', '含测评毛利率')
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
                ->setCellValue('I' . $temuIndex, $temuItem['sale_amount'])
                ->setCellValue('J' . $temuIndex, $temuItem['refund_amount'])
                ->setCellValue('K' . $temuIndex, $temuItem['sale_selling_fees'])
                ->setCellValue('L' . $temuIndex, $temuItem['refund_selling_fees'])
                ->setCellValue('M' . $temuIndex, $temuItem['adjustment'])
                ->setCellValue('N' . $temuIndex, $temuItem['calcuRes'])
                ->setCellValue('O' . $temuIndex, $temuItem['warehouse_rent'])
                ->setCellValue('P' . $temuIndex, $temuItem['lc_adjustment'])
                ->setCellValue('Q' . $temuIndex, $temuItem['le_adjustment'])
                ->setCellValue('R' . $temuIndex, $temuItem['adCost'])
                ->setCellValue('S' . $temuIndex, $temuItem['operation_expenses'])
                ->setCellValue('T' . $temuIndex, $temuItem['operation_factory'])
                ->setCellValue('U' . $temuIndex, $temuItem['operation_delivery'])
                ->setCellValue('V' . $temuIndex, $temuItem['ddp'])
                ->setCellValue('W' . $temuIndex, $temuItem['ddp_percent'])
                ->setCellValue('X' . $temuIndex, $temuItem['profit'])
                ->setCellValue('Y' . $temuIndex, $temuItem['gross_profit_margin'])
                ->setCellValue('Z' . $temuIndex, $temuItem['ad_percent'])
                ->setCellValue('AA' . $temuIndex, $temuItem['warehouse_percent'])
                ->setCellValue('AB' . $temuIndex, $temuItem['tail_percent'])
                ->setCellValue('AC' . $temuIndex, $temuItem['evaluation_qty'])
                ->setCellValue('AD' . $temuIndex, $temuItem['evaluation_amount'])
                ->setCellValue('AE' . $temuIndex, $temuItem['profit_include_evaluation'])
                ->setCellValue('AF' . $temuIndex, $temuItem['gross_profit_margin_include_evaluation'])
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
            ->setCellValue('I1', '销售总额')
            ->setCellValue('J1', '税')
            ->setCellValue('K1', '退款总额')
            ->setCellValue('L1', '平台佣金')
            ->setCellValue('M1', '平台佣金退款')
            ->setCellValue('N1', '调整费用')
            ->setCellValue('O1', 'FBM尾程')
            ->setCellValue('P1', '海外仓仓储费')
            ->setCellValue('Q1', '良仓调整')
            ->setCellValue('R1', '乐歌调整')
            ->setCellValue('S1', '平台广告费')
            ->setCellValue('T1', '国内广告费')
            ->setCellValue('U1', '工厂运费')
            ->setCellValue('V1', '国内快递费')
            ->setCellValue('W1', '产品DDP总值')
            ->setCellValue('X1', 'DDP占比')
            ->setCellValue('Y1', '毛利')
            ->setCellValue('Z1', '毛利率')
            ->setCellValue('AA1', '广告费占比')
            ->setCellValue('AB1', '仓储费占比')
            ->setCellValue('AC1', '尾程占比')
            ->setCellValue('AD1', '测评数量')
            ->setCellValue('AE1', '测评金额')
            ->setCellValue('AF1', '含测评毛利')
            ->setCellValue('AG1', '含测评毛利率')
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
                ->setCellValue('I' . $ebayIndex, $ebayItem['sale_amount'])
                ->setCellValue('J' . $ebayIndex, $ebayItem['sale_tax'])
                ->setCellValue('K' . $ebayIndex, $ebayItem['refund_amount'])
                ->setCellValue('L' . $ebayIndex, $ebayItem['sale_selling_fees'])
                ->setCellValue('M' . $ebayIndex, $ebayItem['refund_selling_fees'])
                ->setCellValue('N' . $ebayIndex, $ebayItem['adjustment'])
                ->setCellValue('O' . $ebayIndex, $ebayItem['calcuRes'])
                ->setCellValue('P' . $ebayIndex, $ebayItem['warehouse_rent'])
                ->setCellValue('Q' . $ebayIndex, $ebayItem['lc_adjustment'])
                ->setCellValue('R' . $ebayIndex, $ebayItem['le_adjustment'])
                ->setCellValue('S' . $ebayIndex, $ebayItem['adCost'])
                ->setCellValue('T' . $ebayIndex, $ebayItem['operation_expenses'])
                ->setCellValue('U' . $ebayIndex, $ebayItem['operation_factory'])
                ->setCellValue('V' . $ebayIndex, $ebayItem['operation_delivery'])
                ->setCellValue('W' . $ebayIndex, $ebayItem['ddp'])
                ->setCellValue('X' . $ebayIndex, $ebayItem['ddp_percent'])
                ->setCellValue('Y' . $ebayIndex, $ebayItem['profit'])
                ->setCellValue('Z' . $ebayIndex, $ebayItem['gross_profit_margin'])
                ->setCellValue('AA' . $ebayIndex, $ebayItem['ad_percent'])
                ->setCellValue('AB' . $ebayIndex, $ebayItem['warehouse_percent'])
                ->setCellValue('AC' . $ebayIndex, $ebayItem['tail_percent'])
                ->setCellValue('AD' . $ebayIndex, $ebayItem['evaluation_qty'])
                ->setCellValue('AE' . $ebayIndex, $ebayItem['evaluation_amount'])
                ->setCellValue('AF' . $ebayIndex, $ebayItem['profit_include_evaluation'])
                ->setCellValue('AG' . $ebayIndex, $ebayItem['gross_profit_margin_include_evaluation'])
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
            ->setCellValue('I1', '销售总额')
            ->setCellValue('J1', '退款总额')
            ->setCellValue('K1', '平台佣金')
            ->setCellValue('L1', '平台佣金退款')
            ->setCellValue('M1', '调整费用')
            ->setCellValue('N1', 'FBM尾程')
            ->setCellValue('O1', '海外仓仓储费')
            ->setCellValue('P1', '良仓调整')
            ->setCellValue('Q1', '乐歌调整')
            ->setCellValue('R1', '平台广告费')
            ->setCellValue('S1', '国内广告费')
            ->setCellValue('T1', '工厂运费')
            ->setCellValue('U1', '国内快递费')
            ->setCellValue('V1', '产品DDP总值')
            ->setCellValue('W1', 'DDP占比')
            ->setCellValue('X1', '毛利')
            ->setCellValue('Y1', '毛利率')
            ->setCellValue('Z1', '广告费占比')
            ->setCellValue('AA1', '仓储费占比')
            ->setCellValue('AB1', '尾程占比')
            ->setCellValue('AC1', '测评数量')
            ->setCellValue('AD1', '测评金额')
            ->setCellValue('AE1', '含测评毛利')
            ->setCellValue('AF1', '含测评毛利率')
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
                ->setCellValue('I' . $sheinIndex, $sheinItem['sale_amount'])
                ->setCellValue('J' . $sheinIndex, $sheinItem['refund_amount'])
                ->setCellValue('K' . $sheinIndex, $sheinItem['sale_selling_fees'])
                ->setCellValue('L' . $sheinIndex, $sheinItem['refund_selling_fees'])
                ->setCellValue('M' . $sheinIndex, $sheinItem['adjustment'])
                ->setCellValue('N' . $sheinIndex, $sheinItem['calcuRes'])
                ->setCellValue('O' . $sheinIndex, $sheinItem['warehouse_rent'])
                ->setCellValue('P' . $sheinIndex, $sheinItem['lc_adjustment'])
                ->setCellValue('Q' . $sheinIndex, $sheinItem['le_adjustment'])
                ->setCellValue('R' . $sheinIndex, $sheinItem['adCost'])
                ->setCellValue('S' . $sheinIndex, $sheinItem['operation_expenses'])
                ->setCellValue('T' . $sheinIndex, $sheinItem['operation_factory'])
                ->setCellValue('U' . $sheinIndex, $sheinItem['operation_delivery'])
                ->setCellValue('V' . $sheinIndex, $sheinItem['ddp'])
                ->setCellValue('W' . $sheinIndex, $sheinItem['ddp_percent'])
                ->setCellValue('X' . $sheinIndex, $sheinItem['profit'])
                ->setCellValue('Y' . $sheinIndex, $sheinItem['gross_profit_margin'])
                ->setCellValue('Z' . $sheinIndex, $sheinItem['ad_percent'])
                ->setCellValue('AA' . $sheinIndex, $sheinItem['warehouse_percent'])
                ->setCellValue('AB' . $sheinIndex, $sheinItem['tail_percent'])
                ->setCellValue('AC' . $sheinIndex, $sheinItem['evaluation_qty'])
                ->setCellValue('AD' . $sheinIndex, $sheinItem['evaluation_amount'])
                ->setCellValue('AE' . $sheinIndex, $sheinItem['profit_include_evaluation'])
                ->setCellValue('AF' . $sheinIndex, $sheinItem['gross_profit_margin_include_evaluation'])
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
            ->setCellValue('C1', '仓库SKU')
            ->setCellValue('D1', '数量')
            ->setCellValue('E1', '订单状态')
            ->setCellValue('F1', '尾程')
            ->setCellValue('G1', '运营人员')
            ->setCellValue('H1', '采购人员')
        ;

        $orderResendIndex = 1;
        foreach ($orderResend as $orderResendItem) {
            $orderResendIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $orderResendIndex, $orderResendItem['platform'])
                ->setCellValue('B' . $orderResendIndex, $orderResendItem['user_account'])
                ->setCellValue('C' . $orderResendIndex, $orderResendItem['warehouse_sku'])
                ->setCellValue('D' . $orderResendIndex, $orderResendItem['qty'])
                ->setCellValue('E' . $orderResendIndex, $orderResendItem['order_status'])
                ->setCellValue('F' . $orderResendIndex, $orderResendItem['tail'])
                ->setCellValue('G' . $orderResendIndex, $orderResendItem['seller'])
                ->setCellValue('H' . $orderResendIndex, $orderResendItem['purchaser'])
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
            ->setCellValue('G1', '核算月份')
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
                ->setCellValue('G' . $expensesIndex, $expensesItem['calculate_month'])
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
            ;
        }
    }

    public function excelSheetSet()
    {
        return $this->objPHPExcel;
    }
}
