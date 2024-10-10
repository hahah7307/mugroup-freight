<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\ProductModel;
use app\Manage\model\ProductUpdateModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class ProductUpdate extends Command
{
    protected function configure()
    {
        $this->setName('ProductUpdate')->setDescription('Here is the ProductUpdate');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        $productObj = new ProductUpdateModel();
        $ecUpdate = $productObj->find(1);
        if (date('Ymd') == $ecUpdate['date']
            && $ecUpdate['is_finished'] == 1
        ) {
            echo "success";exit();
        }

        // 当日产品数据开始清表更新
        if (date('Ymd') > $ecUpdate['date']) {
            Db::execute("TRUNCATE TABLE mu_ecang_product");
            ProductUpdateModel::update(['id' => $ecUpdate['id'], 'date' => date('Ymd'), 'page' => 1, 'is_finished' => 0]);
            $ecUpdate['page'] = 1;
        }

        Db::startTrans();
        try {
            // 易仓产品更新
            if (date('Ymd') == $ecUpdate['date'] && $ecUpdate['is_finished'] == 1){
                echo "success";
            } else {
                $ecProductRes = ApiClient::EcWarehouseApi(Config::get("ec_wms_uri"), "getProductList", '{"page":' . $ecUpdate['page'] . '}');
                $ecProductList = $ecProductRes['data'];
                if (!empty($ecProductRes['data']) && count($ecProductList) <= 0) {
                    ProductUpdateModel::update(['id' => $ecUpdate['id'], 'is_finished' => 1]);
                } else {
                    $addData = [];
                    foreach ($ecProductList as $item) {
                        $productInfo = ProductModel::get(['productSku' => $item['productSku']]);
                        if (!empty($productInfo)) {
                            continue;
                        }
                        $productDetail = $item;
                        unset($productDetail['productPackage']);
                        unset($productDetail['productCost']);
                        $productDetail['productPackage'] = json_encode($item['productPackage']);
                        $productDetail['productCost'] = json_encode($item['productCost']);
                        $addData[] = $productDetail;
                        unset($item);
                    }
                    unset($ecProductList);
                    $productObj = new ProductModel();
                    $productObj->saveAll($addData);
                    ProductUpdateModel::update(['id' => $ecUpdate['id'], 'page' => $ecUpdate['page'] + 1]);
                    unset($addData);
                }
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