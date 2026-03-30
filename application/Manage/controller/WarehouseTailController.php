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
                        "zone"						=>	intval(substr($item[24], 4, 1)),
                        "charge_weight"				=>	$item[25],
                        "real_weight"				=>	$item[26],
                        "fuel_rate"					=>	$item[28],
                        "outbound"					=>	$item[29],
                        "base"						=>	$item[30],
                        "residential"				=>	$item[31],
                        "das_1"						=>	$item[34],
                        "das_2"						=>	$item[35],
                        "das_3"						=>	$item[36],
                        "signature"					=>	$item[35],
                        "ahs"						=>	$item[37],
                        "oversize"					=>	$item[39],
                        "ahs_pss"					=>	$item[38],
                        "oversize_pss"				=>	$item[40],
                        "fuel_cost"					=>	$item[47],
                        "total"						=>	$item[52],
                        "sku"						=>	$item[53],
                        "length"					=>	$item[54],
                        "width"						=>	$item[55],
                        "height"					=>	$item[56],
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
                        "real_weight"				=>	$item[70],
                        "outbound"					=>	$item[23],
                        "base"						=>	$item[22],
                        "residential"				=>	$item[43],
                        "das_1"						=>	$item[35],
                        "das_2"						=>	$item[36],
                        "signature"					=>	$item[33],
                        "ahs"						=>	$item[26] + $item[27],
                        "oversize"					=>	$item[28],
                        "fuel_cost"					=>	$item[24],
                        "total"						=>	$item[53],
                        "sku"						=>	substr($item[63], 5),
                        "length"					=>	$item[65],
                        "width"						=>	$item[66],
                        "height"					=>	$item[67],
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
