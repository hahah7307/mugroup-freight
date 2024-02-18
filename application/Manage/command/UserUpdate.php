<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\UserModel;
use app\Manage\model\UserUpdateModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class UserUpdate extends Command
{
    protected function configure()
    {
        $this->setName('UserUpdate')->setDescription('Here is the UserUpdate');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        $userUpdateObj = new UserUpdateModel();
        $update = $userUpdateObj->find(1);
        if (date('Ymd') == $update['date']
            && $update['is_finished'] == 1
        ) {
            echo "success";exit();
        }

        // 当日产品数据开始清表更新
        if (date('Ymd') > $update['date']) {
            Db::execute("TRUNCATE TABLE mu_ecang_user");
            UserUpdateModel::update(['id' => $update['id'], 'date' => date('Ymd'), 'is_finished' => 0]);
        }

        Db::startTrans();
        try {
            // 易仓管理员更新
            $userRes = ApiClient::EcWarehouseApi(Config::get("ec_wms_uri"), "getUser", '{}');
            $userList = $userRes['data'];
            $addData = [];
            foreach ($userList as $item) {
                $productDetail = $item;
                $addData[] = $productDetail;
                unset($item);
            }
            unset($userList);
            $userUpdateObj = new UserModel();
            $userUpdateObj->saveAll($addData);
            unset($addData);
            UserUpdateModel::update(['id' => $update['id'], 'is_finished' => 1]);

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