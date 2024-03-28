<?php
namespace app\Manage\command;

use app\Manage\model\InventoryBatchModel;
use app\Manage\model\LcInventoryBatchModel;
use app\Manage\model\LeInventoryBatchModel;
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
                $product = ProductModel::get(['productSku' => $item['product_sku']]);
                $volume = $product['productLength'] * $product['productWidth'] * $product['productHeight'] / 1000000;

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
                $product = ProductModel::get(['productSku' => substr($item['lecangsCode'], 6)]);
                $volume = $product['productLength'] * $product['productWidth'] * $product['productHeight'] / 1000000;

                $storageFeeObj = new StorageFeeModel();
                $condition['state'] = StorageFeeModel::STATE_ACTIVE;
                $condition['storage_id'] = 2;
                $condition['start_at'] = ['lt', $item['created_time']];
                $condition['end_at'] = ['egt', $item['created_time']];
                $fees = $storageFeeObj->where($condition)->order('level asc')->select();
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