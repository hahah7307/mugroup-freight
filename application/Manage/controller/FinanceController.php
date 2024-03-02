<?php
namespace app\Manage\controller;

use app\Manage\model\FinanceOrderModel;
use app\Manage\model\FinanceOrderOutboundModel;
use app\Manage\model\FinanceReportModel;
use app\Manage\model\FinanceTableModel;
use app\Manage\model\OrderAddressModel;
use app\Manage\model\OrderModel;
use app\Manage\model\ProductModel;
use app\Manage\validate\FinanceReportValidate;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Reader_Exception;
use PHPExcel_Style_Fill;
use SoapClient;
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

        $sqlReport = '
SELECT
	platform 平台,
	userAccount 店铺,
	SUM(count) 销量,
	SUM(product_sales) product_sales,
	SUM(shipping_credits) shipping_credits,
	SUM(gift_wrap_credits) gift_wrap_credits,
	SUM(regulatory_fee) regulatory_fee,
	SUM(promotional_rebates) promotional_rebates,
	SUM(selling_fees) selling_fees,
	SUM(fba_fees) fba_fees,
	SUM(tail_amount) 尾程,
	description
FROM
(	
SELECT
	ec_o.platform platform,
	ec_o.userAccount userAccount,
	o.payment_id payment_id,
	o.product_sales product_sales,
	o.shipping_credits shipping_credits,
	o.gift_wrap_credits gift_wrap_credits,
	o.regulatory_fee regulatory_fee,
	o.promotional_rebates promotional_rebates,
	o.selling_fees selling_fees,
	o.fba_fees fba_fees,
	o.description description,
	COUNT(ec_o.id) count,
	SUM(d.tail_amount) tail_amount
FROM
	mu_finance_order o
	LEFT JOIN mu_finance_order_outbound d ON o.id = d.finance_order_id
	LEFT JOIN mu_ecang_order ec_o ON d.ecang_order_id = ec_o.id 
WHERE
	order_type = "Order" 
    AND o.rid = ' . $report_id . '
GROUP BY
	platform,
	userAccount,
	payment_id,
	product_sales,
	shipping_credits,
	gift_wrap_credits,
	regulatory_fee,
	promotional_rebates,
	selling_fees,
	fba_fees,
	description
) c
GROUP BY
	platform,
	userAccount,
	description;';
        $reportRes = $financeReportObj->query($sqlReport);

        $adCosetRes = '
SELECT
	b.name 店铺,
	a.msku 店铺SKU,
	a.localSku 系统SKU,
	SUM(a.totalSalesQuantity) 销量,
	SUM(a.totalAdsCost) 广告费,
	a.reportDateMonth 月份,
	a.principalRealname 运营人员,
	a.productDeveloperRealname 开发人员
FROM
	mu_ak_ad_cost a
	LEFT JOIN mu_ak_seller b ON a.sid = b.sid
WHERE
	a.reportDateMonth = "' . $report['month'] . '"
GROUP BY
	b.name,
	a.reportDateMonth,
	a.principalRealname,
	a.productDeveloperRealname,
	a.msku,
	a.localSku
