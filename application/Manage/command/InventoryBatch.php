<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\InventoryBatchCreateModel;
use app\Manage\model\InventoryBatchModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class InventoryBatch extends Command
{
    protected function configure()
    {
        $this->setName('inventoryBatch')->setDescription('Here is the inventoryBatch');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        // 校验今日是否完成
        $createData = InventoryBatchCreateModel::find();
        if ($createData['date'] == date('Ymd') && $createData['is_finish'] == 1) {
            return;
        }
        if ($createData['date'] < date('Ymd') && $createData['is_finish'] == 1) {
            $createData = [
                'page'      =>  1,
                'num'       =>  100,
                'date'      =>  date('Ymd'),
                'is_finish' =>  0
            ];
            InventoryBatchCreateModel::update($createData, ['id' => 1]);
        }

        Db::startTrans();
        try {
            $apiRes = ApiClient::EcWarehouseApi(Config::get("ec_wms_uri"), "getInventoryBatch", '{"page":' . $createData['page'] . ', "pageSize":' . $createData['num'] . '}');
            if ($apiRes['code'] == 0) {
                throw new \think\Exception($apiRes['msg']);
            }
            $data = $apiRes['data'];

            $batchData = [];
            $inventoryBatchObj = new InventoryBatchModel();
            foreach ($data as $item) {
                $inventoryBatchItem = $inventoryBatchObj->where(['ib_id' => $item['ib_id'], 'createdDate' => date('Ymd')])->find();
                if ($inventoryBatchItem) {
                    continue;
                }

                // 校验开始时间
                Config::load(APP_PATH . 'Manage/config.php');
                $inventory_batch_time = Config::get('INVENTORY_BATCH_TIME');
                if (!array_key_exists($item['lcCode'], $inventory_batch_time) || intval(date('H')) < $inventory_batch_time[$item['lcCode']]) {
                    continue;
                }

                $batchData[] = [
                    'ib_id'                 =>  $item['ib_id'],
                    'productSku'            =>  $item['productSku'],
                    'warehouseId'           =>  $item['warehouseId'],
                    'lcCode'                =>  $item['lcCode'],
                    'referenceNo'           =>  $item['referenceNo'],
                    'roCode'                =>  $item['roCode'],
                    'poCode'                =>  $item['poCode'],
                    'status'                =>  $item['status'],
                    'holdStatus'            =>  $item['holdStatus'],
                    'ibQuantity'            =>  $item['ibQuantity'],
                    'outQuantity'           =>  $item['outQuantity'],
                    'type'                  =>  $item['type'],
                    'fifoTime'              =>  $item['fifoTime'],
                    'updateTime'            =>  $item['updateTime'],
                    'age'                   =>  $item['age'],
                    'isNeedDeclare'         =>  $item['isNeedDeclare'],
                    'unitPrice'             =>  $item['unitPrice'],
                    'purchaseTaxationFee'   =>  $item['purchaseTaxationFee'],
                    'purchaseShipFee'       =>  $item['purchaseShipFee'],
                    'shippingFee'           =>  $item['shippingFee'],
                    'tariffFee'             =>  $item['tariffFee'],
                    'currencyCode'          =>  $item['currencyCode'],
                    'createdAt'             =>  date('Y-m-d H:i:s'),
                    'createdDate'           =>  date('Ymd'),
                    'createdMonth'          =>  date('Ym'),
                    'createdYear'           =>  date('Y'),
                ];
            }

            if ($inventoryBatchObj->saveAll($batchData)) {
                if (count($data) < $createData['num']) {
                    $newCreateData = [
                        'page'      =>  1,
                    ];
                } else {
                    $newCreateData = [
                        'page'      =>  $createData['page'] + 1,
                    ];
                }
                InventoryBatchCreateModel::update($newCreateData, ['id' => 1]);

                Db::commit();
                echo "success";
            } else {
                throw new Exception("批量添加失败！");
            }
        } catch (\SoapFault $e) {
            Db::rollback();
            dump('SoapFault:'.$e);
        } catch (\Exception $e) {
            Db::rollback();
            dump('Exception:'.$e);
        }
    }
}