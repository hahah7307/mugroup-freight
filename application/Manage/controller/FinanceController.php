<?php
namespace app\Manage\controller;

use app\Manage\command\FinanceNotify;
use app\Manage\command\FinanceOrderShare;
use app\Manage\model\AkAdCostCreateModel;
use app\Manage\model\AmazonPayment;
use app\Manage\model\FinanceAdCostModel;
use app\Manage\model\FinanceEvaluationModel;
use app\Manage\model\FinanceOrderAdditionalModel;
use app\Manage\model\FinanceOrderAdjustmentModel;
use app\Manage\model\FinanceOrderFbaInventoryModel;
use app\Manage\model\FinanceOrderLiquidationModel;
use app\Manage\model\FinanceOrderPromotionModel;
use app\Manage\model\FinanceOrderRefundModel;
use app\Manage\model\FinanceOrderSaleModel;
use app\Manage\model\FinanceOrderOutboundModel;
use app\Manage\model\FinanceOrderShareModel;
use app\Manage\model\FinanceOrderShippingServiceModel;
use app\Manage\model\FinanceOrderStatisticsEditModel;
use app\Manage\model\FinanceOrderStatisticsModel;
use app\Manage\model\FinanceOrderTransferModel;
use app\Manage\model\FinanceOrderWayfairModel;
use app\Manage\model\FinanceReportModel;
use app\Manage\model\FinanceSkuRelationModel;
use app\Manage\model\FinanceStoreModel;
use app\Manage\model\FinanceTableModel;
use app\Manage\model\FinanceWarehouseModel;
use app\Manage\model\FinanceWarehouseWFSModel;
use app\Manage\model\FinanceWayfairCoreModel;
use app\Manage\validate\FinanceOrderStatisticsValidate;
use app\Manage\validate\FinanceReportValidate;
use app\Manage\validate\FinanceTableValidate;
use app\Manage\validate\FinanceWayfairCoreValidate;
use DateTime;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Reader_Exception;
use PHPExcel_Style_Fill;
use think\Cache;
use think\Db;
use think\db\exception\BindParamException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\Session;
use think\Config;

