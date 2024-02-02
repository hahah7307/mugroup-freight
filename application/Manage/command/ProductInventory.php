<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\ProductInventoryCreateModel;
use app\Manage\model\ProductInventoryModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class ProductInventory extends Command
{
    protected function configure()
    {
        $this->setName('ProductInventory')->setDescription('Here is the ProductInventory');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        // 校验今日是否完成
        $createData = ProductInventoryCreateModel::get(1);
        if ($createData['date'] == date('Ymd') && $createData['is_finished'] == 1) {
            return;
        }
        if ($createData['date'] < date('Ymd')) {
            $createData = [
                'page'          =>  1,
                'date'          =>  date('Ymd'),
                'is_finished'   =>  0
            ];
            ProductInventoryCreateModel::update($createData, ['id' => 1]);
        }

        Db::startTrans();
        try {
            $apiRes = ApiClient::EcWarehouseApi(Config::get("ec_wms_uri"), "getProductInventory", '{"page":' . $createData['page'] . ', "pageSize":' . $createData['pageSize'] . '}');
            if ($apiRes['code'] == 0) {
                throw new \think\Exception($apiRes['msg']);
            }
            $data = $apiRes['data'];

            $batchData = [];
            $productInventoryObj = new ProductInventoryModel();
            foreach ($data as $item) {
                $productInventoryItem = $productInventoryObj->where(['productSku' => $item['productSku'], 'warehouseId' => $item['warehouseId'], 'createdDate' => date('Ymd')])->find();
                if ($productInventoryItem) {
                    continue;
                }

                $batchData[] = [
                    'productSku'                    =>  $item['productSku'],
                    'productTitle'                  =>  $item['productTitle'],
                    'productTitleEn'                =>  $item['productTitleEn'],
                    'productWeight'                 =>  $item['productWeight'],
                    'warehouseId'                   =>  $item['warehouseId'],
                    'warehouseCode'                 =>  $item['warehouseCode'],
                    'warehouseName'                 =>  $item['warehouseName'],
                    'saleStatus'                    =>  $item['saleStatus'],
                    'purchaseOnway'                 =>  $item['purchaseOnway'],
                    'returnOnway'                   =>  $item['returnOnway'],
                    'pending'                       =>  $item['pending'],
                    'inUsed'                        =>  $item['inUsed'],
                    'warningQty'                    =>  $item['warningQty'],
                    'Sellable'                      =>  $item['Sellable'],
                    'Shared'                        =>  $item['Shared'],
                    'canSaleDays'                   =>  $item['canSaleDays'],
                    'reserved'                      =>  $item['reserved'],
                    'noStock'                       =>  $item['noStock'],
                    'noStockDays'                   =>  $item['noStockDays'],
                    'unsellable'                    =>  $item['unsellable'],
                    'outbound'                      =>  $item['outbound'],
                    'inventoryCost'                 =>  $item['inventoryCost'],
                    'currencyCode'                  =>  $item['currencyCode'],
                    'updateTime'                    =>  $item['updateTime'],
                    'actual_usable_inventory'       =>  $item['actual_usable_inventory'],
                    'piPlanned'                     =>  $item['piPlanned'],
                    'purchaseQuantity'              =>  $item['purchaseQuantity'],
                    'pendingQcQty'                  =>  $item['pendingQcQty'],
                    'createdAt'                     =>  date('Y-m-d H:i:s'),
                    'createdDate'                   =>  date('Ymd'),
                    'createdMonth'                  =>  date('Ym'),
                    'createdYear'                   =>  date('Y'),
                ];
            }

            if ($productInventoryObj->saveAll($batchData)) {
                if (count($data) < $createData['pageSize']) {
                    $newCreateData = [
                        'is_finished'   =>  1,
                    ];
                } else {
                    $newCreateData = [
                        'page'          =>  $createData['page'] + 1,
                    ];
                }
                ProductInventoryCreateModel::update($newCreateData, ['id' => 1]);

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