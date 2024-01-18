<?php
namespace app\Manage\command;

use app\Manage\model\DateStockConsumeModel;
use app\Manage\model\DateStockReceivingModel;
use app\Manage\model\DateStockUpdateModel;
use app\Manage\model\ReceivingItemModel;
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
     */
    protected function execute(Input $input, Output $output)
    {
        Config::load(APP_PATH . 'Manage/config.php');
        $warehouseIds = getWarehouseID();
        $ymd = date('Ymd');
        $receivingItemObj = new ReceivingItemModel();

        $dataStockReceiving1 = DateStockUpdateModel::get(1);
        if ($dataStockReceiving1['date'] < $ymd) {
            $sql = 'SELECT SUM( i.rd_received_qty ) quantity_sum, i.product_barcode product_sku, r.warehouse_id warehouse_id, DATE_FORMAT(now(), "%Y%m%d") date FROM mu_ecang_receiving r RIGHT JOIN mu_ecang_receiving_item i ON r.id = i.receiving_id WHERE i.rd_status > 0 AND r.warehouse_id IN (' . implode(',', $warehouseIds) . ') GROUP BY i.product_barcode, r.warehouse_id;';
            $resData = $receivingItemObj->query($sql);
            $receivingDate = new DateStockReceivingModel();
            $receivingDate->insertAll($resData);

            DateStockUpdateModel::update(['date' => $ymd], ['id' => 1]);
        }

        $dataStockReceiving2 = DateStockUpdateModel::get(2);
        if ($dataStockReceiving2['date'] < $ymd) {
            $sql2 = 'SELECT SUM( d.qty ) quantity_sum, d.productSku product_sku, o.warehouseId warehouse_id, DATE_FORMAT( now( ), "%Y%m%d" ) date FROM mu_ecang_order_detail d LEFT JOIN mu_ecang_order o ON o.id = d.order_id WHERE o.STATUS = 4 AND o.warehouseId IN (' . implode(',', $warehouseIds) . ') GROUP BY d.productSku, o.warehouseId;';
            $resData2 = $receivingItemObj->query($sql2);
            $consumeDate = new DateStockConsumeModel();
            $consumeDate->insertAll($resData2);

            DateStockUpdateModel::update(['date' => $ymd], ['id' => 2]);
        }

        $output->writeln("success");
    }
}