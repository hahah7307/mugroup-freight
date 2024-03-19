<?php
namespace app\Manage\command;

use app\Manage\model\InventoryBatchModel;
use app\Manage\model\LcInventoryBatchModel;
use app\Manage\model\LcProductModel;
use app\Manage\model\LeInventoryBatchModel;
use app\Manage\model\ProductModel;
use app\Manage\model\StorageAreaModel;
use app\Manage\model\StorageBaseModel;
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
        $this->setName('InventorySettlement')->setDescription('Here is the InventorySettlement');
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
            // 易仓仓储费计算
            $inventorySettlementObj = new InventoryBatchModel();
            $data = $inventorySettlementObj->where('is_settlement', 0)->order('id asc')->limit(Config::get('inventory_batch_num'))->select();
            foreach ($data as $item) {
                $product = ProductModel::get(['productSku' => $item['productSku']]);
                $volume = $product['productLength'] * $product['productWidth'] * $product['productHeight'] / 1000000;
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
                    'storageFee'            =>  round($storageFee, 7),
                    'is_settlement'         =>  1,
                ];

                $inventorySettlementObj->update($newData);
            }
            unset($data);

            // 良仓仓储费计算
            $lcInventoryBatchObj = new LcInventoryBatchModel();
            $data = $lcInventoryBatchObj->with('receiving')->where('is_finished', 0)->order('id asc')->limit(Config::get('inventory_batch_num'))->select();
            foreach ($data as $item) {
                $warehouseCode = $item['receiving']['warehouse_code'];
                $productItem = LcProductModel::get(['product_sku' => $item['product_sku']]);
                $productItemWarehouseAttr = json_decode($productItem['warehouse_attribute'], true);
                $volume = 0;
                foreach ($productItemWarehouseAttr as $warehouseAttr) {
                    if ($warehouseAttr['warehouse_code'] == $warehouseCode) {
                        $volume = $warehouseAttr['product_length'] * $warehouseAttr['product_width'] * $warehouseAttr['product_height'] / 1000000;
                    }
                }
                $volume = $volume == 0 ? $productItem['product_length'] * $productItem['product_width'] * $productItem['product_height'] / 1000000 : $volume;

                $storageArea = new StorageAreaModel();
                $storageAreaItem = $storageArea->where('storage_code', 'like', '%' . $warehouseCode)->find();
                $storage_id = $storageAreaItem['storage_id'];
                $storageFeeObj = new StorageFeeModel();
                $fees = $storageFeeObj->where(['state' => StorageFeeModel::STATE_ACTIVE, 'storage_id' => $storage_id])->order('level asc')->select();
                $storageFeeUnit = 0;
                foreach ($fees as $value) {
                    if ($item['stock_age'] > $value['condition']) {
                        $storageFeeUnit = $value['value'];
                        break;
                    }
                }
                $storageFee = $storageFeeUnit * $volume * $item['ib_quantity'];
                $newData = [
                    'id'                    =>  $item['id'],
                    'volume'                =>  round($volume, 7),
                    'price'                 =>  $storageFee,
                    'is_finished'           =>  1,
                ];

                $lcInventoryBatchObj->update($newData);
            }
            unset($data);

            // 乐歌仓储费计算
            $leInventoryBatchObj = new LeInventoryBatchModel();
            $data = $leInventoryBatchObj->where('is_finished', 0)->order('id asc')->limit(Config::get('inventory_batch_num'))->select();
            foreach ($data as $item) {
                $volume = $item['wmsLength'] * StorageBaseModel::INCH2CM / 100
                    * $item['wmsWidth']  * StorageBaseModel::INCH2CM / 100
                    * $item['wmsHeight'] * StorageBaseModel::INCH2CM / 100;

                $storage_id = 2;
                $storageFeeObj = new StorageFeeModel();
                $fees = $storageFeeObj->where(['state' => StorageFeeModel::STATE_ACTIVE, 'storage_id' => $storage_id])->order('level asc')->select();
                $storageFeeUnit = 0;
                foreach ($fees as $value) {
                    if ($item['inventoryAge'] > $value['condition']) {
                        $storageFeeUnit = $value['value'];
                        break;
                    }
                }
                $storageFee = $storageFeeUnit * $volume * $item['goodsNum'];
                $newData = [
                    'id'                    =>  $item['id'],
                    'volume'                =>  round($volume, 6),
                    'price'                 =>  round($storageFee, 6),
                    'is_finished'           =>  1,
                ];

                $leInventoryBatchObj->update($newData);
            }
            unset($data);

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