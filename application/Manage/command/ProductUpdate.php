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
        $this->setName('productUpdate')->setDescription('Here is the productUpdate');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        $update = ProductUpdateModel::find()->toArray();
        if (date('Ymd') == $update['date'] && $update['is_finished'] == 1) {
            echo "success";exit();
        }

        if (date('Ymd') > $update['date']) {
            Db::execute("TRUNCATE TABLE mu_ecang_product");
            ProductUpdateModel::update(['id' => $update['id'], 'date' => date('Ymd'), 'page' => 1, 'is_finished' => 0]);
            $update['page'] = 1;
        }

        Db::startTrans();
        try {
            $apiRes = ApiClient::EcWarehouseApi(Config::get("ec_wms_uri"), "getProductList", '{"page":' . $update['page'] . '}');
            if ($apiRes['code'] == 0) {
                throw new \think\Exception($apiRes['msg']);
            }
            $data = $apiRes['data'];

            if (count($data) <= 0) {
                ProductUpdateModel::update(['id' => $update['id'], 'page' => $update['page'] + 1, 'is_finished' => 1]);
                echo "success";exit();
            }

            $addData = [];
            foreach ($data as $item) {
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
            unset($data);
            $productObj = new ProductModel();
            $productObj->saveAll($addData);
            ProductUpdateModel::update(['id' => $update['id'], 'page' => $update['page'] + 1]);
            unset($addData);
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