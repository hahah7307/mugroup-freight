<?php
namespace app\Manage\command;

use app\Manage\model\DateStockCalculateModel;
use app\Manage\model\DateStockConsumeModel;
use app\Manage\model\DateStockReceivingModel;
use app\Manage\model\LcInventoryBatchModel;
use app\Manage\model\LeInventoryOverviewModel;
use app\Manage\model\ProductModel;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;

class DateStockCalculate extends Command
{
    protected function configure()
    {
        $this->setName('DateStockCalculate')->setDescription('Here is the DateStockCalculate');
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception
     * @throws \SoapFault
     * @throws \Exception
     */
    protected function execute(Input $input, Output $output)
    {
        Config::load(APP_PATH . 'storage.php');

        if (date('H') < 17) {
            return;
        }

        $ymd = date('Ymd');
        $dateStockCalculateObj = new DateStockCalculateModel();
        $count = $dateStockCalculateObj->where(['date' => $ymd])->count();
        if ($count == 0) {
            $dateStockCalculateData = [];
            $ecProduct = ProductModel::all();
            $warehouse = getStorageArea();
            foreach ($ecProduct as $item) {
                foreach ($warehouse as $w) {
                    $dateStockCalculateData[] = [
                        'product_sku'   =>  $item['productSku'],
                        'warehouse_id'  =>  $w['warehouseId'],
                        'date'          =>  $ymd
                    ];
                }
            }
            $dateStockCalculateObj->insertAll($dateStockCalculateData);
            unset($dateStockCalculateData);
            unset($dateStockCalculateObj);
            unset($ecProduct);
            unset($warehouse);
        } else {
            $list = $dateStockCalculateObj->where(['date' => $ymd, 'is_finished' => 0])->order('id asc')->limit(1000)->select();
            $updateData = [];
            foreach ($list as $item) {
                $dateStockReceivingObj = new DateStockReceivingModel();
                $dateStockReceiving = $dateStockReceivingObj->where(['product_sku' => $item['product_sku'], 'warehouse_id' => $item['warehouse_id'], 'date' => $ymd])->find();
                $dateStockConsumeObj = new DateStockConsumeModel();
                $dateStockConsume = $dateStockConsumeObj->where(['product_sku' => $item['product_sku'], 'warehouse_id' => $item['warehouse_id'], 'date' => $ymd])->find();
                if (in_array($item['warehouse_id'], [28, 29, 32, 34, 36])) {
                    $warehouse = [
                        28  =>  'USATL06',
                        29  =>  'USLAX08',
                        32  =>  'USLAX09',
                        34  =>  'USLAX05',
                        36  =>  'USNJ06'
                    ];
                    $lcInventoryBatchObj = new LcInventoryBatchModel();
                    $lcInventoryBatchSellable = $lcInventoryBatchObj->where(['product_sku' => $item['product_sku'], 'warehouse_code' => $warehouse[$item['warehouse_id']], 'created_date' => $ymd])->sum('sellable_quantity');
                    $updateData[] = [
                        'id'                        =>  $item['id'],
                        'date_stock_receiving_id'   =>  $dateStockReceiving['id'],
                        'date_stock_consume_id'     =>  $dateStockConsume['id'],
                        'stock'                     =>  intval($dateStockReceiving['quantity_sum']) - intval($dateStockConsume['quantity_sum']),
                        'storage_stock'             =>  intval($lcInventoryBatchSellable),
                        'is_finished'               =>  1
                    ];
                } elseif (in_array($item['warehouse_id'], [24, 26, 33, 37])) {
//                    $warehouse = [
//                        24  =>  'CAP',
//                        26  =>  'CAP',
//                        33  =>  'MEM',
//                        37  =>  'PAW'
//                    ];
                    $leInventoryBatchObj = new LeInventoryOverviewModel();
                    $leInventoryBatchSum = $leInventoryBatchObj->where(['goodsCode' => $item['product_sku'], 'warehouseId' => $item['warehouse_id'], 'created_date' => $ymd])->sum('uesNum');
                    $updateData[] = [
                        'id'                        =>  $item['id'],
                        'date_stock_receiving_id'   =>  $dateStockReceiving['id'],
                        'date_stock_consume_id'     =>  $dateStockConsume['id'],
                        'stock'                     =>  intval($dateStockReceiving['quantity_sum']) - intval($dateStockConsume['quantity_sum']),
                        'storage_stock'             =>  intval($leInventoryBatchSum),
                        'is_finished'               =>  1
                    ];
                }
            }
            $dateStockCalculateObj->saveAll($updateData);
        }

        $output->writeln("success");
    }
}