<?php
namespace app\Manage\command;

use app\Manage\model\AkOpenAPI;
use app\Manage\model\AkSellerModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class AkSeller extends Command
{
    protected function configure()
    {
        $this->setName('AkSeller')->setDescription('Here is the AkSeller');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        Db::execute("TRUNCATE TABLE mu_ak_seller");

        Db::startTrans();
        try {
            // 加载自定义配置
            Config::load(APP_PATH . 'storage.php');

            $res = AkOpenAPI::makeRequest("/erp/sc/data/seller/lists", "GET");
            if ($res['code'] == 0 && $res['message'] == 'success') {
                $seller = [];
                foreach ($res['data'] as $item) {
                    $seller[] = $item;
                }

                $akSellerObj = new AkSellerModel();
                $akSellerObj->insertAll($seller);
            } else {
                throw new Exception("请求异常！");
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