ORDER BY
	b.name,
	a.localSku';
        $adCostRes = $financeReportObj->query($adCosetRes);

        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();

        // Set name sheet
        $objPHPExcel->setActiveSheetIndex(0)->setTitle('尾程报表');

        // Add some data
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '平台')
            ->setCellValue('B1', '店铺')
            ->setCellValue('C1', '销量')
            ->setCellValue('D1', 'product_sales')
            ->setCellValue('E1', 'shipping_credits')
            ->setCellValue('F1', 'gift_wrap_credits')
            ->setCellValue('G1', 'regulatory_fee')
            ->setCellValue('H1', 'promotional_rebates')
            ->setCellValue('I1', 'selling_fees')
            ->setCellValue('J1', 'fba_fees')
            ->setCellValue('K1', '尾程')
            ->setCellValue('L1', 'description')
        ;

        $reportIndex = 1;
        foreach ($reportRes as $item) {
            $reportIndex ++;
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . $reportIndex, $item['平台'])
                ->setCellValue('B' . $reportIndex, $item['店铺'])
                ->setCellValue('C' . $reportIndex, $item['销量'])
                ->setCellValue('D' . $reportIndex, $item['product_sales'])
                ->setCellValue('E' . $reportIndex, $item['shipping_credits'])
                ->setCellValue('F' . $reportIndex, $item['gift_wrap_credits'])
                ->setCellValue('G' . $reportIndex, $item['regulatory_fee'])
                ->setCellValue('H' . $reportIndex, $item['promotional_rebates'])
                ->setCellValue('I' . $reportIndex, $item['selling_fees'])
                ->setCellValue('J' . $reportIndex, $item['fba_fees'])
                ->setCellValue('K' . $reportIndex, $item['尾程'])
                ->setCellValue('L' . $reportIndex, $item['description'])
            ;
        }

        // create new sheet
        $objPHPExcel->createSheet();

        // Set name sheet
        $objPHPExcel->setActiveSheetIndex(1)->setTitle('广告费分摊');

        // Add some data
        $objPHPExcel->setActiveSheetIndex(1)
            ->setCellValue('A1', '店铺号')
            ->setCellValue('B1', '店铺SKU')
            ->setCellValue('C1', '系统SKU')
            ->setCellValue('D1', '销量')
            ->setCellValue('E1', '广告费')
            ->setCellValue('F1', '月份')
            ->setCellValue('G1', '运营人员')
            ->setCellValue('H1', '开发人员')
        ;

        $adIndex = 1;
        foreach ($adCostRes as $item) {
            $adIndex ++;
            $objPHPExcel->setActiveSheetIndex(1)
                ->setCellValue('A' . $adIndex, $item['店铺'])
                ->setCellValue('B' . $adIndex, $item['店铺SKU'])
                ->setCellValue('C' . $adIndex, $item['系统SKU'])
                ->setCellValue('D' . $adIndex, $item['销量'])
                ->setCellValue('E' . $adIndex, $item['广告费'])
                ->setCellValue('F' . $adIndex, $item['月份'])
                ->setCellValue('G' . $adIndex, $item['运营人员'])
                ->setCellValue('H' . $adIndex, $item['开发人员'])
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
        $file= "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();

        if (count($data[0]) != 30) {
            $this->error("请上传正确的表格", Session::get(Config::get('BACK_URL')));
        }

        Db::startTrans();
        try {
            $tableData = [
                'rid'           =>  $rid,
                'table_name'    =>  $origin,
                'created_at'    =>  date('Y-m-d H:i:s')
            ];
            $financeTableObj = new FinanceTableModel();
            if ($tableId = $financeTableObj->insertGetId($tableData)) {
                $new = [];
                foreach ($data as $item) {
                    if (!is_numeric($item[29])) {
                        continue;
                    }
                    $new[] = [
                        "rid"                       =>  $rid,
                        "table_id"                  =>  $tableId,
                        "settlement_id"             =>  $item[1],
                        "payment_id"                =>  $item[3],
                        "order_time"                =>  date('Y-m-d H:i:s', strtotime($item[0])),
                        "order_type"                =>  $item[2],
                        "sku"                       =>  $item[4],
                        "description"               =>  $item[5],
                        "qty"                       =>  $item[6],
                        "market_place"              =>  $item[7],
                        "account_type"              =>  $item[8],
                        "fulfillment"               =>  $item[9],
                        "order_city"                =>  $item[10],
                        "order_state"               =>  $item[11],
                        "postal"                    =>  $item[12],
                        "tax_collection_model"      =>  $item[13],
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
                }
                $financeOrderObj = new FinanceOrderModel();
                if (!$financeOrderObj->saveAll($new)) {
                    throw new \think\Exception('Payment导入失败！');
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
        $order = new FinanceOrderModel();
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
        $financeOrderObj = new FinanceOrderModel();
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
}
