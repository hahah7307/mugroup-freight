<?php
namespace app\Manage\command;

use app\Manage\model\DateStockConsumeModel;
use app\Manage\model\DateStockReceivingModel;
use app\Manage\model\DateStockUpdateModel;
use app\Manage\model\ReceivingItemModel;
use app\Manage\model\StockOpeningModel;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;

class DateStockUpdate extends Command
{
    protected function configure()
    {
        $this->setName('DateStockUpdate')->setDescription('Here is the DateStockUpdate');
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
        Config::load(APP_PATH . 'Manage/config.php');
        Config::load(APP_PATH . 'storage.php');

        $ymd = date('Ymd');
        $receivingItemObj = new ReceivingItemModel();

        $dataStockUpdate = DateStockUpdateModel::all();
        foreach ($dataStockUpdate as $update) {
            if ($update['date'] < $ymd && $update['hour'] <= date('H')) {
                $warehouseIds = explode(',', $update['warehouseIds']);
                if ($update['model'] == "DateStockReceivingModel") {
                    $sql = 'SELECT SUM( i.rd_received_qty ) quantity_sum, i.product_barcode product_sku, r.warehouse_id warehouse_id, DATE_FORMAT(now(), "%Y%m%d") date FROM mu_ecang_receiving r RIGHT JOIN mu_ecang_receiving_item i ON r.id = i.receiving_id WHERE i.rd_status > 0 AND r.receiving_type = 0 AND r.complete_time > "' . Config::get('stock_date') . '" AND r.warehouse_id IN (' . $update['warehouseIds'] . ') GROUP BY i.product_barcode, r.warehouse_id;';
                    $resData = $receivingItemObj->query($sql);
                    $receivingDate = new DateStockReceivingModel();
                    $receivingDate->insertAll($resData);
                    $stockOpeningObj = new StockOpeningModel();
                    $stockOpening = $stockOpeningObj->where(['warehouse_id' => ['in', $warehouseIds]])->select();
                    $updateData = [];
                    $insertData = [];
                    foreach ($stockOpening as $item) {
                        $receivingItem = $receivingDate->where(['product_sku' => $item['product_sku'], 'warehouse_id' => $item['warehouse_id'], 'date' => $ymd])->find();
                        if ($receivingItem) {
                            $receivingItem['quantity_sum'] = $receivingItem['quantity_sum'] + $item['stock'];
                            $updateData[] = $receivingItem->toArray();
                        } else {
                            $receivingItem = [
                                'quantity_sum'  =>  $item['stock'],
                                'product_sku'   =>  $item['product_sku'],
                                'warehouse_id'  =>  $item['warehouse_id'],
                                'date'          =>  $ymd
                            ];
                            $insertData[] = $receivingItem;
                        }
                    }
                    $receivingDate->saveAll($updateData);
                    $receivingDate->insertAll($insertData);

                    DateStockUpdateModel::update(['date' => $ymd], ['id' => $update['id']]);
                } elseif ($update['model'] == "DateStockConsumeModel") {
                    $sql = 'SELECT SUM( d.qty ) quantity_sum, d.warehouseSku product_sku, o.warehouseId warehouse_id, DATE_FORMAT( now( ), "%Y%m%d" ) date FROM mu_ecang_order_detail d LEFT JOIN mu_ecang_order o ON o.id = d.order_id WHERE o.STATUS = 4 AND o.dateWarehouseShipping > "' . Config::get('stock_date') . '" AND o.warehouseId IN (' . $update['warehouseIds'] . ') GROUP BY d.warehouseSku, o.warehouseId;';
                    $resData = $receivingItemObj->query($sql);
                    $consumeDate = new DateStockConsumeModel();
                    $consumeDate->insertAll($resData);

                    DateStockUpdateModel::update(['date' => $ymd], ['id' => $update['id']]);
                }
            }
        }

        $output->writeln("success");
    }
}