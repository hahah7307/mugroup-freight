<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\ReceivingItemModel;
use app\Manage\model\ReceivingModel;
use app\Manage\model\ReceivingUpdateModel;
use SoapFault;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;

class ReceivingUpdate extends Command
{
    protected function configure()
    {
        $this->setName('LcReceivingUpdate')->setDescription('Here is the LcReceivingUpdate');
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception|SoapFault
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        // 订单查询当前页数
        $data = ReceivingUpdateModel::find()->toArray();
        $receivingObj = new ReceivingModel();
        $receivingData = $receivingObj->where(['receiving_status' => ['in', [5,6]]])->limit($data['index'] . ','. $data['pageSize'])->select();

        Db::startTrans();
        try {
            foreach ($receivingData as $item) {
                $apiRes = ApiClient::EcWarehouseApi(Config::get('ec_wms_uri'), "getReceiving", '{"pagination":{"pageSize":100,"page":1},"receiving_code":"' . $item['receiving_code'] . '"}');
                if ($apiRes['code'] == 1 && count($apiRes['data']) > 0) {
                    $newData = $apiRes['data'][0];
                    unset($newData['product_info']);
                    if ($receivingObj->update($newData, ['id' => $item['id']])) {
                        $receivingItemObj = new ReceivingItemModel();
                        $receivingItemData = $receivingItemObj->where(['receiving_id' => $item['id']])->select();
                        if ($receivingItemData) {
                            $apiItems = $apiRes['data'][0]['product_info'];
                            $itemArr = [];
                            foreach ($apiItems as $apiItem) {
                                $newItem = $apiItem;
                                foreach ($receivingItemData as $receivingItem) {
                                    if ($receivingItem['product_barcode'] == $newItem['product_barcode']) {
                                        $newItem['id'] = $receivingItem['id'];
                                        $newItem['receiving_id'] = $item['id'];
                                    }
                                }
                                $itemArr[] = $newItem;
                            }
                            if (!$receivingItemObj->saveAll($itemArr)) {
                                throw new Exception("易仓入库单详情更新失败！");
                            }
                        }
                    } else {
                        throw new Exception("易仓入库单更新失败！");
                    }
                }
            }

            if (count($receivingData) >= $data['pageSize']) {
                ReceivingUpdateModel::update(['id' => $data['id'], 'index' => $data['index'] + $data['pageSize']]);
            } else {
                ReceivingUpdateModel::update(['id' => $data['id'], 'index' => 0]);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $output->writeln($e->getMessage());
        }

        $output->writeln("success");
    }
}