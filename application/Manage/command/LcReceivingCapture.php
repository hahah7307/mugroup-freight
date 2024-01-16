<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\LcReceivingItemModel;
use app\Manage\model\LcReceivingModel;
use app\Manage\model\LcReceivingPageModel;
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

class LcReceivingCapture extends Command
{
    protected function configure()
    {
        $this->setName('LcReceivingCapture')->setDescription('Here is the LcReceivingCapture');
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
        $data = LcReceivingPageModel::find()->toArray();

        $apiRes = ApiClient::LcWarehouseApi("getAsnList", '{"pageSize":' . $data['pageSize'] . ',"page":' . $data['page'] . '}');
        if ($apiRes['code'] == 1 && count($apiRes['data']) > 0) {
            Db::startTrans();
            try {
                foreach ($apiRes['data'] as $item) {
                    if (LcReceivingModel::get(['receiving_code' => $item['receiving_code']])) {
                        continue;
                    }
                    $receivingData = $item;
                    unset($receivingData['receiving_cost']);
                    unset($receivingData['items']);
                    unset($receivingData['serial_numbers']);
                    unset($receivingData['box_info']);
                    $receivingData['receiving_cost'] = json_encode($item['receiving_cost']);
                    $receivingData['serial_numbers'] = json_encode($item['serial_numbers']);
                    $receivingData['box_info'] = json_encode($item['box_info']);
                    if ($newId = LcReceivingModel::create($receivingData)->getLastInsID()) {
                        if ($item['items']) {
                            $productItems = [];
                            foreach ($item['items'] as $receivingItem) {
                                $productItem = $receivingItem;
                                unset($productItem['loTypeCount']);
                                unset($productItem['warehouse_attr']);
                                unset($productItem['product_cost']);
                                $productItem['loTypeCount'] = json_encode($receivingItem['loTypeCount']);
                                $productItem['warehouse_attr'] = json_encode($receivingItem['warehouse_attr']);
                                $productItem['product_cost'] = json_encode($receivingItem['product_cost']);
                                $productItem['receiving_id'] = $newId;
                                $productItem['receiving_code'] = $item['receiving_code'];
                                $productItems[] = $productItem;
                                unset($productItem);
                            }
                            unset($receivingItem);
                            $lcReceivingItem = new LcReceivingItemModel();
                            if (!$lcReceivingItem->insertAll($productItems)) {
                                throw new Exception("易仓入库单产品详情插入失败！");
                            }
                            unset($productItems);
                        }
                    } else {
                        throw new Exception("易仓入库单插入失败！");
                    }
                    unset($receivingData);
                }

                if (count($apiRes['data']) >= $data['pageSize']) {
                    LcReceivingPageModel::update(['id' => $data['id'], 'page' => $data['page'] + 1, 'index' => 0]);
                } else {
                    LcReceivingPageModel::update(['id' => $data['id'], 'index' => count($apiRes['data'])]);
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $output->writeln($e->getMessage());
            }
        }

        $output->writeln("success");
    }
}