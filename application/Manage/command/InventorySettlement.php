<?php
namespace app\Manage\command;

use app\Manage\model\InventoryBatchModel;
use app\Manage\model\LcInventoryBatchModel;
use app\Manage\model\LeInventoryBatchModel;
use app\Manage\model\ProductModel;
use app\Manage\model\StorageAreaModel;
use app\Manage\model\StorageFeeModel;
use app\Manage\model\StorageWarehouseRentModel;
use app\Manage\model\WydInventoryBatchModel;
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
            $productObj = new ProductModel();

//            // 良仓仓储费计算
//            $lcInventoryBatchObj = new LcInventoryBatchModel();
//            $data = $lcInventoryBatchObj->with('receiving')->where('is_finished', 0)->order('id asc')->limit(Config::get('inventory_batch_num'))->select();
//            foreach ($data as $item) {
//                $warehouseCode = $item['receiving']['warehouse_code'];
//                $product = $productObj->where(['productSku' => $item['product_sku']])->find();
//                $volume = $product['productLength'] * $product['productWidth'] * $product['productHeight'] / 1000000;
//
//                $storageArea = new StorageAreaModel();
//                $storageAreaItem = $storageArea->where('storage_code', 'like', '%' . $warehouseCode)->find();
//                $storage_id = $storageAreaItem['storage_id'];
//                $storageFeeObj = new StorageFeeModel();
//                $fees = $storageFeeObj->where(['state' => StorageFeeModel::STATE_ACTIVE, 'storage_id' => $storage_id])->order('level asc')->select();
//                $storageFeeUnit = 0;
//                foreach ($fees as $value) {
//                    if ($item['stock_age'] > $value['condition']) {
//                        $storageFeeUnit = $value['value'];
//                        break;
//                    }
//                }
//                $storageFee = $storageFeeUnit * $volume * $item['ib_quantity'];
//                $newData = [
//                    'id'                    =>  $item['id'],
//                    'volume'                =>  round($volume, 7),
//                    'price'                 =>  $storageFee,
//                    'is_finished'           =>  1,
//                ];
//
//                $lcInventoryBatchObj->update($newData);
//            }
//            unset($data);

            // 乐歌仓储费计算
            $leInventoryBatchObj = new LeInventoryBatchModel();
            $data = $leInventoryBatchObj->where('is_finished', 0)->order('id asc')->limit(Config::get('inventory_batch_num'))->select();
            $newData = [];
            foreach ($data as $item) {
                $volume = $item['wmsLength'] * 2.54 * $item['wmsWidth'] * 2.54 * $item['wmsHeight'] * 2.54 / 1000000;
                $storageFeeObj = new StorageWarehouseRentModel();
                $condition['warehouse_code'] = $item['warehouseCode'];
                $condition['start_at'] = ['lt', $item['created_time']];
                $condition['end_at'] = ['egt', $item['created_time']];
                $fees = $storageFeeObj->where($condition)->order('age_from desc')->select();
                if (empty($fees)) {
                    break;
                }
                $storageFeeUnit = 0;
                foreach ($fees as $value) {
                    if ($item['inventoryAge'] > $value['age_from']) {
                        $storageFeeUnit = $value['value'];
                        break;
                    }
                }
                $storageFee = $storageFeeUnit * $volume * $item['goodsNum'];
                $newData[] = [
                    'id'                    =>  $item['id'],
                    'volume'                =>  round($volume, 6),
                    'price'                 =>  round($storageFee, 6),
                    'is_finished'           =>  1,
                ];
            }

            $leInventoryBatchObj->saveAll($newData);
            unset($data);
            unset($item);
            unset($newData);

            // 无忧达仓储费计算
            $wydInventoryBatchObj = new WydInventoryBatchModel();
            $data = $wydInventoryBatchObj->where('is_finished', 0)->order('id asc')->limit(Config::get('inventory_batch_num'))->select();
            $newData = [];
            foreach ($data as $item) {
                $volume = $item['goodsLength'] * $item['goodsWidth'] * $item['goodsHigh'] / 1000000;
                $storageFeeObj = new StorageWarehouseRentModel();
                $condition['warehouse_code'] = $item['warehouseCode'];
                $condition['start_at'] = ['lt', $item['created_time']];
                $condition['end_at'] = ['egt', $item['created_time']];
                $fees = $storageFeeObj->where($condition)->order('age_from desc')->select();
                if (empty($fees)) {
                    break;
                }
                $storageFeeUnit = 0;
                foreach ($fees as $value) {
                    if ($item['storageAge'] > $value['age_from']) {
                        $storageFeeUnit = $value['value'];
                        break;
                    }
                }
                $storageFee = $storageFeeUnit * $volume * $item['inventoryNum'];
                $newData[] = [
                    'id'                    =>  $item['id'],
                    'volume'                =>  round($volume, 6),
                    'price'                 =>  round($storageFee, 6),
                    'is_finished'           =>  1,
                ];
            }

            $wydInventoryBatchObj->saveAll($newData);
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