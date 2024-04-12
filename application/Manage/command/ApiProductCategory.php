<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\ApiProductCategoryUpdateModel;
use app\Manage\model\LcApiProductCategoryModel;
use app\Manage\model\LeApiProductCategoryModel;
use app\Manage\model\ApiProductCategoryModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class ApiProductCategory extends Command
{
    protected function configure()
    {
        $this->setName('ApiProductCategory')->setDescription('Here is the ApiProductCategory');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        $categoryUpdateObj = new ApiProductCategoryUpdateModel();
        $categoryUpdate = $categoryUpdateObj->find(1);
        if (date('Ymd') == $categoryUpdate['date']
            && $categoryUpdate['is_finished'] == 1
        ) {
            echo "success";exit();
        }

        // 当日产品数据开始清表更新
        if (date('Ymd') > $categoryUpdate['date']) {
            Db::execute("TRUNCATE TABLE mu_api_product_category");
            ApiProductCategoryUpdateModel::update(['id' => $categoryUpdate['id'], 'date' => date('Ymd'), 'page' => 1, 'is_finished' => 0]);
            $categoryUpdate['page'] = 1;
        }

        Db::startTrans();
        try {
            $list = ApiClient::httpCurl("http://47.101.47.220/Home/Api/getSkuCategory/page/" . $categoryUpdate['page'] . "/pageSize/" . $categoryUpdate['pageSize'], "GET");
            $listRes = json_decode($list, true);
            if ($listRes['code'] == 200) {
                if (count($listRes['data']) <= 0) {
                    ApiProductCategoryUpdateModel::update(['id' => $categoryUpdate['id'], 'page' => $categoryUpdate['page'] + 1, 'is_finished' => 1]);
                } else {
                    $addData = [];
                    foreach ($listRes['data'] as $item) {
                        $productInfo = ApiProductCategoryModel::get(['productSku' => $item['sku']]);
                        if (!empty($productInfo)) {
                            continue;
                        }
                        $addData[] = [
                            'productSku'        =>  $item['sku'],
                            'category_name_1'   =>  $item['cate'],
                            'category_name_2'   =>  $item['category']['name']
                        ];
                        unset($item);
                    }
                    unset($ecProductList);
                    $categoryUpdateObj = new ApiProductCategoryModel();
                    $categoryUpdateObj->saveAll($addData);
                    ApiProductCategoryUpdateModel::update(['id' => $categoryUpdate['id'], 'page' => $categoryUpdate['page'] + 1]);
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