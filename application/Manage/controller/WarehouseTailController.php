<?php
namespace app\Manage\controller;

use app\Manage\model\WarehouseTailModel;
use Exception;
use PHPExcel_IOFactory;
use PHPExcel_Reader_Exception;
use think\Db;
use think\exception\DbException;
use think\Config;

class WarehouseTailController extends BaseController
{
    /**
     * @throws DbException
     */
    public function index(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['saleOrderCode|shipmentNo|refNo|sku'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);
        //
        $model = new WarehouseTailModel();
        $list = $model->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);

        $this->assign('totalSum', $model->where($where)->sum('total'));

        return view();
    }

    /**
     * @throws DbException
     * @throws PHPExcel_Reader_Exception
     * @throws \think\Exception
     */
    public function import(): \think\response\View
    {
        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';

        $filename = input('filename');
        $month = input('month');
        $file= "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheets = $excelObj->getSheetNames();
        $warehouse = 'LC';
        $worksheet = [];
        foreach ($worksheets as $k => $v) {
            if ($v == '运费') {
                $warehouse = 'LE';
                $worksheet = $excelObj->getSheet($k);
//            } elseif (strpos($v, 'B2C')) {
            } elseif ($v =='B2C') {
                $worksheet = $excelObj->getSheet($k);
            }
        }
        $data = $worksheet->toArray();
        unset($data[0]);

        Db::startTrans();
        try {
            $warehouseTailData = [];
            $financeWarehouseTailObj = new WarehouseTailModel();
            if ($warehouse == "LE") {
                foreach ($data as $item) {
                    $warehouseTailData[] = [
                        "saleOrderCode"				=>	$item[1],
                        "shipmentNo"				=>	$item[4],
                        "refNo"						=>	$item[5],
                        "warehouseCode"				=>	$item[10],
                        "status"					=>	$item[11],
                        "postalCode"				=>	$item[22],
                        "shippingDate"				=>	$item[23],
                        "zone"						=>	intval(substr($item[20], 4, 1)),
                        "charge_weight"				=>	$item[25],
                        "real_weight"				=>	$item[26],
                        "fuel_rate"					=>	$item[28],
                        "outbound"					=>	$item[29],
                        "base"						=>	$item[30],
                        "residential"				=>	$item[31],
                        "das_1"						=>	$item[34],
                        "das_2"						=>	$item[33],
                        "das_3"						=>	$item[32],
                        "signature"					=>	$item[35],
                        "ahs"						=>	$item[39],
                        "oversize"					=>	$item[40],
                        "ahs_pss"					=>	$item[41],
                        "oversize_pss"				=>	$item[42],
                        "fuel_cost"					=>	$item[44],
                        "other"						=>	$item[43] + $item[45] + $item[46] + $item[47] + $item[48],
                        "total"						=>	$item[50],
                        "sku"						=>	$item[51],
                        "length"					=>	$item[52],
                        "width"						=>	$item[53],
                        "height"					=>	$item[54],
                        "month"                     =>  date('Ym', strtotime($month . "-01")),
                        "warehouseName"             =>  $warehouse
                    ];
                }
            } elseif ($warehouse == "LC") {
                foreach ($data as $item) {
                    $warehouseTailData[] = [
                        "saleOrderCode"				=>	$item[2],
                        "shipmentNo"				=>	$item[6],
                        "refNo"						=>	$item[4],
                        "warehouseCode"				=>	$item[1],
                        "postalCode"				=>	$item[12],
                        "shippingDate"				=>	$item[9],
                        "zone"						=>	intval($item[15]),
                        "charge_weight"				=>	$item[13],
                        "real_weight"				=>	$item[63],
                        "outbound"					=>	$item[17] + intval($item[35]),
                        "base"						=>	$item[16],
                        "residential"				=>	$item[39],
                        "das_1"						=>	$item[30],
                        "das_2"						=>	$item[31],
                        "signature"					=>	$item[28],
                        "ahs"						=>	$item[21] + $item[22],
                        "oversize"					=>	$item[23],
                        "fuel_cost"					=>	$item[19],
                        "total"						=>	$item[48],
                        "sku"						=>	substr($item[58], 5),
                        "length"					=>	$item[60],
                        "width"						=>	$item[61],
                        "height"					=>	$item[62],
                        "month"                     =>  date('Ym', strtotime($month . "-01")),
                        "warehouseName"             =>  $warehouse
                    ];
                }
            }
            $financeWarehouseTailObj->insertAll($warehouseTailData);

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), url('index'));
        }
        $this->redirect(url('index'));
    }
}
