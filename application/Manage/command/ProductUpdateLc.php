<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\LcProductModel;
use app\Manage\model\ProductUpdateModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class ProductUpdateLc extends Command
{
    protected function configure()
    {
        $this->setName('ProductUpdateLc')->setDescription('Here is the ProductUpdateLc');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        $productObj = new ProductUpdateModel();
        $lcUpdate = $productObj->find(2);
        if (date('Ymd') == $lcUpdate['date']
            && $lcUpdate['is_finished'] == 1
        ) {
            echo "success";exit();
        }

        // 当日产品数据开始清表更新
        if (date('Ymd') > $lcUpdate['date']) {
            Db::execute("TRUNCATE TABLE mu_lc_product");
            ProductUpdateModel::update(['id' => $lcUpdate['id'], 'date' => date('Ymd'), 'page' => 1, 'is_finished' => 0]);
            $lcUpdate['page'] = 1;
        }

        Db::startTrans();
        try {
            // 良仓产品更新
            if (date('Ymd') == $lcUpdate['date'] && $lcUpdate['is_finished'] == 1){
                echo "success";
            } else {
                if (date('H') >= 1) {
                    $lcProductRes = ApiClient::LcWarehouseApi("getProductList", '{"pageSize":50,"page":' . $lcUpdate['page'] . '}');
                    $lcProductList = $lcProductRes['data'];
                    if (count($lcProductList) <= 0) {
                        ProductUpdateModel::update(['id' => $lcUpdate['id'], 'is_finished' => 1]);
                    } else {
                        $addData = [];
                        foreach ($lcProductList as $item) {
                            $productInfo = LcProductModel::get(['product_sku' => $item['product_sku']]);
                            if (!empty($productInfo)) {
                                continue;
                            }
                            $productDetail = $item;
                            unset($productDetail['warehouse_attribute']);
                            $productDetail['warehouse_attribute'] = json_encode($item['warehouse_attribute']);
                            $productDetail['product_modify_time'] = empty($item['product_modify_time']) ? null : $item['product_modify_time'];
                            $addData[] = $productDetail;
                            unset($item);
                        }
                        unset($lcProductList);
                        $productObj = new LcProductModel();
                        $productObj->saveAll($addData);
                        ProductUpdateModel::update(['id' => $lcUpdate['id'], 'page' => $lcUpdate['page'] + 1]);
                        unset($addData);
                    }
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