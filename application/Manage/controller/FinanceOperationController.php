<?php
namespace app\Manage\controller;

use app\Manage\model\FinanceExcelInit;
use app\Manage\model\FinanceOperationDeliveryModel;
use app\Manage\model\FinanceOperationExpensesModel;
use app\Manage\model\FinanceOperationFactoryClaimModel;
use app\Manage\model\FinanceOperationFactoryModel;
use Exception;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Reader_Exception;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Session;
use think\Config;

class FinanceOperationController extends BaseController
{
    /**
     */
    public function index(): \think\response\View
    {
        $type = $this->request->get('type', 1);
        $this->assign('type', $type);
        if ($type == 1) {
            $field = 'calculate_month';
        } elseif ($type == 2) {
            $field = 'month';
        } else {
            $field = 'calculate_month';
        }

        $month = $this->request->get('month', date('Y-m', strtotime('-1 month')));
        $calculate_month = date('Ym', strtotime($month . '-01'));
        $this->assign('month', $month);

        $expenses = new FinanceOperationExpensesModel();
        $list1 = $expenses->where([$field => $calculate_month])->order('id asc')->select();
        $this->assign('list1', $list1);
        $this->assign('list1_sum', $expenses->where([$field => $calculate_month])->sum('total'));

        //
        $factory = new FinanceOperationFactoryModel();
        $list2 = $factory->where([$field => $calculate_month])->order('id asc')->select();
        $this->assign('list2', $list2);
        $this->assign('list2_sum', $factory->where([$field => $calculate_month])->sum('total'));

        //
        $delivery = new FinanceOperationDeliveryModel();
        $list3 = $delivery->where([$field => $calculate_month])->order('id asc')->select();
        $this->assign('list3', $list3);
        $this->assign('list3_sum', $delivery->where([$field => $calculate_month])->sum('total'));

        return view();
    }

