<?php
namespace app\Manage\controller;

use app\Manage\model\FinanceOrderModel;
use app\Manage\model\FinanceOrderOutboundModel;
use app\Manage\model\OrderAddressModel;
use app\Manage\model\OrderModel;
use app\Manage\model\ProductModel;
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
    public function index(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['payment_id'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

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
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'order_type' => $order_type, 'fulfillment' => $fulfillment, 'page_num' => $page_num]]);
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
        $file= "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();

        if (count($data[0]) != 30) {
            $this->error("请上传正确的表格", Session::get(Config::get('BACK_URL')));
        }

        $new = [];
        foreach ($data as $item) {
            if (!is_numeric($item[29])) {
                continue;
            }
            $new[] = [
                "tag"                       =>  intval($filename),
                "filename"                  =>  $origin,
                "payment_id"                =>  $item[3],
                "order_type"                =>  $item[2],
                "sku"                       =>  $item[4],
                "qty"                       =>  $item[6],
                "account_type"              =>  $item[8],
                "fulfillment"               =>  $item[9],
                "postal"                    =>  $item[12],
                "product_sales"             =>  sprintf('%.2f',abs($item[14])),
                "product_sales_tax"         =>  sprintf('%.2f',abs($item[15])),
                "shipping_credits"          =>  sprintf('%.2f',abs($item[16])),
                "shipping_credits_tax"      =>  sprintf('%.2f',abs($item[17])),
                "gift_wrap_credits"         =>  sprintf('%.2f',abs($item[18])),
                "gift_wrap_credits_tax"     =>  sprintf('%.2f',abs($item[19])),
                "regulatory_fee"            =>  sprintf('%.2f',abs($item[20])),
                "regulatory_fee_tax"        =>  sprintf('%.2f',abs($item[21])),
                "promotional_rebates"       =>  sprintf('%.2f',abs($item[22])),
                "promotional_rebates_tax"   =>  sprintf('%.2f',abs($item[23])),
                "marketplace_withheld_tax"  =>  sprintf('%.2f',abs($item[24])),
                "selling_fees"              =>  sprintf('%.2f',abs($item[25])),
                "fba_fees"                  =>  sprintf('%.2f',abs($item[26])),
                "other_transaction_fees"    =>  sprintf('%.2f',abs($item[27])),
                "other"                     =>  sprintf('%.2f',abs($item[28])),
                "total"                     =>  sprintf('%.2f',abs($item[29])),
                "created_date"              =>  date('Y-m-d H:i:s', strtotime($item[0]))
            ];
        }
        $financeOrderObj = new FinanceOrderModel();
        $financeOrderObj->saveAll($new);

        $this->redirect(Session::get(Config::get('BACK_URL'), 'manage'));
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
