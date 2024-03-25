<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOrderOutboundModel;
use app\Manage\model\FinanceStoreModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class FinanceNotify extends Command
{
    protected function configure()
    {
        $this->setName('FinanceNotify')->setDescription('Here is the FinanceNotify');
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
            $financeOutboundObj = new FinanceOrderOutboundModel();
            $list = $financeOutboundObj->where(['is_notify' => 0])->order('id asc')->limit(Config::get('finance_notify_num'))->order('dateWarehouseShipping asc')->select();
            if (count($list)) {
                $financeStoreObj = new FinanceStoreModel();
                foreach ($list as $item) {
                    $sku = $item['warehouse_sku'];
                    $storeItems = $financeStoreObj->where(['sku' => $sku])->order('entering_date asc')->select(); // 剩余库存
                    if (count($storeItems) <= 0) {
                        $financeOutboundObj->update(['is_notify' => 1], ['id' => $item['id']]);
                    }
                    $outboundCount = $financeOutboundObj->where(['warehouse_sku' => $sku, 'is_notify' => 1])->sum('qty'); // 已发总计
                    foreach ($storeItems as $storeItem) {
                        if ($outboundCount + $item['qty'] > $storeItem['available_quantity']) {
                            $outboundCount -= $storeItem['available_quantity'];
                        } else {
                            $financeOutboundObj->update(['store_id' => $storeItem['id'], 'is_notify' => 1], ['id' => $item['id']]);
                            $outboundCount = 0;
                            break;
                        }
                        unset($storeItem);
                    }
                    if ($outboundCount >= 0) {
                        $financeOutboundObj->update(['is_notify' => 1], ['id' => $item['id']]);
                    }
                    unset($outboundCount);
                }
            }

            Db::commit();
            $output->writeln("success");
        } catch (\Exception $e) {
            Db::rollback();
            $output->writeln($e->getMessage());
        }
    }
}