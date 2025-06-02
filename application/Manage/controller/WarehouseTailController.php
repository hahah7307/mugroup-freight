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
                        "shipmentNo"				=>	$item[3],
                        "refNo"						=>	$item[4],
                        "warehouseCode"				=>	$item[9],
                        "status"					=>	$item[10],
                        "postalCode"				=>	$item[18],
                        "shippingDate"				=>	$item[19],
                        "zone"						=>	substr($item[20], 4, 1),
                        "charge_weight"				=>	$item[21],
                        "real_weight"				=>	$item[22],
                        "fuel_rate"					=>	$item[24],
                        "outbound"					=>	$item[25],
                        "base"						=>	$item[26],
                        "residential"				=>	$item[27],
                        "das_1"						=>	$item[30],
                        "das_2"						=>	$item[29],
                        "das_3"						=>	$item[28],
                        "signature"					=>	$item[31],
                        "ahs"						=>	$item[35],
                        "oversize"					=>	$item[36],
                        "ahs_pss"					=>	$item[37],
                        "oversize_pss"				=>	$item[38],
                        "fuel_cost"					=>	$item[40],
                        "other"						=>	$item[39] + $item[41] + $item[42] + $item[43] + $item[44],
                        "total"						=>	$item[46],
                        "sku"						=>	$item[47],
                        "length"					=>	$item[48],
                        "width"						=>	$item[49],
                        "height"					=>	$item[50],
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
                        "real_weight"				=>	$item[60],
                        "outbound"					=>	$item[17] + intval($item[32]),
                        "base"						=>	$item[16],
                        "residential"				=>	$item[36],
                        "das_1"						=>	$item[30],
                        "das_2"						=>	$item[31],
                        "signature"					=>	$item[28],
                        "ahs"						=>	$item[21] + $item[22],
                        "oversize"					=>	$item[23],
                        "fuel_cost"					=>	$item[19],
                        "total"						=>	$item[45],
                        "sku"						=>	substr($item[55], 5),
                        "length"					=>	$item[57],
                        "width"						=>	$item[58],
                        "height"					=>	$item[59],
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
