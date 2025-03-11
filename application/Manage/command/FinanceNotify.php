<?php
namespace app\Manage\command;

use app\Manage\model\FinanceAdCostModel;
use app\Manage\model\FinanceEvaluationModel;
use app\Manage\model\FinanceOrderAdditionalModel;
use app\Manage\model\FinanceOrderAdjustmentModel;
use app\Manage\model\FinanceOrderLiquidationModel;
use app\Manage\model\FinanceOrderOutboundModel;
use app\Manage\model\FinanceOrderShareValidate;
use app\Manage\model\FinanceOrderShippingServiceModel;
use app\Manage\model\FinanceReportModel;
use app\Manage\model\FinanceSkuRelationModel;
use app\Manage\model\FinanceWarehouseFbmModel;
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
                    if (!FinanceOrderShareValidate::CompleteWayfairOrder()) {
                        $output->writeln("Wayfair Unready");
                        exit();
                    }

                    if (!FinanceOrderShareValidate::CompleteSheinOrder()) {
                        $output->writeln("Shein Unready");
                        exit();
                    }

                    if (!FinanceOrderShareValidate::CompleteIsShare($report['id'])) {
                        $output->writeln("Share Unready");
                        exit();
                    }

                    if (!FinanceOrderShareValidate::CompleteStoreImport($report['id'])) {
                        $output->writeln("Store Unready");
                        exit();
                    }

                    // 检测仓租费是否导入
                    $warehouseObj = new FinanceWarehouseModel();
                    $warehouse = $warehouseObj->where(['report_id' => $report['id']])->order('id asc')->select();
                    if (count($warehouse) == 0) {
                        $output->writeln("Warehouse Unready");exit();
                    }

                    // 检测额外费用是否导入
                    $additionalObj = new FinanceOrderAdditionalModel();
                    $additional = $additionalObj->where(['report_id' => $report['id']])->order('id asc')->select();
                    if (count($additional) == 0) {
                        $output->writeln("Additional Unready");exit();
                    }

                    // 检测测评订单是否导入
                    $evaluationObj = new FinanceEvaluationModel();
                    $evaluation = $evaluationObj->where(['report_id' => $report['id']])->order('id asc')->select();
                    if (count($evaluation) == 0) {
                        $output->writeln("Evaluation Unready");exit();
                    }

                    // 检测广告是否导入
                    $adCostObj = new FinanceAdCostModel();
                    $adCost = $adCostObj->where(['report_id' => $report['id']])->order('id asc')->select();
                    if (count($adCost) == 0) {
                        $output->writeln("AdCost Unready");exit();
                    }

                    // 检测产品与运营的映射关系是否导入
                    $financeSkuRelationObj = new FinanceSkuRelationModel();
                    $skuRelation = $financeSkuRelationObj->where(['report_id' => $report['id']])->order('id asc')->select();
                    if (count($skuRelation) == 0) {
                        $output->writeln("SkuRelation Unready");exit();
                    }

                    // 检测出库数据是否完全关联ddp和仓储费分摊
                    $financeOutboundObj = new FinanceOrderOutboundModel();
                    $outbound = $financeOutboundObj->where(['report_id' => $report['id']])->where(['is_notify' => 0])->order('shipping_time asc')->select();
                    if (count($outbound) > 0) {
                        $output->writeln("Outbound Unready");exit();
                    }

                    // 检测分摊是否完成
                    $financeOrderShippingObj = new FinanceOrderShippingServiceModel();
                    $shipping = $financeOrderShippingObj->where(['report_id' => $report['id']])->where('share_code', null)->order('id asc')->select();
                    if (count($shipping) > 0) {
                        $output->writeln("ShippingServiceShare Unready");exit();
                    }

                    $financeOrderAdjustmentObj = new FinanceOrderAdjustmentModel();
                    $adjustment = $financeOrderAdjustmentObj->where(['report_id' => $report['id']])->where('share_code', null)->order('id asc')->select();
                    if (count($adjustment) > 0) {
                        $output->writeln("AdjustmentShare Unready");exit();
                    }

//                    $financeOrderLiquidationObj = new FinanceOrderLiquidationModel();
//                    $liquidation = $financeOrderLiquidationObj->where(['report_id' => $report['id']])->where('total', 'neq', 0)->where('share_code', null)->order('id asc')->select();
//                    if (count($liquidation) > 0) {
//                        $output->writeln("LiquidationShare Unready");exit();
//                    }
//
//                    $additionalPromotion = $additionalObj->where(['report_id' => $report['id']])->where('share_code', null)->where('promotion', 'not null')->order('id asc')->select();
//                    if (count($additionalPromotion) > 0) {
//                        $output->writeln("AdditionalPromotionShare Unready");exit();
//                    }

                    $additionalLcAdjustment = $additionalObj->where(['report_id' => $report['id']])->where('share_code', null)->where('lc_adjustment', 'not null')->order('id asc')->select();
                    if (count($additionalLcAdjustment) > 0) {
                        $output->writeln("AdditionalLcAdjustmentShare Unready");exit();
                    }

                    $additionalLeAdjustment = $additionalObj->where(['report_id' => $report['id']])->where('share_code', null)->where('le_adjustment', 'not null')->order('id asc')->select();
                    if (count($additionalLeAdjustment) > 0) {
                        $output->writeln("AdditionalLeAdjustmentShare Unready");exit();
                    }

                    // 检测无销售的sku仓储费是否归类主件
                    $warehouseFbmObj = new FinanceWarehouseFbmModel();
                    $WarehouseFbmMainSku = $warehouseFbmObj->where('main_sku', null)->order('id asc')->select();
                    if (count($WarehouseFbmMainSku) > 0) {
                        $output->writeln("WarehouseFbmMainSku Unready");exit();
                    }

                    $WarehouseFbmShare = $warehouseFbmObj->where('share_code', null)->order('id asc')->select();
                    if (count($WarehouseFbmShare) > 0) {
                        $output->writeln("WarehouseFbmShare Unready");exit();
                    }

                    $financeReportObj->save(['is_notify' => 1], ['id' => $report['id']]);
                    $financeReportObj->save(['is_share' => 0], ['id' => $report['id']]);
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