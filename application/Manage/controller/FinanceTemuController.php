<?php
namespace app\Manage\controller;

use app\Manage\model\FinanceTemuTailModel;
use PHPExcel_IOFactory;
use PHPExcel_Reader_Exception;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\Session;
use think\Config;

class FinanceTemuController extends BaseController
{
    public function index(): \think\response\View
    {

        return view();
    }

    /**
     * @throws DbException
     */
    public function tail(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['package_number|waybill_number'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 订单列表
        $order = new FinanceTemuTailModel();
        $list = $order->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'page_num' => $page_num]]);
        $this->assign('list', $list);

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
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();
        unset($data[0]);

        Db::startTrans();
        try {
            $orderData = [];
            $saveData = [];
            $financeTemuTailObj = new FinanceTemuTailModel();
            foreach ($data as $item) {
                if ($item[3] == '调整(退款)') {
                    $order = $financeTemuTailObj->where(['package_number' => $item[0], 'waybill_number' => $item[1], 'total' => $item[4] * -1])->find();
                } else {
                    $order = $financeTemuTailObj->where(['package_number' => $item[0], 'waybill_number' => $item[1], 'total' => $item[4]])->find();
                }

                if (!empty($order)) {
                    if ($order['total'] * -1 == $item[4]) {
                        continue;
                    }
                    if (empty($order['time']) && $item[7] != "--") {
                        // 更新
                        $saveData[] = [
                            'id'                            =>  $order['id'],
                            'reconciliation_bill_status'    =>  $item[6],
                            'time'                          =>  $item[7]
                        ];
                    } else {
                        continue;
                    }
                } else {
                    $orderData[] = [
                        "package_number"                =>  $item[0],
                        "waybill_number"                =>  $item[1],
                        "service_provider_code"         =>  $item[2],
                        "bill_type"                     =>  $item[3],
                        "total"                         =>  $item[3] == '调整(退款)' ? $item[4] * -1 : $item[4],
                        "currency"                      =>  $item[5],
                        "reconciliation_bill_status"    =>  $item[6],
                        "time"                          =>  $item[7] == '--' ? null : $item[7],
                    ];
                }
            }

            $financeTemuTailObj->saveAll($saveData);
            $financeTemuTailObj->insertAll($orderData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('tail'));
        }
        $this->redirect(url('tail'));
    }
}
