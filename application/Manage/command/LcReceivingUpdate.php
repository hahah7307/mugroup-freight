<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\LcReceivingItemModel;
use app\Manage\model\LcReceivingModel;
use app\Manage\model\LcReceivingUpdateModel;
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

class LcReceivingUpdate extends Command
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
        $data = LcReceivingUpdateModel::find()->toArray();
        $lcReceivingObj = new LcReceivingModel();
        $lcReceivingData = $lcReceivingObj->where(['receiving_status' => ['neq', 'E']])->limit($data['index'] . ','. $data['pageSize'])->select();

        Db::startTrans();
        try {
            foreach ($lcReceivingData as $item) {
                $apiRes = ApiClient::LcWarehouseApi("getAsnList", '{"page":1,"pageSize":100,"receiving_code":"' . $item['receiving_code'] . '"}');
                if ($apiRes['code'] == 1 && count($apiRes['data']) > 0) {
                    $newData = $apiRes['data'][0];
                    unset($newData['receiving_cost']);
                    unset($newData['items']);
                    unset($newData['serial_numbers']);
                    unset($newData['box_info']);
                    $newData['receiving_cost'] = json_encode($apiRes['data'][0]['receiving_cost']);
                    $newData['serial_numbers'] = json_encode($apiRes['data'][0]['serial_numbers']);
                    $newData['box_info'] = json_encode($apiRes['data'][0]['box_info']);
                    if ($lcReceivingObj->update($newData, ['id' => $item['id']])) {
                        $lcReceivingItemObj = new LcReceivingItemModel();
                        $lcReceivingItemData = $lcReceivingItemObj->where(['receiving_id' => $item['id']])->select();
                        if ($lcReceivingItemData) {
                            $apiItems = $apiRes['data'][0]['items'];
                            $itemArr = [];
                            foreach ($apiItems as $apiItem) {
                                $newItem = $apiItem;
                                unset($newItem['loTypeCount']);
                                unset($newItem['warehouse_attr']);
                                unset($newItem['product_cost']);
                                $newItem['loTypeCount'] = json_encode($apiItem['loTypeCount']);
                                $newItem['warehouse_attr'] = json_encode($apiItem['warehouse_attr']);
                                $newItem['product_cost'] = json_encode($apiItem['product_cost']);
                                foreach ($lcReceivingItemData as $receivingItem) {
                                    if ($receivingItem['product_sku'] == $newItem['product_sku']) {
                                        $newItem['id'] = $receivingItem['id'];
                                        $newItem['receiving_id'] = $item['id'];
                                    }
                                }
                                $itemArr[] = $newItem;
                            }
                            if (!$lcReceivingItemObj->saveAll($itemArr)) {
                                throw new Exception("良仓入库单详情更新失败！");
                            }
                        }
                    } else {
                        throw new Exception("良仓入库单更新失败！");
                    }
                }
            }

            if (count($lcReceivingData) >= $data['pageSize']) {
                LcReceivingUpdateModel::update(['id' => $data['id'], 'index' => $data['index'] + $data['pageSize']]);
            } else {
                LcReceivingUpdateModel::update(['id' => $data['id'], 'index' => 0]);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $output->writeln($e->getMessage());
        }

        $output->writeln("success");
    }
}