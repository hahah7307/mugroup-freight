<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\OrderModel;
use app\Manage\model\OrderShippingUpdateModel;
use app\Manage\model\StorageAreaModel;
use app\Manage\model\StorageModel;
use SoapFault;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;

class ShippingOrderCode extends Command
{
    protected function configure()
    {
        $this->setName('ShippingOrderCode')->setDescription('Here is the ShippingOrderCode');
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws SoapFault
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        $update = OrderShippingUpdateModel::find()->toArray();
        $orderObj = new OrderModel();
        $orderList = $orderObj->order('id asc')->limit($update['orderId'] . "," . $update['pageSize'])->select();
        foreach ($orderList as $item) {
            $warehouseCode = $item['warehouseCode'];
            $storageArea = new StorageAreaModel();
            $area = $storageArea->where(['storage_code' => $warehouseCode, 'state' => 1])->find();
            if (!empty($area)) {
                if ($area['storage_id'] == StorageModel::LIANGCANGID) {
                    //
                    $lcApiRes = ApiClient::LcWarehouseApi("getOrderByRefCode", '{"reference_no":"' . $item['saleOrderCode'] . '"}');
                    if ($lcApiRes['code'] == 1 && !empty($lcApiRes['data'])) {
                        $shippingOrderCode = $lcApiRes['data']['order_code'];
                        if ($shippingOrderCode) {
                            $orderObj->update(['id' => $item['id'], 'shippingOrderCode' => $shippingOrderCode]);
                        }
                    }
                }

                if ($area['storage_id'] == StorageModel::LECANGID) {
                    //
                    $leData = [
                        'orderNo' => $item['saleOrderCode']
                    ];
                    $leApiRes = ApiClient::LeWarehouseApi("https://app.lecangs.com/api/oms/omsTocOrder/getByOrderNo", "POST", $leData);
                    if ($leApiRes['code'] == 1 && !empty($leApiRes['data'])) {
                        $shippingOrderCode = $leApiRes['data']['orderNo'];
                        if ($shippingOrderCode) {
                            $orderObj->update(['id' => $item['id'], 'shippingOrderCode' => $shippingOrderCode]);
                        }
                    }
                }
            }
            continue;
        }
        $update['orderId'] = count($orderList) < $update['pageSize'] ? 0 : $update['orderId'] + count($orderList);
        OrderShippingUpdateModel::update($update);

        $output->writeln("success");
    }
}