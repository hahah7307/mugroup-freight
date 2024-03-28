<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\LeInventoryBatchModel;
use app\Manage\model\LeInventoryBatchCreateModel;
use SoapFault;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;

class LeInventoryBatch extends Command
{
    protected function configure()
    {
        $this->setName('LeInventoryBatch')->setDescription('Here is the LeInventoryBatch');
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

        $dataCa = LeInventoryBatchCreateModel::get(1);
        if ($dataCa['date'] < date('Ymd')) {
            $dataCa = [
                'id'            =>  1,
                'page'          =>  1,
                'pageSize'      =>  100,
                'date'          =>  date('Ymd'),
                'is_finished'   =>  0
            ];
            LeInventoryBatchCreateModel::update($dataCa);
        } else {
            if ($dataCa['date'] == date('Ymd') && $dataCa['is_finished'] == 0) {
                if (date('H') >= $dataCa['hour']) {
                    $apiRes = ApiClient::LeWarehouseApi("https://app.lecangs.com/api/oms/inventoryBatch/api/list", "POST", ['pageNum' => $dataCa['page'], 'pageSize' => $dataCa['pageSize']]);
                    if ($apiRes['code'] == 1) {
                        $batchData = [];
                        foreach ($apiRes['data']['list'] as $item) {
                            if ($item['warehouseCode'] == $dataCa['warehouseCode']) {
                                $batchData[] = [
                                    'businessNo' => $item['businessNo'],
                                    'warehouseCode' => $item['warehouseCode'],
                                    'lecangsCode' => $item['lecangsCode'],
                                    'cnName' => $item['cnName'],
                                    'enName' => $item['enName'],
                                    'goodsNum' => $item['goodsNum'],
                                    'warehouseDate' => $item['warehouseDate'],
                                    'wmsLength' => $item['wmsLength'],
                                    'wmsWidth' => $item['wmsWidth'],
                                    'wmsHeight' => $item['wmsHeight'],
                                    'measureUnit' => $item['measureUnit'],
                                    'wmsWeight' => $item['wmsWeight'],
                                    'weightUnit' => $item['weightUnit'],
                                    'inventoryAge' => $item['inventoryAge'],
                                    'created_year' => date('Y'),
                                    'created_month' => date('Ym'),
                                    'created_date' => date('Ymd'),
                                    'created_time' => date('Y-m-d H:i:s')
                                ];
                                unset($item);
                            }
                        }
                        $leInventoryBatchObj = new LeInventoryBatchModel();
                        $leInventoryBatchObj->insertAll($batchData);
                        unset($batchData);
                        unset($leInventoryBatchObj);

                        if (count($apiRes['data']['list']) >= $dataCa['pageSize']) {
                            LeInventoryBatchCreateModel::update(['id' => $dataCa['id'], 'page' => $dataCa['page'] + 1]);
                        } else {
                            LeInventoryBatchCreateModel::update(['id' => $dataCa['id'], 'is_finished' => 1]);
                        }
                    }
                }
            }
        }

        $dataPaw = LeInventoryBatchCreateModel::get(2);
        if ($dataPaw['date'] < date('Ymd')) {
            $dataPaw = [
                'id'            =>  2,
                'page'          =>  1,
                'pageSize'      =>  100,
                'date'          =>  date('Ymd'),
                'is_finished'   =>  0
            ];
            LeInventoryBatchCreateModel::update($dataPaw);
        } else {
            if ($dataPaw['date'] == date('Ymd') && $dataPaw['is_finished'] == 0) {
                if (date('H') >= $dataPaw['hour']) {
                    $apiRes = ApiClient::LeWarehouseApi("https://app.lecangs.com/api/oms/inventoryBatch/api/list", "POST", ['pageNum' => $dataPaw['page'], 'pageSize' => $dataPaw['pageSize']]);
                    if ($apiRes['code'] == 1) {
                        $batchData = [];
                        foreach ($apiRes['data']['list'] as $item) {
                            if ($item['warehouseCode'] == $dataPaw['warehouseCode']) {
                                $batchData[] = [
                                    'businessNo' => $item['businessNo'],
                                    'warehouseCode' => $item['warehouseCode'],
                                    'lecangsCode' => $item['lecangsCode'],
                                    'cnName' => $item['cnName'],
                                    'enName' => $item['enName'],
                                    'goodsNum' => $item['goodsNum'],
                                    'warehouseDate' => $item['warehouseDate'],
                                    'wmsLength' => $item['wmsLength'],
                                    'wmsWidth' => $item['wmsWidth'],
                                    'wmsHeight' => $item['wmsHeight'],
                                    'measureUnit' => $item['measureUnit'],
                                    'wmsWeight' => $item['wmsWeight'],
                                    'weightUnit' => $item['weightUnit'],
                                    'inventoryAge' => $item['inventoryAge'],
                                    'created_year' => date('Y'),
                                    'created_month' => date('Ym'),
                                    'created_date' => date('Ymd'),
                                    'created_time' => date('Y-m-d H:i:s')
                                ];
                                unset($item);
                            }
                        }
                        $leInventoryBatchObj = new LeInventoryBatchModel();
                        $leInventoryBatchObj->insertAll($batchData);
                        unset($batchData);
                        unset($leInventoryBatchObj);

                        if (count($apiRes['data']['list']) >= $dataPaw['pageSize']) {
                            LeInventoryBatchCreateModel::update(['id' => $dataPaw['id'], 'page' => $dataPaw['page'] + 1]);
                        } else {
                            LeInventoryBatchCreateModel::update(['id' => $dataPaw['id'], 'is_finished' => 1]);
                        }
                    }
                }
            }
        }

        $output->writeln("success");
    }
}