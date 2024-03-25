<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOrderRefundModel;
use app\Manage\model\FinanceOrderSaleModel;
use app\Manage\model\FinanceOrderOutboundModel;
use app\Manage\model\FinanceOrderShippingServiceModel;
use app\Manage\model\FinanceReportModel;
use app\Manage\model\FinanceTableModel;
use app\Manage\model\OrderDetailModel;
use app\Manage\model\OrderModel;
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
            $financeObj = new FinanceOrderSaleModel();
            $list = $financeObj->where(['is_notify' => 0])->order('id asc')->limit(Config::get('finance_notify_num'))->select();
            if (count($list)) {
                $newData = [];
                foreach ($list as $item) {
                    $finance = OrderModel::all(['refNo' => $item['payment_id']]);
                    $outbound_id = [];
                    foreach ($finance as $outbound) {
                        $orderDetailObj = new OrderDetailModel();
                        $orderDetail = $orderDetailObj->with('product')->where(['order_id' => $outbound['id']])->find()->toArray();
                        $outbound_id[] = $outbound['id'];
                        $newData[] = [
                            'report_id'         =>  $item['report_id'],
                            'table_id'          =>  $item['table_id'],
                            'finance_order_id'  =>  $item['id'],
                            'ecang_order_id'    =>  $outbound['id'],
                            'payment_id'        =>  $item['payment_id'],
                            'saleOrderCode'     =>  $outbound['saleOrderCode'],
                            'warehouse_sku'     =>  $orderDetail['product']['productSku'],
                            'oprater_id'        =>  $orderDetail['product']['personOpraterId'],
                            'seller_id'         =>  $orderDetail['product']['personSellerId'],
                            'currency'          =>  $outbound['currency'],
                            'paid_amount'       =>  $outbound['amountpaid'],
                            'tail_amount'       =>  $outbound['calcuRes'],
                            'warehouse_id'      =>  $outbound['warehouseId'],
                            'warehouse_code'    =>  $outbound['warehouseCode'],
                            'created_date'      =>  date('Y-m-d H:i:s')
                        ];
                    }
                    $notifyData = [
                        'id'            =>  $item['id'],
                        'is_notify'     =>  1
                    ];
                    if (!$financeObj->update($notifyData)) {
                        throw new Exception("原始订单与出库数据关联失败！");
                    }

                    // 回调表格和报告状态
                    $orderCount = $financeObj->where(['is_notify' => 0, 'table_id' => $item['table_id']])->count();
                    if ($orderCount <= 0) {
                        $financeTableObj = new FinanceTableModel();
                        $financeTableObj->update(['is_notify' => 1, 'id' => $item['table_id']]);
                        $tableCount = $financeTableObj->where(['is_notify' => 0, 'rid' => $item['rid']])->count();
                        if ($tableCount <= 0) {
                            $financeReportObj = new FinanceReportModel();
                            $financeReportObj->update(['is_notify' => 1, 'id' => $item['rid']]);
                        }
                    }

                    unset($outbound_id);
                }
                $financeOrderOutboundObj = new FinanceOrderOutboundModel();
                if (!$financeOrderOutboundObj->insertAll($newData)) {
                    throw new Exception("批量新增失败！");
                }
                Db::commit();
                unset($newData);
            }

            $financeOrderShippingServiceObj = new FinanceOrderShippingServiceModel();
            $shipping_service = $financeOrderShippingServiceObj->where(['is_finished' => 0])->order('id asc')->limit(100)->select();
            if (count($shipping_service)) {
                $orderRefundObj = new FinanceOrderRefundModel();
                foreach ($shipping_service as $value) {
                    $orderRefundItem = $orderRefundObj->where(['payment_id' => $value['payment_id']])->find();
                    if ($orderRefundItem) {
                        $financeOrderShippingServiceObj->update(['sku' => $orderRefundItem['sku'], 'is_finished' => 1], ['id' => $value['id']]);
                    } else {
                        $financeOrderShippingServiceObj->update(['is_finished' => 1], ['id' => $value['id']]);
                    }
                }
            }

            $output->writeln("success");
        } catch (\Exception $e) {
            Db::rollback();
            $output->writeln($e->getMessage());
        }
    }
}