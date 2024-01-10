<?php
namespace app\Manage\command;

use app\Manage\model\InventoryAdjustmentModel;
use Exception;
use SoapClient;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class InventoryAdjustment extends Command
{
    protected function configure()
    {
        $this->setName('inventoryAdjustment')->setDescription('Here is the inventoryAdjustment');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        header("content-type:text/html;charset=utf-8");

        Db::startTrans();
        try {
            $url = "https://nt5e7hf.eccang.com/default/svc-open/web-service-v2";
            $soapClient = new SoapClient($url);
            $params = [
                'paramsJson'    =>  '{"timeFrom":"' . date('Y-m-d H:i', time() - 60 * 60) . '","timeTo":"' . date('Y-m-d H:i') . '"}',
                'userName'      =>  "NJJ",
                'userPass'      =>  "alex02081888",
                'service'       =>  "getAdjustmentInventoryList"
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
            $AdjustmentData = [];
            $inventoryAdjustmentObj = new InventoryAdjustmentModel();
            foreach ($data as $item) {
                $inventoryAdjustment = $inventoryAdjustmentObj->where(['ibl_id' => $item['ibl_id']])->find();
                if ($inventoryAdjustment) {
                    continue;
                }
                $AdjustmentData[] = [
                    'ibl_id'                =>  $item['ibl_id'],
                    'productSku'            =>  $item['productSku'],
                    'warehouseId'           =>  $item['warehouseId'],
                    'lcCode'                =>  $item['lcCode'],
                    'applicationCode'       =>  $item['applicationCode'],
                    'refNo'                 =>  $item['refNo'],
                    'roCode'                =>  $item['roCode'],
                    'quantityBefore'        =>  $item['quantityBefore'],
                    'quantityAfter'         =>  $item['quantityAfter'],
                    'userId'                =>  $item['userId'],
                    'time'                  =>  $item['time'],
                    'iblnote'               =>  $item['iblnote'],
                ];
            }

            if ($inventoryAdjustmentObj->saveAll($AdjustmentData)) {
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