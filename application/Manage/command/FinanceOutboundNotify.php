<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOrderOutboundModel;
use app\Manage\model\FinanceStoreModel;
use app\Manage\model\FinanceWarehouseModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class FinanceOutboundNotify extends Command
{
    protected function configure()
    {
        $this->setName('FinanceOutboundNotify')->setDescription('Here is the FinanceOutboundNotify');
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
            $financeWarehouseObj = new FinanceWarehouseModel();
            $list = $financeOutboundObj->where(['is_notify' => 0])->limit(Config::get('finance_notify_num'))->order('dateWarehouseShipping asc')->select();
            if (count($list)) {
                $financeStoreObj = new FinanceStoreModel();
                foreach ($list as $item) {
                    $sku = $item['warehouse_sku'];
                    // 仓储费单价
                    $warehouse_rent_total = $financeWarehouseObj->where(['sku' => $sku])->sum('total');
                    $outbound_qty = $financeOutboundObj->where(['warehouse_sku' => $sku, 'report_id' => $item['report_id']])->sum('qty');
                    $warehouse_rent = round($warehouse_rent_total / $outbound_qty, 9) * $item['qty'];

                    $storeItems = $financeStoreObj->where(['sku' => $sku])->order('entering_date asc')->select(); // 剩余库存
                    if (count($storeItems) <= 0) {
                        $financeOutboundObj->update(['is_notify' => 1, 'warehouse_rent' => $warehouse_rent], ['id' => $item['id']]);
                    } else {
                        $outboundCount = $financeOutboundObj->where(['warehouse_sku' => $sku, 'is_notify' => 1])->sum('qty'); // 已发总计
                        foreach ($storeItems as $storeItem) {
                            if ($outboundCount + $item['qty'] > $storeItem['available_quantity']) {
                                $outboundCount -= $storeItem['available_quantity'];
                            } else {
                                $financeOutboundObj->update(['store_id' => $storeItem['id'], 'is_notify' => 1, 'warehouse_rent' => $warehouse_rent], ['id' => $item['id']]);
                                $outboundCount = 0;
                                break;
                            }
                            unset($storeItem);
                        }
                        if ($outboundCount >= 0) {
                            $financeOutboundObj->update(['is_notify' => 1, 'warehouse_rent' => $warehouse_rent], ['id' => $item['id']]);
                        }
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