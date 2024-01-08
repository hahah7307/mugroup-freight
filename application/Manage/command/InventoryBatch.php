<?php
namespace app\Manage\command;

use app\Manage\model\InventoryBatchCreateModel;
use app\Manage\model\InventoryBatchModel;
use Exception;
use SoapClient;
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
        header("content-type:text/html;charset=utf-8");
        // 校验开始时间
        if (intval(date('i')) < 2) {
            echo "not time";
            return;
        }

        // 校验今日是否完成
        $createData = InventoryBatchCreateModel::find();
        if ($createData['date'] == date('Ymd') && $createData['is_finish'] == 1) {
            return;
        }
        if ($createData['date'] < date('Ymd') && $createData['is_finish'] == 1) {
            $createData = [
                'page'      =>  1,
                'date'      =>  date('Ymd'),
                'is_finish' =>  0
            ];
            InventoryBatchCreateModel::update($createData, ['id' => 1]);
        }

        Db::startTrans();
        try {
            $url = "https://nt5e7hf.eccang.com/default/svc-open/web-service-v2";
            $soapClient = new SoapClient($url);
            $params = [
                'paramsJson'    =>  '{"page":' . $createData['page'] . ', "pageSize":' . $createData['num'] . '}',
                'userName'      =>  "NJJ",
                'userPass'      =>  "alex02081888",
                'service'       =>  "getInventoryBatch"
            ];
            $ret = $soapClient->callService($params);
            file_put_contents( APP_PATH . '/../runtime/log/test.log', PHP_EOL . "[" . date('Y-m-d H:i:s') . "] : " . var_export($ret,TRUE), FILE_APPEND);
            $retArr = get_object_vars($ret);
            $retJson = $retArr['response'];
            $result = json_decode($retJson, true);
            if ($result['code'] != "200") {
                throw new \think\Exception("error code: " . $result['code'] . "(" . $result['message'] . ")");
            }

            $data = $result['data'];
            $batchData = [];
            $inventoryBatchObj = new InventoryBatchModel();
            foreach ($data as $item) {
                $inventoryBatchItem = $inventoryBatchObj->where(['ib_id' => $item['ib_id'], 'createdDate' => date('Ymd')])->find();
                if ($inventoryBatchItem) {
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
                $is_finish = count($data) < $createData['num'] ? 1 : 0;
                $newCreateData = [
                    'page'      =>  $createData['page'] + 1,
                    'is_finish' =>  $is_finish
                ];
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