    /**
     * @throws DbException
     */
    public function expenses(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['sku|applicant|type|content|export_platform'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        //
        $expenses = new FinanceOperationExpensesModel();
        $list = $expenses->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'page_num' => $page_num]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function expenses_import()
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
            $expensesData = [];
            $financeOperationExpensesObj = new FinanceOperationExpensesModel();
            foreach ($data as $item) {
                $expensesData[] = [
                    "month"                 =>  intval($item[0]),
                    "sku"                   =>  $item[1],
                    "applicant"             =>  $item[2],
                    "currency"              =>  $item[3],
                    "total"                 =>  sprintf('%.2f', str_replace(',', '', $item[4])),
                    "content"               =>  $item[5],
                    "type"                  =>  $item[6],
                    "export_platform"       =>  $item[8],
                ];
            }
            $financeOperationExpensesObj->insertAll($expensesData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('expenses'));
        }
        $this->redirect(url('expenses'));
    }

    /**
     * @throws DataNotFoundException
     * @throws \PHPExcel_Writer_Exception
     * @throws ModelNotFoundException
     * @throws PHPExcel_Reader_Exception
     * @throws DbException
     */
    public function expenses_export()
    {
        $financeOperationExpensesObj = new FinanceOperationExpensesModel();
        $list = $financeOperationExpensesObj->select();
        if (empty($list)) {
            $this->error('异常操作！', url('report'));
        }

        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();
        $financeExcelInit = new FinanceExcelInit($objPHPExcel);
        $financeExcelInit->getFinanceOperationExpensesExport(0, $list);
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

    public function expenses_delete()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $skuRelationObj = new FinanceOperationExpensesModel();
            if ($skuRelationObj->where('id', $post['id'])->delete()) {
                echo json_encode(['code' => 1, 'msg' => '删除成功']);
            } else {
                echo json_encode(['code' => 0, 'msg' => '删除失败，请重试']);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }

    /**
     * @throws DbException
     */
    public function factory(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['sku|type|content'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        //
        $factory = new FinanceOperationFactoryModel();
        $list = $factory->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'page_num' => $page_num]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function factory_import()
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
            $factoryData = [];
            $financeOperationFactoryObj = new FinanceOperationFactoryModel();
            foreach ($data as $item) {
                $factoryData[] = [
                    "month"                 =>  intval($item[0]),
                    "sku"                   =>  $item[1],
                    "currency"              =>  $item[3],
                    "total"                 =>  sprintf('%.2f', str_replace(',', '', $item[2])),
                    "content"               =>  $item[4],
                    "type"                  =>  $item[5],
                    "factory_type"          =>  $item[6],
                    "invoice_no"            =>  $item[7]
                ];
            }
            $financeOperationFactoryObj->insertAll($factoryData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('factory'));
        }
        $this->redirect(url('factory'));
    }

    /**
     * @throws DataNotFoundException
     * @throws \PHPExcel_Writer_Exception
     * @throws ModelNotFoundException
     * @throws PHPExcel_Reader_Exception
     * @throws DbException
     */
    public function factory_export()
    {
        $financeOperationFactoryObj = new FinanceOperationFactoryModel();
        $list = $financeOperationFactoryObj->select();
        if (empty($list)) {
            $this->error('异常操作！', url('report'));
        }

        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();
        $financeExcelInit = new FinanceExcelInit($objPHPExcel);
        $financeExcelInit->getFinanceOperationFactoryExport(0, $list);
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

    public function factory_delete()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $skuRelationObj = new FinanceOperationFactoryModel();
            if ($skuRelationObj->where('id', $post['id'])->delete()) {
                echo json_encode(['code' => 1, 'msg' => '删除成功']);
            } else {
                echo json_encode(['code' => 0, 'msg' => '删除失败，请重试']);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }

    /**
     * @throws DbException
     */
    public function delivery(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['tracking_number|sender|seller|platform|user_account|sku'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        //
        $delivery = new FinanceOperationDeliveryModel();
        $list = $delivery->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'page_num' => $page_num]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function delivery_import()
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
            $deliveryData = [];
            $financeOperationDeliveryObj = new FinanceOperationDeliveryModel();
            foreach ($data as $item) {
                $deliveryData[] = [
                    "month"                 =>  intval($item[0]),
                    "tracking_number"       =>  $item[1],
                    "delivery_date"         =>  date('Ymd', strtotime($item[2])),
                    "sender"                =>  $item[3],
                    "seller"                =>  $item[4],
                    "platform"              =>  $item[5],
                    "user_account"          =>  $item[6],
                    "sku"                   =>  $item[7],
                    "total"                 =>  sprintf('%.2f', str_replace(',', '', $item[8])),
                ];
            }
            $financeOperationDeliveryObj->insertAll($deliveryData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('delivery'));
        }
        $this->redirect(url('delivery'));
    }

    /**
     * @throws DataNotFoundException
     * @throws \PHPExcel_Writer_Exception
     * @throws ModelNotFoundException
     * @throws PHPExcel_Reader_Exception
     * @throws DbException
     */
    public function delivery_export()
    {
        $financeOperationDeliveryObj = new FinanceOperationDeliveryModel();
        $list = $financeOperationDeliveryObj->select();
        if (empty($list)) {
            $this->error('异常操作！', url('report'));
        }

        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();
        $financeExcelInit = new FinanceExcelInit($objPHPExcel);
        $financeExcelInit->getFinanceOperationDeliveryExport(0, $list);
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

    public function delivery_delete()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $skuRelationObj = new FinanceOperationDeliveryModel();
            if ($skuRelationObj->where('id', $post['id'])->delete()) {
                echo json_encode(['code' => 1, 'msg' => '删除成功']);
            } else {
                echo json_encode(['code' => 0, 'msg' => '删除失败，请重试']);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }

    /**
     * @throws DbException
     */
    public function factory_claim(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['sku|type|content'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $month = $this->request->get('month', date('Y-m', strtotime('-1 month')));
        $calculate_month = date('Ym', strtotime($month . '-01'));
        $where['month'] = $calculate_month;
        $this->assign('month', $month);

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        //
        $factory_claim = new FinanceOperationFactoryClaimModel();
        $list = $factory_claim->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'month' => $month, 'page_num' => $page_num]]);
        $this->assign('list', $list);
        $this->assign('list_sum', $factory_claim->where($where)->sum('total'));

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function factory_claim_import()
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
            $factory_claimData = [];
            $financeOperationFactoryObj = new FinanceOperationFactoryClaimModel();
            foreach ($data as $item) {
                $factory_claimData[] = [
                    "month"                 =>  intval($item[0]),
                    "sku"                   =>  $item[1],
                    "currency"              =>  $item[3],
                    "total"                 =>  sprintf('%.2f', str_replace(',', '', $item[2])),
                    "content"               =>  $item[4],
                    "type"                  =>  $item[5],
                    "factory_type"          =>  $item[6],
                    "invoice_no"            =>  $item[7]
                ];
            }
            $financeOperationFactoryObj->insertAll($factory_claimData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('factory_claim'));
        }
        $this->redirect(url('factory_claim'));
    }

    /**
     * @throws DataNotFoundException
     * @throws \PHPExcel_Writer_Exception
     * @throws ModelNotFoundException
     * @throws PHPExcel_Reader_Exception
     * @throws DbException
     */
    public function factory_claim_export()
    {
        $financeOperationFactoryClaimObj = new FinanceOperationFactoryClaimModel();
        $list = $financeOperationFactoryClaimObj->select();
        if (empty($list)) {
            $this->error('异常操作！', url('report'));
        }

        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();
        $financeExcelInit = new FinanceExcelInit($objPHPExcel);
        $financeExcelInit->getFinanceOperationFactoryClaimExport(0, $list);
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

    public function factory_claim_delete()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $skuRelationObj = new FinanceOperationFactoryClaimModel();
            if ($skuRelationObj->where('id', $post['id'])->delete()) {
                echo json_encode(['code' => 1, 'msg' => '删除成功']);
            } else {
                echo json_encode(['code' => 0, 'msg' => '删除失败，请重试']);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }
}
