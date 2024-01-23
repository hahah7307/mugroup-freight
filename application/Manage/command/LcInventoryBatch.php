<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\LcInventoryBatchModel;
use app\Manage\model\LcInventoryBatchCreateModel;
use SoapFault;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;

class LcInventoryBatch extends Command
{
    protected function configure()
    {
        $this->setName('LcInventoryBatch')->setDescription('Here is the LcInventoryBatch');
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception|SoapFault
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');
        Config::load(APP_PATH . 'Manage/config.php');

        // 订单查询当前页数
        $data = LcInventoryBatchCreateModel::find()->toArray();
        if ($data['date'] == date('Ymd') && $data['is_finished'] == 1) {
            return;
        }
        if ($data['date'] < date('Ymd') && $data['is_finished'] == 1) {
            $data = [
                'id'            =>  1,
                'page'          =>  1,
                'pageSize'      =>  100,
                'date'          =>  date('Ymd'),
                'is_finished'   =>  0
            ];
            LcInventoryBatchCreateModel::update($data);
        }
        if (date('H') < Config::get('INVENTORY_BATCH_TIME')['LC-CANG']) {
            return;
        }

        $apiRes = ApiClient::LcWarehouseApi("getProductInventory", '{"pageSize":' . $data['pageSize'] . ',"page":' . $data['page'] . '}');
        if ($apiRes['code'] == 1 && $data['is_finished'] == 0) {
            $batchData = [];
            foreach ($apiRes['data'] as $item) {
                if (!empty($item['batch_info'])) {
                    $lcInventoryBatch = $item['batch_info'];
                    foreach ($lcInventoryBatch as $batchItem) {
                        if (LcInventoryBatchModel::get(['receiving_code' => $batchItem['receiving_code'], 'product_sku' => $item['product_sku'], 'warehouse_code' => $item['warehouse_code'], 'created_date' => date('Ymd')])) {
                            continue;
                        }
                        $batchData[] = [
                            'product_sku'           =>  $item['product_sku'],
                            'warehouse_code'        =>  $item['warehouse_code'],
                            'receiving_code'        =>  $batchItem['receiving_code'],
                            'ib_quantity'           =>  $batchItem['ib_quantity'],
                            'ib_type'               =>  $batchItem['ib_type'],
                            'ib_status'             =>  $batchItem['ib_status'],
                            'ib_fifo_time'          =>  $batchItem['ib_fifo_time'],
                            'lc_code'               =>  $batchItem['lc_code'],
                            'reserved_quantity'     =>  $batchItem['reserved_quantity'],
                            'sellable_quantity'     =>  $batchItem['sellable_quantity'],
                            'stock_age'             =>  $batchItem['stock_age'],
                            'created_year'          =>  date('Y'),
                            'created_month'         =>  date('Ym'),
                            'created_date'          =>  date('Ymd'),
                            'created_time'          =>  date('Y-m-d H:i:s')
                        ];
                        unset($batchItem);
                    }
                }
            }

            $lcInventoryBatch = new LcInventoryBatchModel();
            $lcInventoryBatch->insertAll($batchData);
            unset($batchData);

            if (count($apiRes['data']) >= $data['pageSize']) {
                LcInventoryBatchCreateModel::update(['id' => $data['id'], 'page' => $data['page'] + 1]);
            } else {
                LcInventoryBatchCreateModel::update(['id' => $data['id'], 'is_finished' => 1]);
            }
        }

        $output->writeln("success");
    }
}