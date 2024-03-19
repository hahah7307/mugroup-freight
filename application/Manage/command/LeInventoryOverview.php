<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\LeInventoryOverviewModel;
use app\Manage\model\LeInventoryOverviewCreateModel;
use SoapFault;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;

class LeInventoryOverview extends Command
{
    protected function configure()
    {
        $this->setName('LeInventoryOverview')->setDescription('Here is the LeInventoryOverview');
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

        $dataCa = LeInventoryOverviewCreateModel::get(1);
        if ($dataCa['date'] < date('Ymd')) {
            $dataCa = [
                'id'            =>  1,
                'page'          =>  1,
                'pageSize'      =>  100,
                'date'          =>  date('Ymd'),
                'is_finished'   =>  0
            ];
            LeInventoryOverviewCreateModel::update($dataCa);
        } else {
            if ($dataCa['date'] == date('Ymd') && $dataCa['is_finished'] == 0) {
                if (date('H') >= Config::get('INVENTORY_BATCH_TIME')['CAP2']) {
                    $apiRes = ApiClient::LeWarehouseApi("https://app.lecangs.com/api/oms/inventoryOverview/apiPage", "POST", ['pageNum' => $dataCa['page'], 'pageSize' => $dataCa['pageSize'], 'warehouseCode' => $dataCa['warehouseCode']]);
                    if ($apiRes['code'] == 1) {
                        $batchData = [];
                        foreach ($apiRes['data']['list'] as $item) {
                            if (empty($item['uesNum'])) {
                                continue;
                            }

                            $batchData[] = [
                                'lecangsCode'   =>  $item['lecangsCode'],
                                'goodsCode'     =>  $item['goodsCode'],
                                'cnName'        =>  $item['cnName'],
                                'enName'        =>  $item['enName'],
                                'warehouseId'   =>  24,
                                'warehouseCode' =>  $item['warehouseCode'],
                                'goodsLevel'    =>  $item['goodsLevel'],
                                'validStatus'   =>  $item['validStatus'],
                                'onWayNum'      =>  $item['onWayNum'],
                                'pendingNum'    =>  $item['pendingNum'],
                                'goodsNum'      =>  $item['goodsNum'],
                                'blockedNum'    =>  $item['blockedNum'],
                                'uesNum'        =>  $item['uesNum'],
                                'created_year'  =>  date('Y'),
                                'created_month' =>  date('Ym'),
                                'created_date'  =>  date('Ymd'),
                                'created_time'  =>  date('Y-m-d H:i:s')
                            ];
                            unset($item);
                        }
                        $leInventoryBatchObj = new LeInventoryOverviewModel();
                        $leInventoryBatchObj->insertAll($batchData);
                        unset($batchData);
                        unset($leInventoryBatchObj);

                        if (count($apiRes['data']['list']) >= $dataCa['pageSize']) {
                            LeInventoryOverviewCreateModel::update(['id' => $dataCa['id'], 'page' => $dataCa['page'] + 1]);
                        } else {
                            LeInventoryOverviewCreateModel::update(['id' => $dataCa['id'], 'is_finished' => 1]);
                        }
                    }
                }
            }
        }

        $dataPa = LeInventoryOverviewCreateModel::get(2);
        if ($dataPa['date'] < date('Ymd')) {
            $dataPa = [
                'id'            =>  2,
                'page'          =>  1,
                'pageSize'      =>  100,
                'date'          =>  date('Ymd'),
                'is_finished'   =>  0
            ];
            LeInventoryOverviewCreateModel::update($dataPa);
        } else {
            if ($dataPa['date'] == date('Ymd') && $dataPa['is_finished'] == 0) {
                if (date('H') >= Config::get('INVENTORY_BATCH_TIME')['LG-USA-PA01']) {
                    $apiRes = ApiClient::LeWarehouseApi("https://app.lecangs.com/api/oms/inventoryOverview/apiPage", "POST", ['pageNum' => $dataPa['page'], 'pageSize' => $dataPa['pageSize'], 'warehouseCode' => $dataPa['warehouseCode']]);
                    if ($apiRes['code'] == 1) {
                        $batchData = [];
                        foreach ($apiRes['data']['list'] as $item) {
                            if (empty($item['uesNum'])) {
                                continue;
                            }

                            $batchData[] = [
                                'lecangsCode'   =>  $item['lecangsCode'],
                                'goodsCode'     =>  $item['goodsCode'],
                                'cnName'        =>  $item['cnName'],
                                'enName'        =>  $item['enName'],
                                'warehouseId'   =>  37,
                                'warehouseCode' =>  $item['warehouseCode'],
                                'goodsLevel'    =>  $item['goodsLevel'],
                                'validStatus'   =>  $item['validStatus'],
                                'onWayNum'      =>  $item['onWayNum'],
                                'pendingNum'    =>  $item['pendingNum'],
                                'goodsNum'      =>  $item['goodsNum'],
                                'blockedNum'    =>  $item['blockedNum'],
                                'uesNum'        =>  $item['uesNum'],
                                'created_year'  =>  date('Y'),
                                'created_month' =>  date('Ym'),
                                'created_date'  =>  date('Ymd'),
                                'created_time'  =>  date('Y-m-d H:i:s')
                            ];
                            unset($item);
                        }
                        $leInventoryBatchObj = new LeInventoryOverviewModel();
                        $leInventoryBatchObj->insertAll($batchData);
                        unset($batchData);
                        unset($leInventoryBatchObj);

                        if (count($apiRes['data']['list']) >= $dataPa['pageSize']) {
                            LeInventoryOverviewCreateModel::update(['id' => $dataPa['id'], 'page' => $dataPa['page'] + 1]);
                        } else {
                            LeInventoryOverviewCreateModel::update(['id' => $dataPa['id'], 'is_finished' => 1]);
                        }
                    }
                }
            }
        }


        $output->writeln("success");
    }
}