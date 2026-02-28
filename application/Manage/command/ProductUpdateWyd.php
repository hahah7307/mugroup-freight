<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\WydProductModel;
use app\Manage\model\ProductUpdateModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class ProductUpdateWyd extends Command
{
    protected function configure()
    {
        $this->setName('ProductUpdateWyd')->setDescription('Here is the ProductUpdateWyd');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        $productObj = new ProductUpdateModel();
        $wydUpdate = $productObj->find(4);
        if (date('Ymd') == $wydUpdate['date']
            && $wydUpdate['is_finished'] == 1
        ) {
            echo "success";exit();
        }

        // 当日产品数据开始清表更新
        if (date('Ymd') > $wydUpdate['date']) {
            Db::execute("TRUNCATE TABLE mu_wyd_product");
            ProductUpdateModel::update(['id' => $wydUpdate['id'], 'date' => date('Ymd'), 'page' => 1, 'is_finished' => 0]);
            $wydUpdate['page'] = 1;
        }

        Db::startTrans();
        try {
            // 乐歌产品更新
            if (date('Ymd') == $wydUpdate['date'] && $wydUpdate['is_finished'] == 1){
                echo "success";
            } else {
                if (date('H') >= 1) {
                    $wydProductParams = ['goodsType' => 1, 'pageNum' => $wydUpdate['page'], 'pageSize' => 50];
                    $wydProductRes = ApiClient::WydWarehouseApi("/oms/openapi/goods/v1/goods/queryPage", "POST", $wydProductParams);
                    if ($wydProductRes['code'] == 1) {
                        $data = \GuzzleHttp\json_decode($wydProductRes['data'], true);
                        if (count($data['records']) <= 0) {
                            ProductUpdateModel::update(['id' => $wydUpdate['id'], 'is_finished' => 1]);
                        } else {
                            $addData = [];
                            foreach ($data['records'] as $item) {
                                $productInfo = WydProductModel::get(['sku' => $item['sku']]);
                                if (!empty($productInfo)) {
                                    continue;
                                }
                                $productDetail = $item;
                                $productDetail['codeList'] = \GuzzleHttp\json_encode($item['codeList']);
                                $productDetail['extAttribute'] = \GuzzleHttp\json_encode($item['extAttribute']);
                                $addData[] = $productDetail;
                                unset($item);
                            }
                            unset($leProductList);
                            $productObj = new WydProductModel();
                            $productObj->saveAll($addData);
                            ProductUpdateModel::update(['id' => $wydUpdate['id'], 'page' => $wydUpdate['page'] + 1]);
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