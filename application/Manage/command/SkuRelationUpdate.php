<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\SkuModel;
use app\Manage\model\SkuRelationModel;
use app\Manage\model\SkuRelationUpdateModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class SkuRelationUpdate extends Command
{
    protected function configure()
    {
        $this->setName('SkuRelationUpdate')->setDescription('Here is the SkuRelationUpdate');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        $updateObj = new SkuRelationUpdateModel();
        $ecUpdate = $updateObj->find(1);
        if (date('Ymd') == $ecUpdate['date'] && $ecUpdate['is_finished'] == 1) {
            echo "success";exit();
        }

        // 当日产品数据开始清表更新
        if (date('Ymd') > $ecUpdate['date']) {
            Db::execute("TRUNCATE TABLE mu_ecang_sku");
            Db::execute("TRUNCATE TABLE mu_ecang_sku_relation");
            SkuRelationUpdateModel::update(['id' => $ecUpdate['id'], 'date' => date('Ymd'), 'page' => 1, 'is_finished' => 0]);
            $ecUpdate['page'] = 1;
        }

        Db::startTrans();
        try {
            // 易仓sku关联映射
            $skuRelationRes = ApiClient::EcWarehouseApi(Config::get("ec_eb_uri"), "getSkuRelation", '{"page":' . $ecUpdate['page'] . '}');
            $skuRelation = $skuRelationRes['data'];
            if (count($skuRelation) <= 0) {
                SkuRelationUpdateModel::update(['id' => $ecUpdate['id'], 'page' => $ecUpdate['page'] + 1, 'is_finished' => 1]);
            } else {
                foreach ($skuRelation as $item) {
                    $skuDetail = $item;
                    unset($skuDetail['relation']);
                    $skuObj = new SkuModel();
                    $skuId = $skuObj->insertGetId($skuDetail);
                    if (empty($skuId)) {
                        throw new Exception("sku添加失败！");
                    }

                    $skuRelationData = [];
                    foreach ($item['relation'] as $relation) {
                        $relation['sku_id'] = $skuId;
                        $skuRelationData[] = $relation;
                        unset($relation);
                    }
                    $skuRelationObj = new SkuRelationModel();
                    if (!$skuRelationObj->insertAll($skuRelationData)) {
                        throw new Exception("skuRelation添加失败！");
                    }
                    unset($skuRelationData);
                    unset($item);
                }
                unset($skuRelation);
                SkuRelationUpdateModel::update(['id' => $ecUpdate['id'], 'page' => $ecUpdate['page'] + 1]);
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