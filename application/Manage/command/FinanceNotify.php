<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOrderOutboundModel;
use app\Manage\model\FinanceOrderSaleModel;
use app\Manage\model\FinanceReportModel;
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
            $financeReportObj = new FinanceReportModel();
            $list = $financeReportObj->where(['is_notify' => 0])->order('created_at asc')->select();
            if (count($list)) {
                foreach ($list as $report) {
                    // 检测wayfair订单是否关联完毕
                    $financeOrderSaleObj = new FinanceOrderSaleModel();
                    $orderSale = $financeOrderSaleObj->where(['sku' => null, 'report_id' => $report['id']])->order('id asc')->select();
                    if (count($orderSale) > 0) {
                        continue;
                    }

                    // 检测期初库存是否导入
                    $financeStoreObj = new FinanceStoreModel();
                    $store = $financeStoreObj->where(['report_id' => $report['id']])->order('entering_date asc')->select();
                    if (count($store) == 0) {
                        continue;
                    }

                    // 检测出库数据是否完全关联ddp
                    $financeOutboundObj = new FinanceOrderOutboundModel();
                    $outbound = $financeOutboundObj->where(['is_notify' => 0])->order('dateWarehouseShipping asc')->select();
                    if (count($outbound) > 0) {
                        continue;
                    }

                    $financeReportObj->save(['is_notify' => 1], ['id' => $report['id']]);
                    unset($report);
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