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
            $list = $financeOutboundObj->where(['is_notify' => 0])->limit(Config::get('finance_notify_num'))->order('shipping_time asc')->select();
            if (count($list)) {
                $financeStoreObj = new FinanceStoreModel();
                foreach ($list as $item) {
                    $sku = $item['warehouse_sku'];

                    // 仓储费单价
                    if (self::sku_identify($sku) && $item['fulfillment'] == "Seller") {
                        $warehouse_rent_total = $financeWarehouseObj->where(['sku' => ['like', $sku . '%'], 'report_id' => $item['report_id']])->sum('total');
                        $outbound_qty = $financeOutboundObj->where(['warehouse_sku' => $sku, 'report_id' => $item['report_id'], 'fulfillment' => 'Seller'])->sum('qty');

                        // 判断是否为最后一个
                        $last_one = $financeOutboundObj->where(['warehouse_sku' => $sku, 'report_id' => $item['report_id'], 'is_notify' => 0, 'fulfillment' => 'Seller'])->order('shipping_time desc')->find();
                        if ($last_one['id'] == $item['id']) {
                            $warehouse_rent_sum = $financeOutboundObj->where(['warehouse_sku' => $sku, 'report_id' => $item['report_id'], 'fulfillment' => 'Seller'])->sum('warehouse_rent');
                            $warehouse_rent = $warehouse_rent_total - $warehouse_rent_sum;
                        } else {
                            $warehouse_rent = round($warehouse_rent_total / $outbound_qty, 2) * $item['qty'];
                        }

                        if ($warehouse_rent_total) {
                            $financeWarehouseObj->update(['is_sale' => 1], ['sku' => ['like', $sku . '%'], 'report_id' => $item['report_id']]);
                        }
                    } else {
                        $warehouse_rent = 0;
                    }

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

    static protected function sku_identify($sku): bool
    {
        if (strpos($sku, '-') !== false) {
            if (preg_match('/-\d/', $sku)) {
                if (substr_count($sku, '-') > 1) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return false;
            }
        } else {
            return true;
        }
    }
}