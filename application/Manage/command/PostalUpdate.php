<?php
namespace app\Manage\command;

use app\Manage\model\OrderAddressModel;
use app\Manage\model\OrderAddressPostalModel;
use app\Manage\model\OrderModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class PostalUpdate extends Command
{
    protected function configure()
    {
        $this->setName('postalUpdate')->setDescription('Here is the postalUpdate');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        Db::startTrans();
        try {
            $addressPostalObj = new OrderAddressPostalModel();
            $list = $addressPostalObj->where(['is_notify' => 0])->order('id asc')->limit(Config::get('postal_update_num'))->select();
            foreach ($list as $item) {
                // 留下更新记录
                $notifyData = ['id' => $item['id'], 'is_notify' => 1];
                if (!$addressPostalObj->update($notifyData)) {
                    throw new Exception("留下更新记录失败！");
                }

                $orderObj = new OrderModel();
                $orderData = $orderObj->with('address')->where(['saleOrderCode' => $item['saleOrderCode']])->find();
                if (empty($orderData)) {
                    continue;
                }

                // 更新数据
                if (empty($orderData['address']['postalCode'])) {
                    $addressObj = new OrderAddressModel();
                    $addressObj->save(['postalCode' => $this->postalCodeFormat($item['postalCode'])], ['order_id' => $orderData['id']]);
                }

                if (!$orderObj->update(['isImport' => 1, 'id' => $orderData['id']])) {
                    throw new Exception("易仓订单数据更新失败！");
                }

                // 更新易仓记录
                OrderModel::orderId2DeliverParams($orderData['id']);
                file_put_contents( APP_PATH . '/../runtime/log/PostalUpdate-' . date('Y-m-d') . '.log', PHP_EOL . "[" . date('Y-m-d H:i:s') . "] : " . $item['saleOrderCode'] . " " . var_export("Update Success",TRUE), FILE_APPEND);
                unset($item);
            }

            Db::commit();
            unset($list);
            $output->writeln("success");
        } catch (\Exception $e) {
            Db::rollback();
            $output->writeln($e->getMessage());
        }
    }

    // 客户订单邮编格式化
    public function postalCodeFormat($postalCode): string
    {
        $postalCode = strval($postalCode);
        if (strlen($postalCode) == 4) {
            return "0" . $postalCode;
        } elseif (strlen($postalCode) == 3) {
            return "00" . $postalCode;
        } else {
            return $postalCode;
        }
    }
}