<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOrderAdjustmentModel;
use app\Manage\model\FinanceOrderOutboundModel;
use app\Manage\model\FinanceOrderRefundModel;
use app\Manage\model\FinanceOrderSaleModel;
use app\Manage\model\FinanceStoreModel;
use app\Manage\model\FinanceWarehouseModel;
use Exception;
use think\Cache;
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

        // 检测wayfair订单是否校验完毕
        $wayfairOrder = Cache::get('wayfairOrder');
        if (!empty($wayfairOrder)) {
            $output->writeln("Wayfair Unready");exit();
        }

        // 检测shein订单是否校验完毕
        $orderSaleObj = new FinanceOrderSaleModel();
        $sheinOrder = $orderSaleObj->where('sku', null)->where(['payment_id' => [['like', 'GSUN%']]])->select();
        $financeOrderRefundObj = new FinanceOrderRefundModel();
        $sheinRefund = $financeOrderRefundObj->where('sku', null)->where(['payment_id' => [['like', 'GSUN%']]])->select();
        $financeOrderAdjustmentObj = new FinanceOrderAdjustmentModel();
        $sheinAdjustment = $financeOrderAdjustmentObj->where('sku', null)->where(['payment_id' => [['like', 'GSUN%']]])->select();
        if (count($sheinOrder) + count($sheinRefund) + count($sheinAdjustment) > 0) {
            $output->writeln("Shein Unready");exit();
        }

        Db::startTrans();
        try {
            $financeOutboundObj = new FinanceOrderOutboundModel();
            $list = $financeOutboundObj->where(['is_notify' => 0])->limit(Config::get('finance_notify_num'))->order('shipping_time asc')->select();
            if (count($list)) {
                $financeStoreObj = new FinanceStoreModel();
                foreach ($list as $item) {
                    $sku = $item['warehouse_sku'];

                    $storeItems = $financeStoreObj->where(['sku' => $sku, 'report_id' => $item['report_id']])->order('entering_date asc,shipment_date asc, export_no asc')->select(); // 剩余库存
                    if (count($storeItems) <= 0) {
                        $financeOutboundObj->update(['is_notify' => 1], ['id' => $item['id']]);
                    } else {
                        $outboundCount = $financeOutboundObj->where(['warehouse_sku' => $sku, 'report_id' => $item['report_id'], 'is_notify' => 1])->sum('qty'); // 已发总计
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