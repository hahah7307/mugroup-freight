<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\LeProductModel;
use app\Manage\model\ProductUpdateModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class ProductUpdateLe extends Command
{
    protected function configure()
    {
        $this->setName('ProductUpdateLe')->setDescription('Here is the ProductUpdateLe');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        $productObj = new ProductUpdateModel();
        $leUpdate = $productObj->find(3);
        if (date('Ymd') == $leUpdate['date']
            && $leUpdate['is_finished'] == 1
        ) {
            echo "success";exit();
        }

        // 当日产品数据开始清表更新
        if (date('Ymd') > $leUpdate['date']) {
            Db::execute("TRUNCATE TABLE mu_le_product");
            ProductUpdateModel::update(['id' => $leUpdate['id'], 'date' => date('Ymd'), 'page' => 1, 'is_finished' => 0]);
            $leUpdate['page'] = 1;
        }

        Db::startTrans();
        try {
            // 乐歌产品更新
            if (date('Ymd') == $leUpdate['date'] && $leUpdate['is_finished'] == 1){
                echo "success";
            } else {
                if (date('H') >= 1) {
                    $leProductParams = ['pageNum' => $leUpdate['page'], 'pageSize' => 50];
                    $leProductRes = ApiClient::LeWarehouseApi("https://app.lecangs.com/api/oms/goods/api/list", "POST", $leProductParams);
                    $leProductList = $leProductRes['data']['list'];
                    if (count($leProductList) <= 0) {
                        ProductUpdateModel::update(['id' => $leUpdate['id'], 'is_finished' => 1]);
                    } else {
                        $addData = [];
                        foreach ($leProductList as $item) {
                            $productInfo = LeProductModel::get(['code' => $item['code']]);
                            if (!empty($productInfo)) {
                                continue;
                            }
                            $productDetail = $item;
                            $addData[] = $productDetail;
                            unset($item);
                        }
                        unset($leProductList);
                        $productObj = new LeProductModel();
                        $productObj->saveAll($addData);
                        ProductUpdateModel::update(['id' => $leUpdate['id'], 'page' => $leUpdate['page'] + 1]);
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