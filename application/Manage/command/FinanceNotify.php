<?php
namespace app\Manage\command;

use app\Manage\model\FinanceEvaluationModel;
use app\Manage\model\FinanceOrderAdditionalModel;
use app\Manage\model\FinanceOrderAdjustmentModel;
use app\Manage\model\FinanceOrderLiquidationModel;
use app\Manage\model\FinanceOrderOutboundModel;
use app\Manage\model\FinanceOrderRefundModel;
use app\Manage\model\FinanceOrderSaleModel;
use app\Manage\model\FinanceOrderShippingServiceModel;
use app\Manage\model\FinanceReportModel;
use app\Manage\model\FinanceStoreModel;
use app\Manage\model\FinanceWarehouseModel;
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

                    // 检测仓租费是否导入
                    $warehouseObj = new FinanceWarehouseModel();
                    $warehouse = $warehouseObj->where(['report_id' => $report['id']])->order('id asc')->select();
                    if (count($warehouse) == 0) {
                        continue;
                    }

                    // 检测额外费用是否导入
                    $additionalObj = new FinanceOrderAdditionalModel();
                    $additional = $additionalObj->where(['report_id' => $report['id']])->order('id asc')->select();
                    if (count($additional) == 0) {
                        continue;
                    }

                    // 检测测评订单是否导入
                    $evaluationObj = new FinanceEvaluationModel();
                    $evaluation = $evaluationObj->where(['report_id' => $report['id']])->order('id asc')->select();
                    if (count($evaluation) == 0) {
                        continue;
                    }

                    // 检测期初库存是否导入
                    $financeStoreObj = new FinanceStoreModel();
                    $store = $financeStoreObj->where(['report_id' => $report['id']])->order('entering_date asc')->select();
                    if (count($store) == 0) {
                        continue;
                    }

                    // 检测出库数据是否完全关联ddp和仓储费分摊
                    $financeOutboundObj = new FinanceOrderOutboundModel();
                    $outbound = $financeOutboundObj->where(['report_id' => $report['id']])->where(['is_notify' => 0])->order('shipping_time asc')->select();
                    if (count($outbound) > 0) {
                        continue;
                    }

                    // 检测分摊是否完成
                    $financeOrderRefundObj = new FinanceOrderRefundModel();
                    $refund = $financeOrderRefundObj->where(['report_id' => $report['id']])->where('share_code', null)->order('id asc')->select();
                    if (count($refund) > 0) {
                        continue;
                    }

                    $financeOrderShippingObj = new FinanceOrderShippingServiceModel();
                    $shipping = $financeOrderShippingObj->where(['report_id' => $report['id']])->where('share_code', null)->order('id asc')->select();
                    if (count($shipping) > 0) {
                        continue;
                    }

                    $financeOrderAdjustmentObj = new FinanceOrderAdjustmentModel();
                    $adjustment = $financeOrderAdjustmentObj->where(['report_id' => $report['id']])->where('share_code', null)->order('id asc')->select();
                    if (count($adjustment) > 0) {
                        continue;
                    }

                    $financeOrderLiquidationObj = new FinanceOrderLiquidationModel();
                    $liquidation = $financeOrderLiquidationObj->where(['report_id' => $report['id']])->where('total', 'neq', 0)->where('share_code', null)->order('id asc')->select();
                    if (count($liquidation) > 0) {
                        continue;
                    }

                    $additional = $additionalObj->where(['report_id' => $report['id']])->where('share_code', null)->where('promotion', 'not null')->order('id asc')->select();
                    if (count($additional) > 0) {
                        continue;
                    }

                    // 检测无销售的sku仓储费是否归类主件
                    $noSaleWarehouse = $warehouseObj->where(['is_sale' => 0])->where('main_sku', null)->order('id asc')->select();
                    if (count($noSaleWarehouse) > 0) {
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