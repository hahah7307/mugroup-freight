<?php
namespace app\Manage\controller;

use app\Manage\model\LeInventoryBatchModel;
use PHPExcel_IOFactory;
use PHPExcel_Reader_Exception;
use think\exception\DbException;
use think\Session;
use think\Config;

class LeInventoryController extends BaseController
{
    /**
     * @throws DbException
     */
    public function index(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['businessNo|warehouseCode|lecangsCode|enName|cnName'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $warehouse_code = $this->request->get('warehouseCode', '', 'htmlspecialchars');
        $this->assign('warehouseCode', $warehouse_code);
        if ($warehouse_code) {
            $where['warehouseCode'] = $warehouse_code;
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 库存数据列表
        $inventory = new LeInventoryBatchModel();
        $list = $inventory->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'page_num' => $page_num, 'warehouseCode' => $warehouse_code]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws PHPExcel_Reader_Exception
     */
    public function import()
    {
        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';

        $filename = input('filename');
        $file= "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();
        array_shift($data);

        $newData = [];
        foreach ($data as $item) {
            if (empty($item[0])) {
                continue;
            }
            $newData[] = [
                'date_no'               =>  $item[0],
                'warehouse_code'        =>  $item[3],
                'receiving_code'        =>  $item[6],
                'product_sku'           =>  substr($item[7], 6),
                'type'                  =>  $item[8],
                'product_length'        =>  round($item[10], 6),
                'product_width'         =>  round($item[11], 6),
                'product_height'        =>  round($item[12], 6),
                'ib_quantity'           =>  $item[14],
                'stock_age'             =>  $item[17],
                'created_year'          =>  date('Y', strtotime($item[4])),
                'created_month'         =>  date('Ym', strtotime($item[4])),
                'created_date'          =>  $item[4],
                'created_time'          =>  $item[9]
            ];
        }

        $addressPostal = new LeInventoryBatchModel();
        $addressPostal->insertAll($newData);
        unset($newData);

        $this->redirect(Session::get(Config::get('BACK_URL'), 'manage'));
    }
}
