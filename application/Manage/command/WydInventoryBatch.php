<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\WydInventoryBatchModel;
use app\Manage\model\WydInventoryBatchCreateModel;
use SoapFault;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;

class WydInventoryBatch extends Command
{
    protected function configure()
    {
        $this->setName('WydInventoryBatch')->setDescription('Here is the WydInventoryBatch');
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

        $dataCa = WydInventoryBatchCreateModel::get(1);
        if ($dataCa['date'] < date('Ymd')) {
            $dataCa = [
                'id'            =>  1,
                'page'          =>  1,
                'pageSize'      =>  100,
                'date'          =>  date('Ymd'),
                'is_finished'   =>  0
            ];
            WydInventoryBatchCreateModel::update($dataCa);
        } else {
            if ($dataCa['date'] == date('Ymd') && $dataCa['is_finished'] == 0) {
                if (date('H') >= $dataCa['hour']) {
                    $apiRes = ApiClient::WydWarehouseApi("/oms/openapi/stock/v1/warehouseInventoryBatch/query", "POST", ['pageNo' => $dataCa['page'], 'pageSize' => $dataCa['pageSize']]);
                    if ($apiRes['code'] == 1) {
                        $dataEach = \GuzzleHttp\json_decode($apiRes['data'], true);
                        $batchData = [];
                        $wydInventoryBatchObj = new WydInventoryBatchModel();
                        foreach ($dataEach['records'] as $item) {
//                            $inventory = $wydInventoryBatchObj->where(['inboundBatchNo' => $item['inboundBatchNo'], 'warehouseCode' => $item['warehouseCode'], 'created_date' => date('Ymd')])->find();
//                            if ($inventory) {
//                                continue;
//                            }
                            if ($item['inventoryNum'] == 0 && $item['inventoryAvailableNum'] == 0) {
                                continue;
                            }
                            $batchData[] = [
                                'englishName'           => $item['englishName'],
                                'goodsLength'           => $item['goodsLength'],
                                'goodsHigh'             => $item['goodsHigh'],
                                'masterSku'             => $item['masterSku'],
                                'bnNo'                  => $item['bnNo'],
                                'customerCode'          => $item['customerCode'],
                                'snNo'                  => $item['snNo'],
                                'pickUpTime'            => $item['pickUpTime'],
                                'secondSku'             => $item['secondSku'],
                                'inboundBatchNo'        => $item['inboundBatchNo'],
                                'storageAge'            => $item['storageAge'],
                                'warehouseCode'         => $item['warehouseCode'],
                                'goodsWeight'           => $item['goodsWeight'],
                                'volume'                => $item['volume'],
                                'inventoryNum'          => $item['inventoryNum'],
                                'inventoryAvailableNum' => $item['inventoryAvailableNum'],
                                'goodsWidth'            => $item['goodsWidth'],
                                'created_year'          => date('Y'),
                                'created_month'         => date('Ym'),
                                'created_date'          => date('Ymd'),
                                'created_time'          => date('Y-m-d H:i:s')
                            ];
                            unset($item);
                        }
                        $wydInventoryBatchObj->insertAll($batchData);
                        unset($batchData);
                        unset($wydInventoryBatchObj);

                        if (count($dataEach['records']) >= $dataCa['pageSize']) {
                            WydInventoryBatchCreateModel::update(['id' => $dataCa['id'], 'page' => $dataCa['page'] + 1]);
                        } else {
                            WydInventoryBatchCreateModel::update(['id' => $dataCa['id'], 'is_finished' => 1]);
                        }
                    }
                }
            }
        }

        $output->writeln("success");
    }
}