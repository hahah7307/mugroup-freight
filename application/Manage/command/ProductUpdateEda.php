<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\EdaProductModel;
use app\Manage\model\ProductUpdateModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class ProductUpdateEda extends Command
{
    protected function configure()
    {
        $this->setName('ProductUpdateEda')->setDescription('Here is the ProductUpdateEda');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        $productObj = new ProductUpdateModel();
        $edaUpdate = $productObj->find(5);
        if (date('Ymd') == $edaUpdate['date']
            && $edaUpdate['is_finished'] == 1
        ) {
            echo "success";exit();
        }

        // 当日产品数据开始清表更新
        if (date('Ymd') > $edaUpdate['date']) {
            Db::execute("TRUNCATE TABLE mu_eda_product");
            ProductUpdateModel::update(['id' => $edaUpdate['id'], 'date' => date('Ymd'), 'page' => 1, 'is_finished' => 0]);
            $edaUpdate['page'] = 1;
        }

        Db::startTrans();
        try {
            if (date('Ymd') == $edaUpdate['date'] && $edaUpdate['is_finished'] == 1){
                echo "success";
            } else {
                if (date('H') >= 1) {
                    $edaProductRes = ApiClient::EdaWarehouseApi("/webApi/skuInfo/v1/listProductPage", "POST", ['pageNum' => $edaUpdate['page'], 'pageSize' => 100]);
                    if ($edaProductRes['code'] == 1) {
                        if (count($edaProductRes['data']['rows']) <= 0) {
                            ProductUpdateModel::update(['id' => $edaUpdate['id'], 'is_finished' => 1]);
                        } else {
                            $addData = [];
                            foreach ($edaProductRes['data']['rows'] as $item) {
                                $productInfo = EdaProductModel::get(['skuCode' => $item['skuCode']]);
                                if (!empty($productInfo)) {
                                    continue;
                                }
                                $productDetail = $item;
                                $addData[] = $productDetail;
                                unset($item);
                            }
                            unset($leProductList);
                            $productObj = new EdaProductModel();
                            $productObj->saveAll($addData);
                            ProductUpdateModel::update(['id' => $edaUpdate['id'], 'page' => $edaUpdate['page'] + 1]);
                            unset($addData);
                        }
                    } else {
                        echo "failed";
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