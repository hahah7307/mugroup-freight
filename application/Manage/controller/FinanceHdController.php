<?php
namespace app\Manage\controller;

use app\Manage\model\FinanceHdTailModel;
use PHPExcel_IOFactory;
use PHPExcel_Reader_Exception;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\Session;
use think\Config;

class FinanceHdController extends BaseController
{
    public function index(): \think\response\View
    {

        return view();
    }

    /**
     * @throws DbException
     */
    public function tail($id): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['user_account|warehouse_sku'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 订单列表
        $where['report_id'] = $id;
        $order = new FinanceHdTailModel();
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'page_num' => $page_num]]);
        $this->assign('list', $list);
        $this->assign('cost', $order->where($where)->sum('total'));
        $this->assign('report_id', $id);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');

        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function tail_import()
    {
        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';

        $filename = input('filename');
        $file= "./upload/excel/" . $filename;
        $report_id = input('id');
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();
        unset($data[0]);

        Db::startTrans();
        try {
            $adCostData = [];
            $financeAdCostObj = new FinanceHdTailModel();
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
            $this->error($e->getMessage(), url('tail', ['id' => $report_id]));
        }
        $this->redirect(url('tail', ['id' => $report_id]));
    }
}
