<?php
namespace app\Manage\controller;

use app\Manage\model\AkAdCostCreateModel;
use app\Manage\model\AmazonPayment;
use app\Manage\model\FinanceOrderAdjustmentModel;
use app\Manage\model\FinanceOrderFbaInventoryModel;
use app\Manage\model\FinanceOrderLiquidationModel;
use app\Manage\model\FinanceOrderPromotionModel;
use app\Manage\model\FinanceOrderRefundModel;
use app\Manage\model\FinanceOrderSaleModel;
use app\Manage\model\FinanceOrderOutboundModel;
use app\Manage\model\FinanceOrderShippingServiceModel;
use app\Manage\model\FinanceReportModel;
use app\Manage\model\FinanceStoreModel;
use app\Manage\model\FinanceTableModel;
use app\Manage\validate\FinanceReportValidate;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Reader_Exception;
use PHPExcel_Style_Fill;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
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

        $reportRes = $financeReportObj->query(FinanceReportModel::getReportSql($report_id));
        $adCostSql = '
	SELECT
	b.name 店铺,
	d.pcr_product_sku 仓库SKU,
	SUM( a.totalSalesQuantity * d.pcr_quantity ) 销量,
	SUM( ROUND( a.totalAdsCost * d.pcr_percent * d.pcr_quantity / 100, 8 ) ) 广告费,
	a.reportDateMonth 月份,
	a.principalRealname 运营人员 
FROM
	mu_ak_ad_cost a
	LEFT JOIN mu_ak_seller b ON a.sid = b.sid
	LEFT JOIN mu_ecang_sku c ON a.msku = c.product_sku
	LEFT JOIN mu_ecang_sku_relation d ON c.id = d.sku_id 
WHERE
	a.reportDateMonth = "' . $report['month'] . '" 
GROUP BY
	name,
	pcr_product_sku,
	reportDateMonth,
	principalRealname 
