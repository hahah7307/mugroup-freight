<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\ListingModel;
use app\Manage\model\SkuRelationModel;
use app\Manage\model\ListingUpdateModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class ListingUpdate extends Command
{
    protected function configure()
    {
        $this->setName('ListingUpdate')->setDescription('Here is the ListingUpdate');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        $updateObj = new ListingUpdateModel();
        $ecUpdate = $updateObj->find(1);
        if (date('Ymd') == $ecUpdate['date'] && $ecUpdate['is_finished'] == 1) {
            echo "success";exit();
        }

        if (date('Ymd') > $ecUpdate['date']) {
            Db::execute("TRUNCATE TABLE mu_ecang_listing");
            ListingUpdateModel::update(['id' => $ecUpdate['id'], 'date' => date('Ymd'), 'page' => 1, 'is_finished' => 0]);
            $ecUpdate['page'] = 1;
        }

        Db::startTrans();
        try {
            $listingRes = ApiClient::EcWarehouseApi(Config::get("ec_wms_uri"), "getListingAccess", '{"page":' . $ecUpdate['page'] . ',"pageSize":500}');
            $listing = $listingRes['data'];
            if (count($listing) <= 0) {
                ListingUpdateModel::update(['id' => $ecUpdate['id'], 'page' => $ecUpdate['page'] + 1, 'is_finished' => 1]);
            } else {
                $addData = [];
                foreach ($listing as $item) {
                    $item['depart_name'] = json_encode($item['depart_name']);
                    $item['access_user_name'] = json_encode($item['access_user_name']);
                    $addData[] = $item;
                    unset($item);
                }
                $listingObj = new ListingModel();
                if (!$listingObj->insertAll($addData)) {
                    throw new Exception("添加失败！");
                }
                unset($addData);
                ListingUpdateModel::update(['id' => $ecUpdate['id'], 'page' => $ecUpdate['page'] + 1]);
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