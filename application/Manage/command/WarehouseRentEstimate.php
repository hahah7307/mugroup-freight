<?php
namespace app\Manage\command;

use app\Manage\model\ProductModel;
use app\Manage\model\StorageWarehouseRentModel;
use app\Manage\model\WarehouseRentEstimateBatchModel;
use app\Manage\model\WarehouseRentEstimateModel;
use DateTime;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;

class WarehouseRentEstimate extends Command
{
    protected function configure()
    {
        $this->setName('WarehouseRentEstimate')->setDescription('Here is the WarehouseRentEstimate');
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception
     * @throws \Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');
        Config::load(APP_PATH . 'Manage/config.php');

        $estimateBatchObj = new WarehouseRentEstimateBatchModel();
        $estimateBatchItem = $estimateBatchObj->where(['is_finished' => 0])->order('id asc')->find();
        if (empty($estimateBatchItem)) {
            exit();
        }

        // 判断是否为合格产品
        $productModel = new ProductModel();
        $product = $productModel->where(['productSku' => $estimateBatchItem['warehouse_sku']])->find();
        if (empty($product)) {
            exit();
        }

        // 判断是否有开始时间才可以开始预估
        $estimateObj = new WarehouseRentEstimateModel();
        $estimate = $estimateObj->find($estimateBatchItem['estimate_id']);
        if (empty($estimate['start_date'])) {
            exit();
        }

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
            if ($days > 0) {
                $restStore = round($useDay * $batch['daily_sale'] - $batch['store'], 2);
            } else {
                $restStore = 0;
            }
            unset($storageSum);
        }
        $estimateBatchObj->saveAll($updateData);

        $output->writeln("success");
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
            } elseif ($age == 0) {
                return 0;
            }
        }
        return 10000000; // 没命中返回错误数据
    }
}