ORDER BY
	name,
	pcr_product_sku';
        $adCostRes = $financeReportObj->query($adCostSql);

        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();

        // Set name sheet
        $objPHPExcel->setActiveSheetIndex(0)->setTitle($report['name']);

        // Add some data
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '平台')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', '仓库SKU')
            ->setCellValue('D1', '销售数量')
            ->setCellValue('E1', '退款数量')
            ->setCellValue('F1', '销售总额')
            ->setCellValue('G1', '退款总额')
            ->setCellValue('H1', '佣金')
            ->setCellValue('I1', '亚马逊尾程')
            ->setCellValue('J1', '海外仓尾程')
        ;

        $reportIndex = 1;
        foreach ($reportRes as $item) {
            $reportIndex ++;
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . $reportIndex, $item['平台'])
                ->setCellValue('B' . $reportIndex, $item['店铺'])
                ->setCellValue('C' . $reportIndex, $item['仓库SKU'])
                ->setCellValue('D' . $reportIndex, $item['销售数量'])
                ->setCellValue('E' . $reportIndex, $item['退款数量'])
                ->setCellValue('F' . $reportIndex, $item['销售总额'])
                ->setCellValue('G' . $reportIndex, $item['退款总额'])
                ->setCellValue('H' . $reportIndex, $item['佣金'])
                ->setCellValue('I' . $reportIndex, $item['亚马逊尾程'])
                ->setCellValue('J' . $reportIndex, $item['海外仓尾程'])
            ;
        }

        // create new sheet
        $objPHPExcel->createSheet();

        // Set name sheet
        $objPHPExcel->setActiveSheetIndex(1)->setTitle('广告费分摊');

        // Add some data
        $objPHPExcel->setActiveSheetIndex(1)
            ->setCellValue('A1', '店铺')
            ->setCellValue('B1', '仓库SKU')
            ->setCellValue('C1', '销量')
            ->setCellValue('D1', '广告费')
            ->setCellValue('E1', '月份')
            ->setCellValue('F1', '运营人员')
        ;

        $adIndex = 1;
        foreach ($adCostRes as $item) {
            $adIndex ++;
            $objPHPExcel->setActiveSheetIndex(1)
                ->setCellValue('A' . $adIndex, $item['店铺'])
                ->setCellValue('B' . $adIndex, $item['仓库SKU'])
                ->setCellValue('C' . $adIndex, $item['销量'])
                ->setCellValue('D' . $adIndex, $item['广告费'])
                ->setCellValue('E' . $adIndex, $item['月份'])
                ->setCellValue('F' . $adIndex, $item['运营人员'])
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
     */
    public function index($id): \think\response\View
    {
        $where['rid'] = $id;
        $this->assign('rid', $id);

        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['table_name'] = ['like', '%' . $keyword . '%'];
        }

        // 表格列表
        $order = new FinanceTableModel();
        $list = $order->where($where)->order('id asc')->paginate(Config::get('PAGE_NUM'), false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
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
                'created_at'    =>  date('Y-m-d H:i:s')
            ];
            $financeTableObj = new FinanceTableModel();
            if ($tableId = $financeTableObj->insertGetId($tableData)) {
                $paymentObj = new AmazonPayment();
                if ($payment_type) {
                    $paymentData = $paymentObj->$payment_type($data, $tableId, $rid);

                    $financeOrderSaleObj = new FinanceOrderSaleModel();
                    if (!$financeOrderSaleObj->saveAll($paymentData['orderSaleNew'])) {
                        throw new \think\Exception('Payment导入失败！');
                    }

                    $financeOrderRefundObj = new FinanceOrderRefundModel();
                    if (!$financeOrderRefundObj->saveAll($paymentData['orderRefundNew'])) {
                        throw new \think\Exception('Payment导入失败！');
                    }

                    $financeOrderPromotionObj = new FinanceOrderPromotionModel();
                    if (!$financeOrderPromotionObj->saveAll($paymentData['orderPromotionNew'])) {
                        throw new \think\Exception('Payment导入失败！');
                    }

                    $financeOrderShippingServiceObj = new FinanceOrderShippingServiceModel();
                    if (!$financeOrderShippingServiceObj->saveAll($paymentData['orderShippingServiceNew'])) {
                        throw new \think\Exception('Payment导入失败！');
                    }

                    $financeOrderLiquidationObj = new FinanceOrderLiquidationModel();
                    if (!$financeOrderLiquidationObj->saveAll($paymentData['orderLiquidationNew'])) {
                        throw new \think\Exception('Payment导入失败！');
                    }

                    $financeOrderAdjustmentObj = new FinanceOrderAdjustmentModel();
                    if (!$financeOrderAdjustmentObj->saveAll($paymentData['orderAdjustmentNew'])) {
                        throw new \think\Exception('Payment导入失败！');
                    }

                    $financeOrderAdjustmentObj = new FinanceOrderFbaInventoryModel();
                    if (!$financeOrderAdjustmentObj->saveAll($paymentData['orderFbaInventory'])) {
                        throw new \think\Exception('Payment导入失败！');
                    }

                    if (!FinanceTableModel::update(['userAccount' => $paymentData['userAccount']], ['id' => $tableId])) {
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
    public function outbound(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['payment_id|saleOrderCode'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 订单列表
        $order = new FinanceOrderOutboundModel();
        $list = $order->with(['order_details.details.product', 'order_address.address'])->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'page_num' => $page_num]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
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
            $where['payment_id'] = ['like', '%' . $keyword . '%'];
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
            $financeStoreObj = new FinanceStoreModel();
            if($financeStoreObj->insertAll($storeData)) {
                $sql = "
                SELECT DISTINCT
                    a.report_id,
                    b.id ecang_order_id,
                    c.id ecang_order_detail_id,
                    a.payment_id,
                    b.saleOrderCode,
                    b.dateWarehouseShipping,
                    c.warehouseSku warehouse_sku,
	                c.qty
                FROM
                    mu_finance_order_sale a
                    LEFT JOIN mu_ecang_order b ON a.payment_id = b.refNo
                    LEFT JOIN mu_ecang_order_detail c ON b.id = c.order_id 
                WHERE
                    a.report_id = " . $report_id . " 
                    AND b.`status` = 4 
                ORDER BY
                    b.dateWarehouseShipping;
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
}
