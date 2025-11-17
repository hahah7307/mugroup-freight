<?php
namespace app\Manage\controller;

use app\Manage\model\WarehouseShipmentDiffItemModel;
use app\Manage\model\WarehouseShipmentDiffModel;
use app\Manage\model\WarehouseTailModel;
use Exception;
use PHPExcel_IOFactory;
use PHPExcel_Reader_Exception;
use think\Db;
use think\exception\DbException;
use think\Config;
use think\Session;

class WarehouseShipmentController extends BaseController
{
    /**
     * @throws DbException
     */
    public function area_diff(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['table_name'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        // 查看权限
//        $access_ids = AccountModel::account_access_ids();
//        $where['user_id'] = ['in', $access_ids];

        $quoteTableObj = new WarehouseShipmentDiffModel();
        $list = $quoteTableObj->where($where)->order('id desc')->paginate(Config::get('PAGE_NUM'), false, ['keyword' => $keyword]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws DbException
     * @throws PHPExcel_Reader_Exception
     * @throws \think\Exception
     */
    public function area_diff_import(): \think\response\View
    {
        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';

        $filename = input('filename');
        $origin = input('origin');
        $file= "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheets = $excelObj->getSheetNames();
        $worksheet = [];
        foreach ($worksheets as $key => $sheet) {
            if ($sheet == "出运清单") {
                $worksheet = $excelObj->getSheet($key);
            }
        }
        $data = $worksheet->toArray();
        unset($data[0]);
        unset($data[1]);
        unset($data[2]);
        unset($data[3]);

        Db::startTrans();
        try {
            $tableObj = new WarehouseShipmentDiffModel();
            $table = [
                'table_name'    =>  $origin,
                'created_time'  =>  date('Y-m-d H:i:s')
            ];
            if ($id = $tableObj->insertGetId($table)) {
                $productData = [];
                foreach ($data as $item) {
                    if (empty($item[0])) {
                        continue;
                    }
                    $productData[] = [
                        "diff_id"		                =>	$id,
                        "carton_length"		            =>	$item[0],
                        "carton_width"		            =>	$item[1],
                        "carton_height"		            =>	$item[2],
                        "contact_no"		            =>	$item[3],
                        "contact_user"		            =>	$item[4],
                        "product_sku"		            =>	$item[5],
                        "sku"		                    =>	$item[6],
                        "sku_en_name"		            =>	$item[7],
                        "sku_cn_name"		            =>	$item[8],
                        "export_currency"		        =>	$item[9],
                        "export_unit_price"		        =>	$item[10],
                        "currency"		                =>	$item[11],
                        "unit_price"		            =>	$item[12],
                        "unit"		                    =>	$item[13],
                        "inner_box_qty"		            =>	$item[14],
                        "outer_carton_qty"		        =>	$item[15],
                        "carton_qty"		            =>	$item[16],
                        "outer_carton_gross_weight"	    =>	$item[17],
                        "outer_carton_net_weight"	    =>	$item[18],
                        "outer_carton_volume"		    =>	$item[19],
                        "total_gross_weight"		    =>	$item[20],
                        "total_net_weight"		        =>	$item[21],
                        "total_qty"		                =>	$item[22],
                        "total_volume"		            =>	$item[23],
                        "export_amount"		            =>	$item[24],
                        "amount"		                =>	$item[25],
                        "supplier_abbreviation"		    =>	$item[26],
                        "booth_number"		            =>	$item[27],
                        "cn_product_name"		        =>	$item[28],
                        "en_product_name"		        =>	$item[29],
                        "material"		                =>	$item[30],
                        "invoice_type"		            =>	$item[31],
                        "purchaser"		                =>	$item[32],
                        "miscellaneous_charges"		    =>	$item[33],
                        "miscellaneous_charges_desc"    =>	$item[34],
                        "remarks"		                =>	$item[35],
                        "supplier_phone"		        =>	$item[36],
                        "ratio"		                    =>	$item[37],
                        "city"		                    =>	$item[38],
                        "invoice_city"		            =>	$item[39]
                    ];
                }
                $productObj = new WarehouseShipmentDiffItemModel();
                if (!$productObj->insertAll($productData)) {

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

    /**
     * @throws DbException
     */
    public function area_diff_item(): \think\response\View
    {
        $quoteTableObj = new WarehouseShipmentDiffItemModel();
        $list = $quoteTableObj->order('id desc')->select();
        $this->assign('list', $list);

        return view();
    }
}
