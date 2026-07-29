<?php
namespace app\Manage\controller;

use app\Manage\model\WarehouseRentEstimateBatchModel;
use app\Manage\model\WarehouseRentEstimateModel;
use Exception;
use PHPExcel_IOFactory;
use PHPExcel_Reader_Exception;
use think\Db;
use think\exception\DbException;
use think\Config;
use think\Session;

class WarehouseRentController extends BaseController
{
    /**
     * @throws DbException
     */
    public function estimate(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['title'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);
        //
        $model = new WarehouseRentEstimateModel();
        $list = $model->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function estimate_import()
    {
        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';
        $filename = input('filename');
        $origin = input('origin');
        $file = "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();
        unset($data[0]);

        Db::startTrans();
        try {
            $tableObj = new WarehouseRentEstimateModel();
            $table = [
                'title'         =>  $origin,
                'created_time'  =>  date('Y-m-d H:i:s')
            ];
            if ($id = $tableObj->insertGetId($table)) {
                $batchData = [];
                foreach ($data as $item) {
                    $batchData[] = [
                        "estimate_id"           =>  $id,
                        "warehouse_sku"         =>  $item[0],
                        "warehouse_code"        =>  $item[1],
                        "store"                 =>  $item[2],
                        "age"                   =>  $item[3],
                        "daily_sale"            =>  $item[4],
                        "is_finished"           =>  0,
                    ];
                }
                $batchObj = new WarehouseRentEstimateBatchModel();
                if (!$batchObj->insertAll($batchData)) {

                    throw new Exception('表格导入失败');
                }
            } else {

                throw new Exception('表格导入失败');
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), session('back_url', '', 'manage'));
        }
        $this->redirect(session('back_url', '', 'manage'));
    }

    // 编辑
    /**
     * @throws DbException
     */
    public function edit($id)
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            if (empty($post['start_date'])) {
                echo json_encode(['code' => 0, 'msg' => '缺少开始日期']);
                exit;
            } else {
                $post['start_date'] = date('Ymd', strtotime($post['start_date']));
            }

            if (empty($post['end_date'])) {
                $post['end_date'] = null;
            } else {
                $post['end_date'] = date('Ymd', strtotime($post['end_date']));
            }

            $model = new WarehouseRentEstimateModel();
            if ($model->allowField(true)->save($post, ['id' => $id])) {
                echo json_encode(['code' => 1, 'msg' => '修改成功']);
            } else {
                echo json_encode(['code' => 0, 'msg' => '修改失败，请重试']);
            }
            exit;
        } else {
            $info = WarehouseRentEstimateModel::get(['id' => $id,]);
            $this->assign('info', $info);

            return view();
        }
    }
}
