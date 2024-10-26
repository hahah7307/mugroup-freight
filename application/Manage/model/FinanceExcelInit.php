<?php

namespace app\Manage\model;

use PHPExcel;
use think\db\exception\BindParamException;
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
            ->setCellValue('A1', '平台')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', '仓库Sku')
            ->setCellValue('D1', '销售量')
            ->setCellValue('E1', '退款量')
            ->setCellValue('F1', '销售额')
            ->setCellValue('G1', '税')
            ->setCellValue('H1', '退款额')
            ->setCellValue('I1', '平台佣金')
            ->setCellValue('J1', '平台佣金退款')
            ->setCellValue('K1', '亚马逊尾程')
            ->setCellValue('L1', '亚马逊尾程退款')
            ->setCellValue('M1', '退款其他')
            ->setCellValue('N1', 'DDP')
            ->setCellValue('O1', '广告费')
            ->setCellValue('P1', '仓储费')
            ->setCellValue('Q1', '调整费用')
            ->setCellValue('R1', '清算费用')
            ->setCellValue('S1', '促销费')
            ->setCellValue('T1', '退运费')
            ->setCellValue('U1', '国内广告费')
            ->setCellValue('V1', '工厂运费')
            ->setCellValue('W1', '国内快递费')
            ->setCellValue('X1', '广告费占比')
            ->setCellValue('Y1', '仓储费占比')
            ->setCellValue('Z1', '尾程占比')
            ->setCellValue('AA1', 'DDP占比')
            ->setCellValue('AB1', '毛利')
            ->setCellValue('AC1', '毛利率')
            ->setCellValue('AD1', '测评数量')
            ->setCellValue('AE1', '测评金额')
            ->setCellValue('AF1', '含测评毛利')
            ->setCellValue('AG1', '含测评毛利率')
            ->setCellValue('AH1', '品名')
            ->setCellValue('AI1', '运营人员')
            ->setCellValue('AJ1', '采购人员')
        ;

        $fbaIndex = 1;
        foreach ($fbaWarehouseSku as $fbaItem) {
            $fbaIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $fbaIndex, $fbaItem['platform'])
                ->setCellValue('B' . $fbaIndex, $fbaItem['userAccount'])
                ->setCellValue('C' . $fbaIndex, $fbaItem['warehouse_sku'])
                ->setCellValue('D' . $fbaIndex, $fbaItem['fba_sale_qty'])
                ->setCellValue('E' . $fbaIndex, $fbaItem['fba_refund_qty'])
                ->setCellValue('F' . $fbaIndex, $fbaItem['fba_sale_amount'])
                ->setCellValue('G' . $fbaIndex, $fbaItem['fba_sale_tax'])
                ->setCellValue('H' . $fbaIndex, $fbaItem['fba_refund_amount'])
                ->setCellValue('I' . $fbaIndex, $fbaItem['fba_sale_selling_fees'])
                ->setCellValue('J' . $fbaIndex, $fbaItem['fba_refund_selling_fees'])
                ->setCellValue('K' . $fbaIndex, $fbaItem['fba_fees'])
                ->setCellValue('L' . $fbaIndex, $fbaItem['fba_refund_fees'])
                ->setCellValue('M' . $fbaIndex, $fbaItem['fba_refund_other'])
                ->setCellValue('N' . $fbaIndex, $fbaItem['fba_ddp'])
                ->setCellValue('O' . $fbaIndex, $fbaItem['fba_adCost'])
                ->setCellValue('P' . $fbaIndex, $fbaItem['fba_inventory'])
                ->setCellValue('Q' . $fbaIndex, $fbaItem['adjustment'])
                ->setCellValue('R' . $fbaIndex, $fbaItem['liquidation'])
                ->setCellValue('S' . $fbaIndex, $fbaItem['promotion'])
                ->setCellValue('T' . $fbaIndex, $fbaItem['shipping_service'])
                ->setCellValue('U' . $fbaIndex, $fbaItem['operation_expenses'])
                ->setCellValue('V' . $fbaIndex, $fbaItem['operation_factory'])
                ->setCellValue('W' . $fbaIndex, $fbaItem['operation_delivery'])
                ->setCellValue('X' . $fbaIndex, $fbaItem['ad_percent'])
                ->setCellValue('Y' . $fbaIndex, $fbaItem['inventory_percent'])
                ->setCellValue('Z' . $fbaIndex, $fbaItem['tail_percent'])
                ->setCellValue('AA' . $fbaIndex, $fbaItem['ddp_percent'])
                ->setCellValue('AB' . $fbaIndex, $fbaItem['profit'])
                ->setCellValue('AC' . $fbaIndex, $fbaItem['gross_profit_margin'])
                ->setCellValue('AD' . $fbaIndex, $fbaItem['evaluation_qty'])
                ->setCellValue('AE' . $fbaIndex, $fbaItem['evaluation_amount'])
                ->setCellValue('AF' . $fbaIndex, $fbaItem['profit_include_evaluation'])
                ->setCellValue('AG' . $fbaIndex, $fbaItem['gross_profit_margin_include_evaluation'])
                ->setCellValue('AH' . $fbaIndex, $fbaItem['product_name'])
                ->setCellValue('AI' . $fbaIndex, $fbaItem['seller'])
                ->setCellValue('AJ' . $fbaIndex, $fbaItem['purchaser'])
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
            ->setCellValue('A1', '平台')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', '仓库Sku')
            ->setCellValue('D1', '销售量')
            ->setCellValue('E1', '退款量')
            ->setCellValue('F1', '销售额')
            ->setCellValue('G1', '税')
            ->setCellValue('H1', '退款额')
            ->setCellValue('I1', '平台佣金')
            ->setCellValue('J1', '平台佣金退款')
            ->setCellValue('K1', '退款其他')
            ->setCellValue('L1', 'FBM尾程')
            ->setCellValue('M1', 'DDP')
            ->setCellValue('N1', '广告费')
            ->setCellValue('O1', '仓储费')
            ->setCellValue('P1', '调整费用')
            ->setCellValue('Q1', '清算费用')
            ->setCellValue('R1', '促销费')
            ->setCellValue('S1', '退运费')
            ->setCellValue('T1', '良仓调整费用')
            ->setCellValue('U1', '乐歌调整费用')
            ->setCellValue('V1', '国内广告费')
            ->setCellValue('W1', '工厂运费')
            ->setCellValue('X1', '国内快递费')
            ->setCellValue('Y1', '广告费占比')
            ->setCellValue('Z1', '仓储费占比')
            ->setCellValue('AA1', '尾程占比')
            ->setCellValue('AB1', 'DDP占比')
            ->setCellValue('AC1', '毛利')
            ->setCellValue('AD1', '毛利率')
            ->setCellValue('AE1', '测评数量')
            ->setCellValue('AF1', '测评金额')
            ->setCellValue('AG1', '含测评毛利')
            ->setCellValue('AH1', '含测评毛利率')
            ->setCellValue('AI1', '品名')
            ->setCellValue('AJ1', '运营人员')
            ->setCellValue('AK1', '采购人员')
        ;

        $fbmIndex = 1;
        foreach ($fbmWarehouseSku as $fbmItem) {
            $fbmIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $fbmIndex, $fbmItem['platform'])
                ->setCellValue('B' . $fbmIndex, $fbmItem['userAccount'])
                ->setCellValue('C' . $fbmIndex, $fbmItem['warehouse_sku'])
                ->setCellValue('D' . $fbmIndex, $fbmItem['fbm_sale_qty'])
                ->setCellValue('E' . $fbmIndex, $fbmItem['fbm_refund_qty'])
                ->setCellValue('F' . $fbmIndex, $fbmItem['fbm_sale_amount'])
                ->setCellValue('G' . $fbmIndex, $fbmItem['fbm_sale_tax'])
                ->setCellValue('H' . $fbmIndex, $fbmItem['fbm_refund_amount'])
                ->setCellValue('I' . $fbmIndex, $fbmItem['fbm_sale_selling_fees'])
                ->setCellValue('J' . $fbmIndex, $fbmItem['fbm_refund_selling_fees'])
                ->setCellValue('K' . $fbmIndex, $fbmItem['fbm_refund_other'])
                ->setCellValue('L' . $fbmIndex, $fbmItem['calcuRes'])
                ->setCellValue('M' . $fbmIndex, $fbmItem['fbm_ddp'])
                ->setCellValue('N' . $fbmIndex, $fbmItem['fbm_adCost'])
                ->setCellValue('O' . $fbmIndex, $fbmItem['warehouse_rent'])
                ->setCellValue('P' . $fbmIndex, $fbmItem['adjustment'])
                ->setCellValue('Q' . $fbmIndex, $fbmItem['liquidation'])
                ->setCellValue('R' . $fbmIndex, $fbmItem['promotion'])
                ->setCellValue('S' . $fbmIndex, $fbmItem['shipping_service'])
                ->setCellValue('T' . $fbmIndex, $fbmItem['lc_adjustment'])
                ->setCellValue('U' . $fbmIndex, $fbmItem['le_adjustment'])
                ->setCellValue('V' . $fbmIndex, $fbmItem['operation_expenses'])
                ->setCellValue('W' . $fbmIndex, $fbmItem['operation_factory'])
                ->setCellValue('X' . $fbmIndex, $fbmItem['operation_delivery'])
                ->setCellValue('Y' . $fbmIndex, $fbmItem['ad_percent'])
                ->setCellValue('Z' . $fbmIndex, $fbmItem['inventory_percent'])
                ->setCellValue('AA' . $fbmIndex, $fbmItem['tail_percent'])
                ->setCellValue('AB' . $fbmIndex, $fbmItem['ddp_percent'])
                ->setCellValue('AC' . $fbmIndex, $fbmItem['profit'])
                ->setCellValue('AD' . $fbmIndex, $fbmItem['gross_profit_margin'])
                ->setCellValue('AE' . $fbmIndex, $fbmItem['evaluation_qty'])
                ->setCellValue('AF' . $fbmIndex, $fbmItem['evaluation_amount'])
                ->setCellValue('AG' . $fbmIndex, $fbmItem['profit_include_evaluation'])
                ->setCellValue('AH' . $fbmIndex, $fbmItem['gross_profit_margin_include_evaluation'])
                ->setCellValue('AI' . $fbmIndex, $fbmItem['product_name'])
                ->setCellValue('AJ' . $fbmIndex, $fbmItem['seller'])
                ->setCellValue('AK' . $fbmIndex, $fbmItem['purchaser'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateWalmartSheet($index, $report_id)
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
            ->setCellValue('A1', '平台')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', '仓库Sku')
            ->setCellValue('D1', '销售量')
            ->setCellValue('E1', '退款量')
            ->setCellValue('F1', '销售额')
            ->setCellValue('G1', '退款额')
            ->setCellValue('H1', '平台佣金')
            ->setCellValue('I1', '平台佣金退款')
            ->setCellValue('J1', 'FBM尾程')
            ->setCellValue('K1', 'WFS尾程')
            ->setCellValue('L1', 'DDP')
            ->setCellValue('M1', '广告费')
            ->setCellValue('N1', '仓储费')
            ->setCellValue('O1', 'WFS仓储费')
            ->setCellValue('P1', 'WFS退运费')
            ->setCellValue('Q1', '调整费用')
            ->setCellValue('R1', '良仓调整费用')
            ->setCellValue('S1', '乐歌调整费用')
            ->setCellValue('T1', 'WFS调整费用')
            ->setCellValue('U1', '国内广告费')
            ->setCellValue('V1', '工厂运费')
            ->setCellValue('W1', '国内快递费')
            ->setCellValue('X1', '广告费占比')
            ->setCellValue('Y1', '仓储费占比')
            ->setCellValue('Z1', '尾程占比')
            ->setCellValue('AA1', 'DDP占比')
            ->setCellValue('AB1', '毛利')
            ->setCellValue('AC1', '毛利率')
            ->setCellValue('AD1', '测评数量')
            ->setCellValue('AE1', '测评金额')
            ->setCellValue('AF1', '含测评毛利')
            ->setCellValue('AG1', '含测评毛利率')
            ->setCellValue('AH1', '品名')
            ->setCellValue('AI1', '运营人员')
            ->setCellValue('AJ1', '采购人员')
        ;

        $walmartIndex = 1;
        foreach ($walmartWarehouseSku as $walmartItem) {
            $walmartIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $walmartIndex, $walmartItem['platform'])
                ->setCellValue('B' . $walmartIndex, $walmartItem['userAccount'])
                ->setCellValue('C' . $walmartIndex, $walmartItem['warehouse_sku'])
                ->setCellValue('D' . $walmartIndex, $walmartItem['sale_qty'])
                ->setCellValue('E' . $walmartIndex, $walmartItem['refund_qty'])
                ->setCellValue('F' . $walmartIndex, $walmartItem['sale_amount'])
                ->setCellValue('G' . $walmartIndex, $walmartItem['refund_amount'])
                ->setCellValue('H' . $walmartIndex, $walmartItem['sale_selling_fees'])
                ->setCellValue('I' . $walmartIndex, $walmartItem['refund_selling_fees'])
                ->setCellValue('J' . $walmartIndex, $walmartItem['calcuRes'])
                ->setCellValue('K' . $walmartIndex, $walmartItem['wfs_fulfillment'])
                ->setCellValue('L' . $walmartIndex, $walmartItem['ddp'])
                ->setCellValue('M' . $walmartIndex, $walmartItem['adCost'])
                ->setCellValue('N' . $walmartIndex, $walmartItem['warehouse_rent'])
                ->setCellValue('O' . $walmartIndex, $walmartItem['wfs_warehouse'])
                ->setCellValue('P' . $walmartIndex, $walmartItem['wfs_return_shipping'])
                ->setCellValue('Q' . $walmartIndex, $walmartItem['adjustment'])
                ->setCellValue('R' . $walmartIndex, $walmartItem['lc_adjustment'])
                ->setCellValue('S' . $walmartIndex, $walmartItem['le_adjustment'])
                ->setCellValue('T' . $walmartIndex, $walmartItem['wfs_adjustment'])
                ->setCellValue('U' . $walmartIndex, $walmartItem['operation_expenses'])
                ->setCellValue('V' . $walmartIndex, $walmartItem['operation_factory'])
                ->setCellValue('W' . $walmartIndex, $walmartItem['operation_delivery'])
                ->setCellValue('X' . $walmartIndex, $walmartItem['ad_percent'])
                ->setCellValue('Y' . $walmartIndex, $walmartItem['warehouse_percent'])
                ->setCellValue('Z' . $walmartIndex, $walmartItem['tail_percent'])
                ->setCellValue('AA' . $walmartIndex, $walmartItem['ddp_percent'])
                ->setCellValue('AB' . $walmartIndex, $walmartItem['profit'])
                ->setCellValue('AC' . $walmartIndex, $walmartItem['gross_profit_margin'])
                ->setCellValue('AD' . $walmartIndex, $walmartItem['evaluation_qty'])
                ->setCellValue('AE' . $walmartIndex, $walmartItem['evaluation_amount'])
                ->setCellValue('AF' . $walmartIndex, $walmartItem['profit_include_evaluation'])
                ->setCellValue('AG' . $walmartIndex, $walmartItem['gross_profit_margin_include_evaluation'])
                ->setCellValue('AH' . $walmartIndex, $walmartItem['product_name'])
                ->setCellValue('AI' . $walmartIndex, $walmartItem['seller'])
                ->setCellValue('AJ' . $walmartIndex, $walmartItem['purchaser'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateWayfairSheet($index, $report_id)
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
            ->setCellValue('A1', '平台')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', '仓库Sku')
            ->setCellValue('D1', '销售量')
            ->setCellValue('E1', '退款量')
            ->setCellValue('F1', '销售额')
            ->setCellValue('G1', '退款额')
            ->setCellValue('H1', '平台佣金')
            ->setCellValue('I1', '平台佣金退款')
            ->setCellValue('J1', 'FBM尾程')
            ->setCellValue('K1', 'DDP')
            ->setCellValue('L1', '广告费')
            ->setCellValue('M1', '仓储费')
            ->setCellValue('N1', '调整费用')
            ->setCellValue('O1', '良仓调整费用')
            ->setCellValue('P1', '乐歌调整费用')
            ->setCellValue('Q1', '国内广告费')
            ->setCellValue('R1', '工厂运费')
            ->setCellValue('S1', '国内快递费')
            ->setCellValue('T1', '广告费占比')
            ->setCellValue('U1', '仓储费占比')
            ->setCellValue('V1', '尾程占比')
            ->setCellValue('W1', 'DDP占比')
            ->setCellValue('X1', '毛利')
            ->setCellValue('Y1', '毛利率')
            ->setCellValue('Z1', '测评数量')
            ->setCellValue('AA1', '测评金额')
            ->setCellValue('AB1', '含测评毛利')
            ->setCellValue('AC1', '含测评毛利率')
            ->setCellValue('AD1', '品名')
            ->setCellValue('AE1', '运营人员')
            ->setCellValue('AF1', '采购人员')
        ;

        $wayfairIndex = 1;
        foreach ($wayfairWarehouseSku as $wayfairItem) {
            $wayfairIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $wayfairIndex, $wayfairItem['platform'])
                ->setCellValue('B' . $wayfairIndex, $wayfairItem['userAccount'])
                ->setCellValue('C' . $wayfairIndex, $wayfairItem['warehouse_sku'])
                ->setCellValue('D' . $wayfairIndex, $wayfairItem['sale_qty'])
                ->setCellValue('E' . $wayfairIndex, $wayfairItem['refund_qty'])
                ->setCellValue('F' . $wayfairIndex, $wayfairItem['sale_amount'])
                ->setCellValue('G' . $wayfairIndex, $wayfairItem['refund_amount'])
                ->setCellValue('H' . $wayfairIndex, $wayfairItem['sale_selling_fees'])
                ->setCellValue('I' . $wayfairIndex, $wayfairItem['refund_selling_fees'])
                ->setCellValue('J' . $wayfairIndex, $wayfairItem['calcuRes'])
                ->setCellValue('K' . $wayfairIndex, $wayfairItem['ddp'])
                ->setCellValue('L' . $wayfairIndex, $wayfairItem['adCost'])
                ->setCellValue('M' . $wayfairIndex, $wayfairItem['warehouse_rent'])
                ->setCellValue('N' . $wayfairIndex, $wayfairItem['adjustment'])
                ->setCellValue('O' . $wayfairIndex, $wayfairItem['lc_adjustment'])
                ->setCellValue('P' . $wayfairIndex, $wayfairItem['le_adjustment'])
                ->setCellValue('Q' . $wayfairIndex, $wayfairItem['operation_expenses'])
                ->setCellValue('R' . $wayfairIndex, $wayfairItem['operation_factory'])
                ->setCellValue('S' . $wayfairIndex, $wayfairItem['operation_delivery'])
                ->setCellValue('T' . $wayfairIndex, $wayfairItem['ad_percent'])
                ->setCellValue('U' . $wayfairIndex, $wayfairItem['warehouse_percent'])
                ->setCellValue('V' . $wayfairIndex, $wayfairItem['tail_percent'])
                ->setCellValue('W' . $wayfairIndex, $wayfairItem['ddp_percent'])
                ->setCellValue('X' . $wayfairIndex, $wayfairItem['profit'])
                ->setCellValue('Y' . $wayfairIndex, $wayfairItem['gross_profit_margin'])
                ->setCellValue('Z' . $wayfairIndex, $wayfairItem['evaluation_qty'])
                ->setCellValue('AA' . $wayfairIndex, $wayfairItem['evaluation_amount'])
                ->setCellValue('AB' . $wayfairIndex, $wayfairItem['profit_include_evaluation'])
                ->setCellValue('AC' . $wayfairIndex, $wayfairItem['gross_profit_margin_include_evaluation'])
                ->setCellValue('AD' . $wayfairIndex, $wayfairItem['product_name'])
                ->setCellValue('AE' . $wayfairIndex, $wayfairItem['seller'])
                ->setCellValue('AF' . $wayfairIndex, $wayfairItem['purchaser'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateSheinSheet($index, $report_id)
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
            ->setCellValue('A1', '平台')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', '仓库Sku')
            ->setCellValue('D1', '销售量')
            ->setCellValue('E1', '退款量')
            ->setCellValue('F1', '销售额')
            ->setCellValue('G1', '退款额')
            ->setCellValue('H1', '平台佣金')
            ->setCellValue('I1', '平台佣金退款')
            ->setCellValue('J1', 'FBM尾程')
            ->setCellValue('K1', 'DDP')
            ->setCellValue('L1', '广告费')
            ->setCellValue('M1', '仓储费')
            ->setCellValue('N1', '调整费用')
            ->setCellValue('O1', '良仓调整费用')
            ->setCellValue('P1', '乐歌调整费用')
            ->setCellValue('Q1', '国内广告费')
            ->setCellValue('R1', '工厂运费')
            ->setCellValue('S1', '国内快递费')
            ->setCellValue('T1', '广告费占比')
            ->setCellValue('U1', '仓储费占比')
            ->setCellValue('V1', '尾程占比')
            ->setCellValue('W1', 'DDP占比')
            ->setCellValue('X1', '毛利')
            ->setCellValue('Y1', '毛利率')
            ->setCellValue('Z1', '测评数量')
            ->setCellValue('AA1', '测评金额')
            ->setCellValue('AB1', '含测评毛利')
            ->setCellValue('AC1', '含测评毛利率')
            ->setCellValue('AD1', '品名')
            ->setCellValue('AE1', '运营人员')
            ->setCellValue('AF1', '采购人员')
        ;

        $sheinIndex = 1;
        foreach ($sheinWarehouseSku as $sheinItem) {
            $sheinIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $sheinIndex, $sheinItem['platform'])
                ->setCellValue('B' . $sheinIndex, $sheinItem['userAccount'])
                ->setCellValue('C' . $sheinIndex, $sheinItem['warehouse_sku'])
                ->setCellValue('D' . $sheinIndex, $sheinItem['sale_qty'])
                ->setCellValue('E' . $sheinIndex, $sheinItem['refund_qty'])
                ->setCellValue('F' . $sheinIndex, $sheinItem['sale_amount'])
                ->setCellValue('G' . $sheinIndex, $sheinItem['refund_amount'])
                ->setCellValue('H' . $sheinIndex, $sheinItem['sale_selling_fees'])
                ->setCellValue('I' . $sheinIndex, $sheinItem['refund_selling_fees'])
                ->setCellValue('J' . $sheinIndex, $sheinItem['calcuRes'])
                ->setCellValue('K' . $sheinIndex, $sheinItem['ddp'])
                ->setCellValue('L' . $sheinIndex, $sheinItem['adCost'])
                ->setCellValue('M' . $sheinIndex, $sheinItem['warehouse_rent'])
                ->setCellValue('N' . $sheinIndex, $sheinItem['adjustment'])
                ->setCellValue('O' . $sheinIndex, $sheinItem['lc_adjustment'])
                ->setCellValue('P' . $sheinIndex, $sheinItem['le_adjustment'])
                ->setCellValue('Q' . $sheinIndex, $sheinItem['operation_expenses'])
                ->setCellValue('R' . $sheinIndex, $sheinItem['operation_factory'])
                ->setCellValue('S' . $sheinIndex, $sheinItem['operation_delivery'])
                ->setCellValue('T' . $sheinIndex, $sheinItem['ad_percent'])
                ->setCellValue('U' . $sheinIndex, $sheinItem['warehouse_percent'])
                ->setCellValue('V' . $sheinIndex, $sheinItem['tail_percent'])
                ->setCellValue('W' . $sheinIndex, $sheinItem['ddp_percent'])
                ->setCellValue('X' . $sheinIndex, $sheinItem['profit'])
                ->setCellValue('Y' . $sheinIndex, $sheinItem['gross_profit_margin'])
                ->setCellValue('Z' . $sheinIndex, $sheinItem['evaluation_qty'])
                ->setCellValue('AA' . $sheinIndex, $sheinItem['evaluation_amount'])
                ->setCellValue('AB' . $sheinIndex, $sheinItem['profit_include_evaluation'])
                ->setCellValue('AC' . $sheinIndex, $sheinItem['gross_profit_margin_include_evaluation'])
                ->setCellValue('AD' . $sheinIndex, $sheinItem['product_name'])
                ->setCellValue('AE' . $sheinIndex, $sheinItem['seller'])
                ->setCellValue('AF' . $sheinIndex, $sheinItem['purchaser'])
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
    public function generateTemuSheet($index, $report_id)
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
            ->setCellValue('A1', '平台')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', '仓库Sku')
            ->setCellValue('D1', '销售量')
            ->setCellValue('E1', '退款量')
            ->setCellValue('F1', '销售额')
            ->setCellValue('G1', '退款额')
            ->setCellValue('H1', '平台佣金')
            ->setCellValue('I1', '平台佣金退款')
            ->setCellValue('J1', 'FBM尾程')
            ->setCellValue('K1', 'DDP')
            ->setCellValue('L1', '广告费')
            ->setCellValue('M1', '仓储费')
            ->setCellValue('N1', '调整费用')
            ->setCellValue('O1', '良仓调整费用')
            ->setCellValue('P1', '乐歌调整费用')
            ->setCellValue('Q1', '国内广告费')
            ->setCellValue('R1', '工厂运费')
            ->setCellValue('S1', '国内快递费')
            ->setCellValue('T1', '广告费占比')
            ->setCellValue('U1', '仓储费占比')
            ->setCellValue('V1', '尾程占比')
            ->setCellValue('W1', 'DDP占比')
            ->setCellValue('X1', '毛利')
            ->setCellValue('Y1', '毛利率')
            ->setCellValue('Z1', '测评数量')
            ->setCellValue('AA1', '测评金额')
            ->setCellValue('AB1', '含测评毛利')
            ->setCellValue('AC1', '含测评毛利率')
            ->setCellValue('AD1', '品名')
            ->setCellValue('AE1', '运营人员')
        ;

        $temuIndex = 1;
        foreach ($temuWarehouseSku as $temuItem) {
            $temuIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $temuIndex, $temuItem['platform'])
                ->setCellValue('B' . $temuIndex, $temuItem['userAccount'])
                ->setCellValue('C' . $temuIndex, $temuItem['warehouse_sku'])
                ->setCellValue('D' . $temuIndex, $temuItem['sale_qty'])
                ->setCellValue('E' . $temuIndex, $temuItem['refund_qty'])
                ->setCellValue('F' . $temuIndex, $temuItem['sale_amount'])
                ->setCellValue('G' . $temuIndex, $temuItem['refund_amount'])
                ->setCellValue('H' . $temuIndex, $temuItem['sale_selling_fees'])
                ->setCellValue('I' . $temuIndex, $temuItem['refund_selling_fees'])
                ->setCellValue('J' . $temuIndex, $temuItem['calcuRes'])
                ->setCellValue('K' . $temuIndex, $temuItem['ddp'])
                ->setCellValue('L' . $temuIndex, $temuItem['adCost'])
                ->setCellValue('M' . $temuIndex, $temuItem['warehouse_rent'])
                ->setCellValue('N' . $temuIndex, $temuItem['adjustment'])
                ->setCellValue('O' . $temuIndex, $temuItem['lc_adjustment'])
                ->setCellValue('P' . $temuIndex, $temuItem['le_adjustment'])
                ->setCellValue('Q' . $temuIndex, $temuItem['operation_expenses'])
                ->setCellValue('R' . $temuIndex, $temuItem['operation_factory'])
                ->setCellValue('S' . $temuIndex, $temuItem['operation_delivery'])
                ->setCellValue('T' . $temuIndex, $temuItem['ad_percent'])
                ->setCellValue('U' . $temuIndex, $temuItem['warehouse_percent'])
                ->setCellValue('V' . $temuIndex, $temuItem['tail_percent'])
                ->setCellValue('W' . $temuIndex, $temuItem['ddp_percent'])
                ->setCellValue('X' . $temuIndex, $temuItem['profit'])
                ->setCellValue('Y' . $temuIndex, $temuItem['gross_profit_margin'])
                ->setCellValue('Z' . $temuIndex, $temuItem['evaluation_qty'])
                ->setCellValue('AA' . $temuIndex, $temuItem['evaluation_amount'])
                ->setCellValue('AB' . $temuIndex, $temuItem['profit_include_evaluation'])
                ->setCellValue('AC' . $temuIndex, $temuItem['gross_profit_margin_include_evaluation'])
                ->setCellValue('AD' . $temuIndex, $temuItem['product_name'])
                ->setCellValue('AE' . $temuIndex, $temuItem['seller'])
            ;
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     * @throws \PHPExcel_Exception
     */
    public function generateEbaySheet($index, $report_id)
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
            ->setCellValue('A1', '平台')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', '仓库Sku')
            ->setCellValue('D1', '销售量')
            ->setCellValue('E1', '退款量')
            ->setCellValue('F1', '销售额')
            ->setCellValue('G1', '税')
            ->setCellValue('H1', '退款额')
            ->setCellValue('I1', '平台佣金')
            ->setCellValue('J1', '平台佣金退款')
            ->setCellValue('K1', 'FBM尾程')
            ->setCellValue('L1', 'DDP')
            ->setCellValue('M1', '广告费')
            ->setCellValue('N1', '仓储费')
            ->setCellValue('O1', '调整费用')
            ->setCellValue('P1', '良仓调整费用')
            ->setCellValue('Q1', '乐歌调整费用')
            ->setCellValue('R1', '国内广告费')
            ->setCellValue('S1', '工厂运费')
            ->setCellValue('T1', '国内快递费')
            ->setCellValue('U1', '广告费占比')
            ->setCellValue('V1', '仓储费占比')
            ->setCellValue('W1', '尾程占比')
            ->setCellValue('X1', 'DDP占比')
            ->setCellValue('Y1', '毛利')
            ->setCellValue('Z1', '毛利率')
            ->setCellValue('AA1', '测评数量')
            ->setCellValue('AB1', '测评金额')
            ->setCellValue('AC1', '含测评毛利')
            ->setCellValue('AD1', '含测评毛利率')
            ->setCellValue('AE1', '品名')
            ->setCellValue('AF1', '运营人员')
        ;

        $ebayIndex = 1;
        foreach ($ebayWarehouseSku as $ebayItem) {
            $ebayIndex ++;
            $this->objPHPExcel->setActiveSheetIndex($index)
                ->setCellValue('A' . $ebayIndex, $ebayItem['platform'])
                ->setCellValue('B' . $ebayIndex, $ebayItem['userAccount'])
                ->setCellValue('C' . $ebayIndex, $ebayItem['warehouse_sku'])
                ->setCellValue('D' . $ebayIndex, $ebayItem['sale_qty'])
                ->setCellValue('E' . $ebayIndex, $ebayItem['refund_qty'])
                ->setCellValue('F' . $ebayIndex, $ebayItem['sale_amount'])
                ->setCellValue('G' . $ebayIndex, $ebayItem['sale_tax'])
                ->setCellValue('H' . $ebayIndex, $ebayItem['refund_amount'])
                ->setCellValue('I' . $ebayIndex, $ebayItem['sale_selling_fees'])
                ->setCellValue('J' . $ebayIndex, $ebayItem['refund_selling_fees'])
                ->setCellValue('K' . $ebayIndex, $ebayItem['calcuRes'])
                ->setCellValue('L' . $ebayIndex, $ebayItem['ddp'])
                ->setCellValue('M' . $ebayIndex, $ebayItem['adCost'])
                ->setCellValue('N' . $ebayIndex, $ebayItem['warehouse_rent'])
                ->setCellValue('O' . $ebayIndex, $ebayItem['adjustment'])
                ->setCellValue('P' . $ebayIndex, $ebayItem['lc_adjustment'])
                ->setCellValue('Q' . $ebayIndex, $ebayItem['le_adjustment'])
                ->setCellValue('R' . $ebayIndex, $ebayItem['operation_expenses'])
                ->setCellValue('S' . $ebayIndex, $ebayItem['operation_factory'])
                ->setCellValue('T' . $ebayIndex, $ebayItem['operation_delivery'])
                ->setCellValue('U' . $ebayIndex, $ebayItem['ad_percent'])
                ->setCellValue('V' . $ebayIndex, $ebayItem['warehouse_percent'])
                ->setCellValue('W' . $ebayIndex, $ebayItem['tail_percent'])
                ->setCellValue('X' . $ebayIndex, $ebayItem['ddp_percent'])
                ->setCellValue('Y' . $ebayIndex, $ebayItem['profit'])
                ->setCellValue('Z' . $ebayIndex, $ebayItem['gross_profit_margin'])
                ->setCellValue('AA' . $ebayIndex, $ebayItem['evaluation_qty'])
                ->setCellValue('AB' . $ebayIndex, $ebayItem['evaluation_amount'])
                ->setCellValue('AC' . $ebayIndex, $ebayItem['profit_include_evaluation'])
                ->setCellValue('AD' . $ebayIndex, $ebayItem['gross_profit_margin_include_evaluation'])
                ->setCellValue('AE' . $ebayIndex, $ebayItem['product_name'])
                ->setCellValue('AF' . $ebayIndex, $ebayItem['seller'])
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

    public function excelSheetSet()
    {
        return $this->objPHPExcel;
    }
}
