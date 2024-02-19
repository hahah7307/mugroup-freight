<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\InventoryAdjustmentModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class InventoryAdjustment extends Command
{
    protected function configure()
    {
        $this->setName('InventoryAdjustment')->setDescription('Here is the InventoryAdjustment');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        Db::startTrans();
        try {
            $apiRes = ApiClient::EcWarehouseApi(Config::get("ec_wms_uri"), "getAdjustmentInventoryList", '{"timeFrom":"' . date('Y-m-d H:i', time() - 60 * 60) . '","timeTo":"' . date('Y-m-d H:i') . '"}');
            if ($apiRes['code'] == 0) {
                throw new \think\Exception($apiRes['msg']);
            }
            $data = $apiRes['data'];

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