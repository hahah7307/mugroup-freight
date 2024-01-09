<?php
namespace app\Manage\command;

use app\Manage\model\InventoryBatchModel;
use app\Manage\model\ProductModel;
use app\Manage\model\StorageAreaModel;
use app\Manage\model\StorageFeeModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class InventorySettlement extends Command
{
    protected function configure()
    {
        $this->setName('inventorySettlement')->setDescription('Here is the inventorySettlement');
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
            $inventorySettlementObj = new InventoryBatchModel();
            $data = $inventorySettlementObj->where('is_settlement', 0)->order('id asc')->limit(Config::get('inventory_batch_num'))->select();
            foreach ($data as $item) {
                $product = ProductModel::get(['productSku' => $item['productSku']]);
                $volume = round($product['productLength'] * $product['productWidth'] * $product['productHeight'] / 1000000, 3);
                $storageArea = StorageAreaModel::get(['storage_code' => $item['lcCode']]);
                $storage_id = $storageArea['storage_id'];

                $storageFeeObj = new StorageFeeModel();
                $fees = $storageFeeObj->where(['state' => StorageFeeModel::STATE_ACTIVE, 'storage_id' => $storage_id])->order('level asc')->select();
                $storageFeeUnit = 0;
                foreach ($fees as $value) {
                    if ($item['age'] > $value['condition']) {
                        $storageFeeUnit = $value['value'];
                        break;
                    }
                }
                $storageFee = $storageFeeUnit * $volume * $item['ibQuantity'];
                $newData = [
                    'id'                    =>  $item['id'],
                    'storageFee'            =>  $storageFee,
                    'is_settlement'         =>  1,
                ];

                $inventorySettlementObj->update($newData);
            }

            Db::commit();
            echo "success";
        } catch (\SoapFault $e) {
            Db::rollback();
            dump('SoapFault:'.$e);
        } catch (\Exception $e) {
            Db::rollback();
            dump('Exception:'.$e);
        }
    }
}