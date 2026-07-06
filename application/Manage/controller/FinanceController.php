<?php
namespace app\Manage\controller;

use app\Manage\command\AkAdCost;
use app\Manage\model\AkAdCostCreateModel;
use app\Manage\model\AkAdCostModel;
use app\Manage\model\AmazonPayment;
use app\Manage\model\FinanceAdCostModel;
use app\Manage\model\FinanceEvaluationModel;
use app\Manage\model\FinanceExcelInit;
use app\Manage\model\FinanceObsoleteSkuPercentModel;
use app\Manage\model\FinanceOperationDeliveryModel;
use app\Manage\model\FinanceOperationExpensesModel;
use app\Manage\model\FinanceOperationFactoryClaimModel;
use app\Manage\model\FinanceOperationFactoryModel;
use app\Manage\model\FinanceOrderAdditionalModel;
use app\Manage\model\FinanceOrderAdjustmentModel;
use app\Manage\model\FinanceOrderAdjustmentWfsModel;
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
use app\Manage\model\FinanceOrderSubscriptionModel;
use app\Manage\model\FinanceOrderTemuDetailModel;
use app\Manage\model\FinanceOrderTransferModel;
use app\Manage\model\FinanceOrderWayfairModel;
use app\Manage\model\FinanceProvisionModel;
use app\Manage\model\FinanceReportModel;
use app\Manage\model\FinanceReportSnapshotModel;
use app\Manage\model\FinanceSkuRelationModel;
use app\Manage\model\FinanceStoreModel;
use app\Manage\model\FinanceTableModel;
use app\Manage\model\FinanceWarehouseFbmModel;
use app\Manage\model\FinanceWarehouseModel;
use app\Manage\model\FinanceWarehouseWFSModel;
use app\Manage\model\FinanceWayfairCoreModel;
use app\Manage\model\FinanceWildberriesFeeModel;
use app\Manage\model\FinanceWildberriesOrderModel;
use app\Manage\model\FinanceWildberriesOrderNotifyModel;
use app\Manage\model\FinanceWildberriesShippingModel;
use app\Manage\model\ProductModel;
use app\Manage\validate\FinanceOrderStatisticsValidate;
use app\Manage\validate\FinanceRelationValidate;
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
        $where['id'] = ['egt', 6];
        $order = new FinanceReportModel();
        $list = $order->where($where)->order('id desc')->paginate(Config::get('PAGE_NUM'), false, ['query' => ['keyword' => $keyword]]);
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
     */
    public function report_operation()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $report = FinanceReportModel::get($post['id']);
            FinanceReportModel::update(['is_operation' => 0], ['id' => ['gt', 0]]);
            $is_operation = $report['is_operation'] == 1 ? 0 : 1;
            FinanceReportModel::update(['is_operation' => $is_operation], ['id' => $report['id']]);
            echo json_encode(['code' => 1, 'msg' => '操作成功']);
            exit;
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
            exit;
        }
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     * @throws \PHPExcel_Exception
     */
    public function report_export_1()
    {
        $report_id = input('id');
        $month = input('month');
        $monthInt = date('Ym', strtotime($month . '-01'));
        $lastMonthInt = date('Ym', strtotime('-1 month', strtotime($month . '-01')));

        $financeReportObj = new FinanceReportModel();
        $report = $financeReportObj->find($report_id);
        if (empty($report)) {
            $this->error('异常操作！', url('report'));
        }

        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();
        $financeExcelInit = new FinanceExcelInit($objPHPExcel);
        $financeExcelInit->generateSaleRefundSheet(0, $report_id);
        $financeExcelInit->generatePaymentNoOutboundSheet(1, $report_id);
        $financeExcelInit->generateOrderResendSheet(2, $report_id, $report['month']);
        $financeExcelInit->getOutboundAccountingByReport(3, $report);
        $financeExcelInit->getFinanceOutboundByReport(4, $report);
        $financeExcelInit->generateFbmSheet(5, $report_id, $report['month']);
        $financeExcelInit->generateFbaSheet(6, $report_id, $report['month']);
        $financeExcelInit->generateWalmartSheet(7, $report_id, $report['month']);
        $financeExcelInit->generateWayfairSheet(8, $report_id, $report['month']);
        $financeExcelInit->generateSheinSheet(9, $report_id, $report['month']);
        $financeExcelInit->generateTemuSheet(10, $report_id, $report['month']);
        $financeExcelInit->generateEbaySheet(11, $report_id, $report['month']);
        $financeExcelInit->generateTiktokWarehouseSkuSql(12, $report, $report['month']);
        $financeExcelInit->getHomeDepotWarehouseSkuSql(13, $report, $report['month']);
        $objPHPExcel = $financeExcelInit->excelSheetSet();

        // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        $filename = $monthInt . '_1_' . date("YmdHis") . time() . mt_rand(100000, 999999);
        ob_end_clean();
        header('Content-Disposition:attachment;filename="'.$filename.'.xls"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     * @throws \PHPExcel_Exception
     */
    public function report_export_2()
    {
        $report_id = input('id');
        $month = input('month');
        $monthInt = date('Ym', strtotime($month . '-01'));
        $lastMonthInt = date('Ym', strtotime('-1 month', strtotime($month . '-01')));

        $financeReportObj = new FinanceReportModel();
        $report = $financeReportObj->find($report_id);
        if (empty($report)) {
            $this->error('异常操作！', url('report'));
        }

        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();
        $financeExcelInit = new FinanceExcelInit($objPHPExcel);;
        $financeExcelInit->initWildberriesDirectory(0);
        $financeExcelInit->getWildberriesOrderSql(1, $report);
        $financeExcelInit->getWildberriesWarehouseSkuSql(2, $report);
        $financeExcelInit->getWildberriesCostSql(3);
        $financeExcelInit->getWildberriesMonthCostSql(4, $lastMonthInt);
        $financeExcelInit->getWildberriesMonthCostAccountingSql(5, $monthInt);
        $financeExcelInit->getWildberriesMonthAccrualSql(6, $lastMonthInt);
        $financeExcelInit->getWildberriesExpressDeliverySql(7, $monthInt);
        $financeExcelInit->getWildberriesCostReturnSql(8, $monthInt);
        $objPHPExcel = $financeExcelInit->excelSheetSet();

        // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        $filename = $monthInt . '_2_' . date("YmdHis") . time() . mt_rand(100000, 999999);
        ob_end_clean();
        header('Content-Disposition:attachment;filename="'.$filename.'.xls"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     * @throws \PHPExcel_Exception
     */
    public function report_export_3()
    {
        $report_id = input('id');
        $month = input('month');
        $monthInt = date('Ym', strtotime($month . '-01'));
        $lastMonthInt = date('Ym', strtotime('-1 month', strtotime($month . '-01')));

        $financeReportObj = new FinanceReportModel();
        $report = $financeReportObj->find($report_id);
        if (empty($report)) {
            $this->error('异常操作！', url('report'));
        }

        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();
        $financeExcelInit = new FinanceExcelInit($objPHPExcel);
        $financeExcelInit->generateAccountTransferSheet(0, $report_id);
        $financeExcelInit->generateAccountSubscriptionSheet(1, $report_id);
        $financeExcelInit->generateOrderWayfairSheet(2, $report_id);
        $financeExcelInit->generateOperationExpensesSheet(3, $report_id);
        $financeExcelInit->generateOperationFactorySheet(4, $report_id);
        $financeExcelInit->generateOperationDeliverySheet(5, $report_id);
        $financeExcelInit->generateOperationFactoryClaimSheet(6, $monthInt);
        $objPHPExcel = $financeExcelInit->excelSheetSet();

        // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        $filename = $monthInt . '_3_' . date("YmdHis") . time() . mt_rand(100000, 999999);
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
        $this->assign('refund_other', $order->where($where)->sum('refund_other'));
        $this->assign('selling_fees', $order->where($where)->sum('selling_fees'));
        $this->assign('fba_fees', $order->where($where)->sum('fba_fees'));
        $this->assign('shipping_service', $order->where($where)->sum('shipping_service'));
        $this->assign('adjustment', $order->where($where)->sum('adjustment'));

        $editObj = new FinanceOrderStatisticsEditModel();
        $this->assign('edit', $editObj->where(['is_finished' => 0])->count());

        $this->assign('report_id', $id);
        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws DbException
     * @throws Exception
     */
    public function index_no_outbound($id): \think\response\View
    {
        $model = new FinanceReportModel();
        $paymentNoOutbound = $model->query(FinanceReportModel::getPaymentNoOutboundSql($id));
        $this->assign('list', $paymentNoOutbound);
        $this->assign('id', $id);

        return view();
    }

    /**
     * @throws DbException
     * @throws Exception
     */
    public function index_no_accounting($id): \think\response\View
    {
        $reportObj = new FinanceReportModel();
        $report = $reportObj->find($id);

        $where['is_finished'] = 0;
        if ($report['month']) {
            $t = date('t', strtotime($report['month'] . '-01 00:00:00'));
            $where['paid_time'] = ['between', [$report['month'] . '-01 00:00:00', $report['month'] . '-' . $t . ' 00:00:00']];
        }

        // 表格列表
        $where['order_type'] = ['in', ['sale', '销售订单']];
        $order = new FinanceOrderStatisticsModel();
        $list = $order->where($where)->order('paid_time asc')->paginate(30);
        $this->assign('sale_amount', $order->where($where)->sum('sale_amount'));
        $this->assign('qty_amount', $order->where($where)->sum('qty'));
        $this->assign('list', $list);

        return view();
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     * @throws \PHPExcel_Exception
     */
    public function index_export()
    {
        $report_id = input('id');

        $financeReportObj = new FinanceReportModel();
        $report = $financeReportObj->find($report_id);
        if (empty($report)) {
            $this->error('异常操作！', url('report'));
        }

        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();
        $financeExcelInit = new FinanceExcelInit($objPHPExcel);
        $financeExcelInit->getTablesByFinanceReport(0, $report);
        $objPHPExcel = $financeExcelInit->excelSheetSet();

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
        $payment_type_new = $payment_type == 'shein_semi_managed' ? 'shein' : $payment_type_new;
        $payment_type_new = $payment_type == 'temu_detail' ? 'temu' : $payment_type_new;
        $payment_type_new = $payment_type == 'temu_hk' ? 'temu' : $payment_type_new;
        $file= "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();

        Db::startTrans();
        try {
            $country = strpos($payment_type, 'amazon') !== false && $payment_type != 'amazon_us' ? 'EUROPE' : 'US';
            if ($payment_type == 'wildberries') {
                $country = "Wildberries";
            }
            $tableData = [
                'rid'           =>  $rid,
                'table_name'    =>  $origin,
                'platform'      =>  $payment_type_new,
                'country'       =>  $country,
                'created_at'    =>  date('Y-m-d H:i:s')
            ];
            $financeTableObj = new FinanceTableModel();
            if ($tableId = $financeTableObj->insertGetId($tableData)) {
                $paymentObj = new AmazonPayment();
                if ($payment_type) {
                    if ($payment_type == "temu_hk" || $payment_type == "temu") {
                        $paymentData = $paymentObj->$payment_type($excelObj, $tableId, $rid);
                    } else {
                        $paymentData = $paymentObj->$payment_type($data, $tableId, $rid);
                    }

                    $financeOrderSaleObj = new FinanceOrderSaleModel();
                    if ($payment_type == "wayfair") {
                        // 将wayfair销售数据添加到缓存队列
                        $wayfairOrder = Cache::get('wayfairOrder');
                        Cache::set('wayfairOrder', array_merge((array)$wayfairOrder, (array)$paymentData['orderSaleNew']), 24 * 60 * 60);
                        $sale_amount = 0;
                        $selling_fees = 0;
                        $fba_fees = 0;

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
                            $selling_fees = $financeOrderSaleObj->where(['table_id' => $tableId])->sum('selling_fees');
                            $fba_fees = $financeOrderSaleObj->where(['table_id' => $tableId])->sum('fba_fees');
                            if (in_array($payment_type, ['amazon_uk', 'amazon_de', 'amazon_es', 'amazon_fr', 'amazon_it'])) {
                                $productSaleTax = $financeOrderSaleObj->where(['table_id' => $tableId])->sum('product_sales_tax');
                                $shippingCreditsTax = $financeOrderSaleObj->where(['table_id' => $tableId])->sum('shipping_credits_tax');
                                $giftWrapCreditsTax = $financeOrderSaleObj->where(['table_id' => $tableId])->sum('gift_wrap_credits_tax');
                                $promotionalRebatesTax = $financeOrderSaleObj->where(['table_id' => $tableId])->sum('promotional_rebates_tax');
                                $sale_amount = round($productSale + $productSaleTax + $shipping + $shippingCreditsTax + $gift + $giftWrapCreditsTax + $promotional + $promotionalRebatesTax, 2);
                            } else {
                                $sale_amount = round($productSale + $shipping + $gift + $regulatory + $promotional, 2);
                            }
                        }
                    }

                    if ($payment_type == "walmart") {
                        $financeOrderAdjustmentWfsObj = new FinanceOrderAdjustmentWfsModel();
                        if (!$financeOrderAdjustmentWfsObj->saveAll($paymentData['orderAdjustmentWfs'])) {
                            throw new \think\Exception('Payment导入失败！');
                        }
                    }

                    if ($payment_type == "wildberries") {
                        $financeWildberriesOrderNotifyObj = new FinanceWildberriesOrderNotifyModel();
                        if (!$financeWildberriesOrderNotifyObj->saveAll($paymentData['wildberriesOrderNotify'])) {
                            throw new \think\Exception('Payment导入失败！');
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
                        $refund_other = $financeOrderRefundObj->where(['table_id' => $tableId])->sum('other');
                    }

                    $financeOrderPromotionObj = new FinanceOrderPromotionModel();
                    if (!$financeOrderPromotionObj->saveAll($paymentData['orderPromotionNew'])) {
                        throw new \think\Exception('Payment导入失败！');
                    } else {
                        $promotionSum = $financeOrderPromotionObj->where(['table_id' => $tableId])->sum('total');
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

                    $financeOrderSubscriptionObj = new FinanceOrderSubscriptionModel();
                    if (!$financeOrderSubscriptionObj->saveAll($paymentData['orderSubscriptionNew'])) {
                        throw new \think\Exception('Payment导入失败！');
                    }

                    $financeOrderTemuDetailObj = new FinanceOrderTemuDetailModel();
                    if (!$financeOrderTemuDetailObj->saveAll($paymentData['orderTemuDetails'])) {
                        throw new \think\Exception('Payment导入失败！');
                    }

                    $financeWildberriesShippingObj = new FinanceWildberriesShippingModel();
                    if (!$financeWildberriesShippingObj->saveAll($paymentData['orderShippingNew'])) {
                        throw new \think\Exception('Payment导入失败！');
                    }

                    if (!FinanceTableModel::update(
                        [
                            'userAccount'       =>  $paymentData['userAccount'],
                            'sale_amount'       =>  $sale_amount,
                            'refund_amount'     =>  $refund_amount,
                            'refund_other'      =>  $refund_other,
                            'selling_fees'      =>  $selling_fees,
                            'fba_fees'          =>  $fba_fees,
                            'promotion'         =>  $promotionSum,
                            'shipping_service'  =>  $shippingServiceSum,
                            'liquidation'       =>  $liquidationSum,
                            'adjustment'        =>  $adjustmentSum
                        ], ['id' => $tableId])) {
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

                $financeOrderSubscriptionObj = new FinanceOrderSubscriptionModel();
                $financeOrderSubscriptionObj->where('table_id', $post['id'])->delete();

                $financeOrderTemuDetailObj = new FinanceOrderTemuDetailModel();
                $financeOrderTemuDetailObj->where('table_id', $post['id'])->delete();

                $financeOrderAdjustmentWfsObj = new FinanceOrderAdjustmentWfsModel();
                $financeOrderAdjustmentWfsObj->where('table_id', $post['id'])->delete();

                $financeWildberriesShippingObj = new FinanceWildberriesShippingModel();
                $financeWildberriesShippingObj->where('table_id', $post['id'])->delete();

                $financeWildberriesOrderNotifyObj = new FinanceWildberriesOrderNotifyModel();
                $financeWildberriesOrderNotifyObj->where('table_id', $post['id'])->delete();

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

    public function cost($id): \think\response\View
    {
        $report_id = input('id');
        $this->assign('id', $report_id);

        return view();
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
        $list = $order->with(['store', 'saleOrderCode', 'statistic'])->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'page_num' => $page_num]]);
        $this->assign('list', $list);
        $this->assign('report_id', $id);
        $this->assign('report', FinanceReportModel::get($id));
        $this->assign('generate', count($list));
        $this->assign('qty', $order->where($where)->sum('qty'));

        $outboundAccounting = Cache::get('outboundAccounting');
        if (empty($outboundAccounting)) {
            $this->assign('outboundAccounting', 0);
        } else {
            $this->assign('outboundAccounting', 1);
        }

        return view();
    }

    public function outbound_generate()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $report_id = $post['id'];

            Db::startTrans();
            try {
                $outboundObj = new FinanceOrderOutboundModel();
                $sql = FinanceReportModel::generateOutboundSql($report_id);
                $outboundData = $outboundObj->query($sql);
                $outboundObj->insertAll($outboundData);
                FinanceReportModel::update(['is_share' => 1], ['id' => $report_id]);

                Db::commit();
                echo json_encode(['code' => 1, 'msg' => '出库明细生成成功']);
            } catch (\SoapFault $e) {
                Db::rollback();
                echo json_encode(['code' => 0, 'msg' => $e->getMessage()]);
            } catch (\Exception $e) {
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
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function outbound_accounting()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $report_id = $post['id'];
            $report = FinanceReportModel::get($report_id);

            $outboundObj = new FinanceOrderOutboundModel();
            $list = $outboundObj->where(['report_id' => $report_id])->field("saleOrderCode, '".$report['month'] . "' as month")->select()->toArray();
            Cache::clear();

            $outboundAccounting = Cache::get('outboundAccounting');
            if (Cache::set('outboundAccounting', array_merge((array)$outboundAccounting, (array)$list), 48 * 60 * 60)) {
                echo json_encode(['code' => 1, 'msg' => '操作成功']);
            } else {
                echo json_encode(['code' => 0, 'msg' => '操作失败']);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     * @throws \PHPExcel_Exception
     */
    public function outbound_accounting_export()
    {
        $report_id = input('id');

        $financeReportObj = new FinanceReportModel();
        $report = $financeReportObj->find($report_id);
        if (empty($report)) {
            $this->error('异常操作！', url('report'));
        }

        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();
        $financeExcelInit = new FinanceExcelInit($objPHPExcel);
        $financeExcelInit->getOutboundAccountingByReport(0, $report);
        $objPHPExcel = $financeExcelInit->excelSheetSet();

        // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        $filename = date("YmdHis") . time() . mt_rand(100000, 999999);
        ob_end_clean();
        header('Content-Disposition:attachment;filename="'.$filename.'.xls"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }

    public function outbound_empty($id)
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $reportId = $post['id'];

            Db::startTrans();
            try {
                FinanceReportModel::update(['is_share' => 0], ['id' => $reportId]);

                $outboundObj = new FinanceOrderOutboundModel();
                $outboundObj->where('report_id', $reportId)->delete();

                $shareObj = new FinanceOrderShareModel();
                $shareObj->where('report_id', $reportId)->delete();

                $snapshotObj = new FinanceReportSnapshotModel();
                $snapshotObj->where('report_id', $reportId)->delete();

                $refundObj = new FinanceOrderRefundModel();
                $refundObj->where(['report_id' => $reportId])->update(['share_code' => null]);

                $shippingObj = new FinanceOrderShippingServiceModel();
                $shippingObj->where(['report_id' => $reportId])->update(['share_code' => null]);

                $adjustmentObj = new FinanceOrderAdjustmentModel();
                $adjustmentObj->where(['report_id' => $reportId])->update(['share_code' => null]);

                $adjustmentWfsObj = new FinanceOrderAdjustmentWfsModel();
                $adjustmentWfsObj->where(['report_id' => $reportId])->update(['share_code' => null]);

                $liquidationObj = new FinanceOrderLiquidationModel();
                $liquidationObj->where(['report_id' => $reportId])->update(['share_code' => null]);

                $additionalObj = new FinanceOrderAdditionalModel();
                $additionalObj->where(['report_id' => $reportId])->update(['share_code' => null]);

                $warehouseFbmObj = new FinanceWarehouseFbmModel();
                $warehouseFbmObj->where(['report_id' => $reportId])->update(['share_code' => null]);

                $operationExpensesObj = new FinanceOperationExpensesModel();
                $operationExpensesObj->where(['report_id' => $reportId])->update(['share_code' => null, 'calculate_month' => null, 'report_id' => null]);

                $operationFactoryObj = new FinanceOperationFactoryModel();
                $operationFactoryObj->where(['report_id' => $reportId])->update(['share_code' => null, 'calculate_month' => null, 'report_id' => null]);

                $operationDeliveryObj = new FinanceOperationDeliveryModel();
                $operationDeliveryObj->where(['report_id' => $reportId])->update(['share_code' => null, 'calculate_month' => null, 'report_id' => null]);

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
        $sum = $order->query('SELECT SUM(available_quantity * sku_ddp_unit) sum FROM mu_finance_store WHERE report_id = ' . $id . ';');
        $this->assign('available_sum', $sum[0]['sum']);
        $this->assign('accrual_total',  $order->where($where)->sum('accrual_total'));

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
                    if (!strtotime($item[1])) {
                        $item[1] = DateTime::createFromFormat('m-d-y', $item[1])->format('Y/m/d');
                    }
                    if (!strtotime($item[13])) {
                        $item[13] = DateTime::createFromFormat('m-d-y', $item[13])->format('Y/m/d');
                    }
                    if (!empty($item[27]) && !strtotime($item[27])) {
                        $item[27] = DateTime::createFromFormat('m-d-y', $item[27])->format('Y/m/d');
                    }
//                    if (!strtotime($item[29])) {
//                        $item[29] = DateTime::createFromFormat('m-d-y', $item[29])->format('Y/m/d');
//                    }
                    $storeData[] = [
                        'report_id'                 =>  $report_id,
                        'inbound_number'            =>  $item[0],
                        'entering_date'             =>  date('Ymd', strtotime($item[1])),
                        'currency'                  =>  $item[7],
                        'quantity_amount'           =>  $item[8],
                        'purchase_amount'           =>  $item[9],
                        'cost_amount'               =>  $item[10],
                        'content'                   =>  $item[11],
                        'export_no'                 =>  $item[12],
                        'shipment_date'             =>  date('Ymd', strtotime($item[13])),
                        'sku'                       =>  $item[14],
                        'cn_name'                   =>  $item[15],
                        'entering_quantity'         =>  $item[16],
                        'sku_purchase_unit'         =>  $item[17],
                        'sku_purchase_amount'       =>  $item[18],
                        'sku_ddp_unit'              =>  $item[19],
                        'sku_ddp_amount'            =>  $item[20],
                        'outbound_quantity'         =>  $item[21],
                        'available_quantity'        =>  $item[22],
                        'seller'                    =>  $item[23],
                        'purchaser'                 =>  $item[24],
                        'arriving_date'             =>  date('Ymd', strtotime($item[27])),
                        'contact_no'                =>  $item[28]
                    ];
                }
            }
            $financeStoreObj = new FinanceStoreModel();
            if(!$financeStoreObj->insertAll($storeData)) {
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
                $outboundObj->where('report_id', $reportId)->update(['store_id' => null, 'is_notify' => 0]);

                $financeReportObj = new FinanceReportModel();
                $financeReportObj->save(['is_share' => 1], ['id' => $reportId]);
                $financeReportObj->save(['is_notify' => 0], ['id' => $reportId]);

                $snapshotObj = new FinanceReportSnapshotModel();
                $snapshotObj->where('report_id', $reportId)->delete();

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

        $is_finished = $this->request->get('is_finished', 2);
        $this->assign('is_finished', $is_finished);
        if ($is_finished != 2) {
            $where['is_finished'] = $is_finished;
        }

        $month = $this->request->get('month', date('Y-m', ''));
        if ($month) {
            $t = date('t', strtotime($month . '-01 00:00:00'));
            $where['paid_time'] = ['between', [$month . '-01 00:00:00', $month . '-' . $t . ' 00:00:00']];
        }
        $this->assign('month', $month);

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 订单列表
        $order = new FinanceOrderStatisticsModel();
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'is_finished' => $is_finished, 'month' => $month, 'page_num' => $page_num]]);
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
                $order = $financeOrderStatisticsObj->where(['saleOrderCode' => $item[12]])->find();
                if (!empty($order)) {
                    continue;
                }
                $orderData[] = [
                    "platform"              =>  $item[0],
                    "user_account"          =>  $item[1],
                    "user_account_alias"    =>  $item[2],
                    "site"                  =>  $item[3],
                    "warehouse"             =>  $item[4],
                    "created_time"          =>  date('Y-m-d H:i:s', strtotime($item[6])),
                    "paid_time"             =>  date('Y-m-d H:i:s', strtotime($item[7])),
                    "audit_time"            =>  date('Y-m-d H:i:s', strtotime($item[8])),
                    "shipping_time"         =>  date('Y-m-d H:i:s', strtotime($item[9])),
                    "order_status"          =>  $item[10],
                    "order_type"            =>  $item[11],
                    "saleOrderCode"         =>  trim($item[12]),
                    "payment_id"            =>  trim($item[13]),
                    "platform_sku"          =>  trim($item[14]),
                    "seller_sku"            =>  trim($item[15]),
                    "warehouse_sku"         =>  trim($item[16]),
                    "qty"                   =>  $item[17],
                    "product_name"          =>  $item[18],
                    "product_style"         =>  $item[19],
                    "product_brand"         =>  $item[20],
                    "category_1"            =>  $item[21],
                    "category_2"            =>  $item[22],
                    "category_3"            =>  $item[23],
                    "product_status"        =>  $item[24],
                    "warehouse_no"          =>  $item[25],
                    "service_no"            =>  $item[26],
                    "order_delisting_type"  =>  $item[27],
                    "is_shipping"           =>  $item[28],
                    "shipping_no"           =>  $item[30],
                    "sys_transaction"       =>  $item[31],
                    "shipping_method"       =>  $item[32],
                    "product_weight"        =>  $item[33],
                    "inventory_batch_no"    =>  $item[34],
                    "currency"              =>  $item[35],
                    "sale_unit"             =>  $item[37],
                    "sale_amount"           =>  $item[38],
                    "sale_shipping"         =>  $item[40],
                    "selling_fee"           =>  $item[42],
                    "fba_fee"               =>  $item[43],
                    "tax"                   =>  $item[45]
                ];
            }
            $financeOrderStatisticsObj->insertAll($orderData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), session('back_url', '', 'manage'));
        }
        $this->redirect(session('back_url', '', 'manage'));
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
            $dataSelling = $editObj->query(FinanceReportModel::getOrderStatisticSellingFeesAutoEditSql($reportId));
            $dataFba = $editObj->query(FinanceReportModel::getOrderStatisticFbaFeesAutoEditSql($reportId));
            $sellingRes = $editObj->insertAll($dataSelling);
            $fbaRes = $editObj->insertAll($dataFba);
            if ($sellingRes || $fbaRes) {
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
     * @throws PDOException
     * @throws BindParamException
     * @throws \Exception
     */
    public function order_statistics_temu_sale()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $tableIds = explode(',', $post['data']);
            $report_id = $post['id'];
            if (count(array_unique($tableIds)) != 2) {
                echo json_encode(['code' => 0, 'msg' => '格式错误']);
                exit;
            }

            $tableObj = new FinanceTableModel();
            foreach ($tableIds as $id) {
                $table = $tableObj->find($id);
                if ($table['platform'] != "temu" || $table['userAccount'] != "TEMU_TOLEAD_HOME") {
                    echo json_encode(['code' => 0, 'msg' => '你输入的表ID不正确']);
                    exit;
                }
            }

            $editData = $tableObj->query(FinanceReportModel::getOrderStatisticsTemuSaleSync($tableIds, $report_id));
            $financeOrderStatisticObj = new FinanceOrderStatisticsModel();
            if ($financeOrderStatisticObj->saveAll($editData)) {
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
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     * @throws BindParamException
     * @throws PDOException
     * @throws \PHPExcel_Reader_Exception
     */
    public function order_statistics_diff_export()
    {
        $report_id = input('id');
        $model = new FinanceReportModel();

        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';
        $objPHPExcel = new PHPExcel();
        $financeExcelInit = new FinanceExcelInit($objPHPExcel);
        $financeExcelInit->generateSaleAmountDiffSheet(0, $report_id);
        $financeExcelInit->generateSellingFeeDiffSheet(1, $report_id);
        $financeExcelInit->generateFbaFeeDiffSheet(2, $report_id);
        $objPHPExcel = $financeExcelInit->excelSheetSet();

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

                if (!empty($item[2])) {
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
                        "total_unit"            =>  $item[11],
                        "main_platform"         =>  $item[12]
                    ];
                }
            }

            if ($financeWarehouseObj->insertAll($warehouseData)) {
                $report = FinanceReportModel::get($report_id);
                $lastDay = $report['month'] . '-' . date('t', strtotime($report['month'] . '-01'));
                $model = new FinanceWarehouseFbmModel();
                $data = $model->query(FinanceReportModel::getWarehouseRentJoinSql($lastDay, $report_id));
                $model->insertAll($data);
            } else {
                throw new Exception("导入失败！");
            }

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
                $financeWarehouseFbmObj = new FinanceWarehouseFbmModel();
                $financeWarehouseFbmObj->where('report_id', $reportId)->delete();

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
    public function warehouse_fbm($id): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['main_platform|sku|main_sku|share_code'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 订单列表
        $order = new FinanceWarehouseFbmModel();
        $where['report_id'] = $id;
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);
        $this->assign('report_id', $id);

        $sum = $order->where($where)->sum('total');
        $this->assign('sum', $sum);

        return view();
    }

    /**
     * @throws DbException
     */
    public function warehouse_fbm_edit($id)
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $model = new FinanceWarehouseFBMModel();
            $info = $model->find($id);
            $post['share_code'] = NULL;
            if ($model->update($post, ['main_sku' => $info['main_sku'], 'report_id' => $info['report_id']])) {
                $shareObj = new FinanceOrderShareModel();
                $shareObj->where(['report_id' => $info['report_id'], 'cost_type' => 'WAREHOUSE_FBM', 'warehouse_sku' => $info['main_sku']])->delete();

                echo json_encode(['code' => 1, 'msg' => '修改成功']);
            } else {
                echo json_encode(['code' => 0, 'msg' => '修改失败，请重试']);
            }
        } else {
            $info = FinanceWarehouseFBMModel::get(['id' => $id,]);
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

        $this->assign('lc_adjustment', $order->where($where)->sum('lc_adjustment'));
        $this->assign('le_adjustment', $order->where($where)->sum('le_adjustment'));
        $this->assign('wyd_adjustment', $order->where($where)->sum('wyd_adjustment'));

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

                if (!empty($item[2])) {
                    $additionalData[] = [
                        "report_id"             =>  $report_id,
                        "platform"              =>  $item[0],
                        "user_account"          =>  $item[1],
                        "warehouse_sku"         =>  $item[2],
                        "lc_adjustment"         =>  $item[3],
                        "le_adjustment"         =>  $item[4],
                        "wyd_adjustment"        =>  $item[5],
                    ];
                }
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
        $this->assign('sum', $order->where($where)->sum('cny_actual_paid'));

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

                if (!empty($item[2])) {
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
                if (!empty($item[2])) {
                    $adCostData[] = [
                        "report_id"                 =>  $report_id,
                        "platform"                  =>  $item[0],
                        "user_account"              =>  $item[1],
                        "warehouse_sku"             =>  $item[2],
                        "total"                     =>  $item[3]
                    ];
                }
            }
            $financeAdCostObj->insertAll($adCostData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('ad_cost', ['id' => $report_id]));
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
            $where['share_code|user_account|fulfillment|cost_type|payment|seller_sku|warehouse_sku'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $costType = $this->request->get('cost_type', '', 'htmlspecialchars');
        if ($costType) {
            $where['cost_type'] = $costType;
            $this->assign('cost_type', $costType);
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

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');

        return view();
    }

    // 添加
    /**
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */
    public function relation_add($id)
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $dataValidate = new FinanceRelationValidate();
            if ($dataValidate->scene('add')->check($post)) {
                $model = new FinanceSkuRelationModel();
                $productModel = new ProductModel();
                $product = $productModel->where(['productSku' => $post['warehouse_sku']])->find();
                if ($product) {
                    $post['report_id'] = $id;
                    $post['product_name'] = $product['productTitle'];
                    $post['unit_price'] = $product['sp_unit_price'];
                    $post['warehouse_name'] = '全部仓库';
                    $post['type'] = 1;
                } else {
                    echo json_encode(['code' => 0, 'msg' => '仓库SKU不存在']);
                    exit;
                }
                if ($model->allowField(true)->save($post)) {
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
            $this->assign('report_id', $id);

            return view();
        }
    }

    // 编辑
    /**
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */
    public function relation_edit($id)
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $dataValidate = new FinanceRelationValidate();
            if ($dataValidate->scene('edit')->check($post)) {
                $model = new FinanceSkuRelationModel();
                $productModel = new ProductModel();
                $product = $productModel->where(['productSku' => $post['warehouse_sku']])->find();
                if ($product) {
                    $post['product_name'] = $product['productTitle'];
                    $post['unit_price'] = $product['sp_unit_price'];
                    $post['warehouse_name'] = '全部仓库';
                } else {
                    echo json_encode(['code' => 0, 'msg' => '仓库SKU不存在']);
                    exit;
                }
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
            $this->assign('info', FinanceSkuRelationModel::get($id));

            return view();
        }
    }

    // 删除
    /**
     * @throws DbException
     */
    public function relation_delete()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $block = FinanceSkuRelationModel::get($post['id']);
            if ($block->delete()) {
                echo json_encode(['code' => 1, 'msg' => '操作成功']);
                exit;
            } else {
                echo json_encode(['code' => 0, 'msg' => '操作失败，请重试']);
                exit;
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
            exit;
        }
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

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function relation_refresh()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $reportId = $post['id'];
            $skuRelationObj = new FinanceSkuRelationModel();
            $list = $skuRelationObj->where(['report_id' => $reportId, 'product_name' => NULL])->select()->toArray();
            foreach ($list as $key => $item) {
                $productModel = new ProductModel();
                $product = $productModel->where(['productSku' => $item['warehouse_sku']])->find();
                if ($product) {
                    $list[$key]['product_name'] = $product['productTitle'];
                    $list[$key]['unit_price'] = $product['sp_unit_price'];
                    $list[$key]['warehouse_name'] = '全部仓库';
                }
            }
            if ($skuRelationObj->saveAll($list)) {

                echo json_encode(['code' => 1, 'msg' => '更新完成']);
            } else {
                echo json_encode(['code' => 0, 'msg' => '更新失败，请重试']);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }

    /**
     * @throws DbException
     */
    public function operation_expenses($id): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['month|sku|applicant|content|type'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 列表
        $order = new FinanceOperationExpensesModel();
        $share = new FinanceOrderShareModel();
        $where['report_id'] = $id;
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);
        $this->assign('report_id', $id);
        $this->assign('sum', $order->where(['report_id' => $id])->sum('total'));
        $this->assign('company', $share->where(['report_id' => $id, 'cost_type' => 'OPERATION_EXPENSES_PATENT', 'user_account' => 'company'])->sum('total'));

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');

        return view();
    }

    /**
     * @throws DbException
     */
    public function operation_factory($id): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['month|sku|content|type'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 列表
        $order = new FinanceOperationFactoryModel();
        $where['report_id'] = $id;
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);
        $this->assign('report_id', $id);
        $this->assign('sum', $order->where(['report_id' => $id])->sum('total'));

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');

        return view();
    }

    /**
     * @throws DbException
     */
    public function operation_delivery($id): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['tracking_number|month|sku|sender|seller|user_account'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 列表
        $order = new FinanceOperationDeliveryModel();
        $where['report_id'] = $id;
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);
        $this->assign('report_id', $id);
        $this->assign('sum', $order->where(['report_id' => $id])->sum('total'));

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');

        return view();
    }

    /**
     * @throws DbException
     */
    public function operation_factory_claim($id): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['month|sku|content|type'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 列表
        $report = FinanceReportModel::get($id);
        $order = new FinanceOperationFactoryClaimModel();
        $where['month'] = date('Ym', strtotime($report['month'] . '-01'));
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);
        $this->assign('report_id', $id);
        $this->assign('sum', $order->where($where)->sum('total'));

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');

        return view();
    }

    /**
     * @throws DbException
     */
    public function wfs_fulfillment($id): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['payment_id|sku'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 列表
        $order = new FinanceOrderAdjustmentWfsModel();
        $where['report_id'] = $id;
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);
        $this->assign('report_id', $id);

        $this->assign('wfs_tail', $order->where($where)->where('is_fulfillment', 1)->sum('total'));
        $this->assign('wfs_return', $order->where($where)->where('is_return_shipping', 1)->sum('total'));

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');

        return view();
    }

    /**
     * @throws DbException
     */
    public function ak_ad_cost($id): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['msku|countryCode'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 列表
        $report = FinanceReportModel::get($id);
        $where['reportDateMonth'] = $report['month'];
        $akAdCostModel = new AkAdCostModel();
        $list = $akAdCostModel->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);
        $this->assign('report_id', $id);

        $this->assign('ad_sum', $akAdCostModel->where($where)->sum('totalAdsCost'));
        $this->assign('warehouse_sum', $akAdCostModel->where($where)->field('sum(sharedFbaStorageFee + sharedLabelingFee + fbaStorageFee + longTermStorageFee + sharedFbaDisposalFee + sharedAmazonPartneredCarrierShipmentFee + sharedFbaInboundConvenienceFee + sharedFbaInboundDefectFee + sharedFbaRemovalFee) total')->find()['total']);
        $this->assign('liquidation_1', $akAdCostModel->where($where)->field('sum(fbaLiquidationProceeds + sharedLiquidationsFees) total')->find()['total']);
        $this->assign('liquidation_2', $akAdCostModel->where($where)->where(['countryCode' => ['neq', 'US']])->sum('taxCollected'));
        $this->assign('promotion', $akAdCostModel->where($where)->field('sum(sharedLdFee + sharedCouponFee + sharedVineFee) total')->find()['total']);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');

        return view();
    }

    /**
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */
    public function snapshot_add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $reportId = $post['id'];
            $reportObj = new FinanceReportModel();
            $report = $reportObj->find($reportId);
            if ($report) {
                Db::startTrans();
                try {
                    $snapshotObj = new FinanceReportSnapshotModel();
                    $snapshotObj->where(['report_id' => $report['id']])->delete();
                    if (
                        FinanceReportSnapshotModel::FbmSnapshot($report)
                        && FinanceReportSnapshotModel::FbaSnapshot($report)
                        && FinanceReportSnapshotModel::WalmartSnapshot($report)
                        && FinanceReportSnapshotModel::WayfairSnapshot($report)
                        && FinanceReportSnapshotModel::SheinSnapshot($report)
                        && FinanceReportSnapshotModel::TemuSnapshot($report)
                        && FinanceReportSnapshotModel::EbaySnapshot($report)
                        && FinanceReportSnapshotModel::TiktokSnapshot($report)
                        && FinanceReportSnapshotModel::HomeDepotSnapshot($report)
                        && FinanceReportSnapshotModel::noOutboundSnapshot($report)
                    ) {

                        Db::commit();
                        echo json_encode(['code' => 1, 'msg' => '结存完成']);
                    } else {
                        throw new Exception('结存失败，请重试');
                    }
                } catch (Exception $e) {
                    Db::rollback();
                    echo json_encode(['code' => 0, 'msg' => $e->getMessage()]);
                }
            } else {
                echo json_encode(['code' => 0, 'msg' => '结存失败，请重试']);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }

    /**
     */
    public function snapshot(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['user_account|warehouse_sku|product_name|seller|purchaser'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $platform = $this->request->get('platform', 'amazon-FBM', 'htmlspecialchars');
        $this->assign('platform', $platform);
        if ($platform) {
            $where['platform'] = $platform;
        }

        $month = $this->request->get('month', date('Y-m', strtotime('-2 month')), 'htmlspecialchars');
        $this->assign('month', $month);
        if ($month) {
            $where['month'] = $month;
        }

        // 列表
        $reportSnapshotModel = new FinanceReportSnapshotModel();
        $this->assign('qty_amount', $reportSnapshotModel->where($where)->sum('qty_amount'));
        $this->assign('amount', $reportSnapshotModel->where($where)->sum('amount'));
        $this->assign('profit', $reportSnapshotModel->where($where)->sum('profit'));

        return view();
    }

    /**
     * @throws DbException
     * @throws Exception
     */
    public function getSnapshot()
    {
        $post = $this->request->post();
        if ($post['keyword']) {
            $where['user_account|warehouse_sku|product_name|seller|purchaser'] = ['like', '%' . $post['keyword'] . '%'];
        } else {
            $where = [];
        }

        if ($post['platform']) {
            $where['platform'] = $post['platform'];
        }

        if ($post['month']) {
            $where['month'] = $post['month'];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));

        // 列表
        $akAdCostModel = new FinanceReportSnapshotModel();
        $list = $akAdCostModel->where($where)->order('id asc')->paginate($page_num, '', ['keyword' => $post['keyword']]);

        $response = [
            "code"      => 0,           // 成功状态码
            "msg"       => "",           // 提示信息
            "count"     => $akAdCostModel->where($where)->count(), // 数据总条数
            "data"      => $list->toArray()['data']         // 数据列表
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function obsolete(): \think\response\View
    {
        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['sku|seller_original|seller_current'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $month = $this->request->get('month', date('Y-m', strtotime('-1 month')));
        $this->assign('month', $month);
        $where['month'] = date('Ym', strtotime($month . '-01'));

        // 列表
        $model = new FinanceObsoleteSkuPercentModel();
        $list = $model->where($where)->paginate($page_num, '', ['keyword' => $keyword, 'month' => $month]);
        $this->assign('list', $list);

        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function obsolete_import()
    {
        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';

        $filename = input('filename');
        $month = input('month');
        $file= "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();
        unset($data[0]);

        Db::startTrans();
        try {
            $obsoleteData = [];
            $obsoleteObj = new FinanceObsoleteSkuPercentModel();
            foreach ($data as $item) {
                $obsoleteData[] = [
                    "type"                      =>  $item[11] == '组内交接' ? 1 : 2,
                    "sku"                       =>  $item[0],
                    "seller_original"           =>  $item[2],
                    "seller_current"            =>  $item[3],
                    "seller_in_charge"          =>  $item[4],
                    "warehouse_stock"           =>  $item[5],
                    "local_stock"               =>  $item[6],
                    "daily_sale"                =>  $item[8],
                    "percent"                   =>  $item[9],
                    "month"                     =>  date('Ym', strtotime($month . '-01')),
                    "content"                   =>  $item[10],
                ];
            }
            $obsoleteObj->insertAll($obsoleteData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('obsolete'));
        }
        $this->redirect(url('obsolete'));
    }

    /**
     * @throws DbException
     */
    public function provision($id): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['sku|export_no|seller|purchaser|contact_no'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 订单列表
        $order = new FinanceProvisionModel();
        $where['report_id'] = $id;
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);
        $this->assign('report_id', $id);

        $this->assign('provision_percent_1', $order->where($where)->sum('provision_percent_1'));
        $this->assign('provision_percent_2', $order->where($where)->sum('provision_percent_2'));
        $this->assign('provision_percent_3', $order->where($where)->sum('provision_percent_3'));
        $this->assign('total_unsettled_amount', $order->where($where)->sum('total_unsettled_amount'));

        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function provision_import()
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
            $provisionData = [];
            $financeProvisionObj = new FinanceProvisionModel();
            foreach ($data as $item) {
                $provisionData[] = [
                    "report_id"                 =>  $report_id,
                    "receive_code"				=>	$item[0],
                    "receive_date"				=>	empty($item[1]) ? null : date('Ymd', strtotime($item[1])),
                    "preparer"					=>	$item[2],
                    "company"					=>	$item[3],
                    "company_name"				=>	$item[4],
                    "group_name"				=>	$item[5],
                    "receive_status"			=>	$item[6],
                    "receive_currency"			=>	$item[7],
                    "qty"						=>	$item[8],
                    "purchase_amount"			=>	$item[9],
                    "cost_amount"				=>	$item[10],
                    "content"					=>	$item[11],
                    "export_no"					=>	$item[12],
                    "shipment_date"				=>	empty($item[13]) ? null : date('Ymd', strtotime($item[13])),
                    "sku"						=>	$item[14],
                    "name"						=>	$item[15],
                    "inbound_qty"				=>	$item[16],
                    "unit_price"				=>	$item[17],
                    "amount"					=>	$item[18],
                    "ddp"						=>	$item[19],
                    "ddp_amount"				=>	$item[20],
                    "outbound_qty"				=>	$item[21],
                    "unsettled_qty"				=>	$item[22],
                    "seller"					=>	$item[23],
                    "purchaser"					=>	$item[24],
                    "content_2"					=>	$item[25],
                    "receive_type"				=>	$item[26],
                    "arrive_date"				=>	empty($item[27]) ? null : date('Ymd', strtotime($item[27])),
                    "contact_no"				=>	$item[28],
                    "provision_date"			=>	empty($item[29]) ? null : date('Ymd', strtotime($item[29])),
                    "day_amount"				=>	$item[30],
                    "beyond_6_month"			=>	$item[31],
                    "beyond_8_month"			=>	$item[32],
                    "beyond_14_month"			=>	$item[33],
                    "provision_percent_1"		=>	$item[34],
                    "provision_percent_2"		=>	$item[35],
                    "provision_percent_3"		=>	$item[36],
                    "total_unsettled_amount"	=>	$item[37],
                ];
            }
            $financeProvisionObj->insertAll($provisionData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('provision'));
        }
        $this->redirect(url('provision', ['id' => $report_id]));
    }

    public function provision_empty()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $reportId = $post['id'];
            $provisionObj = new FinanceProvisionModel();
            if ($provisionObj->where('report_id', $reportId)->delete()) {
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
