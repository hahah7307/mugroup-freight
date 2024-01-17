<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\ReceivingItemModel;
use app\Manage\model\ReceivingModel;
use app\Manage\model\ReceivingPageModel;
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

class ReceivingCapture extends Command
{
    protected function configure()
    {
        $this->setName('ReceivingCapture')->setDescription('Here is the ReceivingCapture');
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
        $data = ReceivingPageModel::find()->toArray();

        $apiRes = ApiClient::EcWarehouseApi(Config::get('ec_wms_uri'), "getReceiving", '{"pagination":{"pageSize":' . $data['pageSize'] . ',"page":' . $data['page'] . '},"order_by":["receiving_id asc"]}');
        if ($apiRes['code'] == 1 && count($apiRes['data']) > 0) {
            Db::startTrans();
            try {
                foreach ($apiRes['data'] as $item) {
                    if (ReceivingModel::get(['receiving_code' => $item['receiving_code']])) {
                        continue;
                    }
                    $receivingData = $item;
                    unset($receivingData['product_info']);
                    if ($newId = ReceivingModel::create($receivingData)->getLastInsID()) {
                        if ($item['product_info']) {
                            $productItems = [];
                            foreach ($item['product_info'] as $receivingItem) {
                                $productItem = $receivingItem;
                                $productItem['receiving_id'] = $newId;
                                $productItem['receiving_code'] = $item['receiving_code'];
                                $productItems[] = $productItem;
                                unset($productItem);
                            }
                            unset($receivingItem);
                            $lcReceivingItem = new ReceivingItemModel();
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
                    ReceivingPageModel::update(['id' => $data['id'], 'page' => $data['page'] + 1, 'index' => 0]);
                } else {
                    ReceivingPageModel::update(['id' => $data['id'], 'index' => count($apiRes['data'])]);
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