class FinanceController extends BaseController
{
    /**
     * @throws DbException
     */
    public function report(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['name'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        // 列表
        $order = new FinanceReportModel();
        $list = $order->where($where)->order('id asc')->paginate(Config::get('PAGE_NUM'), false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    // 添加
    public function report_add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post['state'] = FinanceReportModel::STATE_ACTIVE;
            $dataValidate = new FinanceReportValidate();
            if ($dataValidate->scene('add')->check($post)) {
                $model = new FinanceReportModel();
                if ($model->allowField(true)->save($post)) {
                    AkAdCostCreateModel::newOne($post['month']);
                    echo json_encode(['code' => 1, 'msg' => '添加成功']);
                    exit;
                } else {
                    echo json_encode(['code' => 0, 'msg' => '添加失败，请重试']);
                    exit;
                }
            } else {
                echo json_encode(['code' => 0, 'msg' => $dataValidate->getError()]);
                exit;
            }
        } else {

            return view();
        }
    }

    // 编辑
    /**
     * @throws DbException
     */
    public function report_edit($id)
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $dataValidate = new FinanceReportValidate();
            if ($dataValidate->scene('edit')->check($post)) {
                $model = new FinanceReportModel();
                if ($model->allowField(true)->save($post, ['id' => $id])) {
                    AkAdCostCreateModel::newOne($post['month']);
                    echo json_encode(['code' => 1, 'msg' => '修改成功']);
                    exit;
                } else {
                    echo json_encode(['code' => 0, 'msg' => '修改失败，请重试']);
                    exit;
                }
            } else {
                echo json_encode(['code' => 0, 'msg' => $dataValidate->getError()]);
                exit;
            }
        } else {
            $info = FinanceReportModel::get(['id' => $id,]);
            $this->assign('info', $info);

            return view();
        }
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     * @throws \PHPExcel_Exception
     */
    public function report_export()
    {
        $report_id = input('id');
        $month = input('month');

        $financeReportObj = new FinanceReportModel();
        $report = $financeReportObj->find($report_id);
        if (empty($report) || $report['is_notify'] != 1) {
            $this->error('异常操作！', url('report'));
        }

        $saleRefund = $financeReportObj->query(FinanceReportModel::getSaleRefundSql($report_id));
        $warehouseSku = $financeReportObj->query(FinanceReportModel::getWarehouseSkuSql($report_id, $report['month']));
        $warehouseRent = $financeReportObj->query(FinanceReportModel::getWarehouseRentSql($report_id));
        $paymentNoOutbound = $financeReportObj->query(FinanceReportModel::getPaymentNoOutboundSql($report_id));
        $fbaWarehouseSku = $financeReportObj->query(FinanceReportModel::getFbaWarehouseSkuSql($report_id, $report['month']));
        $fbmWarehouseSku = $financeReportObj->query(FinanceReportModel::getFbmWarehouseSkuSql($report_id, $report['month']));
        $walmartWarehouseSku = $financeReportObj->query(FinanceReportModel::getWalmartWarehouseSkuSql($report_id));
        $wayfairWarehouseSku = $financeReportObj->query(FinanceReportModel::getWayfairWarehouseSkuSql($report_id));
        $sheinWarehouseSku = $financeReportObj->query(FinanceReportModel::getSheinWarehouseSkuSql($report_id));
        $userAccountTransfer = $financeReportObj->query(FinanceReportModel::getUserAccountTransfer($report_id));
        $userAccountSubscription = $financeReportObj->query(FinanceReportModel::getUserAccountSubscription($report_id));
        $orderWayfair = $financeReportObj->query(FinanceReportModel::getOrderWayfair($report_id));
        $orderResend = $financeReportObj->query(FinanceReportModel::getOrderResend($report['month']));

        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();

        // Set name sheet
        $objPHPExcel->setActiveSheetIndex(0)->setTitle('销售-退款');

        // Add some data
        $objPHPExcel->setActiveSheetIndex(0)
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
            $objPHPExcel->setActiveSheetIndex(0)
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

        // create new sheet
        $objPHPExcel->createSheet();

        // Set name sheet
        $objPHPExcel->setActiveSheetIndex(1)->setTitle('FBA');

        // Add some data
        $objPHPExcel->setActiveSheetIndex(1)
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

        $fbaIndex = 1;
        foreach ($fbaWarehouseSku as $fbaItem) {
            $fbaIndex ++;
            $objPHPExcel->setActiveSheetIndex(1)
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
                ->setCellValue('U' . $fbaIndex, $fbaItem['ad_percent'])
                ->setCellValue('V' . $fbaIndex, $fbaItem['inventory_percent'])
                ->setCellValue('W' . $fbaIndex, $fbaItem['tail_percent'])
                ->setCellValue('X' . $fbaIndex, $fbaItem['ddp_percent'])
                ->setCellValue('Y' . $fbaIndex, $fbaItem['profit'])
                ->setCellValue('Z' . $fbaIndex, $fbaItem['gross_profit_margin'])
                ->setCellValue('AA' . $fbaIndex, $fbaItem['evaluation_qty'])
                ->setCellValue('AB' . $fbaIndex, $fbaItem['evaluation_amount'])
                ->setCellValue('AC' . $fbaIndex, $fbaItem['profit_include_evaluation'])
                ->setCellValue('AD' . $fbaIndex, $fbaItem['gross_profit_margin_include_evaluation'])
                ->setCellValue('AE' . $fbaIndex, $fbaItem['product_name'])
                ->setCellValue('AF' . $fbaIndex, $fbaItem['seller'])
            ;
        }

        // create new sheet
        $objPHPExcel->createSheet();

        // Set name sheet
        $objPHPExcel->setActiveSheetIndex(2)->setTitle('FBM');

        // Add some data
        $objPHPExcel->setActiveSheetIndex(2)
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
            ->setCellValue('V1', '广告费占比')
            ->setCellValue('W1', '仓储费占比')
            ->setCellValue('X1', '尾程占比')
            ->setCellValue('Y1', 'DDP占比')
            ->setCellValue('Z1', '毛利')
            ->setCellValue('AA1', '毛利率')
            ->setCellValue('AB1', '测评数量')
            ->setCellValue('AC1', '测评金额')
            ->setCellValue('AD1', '含测评毛利')
            ->setCellValue('AE1', '含测评毛利率')
            ->setCellValue('AF1', '品名')
            ->setCellValue('AG1', '运营人员')
        ;

        $fbmIndex = 1;
        foreach ($fbmWarehouseSku as $fbmItem) {
            $fbmIndex ++;
            $objPHPExcel->setActiveSheetIndex(2)
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
                ->setCellValue('V' . $fbmIndex, $fbmItem['ad_percent'])
                ->setCellValue('W' . $fbmIndex, $fbmItem['inventory_percent'])
                ->setCellValue('X' . $fbmIndex, $fbmItem['tail_percent'])
                ->setCellValue('Y' . $fbmIndex, $fbmItem['ddp_percent'])
                ->setCellValue('Z' . $fbmIndex, $fbmItem['profit'])
                ->setCellValue('AA' . $fbmIndex, $fbmItem['gross_profit_margin'])
                ->setCellValue('AB' . $fbmIndex, $fbmItem['evaluation_qty'])
                ->setCellValue('AC' . $fbmIndex, $fbmItem['evaluation_amount'])
                ->setCellValue('AD' . $fbmIndex, $fbmItem['profit_include_evaluation'])
                ->setCellValue('AE' . $fbmIndex, $fbmItem['gross_profit_margin_include_evaluation'])
                ->setCellValue('AF' . $fbmIndex, $fbmItem['product_name'])
                ->setCellValue('AG' . $fbmIndex, $fbmItem['seller'])
            ;
        }

        // create new sheet
        $objPHPExcel->createSheet();

        // Set name sheet
        $objPHPExcel->setActiveSheetIndex(3)->setTitle('Walmart');

        // Add some data
        $objPHPExcel->setActiveSheetIndex(3)
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
            ->setCellValue('P1', '调整费用')
            ->setCellValue('Q1', '良仓调整费用')
            ->setCellValue('R1', '乐歌调整费用')
            ->setCellValue('S1', 'WFS调整费用')
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

        $walmartIndex = 1;
        foreach ($walmartWarehouseSku as $walmartItem) {
            $walmartIndex ++;
            $objPHPExcel->setActiveSheetIndex(3)
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
                ->setCellValue('K' . $walmartIndex, $walmartItem['wfs_tail'])
                ->setCellValue('L' . $walmartIndex, $walmartItem['ddp'])
                ->setCellValue('M' . $walmartIndex, $walmartItem['adCost'])
                ->setCellValue('N' . $walmartIndex, $walmartItem['warehouse_rent'])
                ->setCellValue('O' . $walmartIndex, $walmartItem['wfs_warehouse'])
                ->setCellValue('P' . $walmartIndex, $walmartItem['adjustment'])
                ->setCellValue('Q' . $walmartIndex, $walmartItem['lc_adjustment'])
                ->setCellValue('R' . $walmartIndex, $walmartItem['le_adjustment'])
                ->setCellValue('S' . $walmartIndex, $walmartItem['wfs_adjustment'])
                ->setCellValue('T' . $walmartIndex, $walmartItem['ad_percent'])
                ->setCellValue('U' . $walmartIndex, $walmartItem['warehouse_percent'])
                ->setCellValue('V' . $walmartIndex, $walmartItem['tail_percent'])
                ->setCellValue('W' . $walmartIndex, $walmartItem['ddp_percent'])
                ->setCellValue('X' . $walmartIndex, $walmartItem['profit'])
                ->setCellValue('Y' . $walmartIndex, $walmartItem['gross_profit_margin'])
                ->setCellValue('Z' . $walmartIndex, $walmartItem['evaluation_qty'])
                ->setCellValue('AA' . $walmartIndex, $walmartItem['evaluation_amount'])
                ->setCellValue('AB' . $walmartIndex, $walmartItem['profit_include_evaluation'])
                ->setCellValue('AC' . $walmartIndex, $walmartItem['gross_profit_margin_include_evaluation'])
                ->setCellValue('AD' . $walmartIndex, $walmartItem['product_name'])
                ->setCellValue('AE' . $walmartIndex, $walmartItem['seller'])
            ;
        }

        // create new sheet
        $objPHPExcel->createSheet();

        // Set name sheet
        $objPHPExcel->setActiveSheetIndex(4)->setTitle('Wayfair');

        // Add some data
        $objPHPExcel->setActiveSheetIndex(4)
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
            ->setCellValue('Q1', '广告费占比')
            ->setCellValue('R1', '仓储费占比')
            ->setCellValue('S1', '尾程占比')
            ->setCellValue('T1', 'DDP占比')
            ->setCellValue('U1', '毛利')
            ->setCellValue('V1', '毛利率')
            ->setCellValue('W1', '测评数量')
            ->setCellValue('X1', '测评金额')
            ->setCellValue('Y1', '含测评毛利')
            ->setCellValue('Z1', '含测评毛利率')
            ->setCellValue('AA1', '品名')
            ->setCellValue('AB1', '运营人员')
        ;

        $wayfairIndex = 1;
        foreach ($wayfairWarehouseSku as $wayfairItem) {
            $wayfairIndex ++;
            $objPHPExcel->setActiveSheetIndex(4)
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
                ->setCellValue('Q' . $wayfairIndex, $wayfairItem['ad_percent'])
                ->setCellValue('R' . $wayfairIndex, $wayfairItem['warehouse_percent'])
                ->setCellValue('S' . $wayfairIndex, $wayfairItem['tail_percent'])
                ->setCellValue('T' . $wayfairIndex, $wayfairItem['ddp_percent'])
                ->setCellValue('U' . $wayfairIndex, $wayfairItem['profit'])
                ->setCellValue('V' . $wayfairIndex, $wayfairItem['gross_profit_margin'])
                ->setCellValue('W' . $wayfairIndex, $wayfairItem['evaluation_qty'])
                ->setCellValue('X' . $wayfairIndex, $wayfairItem['evaluation_amount'])
                ->setCellValue('Y' . $wayfairIndex, $wayfairItem['profit_include_evaluation'])
                ->setCellValue('Z' . $wayfairIndex, $wayfairItem['gross_profit_margin_include_evaluation'])
                ->setCellValue('AA' . $wayfairIndex, $wayfairItem['product_name'])
                ->setCellValue('AB' . $wayfairIndex, $wayfairItem['seller'])
            ;
        }

        // create new sheet
        $objPHPExcel->createSheet();

        // Set name sheet
        $objPHPExcel->setActiveSheetIndex(5)->setTitle('Shein');

        // Add some data
        $objPHPExcel->setActiveSheetIndex(5)
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
            ->setCellValue('Q1', '广告费占比')
            ->setCellValue('R1', '仓储费占比')
            ->setCellValue('S1', '尾程占比')
            ->setCellValue('T1', 'DDP占比')
            ->setCellValue('U1', '毛利')
            ->setCellValue('V1', '毛利率')
            ->setCellValue('W1', '测评数量')
            ->setCellValue('X1', '测评金额')
            ->setCellValue('Y1', '含测评毛利')
            ->setCellValue('Z1', '含测评毛利率')
            ->setCellValue('AA1', '品名')
            ->setCellValue('AB1', '运营人员')
        ;

        $sheinIndex = 1;
        foreach ($sheinWarehouseSku as $sheinItem) {
            $sheinIndex ++;
            $objPHPExcel->setActiveSheetIndex(5)
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
                ->setCellValue('Q' . $sheinIndex, $sheinItem['ad_percent'])
                ->setCellValue('R' . $sheinIndex, $sheinItem['warehouse_percent'])
                ->setCellValue('S' . $sheinIndex, $sheinItem['tail_percent'])
                ->setCellValue('T' . $sheinIndex, $sheinItem['ddp_percent'])
                ->setCellValue('U' . $sheinIndex, $sheinItem['profit'])
                ->setCellValue('V' . $sheinIndex, $sheinItem['gross_profit_margin'])
                ->setCellValue('W' . $sheinIndex, $sheinItem['evaluation_qty'])
                ->setCellValue('X' . $sheinIndex, $sheinItem['evaluation_amount'])
                ->setCellValue('Y' . $sheinIndex, $sheinItem['profit_include_evaluation'])
                ->setCellValue('Z' . $sheinIndex, $sheinItem['gross_profit_margin_include_evaluation'])
                ->setCellValue('AA' . $sheinIndex, $sheinItem['product_name'])
                ->setCellValue('AB' . $sheinIndex, $sheinItem['seller'])
            ;
        }

        // create new sheet
        $objPHPExcel->createSheet();

        // Set name sheet
        $objPHPExcel->setActiveSheetIndex(6)->setTitle('账单未出库统计');

        // Add some data
        $objPHPExcel->setActiveSheetIndex(6)
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
            $objPHPExcel->setActiveSheetIndex(6)
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

        // create new sheet
        $objPHPExcel->createSheet();

        // Set name sheet
        $objPHPExcel->setActiveSheetIndex(7)->setTitle('回款统计');

        // Add some data
        $objPHPExcel->setActiveSheetIndex(7)
            ->setCellValue('A1', '平台')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', '合计')
        ;

        $userAccountTransferIndex = 1;
        foreach ($userAccountTransfer as $userAccountTransferItem) {
            $userAccountTransferIndex ++;
            $objPHPExcel->setActiveSheetIndex(7)
                ->setCellValue('A' . $userAccountTransferIndex, $userAccountTransferItem['platform'])
                ->setCellValue('B' . $userAccountTransferIndex, $userAccountTransferItem['userAccount'])
                ->setCellValue('C' . $userAccountTransferIndex, $userAccountTransferItem['total'])
            ;
        }

        // create new sheet
        $objPHPExcel->createSheet();

        // Set name sheet
        $objPHPExcel->setActiveSheetIndex(8)->setTitle('订阅');

        // Add some data
        $objPHPExcel->setActiveSheetIndex(8)
            ->setCellValue('A1', '平台')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', '合计')
        ;

        $userAccountSubscriptionIndex = 1;
        foreach ($userAccountSubscription as $userAccountSubscriptionItem) {
            $userAccountSubscriptionIndex ++;
            $objPHPExcel->setActiveSheetIndex(8)
                ->setCellValue('A' . $userAccountSubscriptionIndex, $userAccountSubscriptionItem['platform'])
                ->setCellValue('B' . $userAccountSubscriptionIndex, $userAccountSubscriptionItem['userAccount'])
                ->setCellValue('C' . $userAccountSubscriptionIndex, $userAccountSubscriptionItem['total'])
            ;
        }

        // create new sheet
        $objPHPExcel->createSheet();

        // Set name sheet
        $objPHPExcel->setActiveSheetIndex(9)->setTitle('Wayfair账单销售');

        // Add some data
        $objPHPExcel->setActiveSheetIndex(9)
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
            $objPHPExcel->setActiveSheetIndex(9)
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

        // create new sheet
        $objPHPExcel->createSheet();

        // Set name sheet
        $objPHPExcel->setActiveSheetIndex(10)->setTitle('Resend');

        // Add some data
        $objPHPExcel->setActiveSheetIndex(10)
            ->setCellValue('A1', '平台')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', '仓库SKU')
            ->setCellValue('D1', '数量')
            ->setCellValue('E1', '订单状态')
            ->setCellValue('F1', '尾程')
        ;

        $orderResendIndex = 1;
        foreach ($orderResend as $orderResendItem) {
            $orderResendIndex ++;
            $objPHPExcel->setActiveSheetIndex(10)
                ->setCellValue('A' . $orderResendIndex, $orderResendItem['platform'])
                ->setCellValue('B' . $orderResendIndex, $orderResendItem['user_account'])
                ->setCellValue('C' . $orderResendIndex, $orderResendItem['warehouse_sku'])
                ->setCellValue('D' . $orderResendIndex, $orderResendItem['qty'])
                ->setCellValue('E' . $orderResendIndex, $orderResendItem['order_status'])
                ->setCellValue('F' . $orderResendIndex, $orderResendItem['tail'])
            ;
        }




        // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        $filename = date("YmdHis") . time() . mt_rand(100000, 999999);
        ob_end_clean();
        header('Content-Disposition:attachment;filename="'.$filename.'.xls"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }

    /**
     * @throws DbException
     * @throws Exception
     */
    public function index($id): \think\response\View
    {
        $where['rid'] = $id;
        $this->assign('rid', $id);

        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['table_name|platform|userAccount|country'] = ['like', '%' . $keyword . '%'];
        }

        // 表格列表
        $order = new FinanceTableModel();
        $list = $order->where($where)->order('id asc')->paginate(Config::get('PAGE_NUM'), false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);
        $this->assign('sale_amount', $order->where($where)->sum('sale_amount'));
        $this->assign('refund_amount', $order->where($where)->sum('refund_amount'));
        $this->assign('promotion', $order->where($where)->sum('promotion'));
        $this->assign('shipping_service', $order->where($where)->sum('shipping_service'));
        $this->assign('liquidation', $order->where($where)->sum('liquidation'));
        $this->assign('adjustment', $order->where($where)->sum('adjustment'));

        $editObj = new FinanceOrderStatisticsEditModel();
        $this->assign('edit', $editObj->where(['is_finished' => 0])->count());

        $this->assign('report_id', $id);
        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws DbException
     */
    public function index_wayfair($id): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['invoice_no'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        $order = new FinanceOrderWayfairModel();
        $where['report_id'] = $id;
        $list = $order->with('wayfairCore')->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);
        $this->assign('sale_amount', $order->where($where)->sum('amount'));
        $this->assign('commission', $order->where($where)->sum('commission'));
        $this->assign('collection', $order->where($where)->sum('collection'));

        $this->assign('report_id', $id);
        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function index_wayfair_import()
    {
        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';

        $filename = input('filename');
        $report_id = input('id');
        $file= "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();
        unset($data[0]);

        Db::startTrans();
        try {
            $wayfairData = [];
            $financeOrderWayfairObj = new FinanceOrderWayfairModel();
            foreach ($data as $key => $item) {
                if ($key == 0) {
                    continue;
                }
                $wayfairData[] = [
                    "report_id"             =>  $report_id,
                    "invoice_no"            =>  $item[0],
                    "order_no"              =>  $item[1],
                    "invoice_date"          =>  DateTime::createFromFormat('m-d-y', $item[2])->format('Y-m-d 00:00:00'),
                    "amount"                =>  $item[3],
                    "ca_commission"         =>  $item[4],
                    "am_commission"         =>  $item[5],
                    "commission"            =>  $item[6],
                    "shipping"              =>  $item[7],
                    "other"                 =>  $item[8],
                    "tax"                   =>  $item[9],
                    "collection"            =>  $item[10],
                    "business"              =>  $item[11],
                    "order_type"            =>  $item[12],
                    "payment_batch"         =>  $item[13],
                    "payment_date"          =>  DateTime::createFromFormat('m-d-y', $item[14])->format('Y-m-d 00:00:00'),
                ];
            }

            // 将wayfair账单添加到缓存队列
            $wayfairOrder = Cache::get('wayfairPayment');
            Cache::set('wayfairPayment', array_merge((array)$wayfairOrder, $wayfairData), 24 * 60 * 60);
            $financeOrderWayfairObj->insertAll($wayfairData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), session('back_url', '', 'manage'));
        }
        $this->redirect(session('back_url', '', 'manage'));
    }

    public function index_wayfair_empty()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $reportId = $post['id'];
            $financeOrderWayfairObj = new FinanceOrderWayfairModel();
            if ($financeOrderWayfairObj->where('report_id', $reportId)->delete()) {
                echo json_encode(['code' => 1, 'msg' => '清空完成']);
            } else {
                echo json_encode(['code' => 0, 'msg' => '清空失败，请重试']);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }

    // 编辑
    /**
     * @throws DbException
     */
    public function index_edit($id)
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $dataValidate = new FinanceTableValidate();
            if ($dataValidate->scene('edit')->check($post)) {
                $model = new FinanceTableModel();
                if ($model->allowField(true)->save($post, ['id' => $id])) {
                    echo json_encode(['code' => 1, 'msg' => '修改成功']);
                } else {
                    echo json_encode(['code' => 0, 'msg' => '修改失败，请重试']);
                }
            } else {
                echo json_encode(['code' => 0, 'msg' => $dataValidate->getError()]);
            }
            exit;
        } else {
            $info = FinanceTableModel::get(['id' => $id,]);
            $this->assign('info', $info);

            return view();
        }
    }

    // 导入excel计算计费重差和最终费用
    /**
     * @throws DataNotFoundException
     * @throws PHPExcel_Reader_Exception
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws Exception
     * @throws \Exception
     */
    public function import()
    {
        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';

        $filename = input('filename');
        $origin = input('origin');
        $rid = input('rid');
        $payment_type = input('payment_type');
        $payment_type_new = strpos($payment_type, 'amazon') !== false ? 'amazon' : $payment_type;
        $file= "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();

        Db::startTrans();
        try {
            $tableData = [
                'rid'           =>  $rid,
                'table_name'    =>  $origin,
                'platform'      =>  $payment_type_new,
                'country'       =>  strpos($payment_type, 'amazon') !== false && $payment_type != 'amazon_us' ? 'EUROPE' : 'US',
                'created_at'    =>  date('Y-m-d H:i:s')
            ];
            $financeTableObj = new FinanceTableModel();
            if ($tableId = $financeTableObj->insertGetId($tableData)) {
                $paymentObj = new AmazonPayment();
                if ($payment_type) {
                    $paymentData = $paymentObj->$payment_type($data, $tableId, $rid);

                    $financeOrderSaleObj = new FinanceOrderSaleModel();
                    if ($payment_type == "wayfair") {
                        // 将wayfair销售数据添加到缓存队列
                        $wayfairOrder = Cache::get('wayfairOrder');
                        Cache::set('wayfairOrder', array_merge((array)$wayfairOrder, (array)$paymentData['orderSaleNew']), 24 * 60 * 60);
                        $sale_amount = 0;

                        // 更新wayfair退款和调整到核心库
                        if ($paymentData['orderWayfairCore']) {
                            $wayfairCoreObj = new FinanceWayfairCoreModel();
                            $wayfairCoreObj->insertAll($paymentData['orderWayfairCore']);
                        }
                    } else {
                        if (!$financeOrderSaleObj->saveAll($paymentData['orderSaleNew'])) {
                            throw new \think\Exception('Payment导入失败！');
                        } else {
                            $productSale = $financeOrderSaleObj->where(['table_id' => $tableId])->sum('product_sales');
                            $shipping = $financeOrderSaleObj->where(['table_id' => $tableId])->sum('shipping_credits');
                            $gift = $financeOrderSaleObj->where(['table_id' => $tableId])->sum('gift_wrap_credits');
                            $regulatory = $financeOrderSaleObj->where(['table_id' => $tableId])->sum('regulatory_fee');
                            $promotional = $financeOrderSaleObj->where(['table_id' => $tableId])->sum('promotional_rebates');
                            $sale_amount = round($productSale + $shipping + $gift + $regulatory + $promotional, 2);
                        }
                    }

                    $financeOrderRefundObj = new FinanceOrderRefundModel();
                    if (!$financeOrderRefundObj->saveAll($paymentData['orderRefundNew'])) {
                        throw new \think\Exception('Payment导入失败！');
                    } else {
                        $productSale = $financeOrderRefundObj->where(['table_id' => $tableId])->sum('product_sales');
                        $shipping = $financeOrderRefundObj->where(['table_id' => $tableId])->sum('shipping_credits');
                        $gift = $financeOrderRefundObj->where(['table_id' => $tableId])->sum('gift_wrap_credits');
                        $regulatory = $financeOrderRefundObj->where(['table_id' => $tableId])->sum('regulatory_fee');
                        $promotional = $financeOrderRefundObj->where(['table_id' => $tableId])->sum('promotional_rebates');
                        $refund_amount = round($productSale + $shipping + $gift + $regulatory + $promotional, 2);
                    }

                    $financeOrderPromotionObj = new FinanceOrderPromotionModel();
                    if (!$financeOrderPromotionObj->saveAll($paymentData['orderPromotionNew'])) {
                        throw new \think\Exception('Payment导入失败！');
                    } else {
                        $promotionSum = $financeOrderPromotionObj->where(['table_id' => $tableId, 'description' => [['like', '%Coupon Redemption Fee%'], ['like', '%Vine Enrollment Fee%'], ['like', '%秒杀%'], ['like', '%促销%'], ['like', '%Lightning Deal%'], 'or']])->sum('total');
                    }

                    $financeOrderShippingServiceObj = new FinanceOrderShippingServiceModel();
                    if (!$financeOrderShippingServiceObj->saveAll($paymentData['orderShippingServiceNew'])) {
                        throw new \think\Exception('Payment导入失败！');
                    } else {
                        $shippingServiceSum = $financeOrderShippingServiceObj->where(['table_id' => $tableId])->sum('total');
                    }

                    $financeOrderLiquidationObj = new FinanceOrderLiquidationModel();
                    if (!$financeOrderLiquidationObj->saveAll($paymentData['orderLiquidationNew'])) {
                        throw new \think\Exception('Payment导入失败！');
                    } else {
                        $liquidationSum = $financeOrderLiquidationObj->where(['table_id' => $tableId])->sum('total');
                    }

                    $financeOrderAdjustmentObj = new FinanceOrderAdjustmentModel();
                    if (!$financeOrderAdjustmentObj->saveAll($paymentData['orderAdjustmentNew'])) {
                        throw new \think\Exception('Payment导入失败！');
                    } else {
                        $adjustmentSum = $financeOrderAdjustmentObj->where(['table_id' => $tableId])->sum('total');
                    }

                    $financeOrderAdjustmentObj = new FinanceOrderFbaInventoryModel();
                    if (!$financeOrderAdjustmentObj->saveAll($paymentData['orderFbaInventory'])) {
                        throw new \think\Exception('Payment导入失败！');
                    }

                    $financeOrderTransferObj = new FinanceOrderTransferModel();
                    if (!$financeOrderTransferObj->saveAll($paymentData['orderTransferNew'])) {
                        throw new \think\Exception('Payment导入失败！');
                    }

                    if (!FinanceTableModel::update(['userAccount' => $paymentData['userAccount'], 'sale_amount' => $sale_amount, 'refund_amount' => $refund_amount, 'promotion' => $promotionSum, 'shipping_service' => $shippingServiceSum, 'liquidation' => $liquidationSum, 'adjustment' => $adjustmentSum], ['id' => $tableId])) {
                        throw new \think\Exception('店铺号同步失败！');
                    }
                } else {
                    throw new \think\Exception('请先选择账单类型！');
                }
            } else {
                throw new \think\Exception('表格导入失败！');
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), Session::get(Config::get('BACK_URL')));
        }
        $this->redirect(Session::get(Config::get('BACK_URL'), 'manage'));
    }

    /**
     * @throws Exception
     */
    public function table_delete()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();

            Db::startTrans();
            try {
                $financeTableObj = new FinanceTableModel();
                $financeTableObj->where('id', $post['id'])->delete();

                $financeOrderSaleObj = new FinanceOrderSaleModel();
                $financeOrderSaleObj->where('table_id', $post['id'])->delete();

                $financeOrderRefundObj = new FinanceOrderRefundModel();
                $financeOrderRefundObj->where('table_id', $post['id'])->delete();

                $financeOrderPromotionObj = new FinanceOrderPromotionModel();
                $financeOrderPromotionObj->where('table_id', $post['id'])->delete();

                $financeOrderShippingServiceObj = new FinanceOrderShippingServiceModel();
                $financeOrderShippingServiceObj->where('table_id', $post['id'])->delete();

                $financeOrderLiquidationObj = new FinanceOrderLiquidationModel();
                $financeOrderLiquidationObj->where('table_id', $post['id'])->delete();

                $financeOrderAdjustmentObj = new FinanceOrderAdjustmentModel();
                $financeOrderAdjustmentObj->where('table_id', $post['id'])->delete();

                $financeOrderAdjustmentObj = new FinanceOrderFbaInventoryModel();
                $financeOrderAdjustmentObj->where('table_id', $post['id'])->delete();

                $financeOrderAdjustmentObj = new FinanceOrderTransferModel();
                $financeOrderAdjustmentObj->where('table_id', $post['id'])->delete();

                $financeWayfairCoreObj = new FinanceWayfairCoreModel();
                $financeWayfairCoreObj->where('table_id', $post['id'])->delete();

                Db::commit();
                echo json_encode(['code' => 1, 'msg' => '删除成功']);
            } catch (Exception $e) {
                Db::rollback();
                echo json_encode(['code' => 0, 'msg' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }

    /**
     * @throws DbException
     */
    public function order(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['payment_id'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $table_id = input('id');
        $where['table_id'] = $table_id;

        $order_type = $this->request->get('order_type');
        if ($order_type) {
            $where['order_type'] = $order_type;
        }
        $this->assign('order_type', $order_type);

        $fulfillment = $this->request->get('fulfillment');
        if ($fulfillment) {
            $where['fulfillment'] = $fulfillment;
        }
        $this->assign('fulfillment', $fulfillment);

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 订单列表
        $order = new FinanceOrderSaleModel();
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'order_type' => $order_type, 'fulfillment' => $fulfillment, 'page_num' => $page_num, 'id' => $table_id]]);
        $this->assign('list', $list);

        return view();
    }

    /**
     * @throws DataNotFoundException
     * @throws \PHPExcel_Writer_Exception
     * @throws \PHPExcel_Exception
     * @throws DbException
     * @throws PHPExcel_Reader_Exception
     * @throws ModelNotFoundException
     */
    public function export()
    {
        $start_time = input('start_time');
        $end_time = input('end_time', date('Y-m-d'));

        if (empty($start_time)) {
            $this->error('缺少开始时间');
        }
        $financeOrderObj = new FinanceOrderSaleModel();
        $orderList = $financeOrderObj->whereBetween('created_date', [$start_time, $end_time])->select();

        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();

        // Set background color
        // A1 - Z1
        for ($s = 65; $s <= 90; $s ++) {
            $objPHPExcel->getActiveSheet()->getStyle(chr($s) . '1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('BDD7EE');
        }

        // Add some data
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '参考单号')
            ->setCellValue('B1', '销售单号')
            ->setCellValue('C1', '系统单号')
            ->setCellValue('D1', '仓库单号')
            ->setCellValue('E1', '仓库代码')
            ->setCellValue('F1', '计费重')
            ->setCellValue('G1', '邮编')
            ->setCellValue('H1', 'Zone')
            ->setCellValue('I1', '出库费')
            ->setCellValue('J1', '基础运费')
            ->setCellValue('K1', 'AHS附加费')
            ->setCellValue('L1', '偏远附加费')
            ->setCellValue('M1', '住宅地址附加费')
            ->setCellValue('N1', 'AHS旺季附加费')
            ->setCellValue('O1', '住宅旺季附加费')
            ->setCellValue('P1', '燃油费')
            ->setCellValue('Q1', '总费用')
            ->setCellValue('R1', '订单创建时间')
        ;

        foreach ($orderList as $k => $item) {
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . ($k + 2), $item['refNo'])
                ->setCellValue('B' . ($k + 2), $item['saleOrderCode'])
                ->setCellValue('C' . ($k + 2), $item['sysOrderCode'])
                ->setCellValue('D' . ($k + 2), $item['warehouseOrderCode'])
                ->setCellValue('E' . ($k + 2), $item['warehouseCode'])
                ->setCellValue('F' . ($k + 2), $item['charged_weight'])
                ->setCellValue('G' . ($k + 2), $item['postalFormat'])
                ->setCellValue('H' . ($k + 2), $item['zoneFormat'])
                ->setCellValue('I' . ($k + 2), $item['outbound'])
                ->setCellValue('J' . ($k + 2), $item['base'])
                ->setCellValue('K' . ($k + 2), $item['ahs'])
                ->setCellValue('L' . ($k + 2), $item['das'])
                ->setCellValue('M' . ($k + 2), $item['rdcFee'])
                ->setCellValue('N' . ($k + 2), $item['ahsds'])
                ->setCellValue('O' . ($k + 2), $item['drdcFee'])
                ->setCellValue('P' . ($k + 2), $item['fuelCost'])
                ->setCellValue('Q' . ($k + 2), $item['calcuRes'])
                ->setCellValue('R' . ($k + 2), $item['created_date'])
            ;
        }

        // Rename sheet
        $objPHPExcel->getActiveSheet()->setTitle('尾程费用');

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);

        // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        $filename = date("YmdHis") . time() . mt_rand(100000, 999999);
        ob_end_clean();
        header('Content-Disposition:attachment;filename="'.$filename.'.xls"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }

    /**
     * @throws DbException
     */
    public function outbound($id): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['payment_id|saleOrderCode|id|warehouse_sku'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 订单列表
        $order = new FinanceOrderOutboundModel();
        $where['report_id'] = $id;
        $list = $order->with(['store', 'saleOrderCode'])->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'page_num' => $page_num]]);
        $this->assign('list', $list);

        return view();
    }

    /**
     * @throws DbException
     */
    public function store($id): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['export_no|sku|content'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 订单列表
        $order = new FinanceStoreModel();
        $where['report_id'] = $id;
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);
        $this->assign('report_id', $id);

        $this->assign('available_qty', $order->where($where)->sum('available_quantity'));
        $sum = $order->query('
SELECT SUM(available_quantity * sku_ddp_unit) sum FROM mu_finance_store WHERE report_id = ' . $id . ';
        ');
        $this->assign('available_sum', $sum[0]['sum']);

        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function store_import()
    {
        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';

        $filename = input('filename');
        $report_id = input('id');
        $file= "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();
        unset($data[0]);
        array_pop($data);

        Db::startTrans();
        try {
            $storeData = [];
            foreach ($data as $item) {
                if (!empty($item[0])) {
                    $storeData[] = [
                        'report_id'                 =>  $report_id,
                        'entering_date'             =>  date('Ymd', strtotime($item[0])),
                        'currency'                  =>  $item[1],
                        'quantity_amount'           =>  $item[2],
                        'purchase_amount'           =>  $item[3],
                        'cost_amount'               =>  $item[4],
                        'arriving_date'             =>  date('Ymd', strtotime($item[5])),
                        'export_no'                 =>  $item[6],
                        'shipment_date'             =>  date('Ymd', strtotime($item[7])),
                        'sku'                       =>  $item[8],
                        'cn_name'                   =>  $item[9],
                        'entering_quantity'         =>  $item[10],
                        'sku_purchase_unit'         =>  $item[11],
                        'sku_purchase_amount'       =>  $item[12],
                        'sku_ddp_unit'              =>  $item[13],
                        'sku_ddp_amount'            =>  $item[14],
                        'outbound_quantity'         =>  $item[15],
                        'available_quantity'        =>  $item[16],
                        'seller'                    =>  $item[17],
                        'purchaser'                 =>  $item[18],
                        'content'                   =>  $item[19],
                        'created_date'              =>  date('Y-m-d H:i:s')
                    ];
                }
            }
            $financeStoreObj = new FinanceStoreModel();
            if($financeStoreObj->insertAll($storeData)) {
                $sql = "
SELECT DISTINCT
	a.report_id,
	b.payment_id,
	b.saleOrderCode,
	b.shipping_time,
	b.platform_sku seller_sku,
	b.warehouse_sku,
	a.fulfillment,
	b.qty 
FROM
	mu_finance_order_sale a
	LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id 
WHERE
	a.report_id = " . $report_id . "
	AND b.saleOrderCode IS NOT NULL
ORDER BY
	b.shipping_time
                ";
                $outboundData = $financeStoreObj->query($sql);
                $outboundObj = new FinanceOrderOutboundModel();
                $outboundObj->insertAll($outboundData);
                echo 'success';
            } else {
                throw new \think\Exception('表格导入失败！');
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), Session::get(Config::get('BACK_URL')));
        }
        $this->redirect(Session::get(Config::get('BACK_URL'), 'manage'));
    }

    public function store_empty()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $reportId = $post['id'];

            Db::startTrans();
            try {
                $storeObj = new FinanceStoreModel();
                $storeObj->where('report_id', $reportId)->delete();

                $outboundObj = new FinanceOrderOutboundModel();
                $outboundObj->where('report_id', $reportId)->delete();

                $shareObj = new FinanceOrderShareModel();
                $shareObj->where('report_id', $reportId)->delete();

                $warehouseObj = new FinanceWarehouseModel();
                $warehouseObj->where('report_id' , $reportId)->update(['is_sale' => 0]);

                $refundObj = new FinanceOrderRefundModel();
                $refundObj->where(['report_id' => $reportId])->update(['share_code' => null]);

                $shippingObj = new FinanceOrderShippingServiceModel();
                $shippingObj->where(['report_id' => $reportId])->update(['share_code' => null]);

                $adjustmentObj = new FinanceOrderAdjustmentModel();
                $adjustmentObj->where(['report_id' => $reportId])->update(['share_code' => null]);

                $liquidationObj = new FinanceOrderLiquidationModel();
                $liquidationObj->where(['report_id' => $reportId])->update(['share_code' => null]);

                $promotionObj = new FinanceOrderAdditionalModel();
                $promotionObj->where(['report_id' => $reportId])->where('promotion', 'not null')->update(['share_code' => null]);
                $promotionObj->where(['report_id' => $reportId])->where('lc_adjustment', 'not null')->update(['share_code' => null]);
                $promotionObj->where(['report_id' => $reportId])->where('le_adjustment', 'not null')->update(['share_code' => null]);

                $financeReportObj = new FinanceReportModel();
                $financeReportObj->save(['is_notify' => 0], ['id' => $reportId]);

                Db::commit();
                echo json_encode(['code' => 1, 'msg' => '清空完成']);
            } catch (\SoapFault $e) {
                Db::rollback();
                echo json_encode(['code' => 0, 'msg' => '清空失败，请重试']);
            } catch (\Exception $e) {
                Db::rollback();
                echo json_encode(['code' => 0, 'msg' => '清空失败，请重试']);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }

    /**
     * @throws DbException
     */
    public function order_statistics(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['payment_id|saleOrderCode|warehouse_sku|warehouse_no|service_no|shipping_no|inventory_batch_no'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 订单列表
        $order = new FinanceOrderStatisticsModel();
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');

        return view();
    }

    /**
     * @throws DbException
     */
    public function order_statistics_edit($id)
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $dataValidate = new FinanceOrderStatisticsValidate();
            if ($dataValidate->scene('edit')->check($post)) {
                $model = new FinanceOrderStatisticsModel();
                if ($model->allowField(true)->save($post, ['id' => $id])) {
                    echo json_encode(['code' => 1, 'msg' => '修改成功']);
                    exit;
                } else {
                    echo json_encode(['code' => 0, 'msg' => '修改失败，请重试']);
                    exit;
                }
            } else {
                echo json_encode(['code' => 0, 'msg' => $dataValidate->getError()]);
                exit;
            }
        } else {
            $info = FinanceOrderStatisticsModel::get(['id' => $id,]);
            $this->assign('info', $info);

            return view();
        }
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function order_statistics_import()
    {
        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';

        $filename = input('filename');
        $file= "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();
        unset($data[0]);
        unset($data[1]);
        unset($data[2]);

        Db::startTrans();
        try {
            $orderData = [];
            $financeOrderStatisticsObj = new FinanceOrderStatisticsModel();
            foreach ($data as $item) {
                $order = $financeOrderStatisticsObj->where(['saleOrderCode' => $item[11]])->find();
                if (!empty($order)) {
                    continue;
                }
                $orderData[] = [
                    "platform"              =>  $item[0],
                    "user_account"          =>  $item[1],
                    "user_account_alias"    =>  $item[2],
                    "site"                  =>  $item[3],
                    "warehouse"             =>  $item[4],
                    "created_time"          =>  date('Y-m-d H:i:s', strtotime($item[5])),
                    "paid_time"             =>  date('Y-m-d H:i:s', strtotime($item[6])),
                    "audit_time"            =>  date('Y-m-d H:i:s', strtotime($item[7])),
                    "shipping_time"         =>  date('Y-m-d H:i:s', strtotime($item[8])),
                    "order_status"          =>  $item[9],
                    "order_type"            =>  $item[10],
                    "saleOrderCode"         =>  trim($item[11]),
                    "payment_id"            =>  trim($item[12]),
                    "platform_sku"          =>  trim($item[13]),
                    "seller_sku"            =>  trim($item[14]),
                    "warehouse_sku"         =>  trim($item[15]),
                    "qty"                   =>  $item[16],
                    "product_name"          =>  $item[17],
                    "product_style"         =>  $item[18],
                    "product_brand"         =>  $item[19],
                    "category_1"            =>  $item[20],
                    "category_2"            =>  $item[21],
                    "category_3"            =>  $item[22],
                    "product_status"        =>  $item[23],
                    "warehouse_no"          =>  $item[24],
                    "service_no"            =>  $item[25],
                    "order_delisting_type"  =>  $item[26],
                    "is_shipping"           =>  $item[27],
                    "shipping_no"           =>  $item[28],
                    "sys_transaction"       =>  $item[29],
                    "shipping_method"       =>  $item[30],
                    "product_weight"        =>  $item[31],
                    "inventory_batch_no"    =>  $item[32],
                    "currency"              =>  $item[33],
                    "sale_unit"             =>  $item[35],
                    "sale_amount"           =>  $item[36],
                    "sale_shipping"         =>  $item[38],
                    "selling_fee"           =>  $item[40],
                    "fba_fee"               =>  $item[43],
                    "tax"                   =>  $item[45]
                ];
            }
            $financeOrderStatisticsObj->insertAll($orderData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('order_statistics'));
        }
        $this->redirect(url('order_statistics'));
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    public function order_statistics_edit_auto()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $reportId = $post['id'];

            $editObj = new FinanceOrderStatisticsEditModel();
            $dataSelling = $editObj->query('
SELECT
    ' . $reportId . ' AS report_id,
    "SELLING_FEE" AS type,
	a.*,
	b.id order_statistics_id,
	b.saleOrderCode,
	b.sale_amount,
	b.selling_fee,
	b.fba_fee,
	b.seller_sku,
	b.warehouse_sku 
FROM
	(
	SELECT DISTINCT
		order_statistic_user_account,
		payment,
		payment_sale_amount,
		payment_selling_fees,
		payment_fba_fees 
	FROM
		(
		SELECT
			a.payment_id order_statistic_payment,
			a.userAccount order_statistic_user_account,
			ROUND( SUM( b.sale_amount ), 7 ) order_statistic_sale_amount,
			ROUND( SUM( b.selling_fee ), 7 ) order_statistic_selling_fees,
			ROUND( SUM( b.fba_fee ), 7 ) order_statistic_fba_fees 
		FROM
			(
			SELECT DISTINCT
				report_id,
				payment_id,
				platform,
				userAccount 
			FROM
				mu_finance_order_sale a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id 
			WHERE
				report_id = ' . $reportId . ' 
			) a
			LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id 
		GROUP BY
			order_statistic_payment,
			order_statistic_user_account 
		) a
		LEFT JOIN (
		SELECT
			payment_id payment,
			SUM( product_sales + shipping_credits + gift_wrap_credits + regulatory_fee + promotional_rebates ) payment_sale_amount,
			SUM( selling_fees ) payment_selling_fees,
			SUM( fba_fees ) payment_fba_fees 
		FROM
			mu_finance_order_sale
        WHERE
            report_id = ' . $reportId . ' 
		GROUP BY
			payment 
		) b ON a.order_statistic_payment = b.payment 
	WHERE
		a.order_statistic_selling_fees != b.payment_selling_fees * - 1
	) a
	LEFT JOIN mu_finance_order_statistics b ON a.payment = b.payment_id;
            ');

            $dataFba = $editObj->query('
SELECT
	' . $reportId . ' AS report_id,
	"FBA_FEE" AS type,
	a.*,
	b.id order_statistics_id,
	b.saleOrderCode,
	b.sale_amount,
	b.selling_fee,
	b.fba_fee,
	b.seller_sku,
	b.warehouse_sku 
FROM
	(
	SELECT DISTINCT
		order_statistic_user_account,
		payment,
		payment_sale_amount,
		payment_selling_fees,
		payment_fba_fees 
	FROM
		(
		SELECT
			a.payment_id order_statistic_payment,
			a.userAccount order_statistic_user_account,
			ROUND( SUM( b.sale_amount ), 7 ) order_statistic_sale_amount,
			ROUND( SUM( b.selling_fee ), 7 ) order_statistic_selling_fees,
			ROUND( SUM( b.fba_fee ), 7 ) order_statistic_fba_fees 
		FROM
			(
			SELECT DISTINCT
				report_id,
				payment_id,
				platform,
				userAccount 
			FROM
				mu_finance_order_sale a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id 
			WHERE
				report_id = ' . $reportId . '
				AND b.platform = "amazon"
			) a
			LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id 
		GROUP BY
			order_statistic_payment,
			order_statistic_user_account 
		) a
		LEFT JOIN (
		SELECT
			payment_id payment,
			SUM( product_sales + shipping_credits + gift_wrap_credits + regulatory_fee + promotional_rebates ) payment_sale_amount,
			SUM( selling_fees ) payment_selling_fees,
			SUM( fba_fees ) payment_fba_fees 
		FROM
			mu_finance_order_sale
		GROUP BY
			payment 
		) b ON a.order_statistic_payment = b.payment 
	WHERE
		a.order_statistic_fba_fees != b.payment_fba_fees * -1
	) a
	LEFT JOIN mu_finance_order_statistics b ON a.payment = b.payment_id
            ');
            if ($editObj->insertAll($dataSelling) && $editObj->insertAll($dataFba)) {
                echo json_encode(['code' => 1, 'msg' => '操作完成']);
            } else {
                echo json_encode(['code' => 0, 'msg' => '操作失败，请重试']);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }

    /**
     * @throws DbException
     */
    public function warehouse($id): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['warehouse_no|date|sku|warehouse_code'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 订单列表
        $order = new FinanceWarehouseModel();
        $where['report_id'] = $id;
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);
        $this->assign('report_id', $id);

        $sum = $order->where($where)->sum('total');
        $this->assign('sum', $sum);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function warehouse_import()
    {
        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';

        $filename = input('filename');
        $report_id = input('id');
        $file= "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();

        Db::startTrans();
        try {
            $warehouseData = [];
            $financeWarehouseObj = new FinanceWarehouseModel();
            foreach ($data as $item) {
                if ($item[0] != "仓租") {
                    continue;
                }
                $warehouseData[] = [
                    "report_id"             =>  $report_id,
                    "date"                  =>  $item[1],
                    "sku"                   =>  $item[2],
                    "warehouse_code"        =>  $item[3],
                    "product_length"        =>  $item[4],
                    "product_width"         =>  $item[5],
                    "product_height"        =>  $item[6],
                    "quantity"              =>  $item[7],
                    "age"                   =>  $item[8],
                    "volume"                =>  $item[9],
                    "total"                 =>  $item[10],
                    "total_unit"            =>  $item[11]
                ];
            }
            $financeWarehouseObj->insertAll($warehouseData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('warehouse', ['id' => $report_id]));
        }
        $this->redirect(url('warehouse', ['id' => $report_id]));
    }

    public function warehouse_empty()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $reportId = $post['id'];
            $warehouseObj = new FinanceWarehouseModel();
            if ($warehouseObj->where('report_id', $reportId)->delete()) {
                $financeReportObj = new FinanceReportModel();
                $financeReportObj->save(['is_notify' => 0], ['id' => $reportId]);

                echo json_encode(['code' => 1, 'msg' => '清空完成']);
            } else {
                echo json_encode(['code' => 0, 'msg' => '清空失败，请重试']);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }

    /**
     * @throws DbException
     */
    public function warehouse_edit($id)
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $model = new FinanceWarehouseModel();
            $info = $model->find($id);
            if ($info['is_sale'] != 0) {
                echo json_encode(['code' => 0, 'msg' => '有销售的sku无法修改']);
            }
            if ($model->update(['main_sku' => $post['new_sku']], ['report_id' => $info['report_id'], 'sku' => $info['sku'], 'main_sku' => $info['main_sku']])) {
                echo json_encode(['code' => 1, 'msg' => '修改成功']);
            } else {
                echo json_encode(['code' => 0, 'msg' => '修改失败，请重试']);
            }
        } else {
            $info = FinanceWarehouseModel::get(['id' => $id,]);
            $this->assign('info', $info);

            return view();
        }
    }

    /**
     * @throws DbException
     */
    public function warehouse_wfs($id): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['vendor_sku'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        $order = new FinanceWarehouseWFSModel();
        $where['report_id'] = $id;
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);
        $this->assign('total', $order->where($where)->sum('total'));

        $this->assign('report_id', $id);
        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function warehouse_wfs_import()
    {
        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';

        $filename = input('filename');
        $report_id = input('id');
        $user_account = input('user_account');
        $file= "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();
        unset($data[0]);

        Db::startTrans();
        try {
            $wfs = [];
            $warehouseWFSObj = new FinanceWarehouseWFSModel();
            foreach ($data as $key => $item) {
                if ($key <= 3) {
                    continue;
                }
                $wfs[] = [
                    "report_id"                 =>  $report_id,
                    "user_account"              =>  $user_account,
                    "partner_gtin"              =>  $item[0],
                    "vendor_sku"                =>  $item[1],
                    "walmart_item_id"           =>  $item[2],
                    "item_name"                 =>  $item[3],
                    "length"                    =>  $item[4],
                    "width"                     =>  $item[5],
                    "height"                    =>  $item[6],
                    "volume"                    =>  $item[7],
                    "weight"                    =>  $item[8],
                    "standard_daily_storage"    =>  $item[9],
                    "peak_daily_storage"        =>  $item[10],
                    "long_term_daily_storage"   =>  $item[11],
                    "average"                   =>  $item[12],
                    "ending"                    =>  $item[13],
                    "total"                     =>  $item[14]
                ];
            }

            $warehouseWFSObj->insertAll($wfs);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), session('back_url', '', 'manage'));
        }
        $this->redirect(session('back_url', '', 'manage'));
    }

    public function warehouse_wfs_empty()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $reportId = $post['id'];
            $warehouseWFSObj = new FinanceWarehouseWFSModel();
            if ($warehouseWFSObj->where('report_id', $reportId)->delete()) {
                echo json_encode(['code' => 1, 'msg' => '清空完成']);
            } else {
                echo json_encode(['code' => 0, 'msg' => '清空失败，请重试']);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }

    /**
     * @throws DbException
     */
    public function additional($id): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['warehouse_sku|user_account'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 订单列表
        $order = new FinanceOrderAdditionalModel();
        $where['report_id'] = $id;
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);
        $this->assign('report_id', $id);

        $this->assign('adjustment', $order->where($where)->sum('claimant'));
        $this->assign('liquidation', $order->where($where)->sum('liquidation'));
        $this->assign('promotion', $order->where($where)->sum('promotion'));
        $this->assign('shipping_service', $order->where($where)->sum('shipping_service'));
        $this->assign('lc_adjustment', $order->where($where)->sum('lc_adjustment'));
        $this->assign('le_adjustment', $order->where($where)->sum('le_adjustment'));
        $this->assign('wfs_adjustment', $order->where($where)->sum('wfs_adjustment'));

        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function additional_import()
    {
        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';

        $filename = input('filename');
        $report_id = input('id');
        $file= "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();

        Db::startTrans();
        try {
            $additionalData = [];
            $financeAdditionalObj = new FinanceOrderAdditionalModel();
            foreach ($data as $key => $item) {
                if ($key == 0 || $key == 1) {
                    continue;
                }
                $additionalData[] = [
                    "report_id"             =>  $report_id,
                    "platform"              =>  $item[0],
                    "user_account"          =>  $item[1],
                    "warehouse_sku"         =>  $item[2],
                    "claimant"              =>  $item[3],
                    "liquidation"           =>  $item[4],
                    "promotion"             =>  $item[5],
                    "shipping_service"      =>  $item[6],
                    "lc_adjustment"         =>  $item[7],
                    "le_adjustment"         =>  $item[8],
                    "wfs_adjustment"        =>  $item[9]
                ];
            }
            $financeAdditionalObj->insertAll($additionalData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('additional'));
        }
        $this->redirect(url('additional', ['id' => $report_id]));
    }

    public function additional_empty()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $reportId = $post['id'];
            $additionalObj = new FinanceOrderAdditionalModel();
            if ($additionalObj->where('report_id', $reportId)->delete()) {
                $promotionObj = new FinanceOrderAdditionalModel();
                $promotionObj->where(['report_id' => $reportId])->where('promotion', 'not null')->update(['share_code' => null]);
                $promotionObj->where(['report_id' => $reportId])->where('lc_adjustment', 'not null')->update(['share_code' => null]);
                $promotionObj->where(['report_id' => $reportId])->where('le_adjustment', 'not null')->update(['share_code' => null]);

                $financeReportObj = new FinanceReportModel();
                $financeReportObj->save(['is_notify' => 0], ['id' => $reportId]);

                echo json_encode(['code' => 1, 'msg' => '清空完成']);
            } else {
                echo json_encode(['code' => 0, 'msg' => '清空失败，请重试']);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }

    /**
     * @throws DbException
     */
    public function evaluation($id): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['saleOrderCode|warehouse_sku'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 列表
        $order = new FinanceEvaluationModel();
        $where['report_id'] = $id;
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);
        $this->assign('report_id', $id);

        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function evaluation_import()
    {
        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';

        $filename = input('filename');
        $report_id = input('id');
        $file= "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();

        Db::startTrans();
        try {
            $additionalData = [];
            $financeAdditionalObj = new FinanceEvaluationModel();
            foreach ($data as $key => $item) {
                if ($key == 0 || $key == 1) {
                    continue;
                }
                $additionalData[] = [
                    "report_id"                 =>  $report_id,
                    "payment"                   =>  $item[1],
                    "warehouse_sku"             =>  $item[2],
                    "usd_sale_amount"           =>  currencyToNumber($item[3]),
                    "usd_paid_amount"           =>  currencyToNumber($item[4]),
                    "cny_actual_paid"           =>  currencyToNumber($item[5]),
                    "usd_actual_paid"           =>  currencyToNumber($item[6]),
                    "seller"                    =>  $item[7],
                    "content"                   =>  $item[8],
                    "date"                      =>  date('Ymd', strtotime($item[0])),
                ];
            }
            $financeAdditionalObj->insertAll($additionalData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('evaluation'));
        }
        $this->redirect(url('evaluation', ['id' => $report_id]));
    }

    public function evaluation_empty()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $reportId = $post['id'];
            $evaluationObj = new FinanceEvaluationModel();
            if ($evaluationObj->where('report_id', $reportId)->delete()) {
                $financeReportObj = new FinanceReportModel();
                $financeReportObj->save(['is_notify' => 0], ['id' => $reportId]);

                echo json_encode(['code' => 1, 'msg' => '清空完成']);
            } else {
                echo json_encode(['code' => 0, 'msg' => '清空失败，请重试']);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }

    /**
     * @throws DbException
     */
    public function ad_cost($id): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['platform|user_account|warehouse_sku'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 列表
        $order = new FinanceAdCostModel();
        $where['report_id'] = $id;
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);
        $this->assign('ad_cost', $order->where($where)->sum('total'));
        $this->assign('report_id', $id);

        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function ad_cost_import()
    {
        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';

        $filename = input('filename');
        $report_id = input('id');
        $file= "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();
        unset($data[0]);

        Db::startTrans();
        try {
            $adCostData = [];
            $financeAdCostObj = new FinanceAdCostModel();
            foreach ($data as $item) {
                $adCostData[] = [
                    "report_id"                 =>  $report_id,
                    "platform"                  =>  $item[0],
                    "user_account"              =>  $item[1],
                    "warehouse_sku"             =>  $item[2],
                    "total"                     =>  $item[3]
                ];
            }
            $financeAdCostObj->insertAll($adCostData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('ad_cost'));
        }
        $this->redirect(url('ad_cost', ['id' => $report_id]));
    }

    public function ad_cost_empty()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $reportId = $post['id'];
            $adCostObj = new FinanceAdCostModel();
            if ($adCostObj->where('report_id', $reportId)->delete()) {
                $financeReportObj = new FinanceReportModel();
                $financeReportObj->save(['is_notify' => 0], ['id' => $reportId]);

                echo json_encode(['code' => 1, 'msg' => '清空完成']);
            } else {
                echo json_encode(['code' => 0, 'msg' => '清空失败，请重试']);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }

    /**
     * @throws DbException
     */
    public function share($id): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['share_code|payment|seller_sku|warehouse_sku'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 列表
        $order = new FinanceOrderShareModel();
        $where['report_id'] = $id;
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);
        $this->assign('report_id', $id);

        return view();
    }

    /**
     * @throws DbException
     */
    public function wayfair_core(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['invoice_no|payment_id|user_account|calculate_month'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // wayfair订单列表
        $order = new FinanceWayfairCoreModel();
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);

        $this->assign('amount', $order->where($where)->sum('sale_amount'));
        $this->assign('commission', $order->where($where)->sum('commission'));
        $this->assign('collection', $order->where($where)->sum('collection'));

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');

        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function wayfair_import()
    {
        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';

        $filename = input('filename');
        $file= "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();
        unset($data[0]);

        Db::startTrans();
        try {
            $orderData = [];
            $financeWayfairCoreObj = new FinanceWayfairCoreModel();
            foreach ($data as $item) {
                $order = $financeWayfairCoreObj->where(['payment_id' => $item[2]])->find();
                if (!empty($order)) {
                    continue;
                }
                $orderData[] = [
                    "invoice_no"                =>  $item[0],
                    "invoice_date"              =>  date('Y-m-d H:i:s', strtotime($item[1])),
                    "payment_id"                =>  $item[2],
                    "sale_amount"               =>  $item[4],
                    "status"                    =>  FinanceWayfairCoreModel::formatExcelStatus($item[5]),
                    "currency"                  =>  $item[6],
                    "commission_rate"           =>  0.04,
                    "commission"                =>  $item[4] * 0.04,
                    "collection"                =>  $item[4] * 0.96,
                    "calculate_month"           =>  $item[12],
                    "user_account"              =>  FinanceWayfairCoreModel::formatExcelUserAccount($item[15])
                ];
            }
            $financeWayfairCoreObj->insertAll($orderData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('wayfair_core'));
        }
        $this->redirect(url('wayfair_core'));
    }

    /**
     * @throws DbException
     */
    public function wayfair_core_edit($id)
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $dataValidate = new FinanceWayfairCoreValidate();
            if ($dataValidate->scene('edit')->check($post)) {
                $model = new FinanceWayfairCoreModel();
                if ($model->allowField(true)->save($post, ['id' => $id])) {
                    echo json_encode(['code' => 1, 'msg' => '修改成功']);
                    exit;
                } else {
                    echo json_encode(['code' => 0, 'msg' => '修改失败，请重试']);
                    exit;
                }
            } else {
                echo json_encode(['code' => 0, 'msg' => $dataValidate->getError()]);
                exit;
            }
        } else {
            $info = FinanceWayfairCoreModel::get(['id' => $id,]);
            $this->assign('info', $info);

            return view();
        }
    }

    /**
     * @throws DbException
     */
    public function relation($id): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['seller_sku|warehouse_sku|product_name|user_account|seller'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 列表
        $order = new FinanceSkuRelationModel();
        $where['report_id'] = $id;
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);
        $this->assign('report_id', $id);

        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function relation_import()
    {
        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';

        $filename = input('filename');
        $report_id = input('id');
        $file= "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();
        unset($data[0]);

        Db::startTrans();
        try {
            $adCostData = [];
            $financeSkuRelationObj = new FinanceSkuRelationModel();
            foreach ($data as $item) {
                $adCostData[] = [
                    "report_id"                 =>  $report_id,
                    "seller_sku"                =>  $item[0],
                    "warehouse_sku"             =>  $item[1],
                    "qty"                       =>  $item[2],
                    "unit_price"                =>  $item[3],
                    "product_name"              =>  $item[4],
                    "percent"                   =>  $item[5],
                    "warehouse_name"            =>  $item[6],
                    "user_account"              =>  $item[7],
                    "created_user"              =>  $item[8],
                    "created_date"              =>  $item[9],
                    "updated_date"              =>  $item[10],
                    "seller"                    =>  $item[11]
                ];
            }
            $financeSkuRelationObj->insertAll($adCostData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('relation'));
        }
        $this->redirect(url('relation', ['id' => $report_id]));
    }

    public function relation_empty()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $reportId = $post['id'];
            $skuRelationObj = new FinanceSkuRelationModel();
            if ($skuRelationObj->where('report_id', $reportId)->delete()) {
                $financeReportObj = new FinanceReportModel();
                $financeReportObj->save(['is_notify' => 0], ['id' => $reportId]);

                echo json_encode(['code' => 1, 'msg' => '清空完成']);
            } else {
                echo json_encode(['code' => 0, 'msg' => '清空失败，请重试']);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }
}
