<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOrderOutboundModel;
use app\Manage\model\FinanceOrderShareValidate;
use app\Manage\model\FinanceStoreModel;
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

        if (!FinanceOrderShareValidate::CompleteWayfairOrder() || !FinanceOrderShareValidate::CompleteSheinOrder()) {
            exit();
        }

        Db::startTrans();
        try {
            $financeOutboundObj = new FinanceOrderOutboundModel();
            $list = $financeOutboundObj->where(['is_notify' => 0])->limit(Config::get('finance_notify_num'))->order('shipping_time asc')->select();
            if (count($list)) {
                $financeStoreObj = new FinanceStoreModel();
                foreach ($list as $item) {
                    if (!FinanceOrderShareValidate::CompleteStoreImport($item['report_id'])) {
                        continue;
                    }

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