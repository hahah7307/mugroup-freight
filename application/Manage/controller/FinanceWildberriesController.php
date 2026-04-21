<?php
namespace app\Manage\controller;

use app\Manage\model\FinanceReportModel;
use app\Manage\model\FinanceWildberriesCostReturnModel;
use app\Manage\model\FinanceWildberriesExpressDeliveryModel;
use app\Manage\model\FinanceWildberriesFeeModel;
use app\Manage\model\FinanceWildberriesOrderModel;
use PHPExcel_IOFactory;
use PHPExcel_Reader_Exception;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\Session;
use think\Config;

class FinanceWildberriesController extends BaseController
{
    public function index(): \think\response\View
    {

        return view();
    }

    /**
     * @throws DbException
     */
    public function wildberries(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['order_no|shipping_no|fbs_no|name|article_wildberries|article_seller'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 订单列表
        $order = new FinanceWildberriesOrderModel();
        $list = $order->with(['fee'])->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'page_num' => $page_num]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');

        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function wildberries_import()
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
            $financeWildberriesOrderObj = new FinanceWildberriesOrderModel();
            foreach ($data as $item) {
                $order = $financeWildberriesOrderObj->where(['order_no' => $item[0]])->find();
                if (!empty($order)) {
                    continue;
                }
                $orderData[] = [
                    'order_no'	            =>	$item[0],
                    'shipping_no'	        =>	$item[1],
                    'fbs_no'	            =>	$item[2],
                    'box_code'	            =>	$item[3],
                    'created_date'	        =>	date('Y-m-d H:i:s', strtotime($item[4])),
                    'scan_date'	            =>	date('Y-m-d H:i:s', strtotime($item[5])),
                    'name'	                =>	$item[6],
                    'size'	                =>	$item[7],
                    'color'	                =>	$item[8],
                    'barcode'	            =>	$item[9],
                    'total'	                =>	$item[10],
                    'currency'	            =>	$item[11],
                    'article_wildberries'	=>	$item[12],
                    'article_seller'	    =>	$item[13],
                    'warehouse_name'	    =>	$item[14],
                    'delivery_date'	        =>	$item[15],
                    'status'	            =>	$item[16],
                    'user_place'	        =>	$item[17],
                    'user_name'	            =>	$item[18],
                    'user_phone'	        =>	$item[19],
                    'item_scan_date'	    =>	date('Y-m-d H:i:s', strtotime($item[20])),
                    'scan_total'	        =>	$item[21],
                    'order_time'	        =>	$item[22]
                ];
            }
            $financeWildberriesOrderObj->insertAll($orderData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('wildberries'));
        }
        $this->redirect(url('wildberries'));
    }

    /**
     * @throws DbException
     */
    public function cost(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['order_no|product_name|sku|month'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 订单列表
        $order = new FinanceWildberriesFeeModel();
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'page_num' => $page_num]]);
        $this->assign('list', $list);
        $this->assign('cost', $order->where($where)->sum('total'));
        $this->assign('shipping_fee', $order->where($where)->sum('shipping_fee'));

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');

        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function cost_import()
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
            $financeWildberriesFeeObj = new FinanceWildberriesFeeModel();
            foreach ($data as $item) {
                if ($item[0]) {
//                    $order = $financeWildberriesFeeObj->where(['order_no' => $item[0]])->find();
//                    if (!empty($order)) {
//                        continue;
//                    }
                    $orderData[] = [
                        'order_no'		    =>	$item[0],
                        'product_name'		=>	$item[1],
                        'sku'		        =>	$item[2],
//                        'created_date'		=>	DateTime::createFromFormat('m-d-y', $item[3])->format('Y-m-d'),
                        'created_date'		=>	date('Y-m-d', strtotime($item[3])),
                        'unit_price'		=>	$item[4],
                        'quantity'		    =>	$item[5],
                        'total'		        =>	$item[6],
                        'shipping_fee'		=>	$item[7],
                        'month'		        =>	substr($item[8], 0, 6)
                    ];
                }
            }
            $financeWildberriesFeeObj->insertAll($orderData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('cost'));
        }
        $this->redirect(url('cost'));
    }

    /**
     * @throws DbException
     */
    public function express_delivery(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['delivery_no|receiver|receiver_addr|sender_addr|sender|company_name|group_name'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $month = $this->request->get('month', date('Y-m', strtotime('-2 month')));
        $where['month'] = date('Ym', strtotime($month . '-01'));
        $this->assign('month', $month);

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 订单列表
        $order = new FinanceWildberriesExpressDeliveryModel();
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'page_num' => $page_num, 'month' => $month]]);
        $this->assign('list', $list);
        $this->assign('sum', $order->where($where)->sum('total'));

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');

        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function express_delivery_import()
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
            $financeWildberriesExpressDeliveryObj = new FinanceWildberriesExpressDeliveryModel();
            foreach ($data as $item) {
                if ($item[1]) {
                    if (empty($item[13])) {
                        throw new Exception("缺少支付月份！");
                    }
                    $orderData[] = [
                        "created_date"		=>	$item[0],
                        "delivery_no"		=>	$item[1],
                        "weight"		    =>	$item[2],
                        "province"		    =>	$item[3],
                        "receiver"		    =>	$item[4],
                        "target_province"	=>	$item[5],
                        "target_city"		=>	$item[6],
                        "receiver_addr"		=>	$item[7],
                        "sender_addr"		=>	$item[8],
                        "sender"		    =>	$item[9],
                        "company_name"		=>	$item[10],
                        "group_name"		=>	$item[11],
                        "total"		        =>	$item[12],
                        "month"		        =>	$item[13]
                    ];
                }
            }
            $financeWildberriesExpressDeliveryObj->insertAll($orderData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('express_delivery'));
        }
        $this->redirect(url('express_delivery'));
    }

    /**
     * @throws DbException
     */
    public function cost_return(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['product_name|seller_sku|sku'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $month = $this->request->get('month', date('Y-m', strtotime('-2 month')));
        $where['month'] = date('Ym', strtotime($month . '-01'));
        $this->assign('month', $month);

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 订单列表
        $order = new FinanceWildberriesCostReturnModel();
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'page_num' => $page_num, 'month' => $month]]);
        $this->assign('list', $list);
        $this->assign('sum', $order->where($where)->sum('quantity'));

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');

        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function cost_return_import()
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
            $financeWildberriesCostReturnObj = new FinanceWildberriesCostReturnModel();
            foreach ($data as $item) {
                if ($item[1]) {
                    if (empty($item[5])) {
                        throw new Exception("缺少支付月份！");
                    }
                    $orderData[] = [
                        "product_name"		=>	$item[0],
                        "seller_sku"		=>	$item[1],
                        "sku"		        =>	$item[2],
                        "size"		        =>	$item[3],
                        "quantity"		    =>	$item[4],
                        "month"	            =>	$item[5]
                    ];
                }
            }
            $financeWildberriesCostReturnObj->insertAll($orderData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('cost_return'));
        }
        $this->redirect(url('cost_return'));
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     * @throws Exception
     */
    public function cost_calculate()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $reportId = $post['id'];
            $costModel = new FinanceWildberriesFeeModel();
            $reportModel = new FinanceReportModel();
            $report = $reportModel->find($reportId);
            $month = date('Ym', strtotime($report['month'] . '-01'));
            $count = $costModel->where(['report_id' => $reportId, 'calculate_month' => $month])->count();
            if ($count) {
                echo json_encode(['code' => 0, 'msg' => '已同步']);
                exit();
            }

            Db::startTrans();
            try {
                $updateData = $reportModel->query(FinanceReportModel::getWildberriesFeeUpdateSql($report['id'], $month));
                if (!$costModel->saveAll($updateData)) {
                    throw new Exception("同步失败！");
                }

                Db::commit();
                echo json_encode(['code' => 1, 'msg' => '同步完成']);
            } catch (\SoapFault $e) {
                Db::rollback();
                dump($e->getMessage());
                echo json_encode(['code' => 0, 'msg' => '同步失败，请重试']);
            } catch (\Exception $e) {
                Db::rollback();
                dump($e->getMessage());
                echo json_encode(['code' => 0, 'msg' => '同步失败，请重试']);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }
}
