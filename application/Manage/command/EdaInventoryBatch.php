<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\EdaInventoryBatchCreateModel;
use app\Manage\model\EdaInventoryBatchModel;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;

class EdaInventoryBatch extends Command
{
    protected function configure()
    {
        $this->setName('EdaInventoryBatch')->setDescription('Here is the EdaInventoryBatch');
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');
        Config::load(APP_PATH . 'Manage/config.php');

        $dataCa = EdaInventoryBatchCreateModel::get(1);
        if ($dataCa['date'] < date('Ymd')) {
            $dataCa = [
                'id'            =>  1,
                'page'          =>  1,
                'pageSize'      =>  100,
                'date'          =>  date('Ymd'),
                'is_finished'   =>  0
            ];
            EdaInventoryBatchCreateModel::update($dataCa);
        } else {
            if ($dataCa['date'] == date('Ymd') && $dataCa['is_finished'] == 0) {
                if (date('H') >= $dataCa['hour']) {
                    $apiRes = ApiClient::edaWarehouseApi("/webApi/userSkuStock/v1/queryInventoryAge", "POST", ['pageNum' => $dataCa['page'], 'pageSize' => $dataCa['pageSize']]);
                    if ($apiRes['code'] == 1) {
                        $dataEach = $apiRes['data']['rows'];
                        $batchData = [];
                        $edaInventoryBatchObj = new EdaInventoryBatchModel();
                        foreach ($dataEach as $item) {
                            $batchData[] = [
                                'userAgeId'             => $item['userAgeId'],
                                'skuCode'               => $item['skuCode'],
                                'barcode'               => $item['barcode'],
                                'skuName'               => $item['skuName'],
                                'skuNameEn'             => $item['skuNameEn'],
                                'stockAge'              => $item['stockAge'],
                                'verifyContent'         => $item['verifyContent'],
                                'weight'                => $item['weight'],
                                'countryName'           => $item['countryName'],
                                'warehouseName'         => $item['warehouseName'],
                                'warehouseId'           => $item['warehouseId'],
                                'warehouseCode'         => $item['warehouseCode'],
                                'businessNo'            => $item['businessNo'],
                                'customerNo'            => $item['customerNo'],
                                'totalStock'            => $item['totalStock'],
                                'shelfDate'             => $item['shelfDate'],
                                'productionBatchNo'     => $item['productionBatchNo'],
                                'productionDate'        => $item['productionDate'],
                                'expirationDate'        => $item['expirationDate'],
                                'expirationWarnDate'    => $item['expirationWarnDate'],
                                'saleForbidDate'        => $item['saleForbidDate'],
                                'created_year'          => date('Y'),
                                'created_month'         => date('Ym'),
                                'created_date'          => date('Ymd'),
                                'created_time'          => date('Y-m-d H:i:s')
                            ];
                            unset($item);
                        }
                        $edaInventoryBatchObj->insertAll($batchData);
                        unset($batchData);
                        unset($edaInventoryBatchObj);

                        if (count($dataEach['records']) >= $dataCa['pageSize']) {
                            EdaInventoryBatchCreateModel::update(['id' => $dataCa['id'], 'page' => $dataCa['page'] + 1]);
                        } else {
                            EdaInventoryBatchCreateModel::update(['id' => $dataCa['id'], 'is_finished' => 1]);
                        }
                    }
                }
            }
        }

        $output->writeln("success");
    }
}