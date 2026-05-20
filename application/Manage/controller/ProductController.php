<?php
namespace app\Manage\controller;

use app\Manage\model\AkAmazonDailyListModel;
use app\Manage\model\FinanceExcelInit;
use app\Manage\model\LcProductModel;
use app\Manage\model\LeProductModel;
use app\Manage\model\ProductModel;
use app\Manage\model\WydProductModel;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Reader_Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Session;
use think\Config;

class ProductController extends BaseController
{
    /**
     * @throws DbException
     */
    public function index(): \think\response\View
    {
        $where = [];
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['productSku'] = ['like', '%' . $keyword . '%'];
        }

        $storage = new ProductModel();
        $list = $storage->where($where)->order('id asc')->paginate(Config::get('PAGE_NUM'), false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws DbException
     */
    public function lc(): \think\response\View
    {
        $where = [];
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['product_sku'] = ['like', '%' . $keyword . '%'];
        }

        $storage = new LcProductModel();
        $list = $storage->where($where)->order('id asc')->paginate(Config::get('PAGE_NUM'), false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws DbException
     */
    public function le(): \think\response\View
    {
        $where = [];
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['code'] = ['like', '%' . $keyword . '%'];
        }

        $storage = new LeProductModel();
        $list = $storage->where($where)->order('id asc')->paginate(Config::get('PAGE_NUM'), false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws DbException
     */
    public function wyd(): \think\response\View
    {
        $where = [];
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['sku'] = ['like', '%' . $keyword . '%'];
        }

        $storage = new WydProductModel();
        $list = $storage->where($where)->order('id asc')->paginate(Config::get('PAGE_NUM'), false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws DbException
     */
    public function performance(): \think\response\View
    {
        $where = [];
        $keyword = $this->request->get('date', date('Y-m-d'), 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['r_date'] = $keyword;
        }

        $model = new AkAmazonDailyListModel();
        $list = $model->where($where)->order('id asc')->paginate(Config::get('PAGE_NUM'), false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws DataNotFoundException
     * @throws \PHPExcel_Writer_Exception
     * @throws ModelNotFoundException
     * @throws PHPExcel_Reader_Exception
     * @throws DbException
     */
    public function expenses_export($date)
    {
        $financeOperationExpensesObj = new AkAmazonDailyListModel();
        $list = $financeOperationExpensesObj->where(['r_date'=>$date])->select();
        if (empty($list)) {
            $this->error('异常操作！', url('report'));
        }

        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();
        $financeExcelInit = new FinanceExcelInit($objPHPExcel);
        $financeExcelInit->getDateProductPerformanceSql(0, $list);
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
}
