<?php
namespace app\Manage\controller;

use app\Manage\model\ProductModel;
use app\Manage\model\StorageWarehouseRentModel;
use app\Manage\model\WarehouseRentEstimateBatchModel;
use app\Manage\model\WarehouseRentEstimateModel;
use DateTime;
use Exception;
use PHPExcel_IOFactory;
use PHPExcel_Reader_Exception;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
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

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception
     */
    public function calculation()
    {
        $estimateBatchObj = new WarehouseRentEstimateBatchModel();
        $estimateBatchItem = $estimateBatchObj->where(['is_finished' => 0])->order('id asc')->find();
        if (empty($estimateBatchItem)) {
            exit();
        }

        $productModel = new ProductModel();
        $product = $productModel->where(['productSku' => $estimateBatchItem['warehouse_sku']])->find();
        if (empty($product)) {
            exit();
        }

        $estimateObj = new WarehouseRentEstimateModel();
        $estimate = $estimateObj->find($estimateBatchItem['estimate_id']);
        if (!empty($estimate['end_date'])) {
            $start = DateTime::createFromFormat('Ymd', $estimate['start_date']);
            $end = DateTime::createFromFormat('Ymd', $estimate['end_date']);
            $amountDays = $start->diff($end)->days + 1;
            $days = $amountDays;
        } else {
            $days = 0;
        }

        $estimateBatch = $estimateBatchObj->where(['estimate_id' => $estimateBatchItem['estimate_id'], 'warehouse_sku' =>$estimateBatchItem['warehouse_sku'], 'is_finished' => 0])->order('age desc')->select();
        $day = 0;
        $restStore = 0;
        $updateData =[];
        foreach ($estimateBatch as $batch) {
            $storageSum = 0;
            for ($i = 0; $i < $day; $i ++) {
                $unit = self::warehouseUnit($batch['age'] + $i, $batch['warehouse_code']);
                $storageSum += $batch['store'] * $unit;
            }
            unset($i);
            unset($unit);
            $useDay = ceil($batch['store'] / $batch['daily_sale']);
            $sum = 0;
            $batch['store'] -= $restStore;
            if (empty($estimate['end_date'])) {
                for ($i = 0; $i < $useDay; $i ++) {
                    $store = $batch['store'] - $i * $batch['daily_sale'];
                    if ($store >= 0) {
                        $unit = self::warehouseUnit($batch['age'] + $day + $i, $batch['warehouse_code']);
                        $sum += $store * $unit;
                    } else {
                        $useDay -= 1;
                    }
                }
                $day += $useDay;
                $updateData[] = [
                    'id'            =>  $batch['id'],
                    'store_balance' =>  0,
                    'value'         =>  ($storageSum + $sum) * $product['productLength'] * $product['productWidth'] * $product['productHeight'] / 1000000,
                    'is_finished'   =>  1
                ];
            } else {
                if ($useDay >= $days) {
                    for ($i = 0; $i < $days; $i ++) {
                        $store = $batch['store'] - $i * $batch['daily_sale'];
                        if ($store >= 0) {
                            $unit = self::warehouseUnit($batch['age']+ $day + $i, $batch['warehouse_code']);
                            $sum += $store * $unit;
                        } {
                            $useDay -= 1;
                        }
                    }
                    $day += $days;
                    $updateData[] = [
                        'id'            =>  $batch['id'],
                        'store_balance' =>  max($batch['store'] - $days * $batch['daily_sale'], 0),
                        'value'         =>  ($storageSum + $sum) * $product['productLength'] * $product['productWidth'] * $product['productHeight'] / 1000000,
                        'is_finished'   =>  1
                    ];
                    $days = 0;
                } else {
                    for ($i = 0; $i < $useDay; $i ++) {
                        $store = $batch['store'] - $i * $batch['daily_sale'];
                        if ($store >= 0) {
                            $unit = self::warehouseUnit($batch['age'] + $day + $i, $batch['warehouse_code']);
                            $sum += $store * $unit;
                        } {
                            $useDay -= 1;
                        }
                    }
                    $day += $useDay;
                    $updateData[] = [
                        'id'            =>  $batch['id'],
                        'store_balance' =>  0,
                        'value'         =>  ($storageSum + $sum) * $product['productLength'] * $product['productWidth'] * $product['productHeight'] / 1000000,
                        'is_finished'   =>  1
                    ];
                    $days -= $useDay;
                }
            }
            $restStore = round($useDay * $batch['daily_sale'] - $batch['store'], 2);
            unset($storageSum);
        }
        dump($estimateBatchObj->saveAll($updateData));
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    static public function warehouseUnit($age, $warehouseCode)
    {
        $warehouseRentObj = new StorageWarehouseRentModel();
        $list = $warehouseRentObj->where(['warehouse_code' => $warehouseCode, 'start_at' => ['lt', date('Y-m-d')], 'end_at' => ['gt', date('Y-m-d')]])->order('age_from desc')->select();
        foreach ($list as $item) {
            if ($age > $item['age_from']) {
                return $item['value'];
            } else {
                continue;
            }
        }
    }
}
