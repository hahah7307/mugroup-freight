<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOrderSaleModel;
use app\Manage\model\FinanceOrderStatisticsModel;
use app\Manage\model\FinanceReportModel;
use app\Manage\model\FinanceTableModel;
use app\Manage\model\FinanceWayfairCoreModel;
use Exception;
use think\Cache;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class FinanceWayfairOrder extends Command
{
    protected function configure()
    {
        $this->setName('FinanceWayfairOrder')->setDescription('Here is the FinanceWayfairOrder');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        Db::startTrans();
        try {
            $cache = Cache::get('wayfairOrder');
            $list = array_slice($cache, 0, 200);
            if (count($list)) {
                $newOrder = [];
                $tableId = [];
                $wayfairCoreObj = new FinanceWayfairCoreModel();
                $statistic = new FinanceOrderStatisticsModel();
                foreach ($list as $key => $item) {
                    if ($item) {
                        $wayfairOrder = $wayfairCoreObj->where(['payment_id' => $item['payment_id'], 'status' => 1])->find();
                        $report = FinanceReportModel::get($item['report_id']);
                        $table = FinanceTableModel::get($item['table_id']);
                        $tableId[] = $item['table_id'];
                        if (empty($wayfairOrder)) {
                            $statisticLast = $statistic->where(['payment_id' => $item['payment_id']])->order('shipping_time desc')->find();
                            $newOrder[] = [
                                'table_id'          =>  $item['table_id'],
                                'invoice_no'        =>  $item['payment_id'],
                                'invoice_date'      =>  empty($statisticLast) ? NULL : $statisticLast['shipping_time'],
                                'payment_id'        =>  $item['payment_id'],
                                'sale_amount'       =>  $item['product_sales'],
                                'status'            =>  1,
                                'currency'          =>  'USD',
                                'commission_rate'   =>  0.04,
                                'commission'        =>  -$item['selling_fees'],
                                'collection'        =>  $item['product_sales'] + $item['selling_fees'],
                                'calculate_month'   =>  date('Ym', strtotime($report['month'])),
                                'user_account'      =>  $table['userAccount']
                            ];
                        }

                        // 给wayfair订单安排seller_sku和数量
                        // wayfair订单一拆到底，按每一个sku发货所以数量可以恒定为1
                        $wayfairOrderStatistic = $statistic->where(['payment_id' => $item['payment_id']])->find();
                        if ($wayfairOrderStatistic) {
                            $list[$key]['sku'] = $wayfairOrderStatistic['platform_sku'];
                        } else {
                            $list[$key]['sku'] = '';
                        }
                        $list[$key]['quantity'] = 1;
                    }
                }

                // 默认wayfair的invoice不会跨月出现
                $financeOrderSaleObj = new FinanceOrderSaleModel();
                if ($financeOrderSaleObj->saveAll(array_filter($list))) {
                    $tableIdUnique = array_unique($tableId);
                    foreach ($tableIdUnique as $value) {
                        $productSale = $financeOrderSaleObj->where(['table_id' => $value])->sum('product_sales');
                        $shipping = $financeOrderSaleObj->where(['table_id' => $value])->sum('shipping_credits');
                        $gift = $financeOrderSaleObj->where(['table_id' => $value])->sum('gift_wrap_credits');
                        $regulatory = $financeOrderSaleObj->where(['table_id' => $value])->sum('regulatory_fee');
                        $promotional = $financeOrderSaleObj->where(['table_id' => $value])->sum('promotional_rebates');
                        $sale_amount = round($productSale + $shipping + $gift + $regulatory + $promotional, 2);
                        FinanceTableModel::update(['sale_amount' => $sale_amount], ['id' => $value]);
                    }
                }
                $wayfairCoreObj->insertAll($newOrder);
            }
            Cache::set('wayfairOrder', array_slice($cache, 200), 24 * 60 * 60);

            Db::commit();
            $output->writeln("success");
        } catch (\Exception $e) {
            Db::rollback();
            $output->writeln($e->getMessage());
        }

        Db::startTrans();
        try {
            $cache = Cache::get('wayfairPayment');
            $list = array_slice($cache, 0, 200);
            if (count($list)) {
                $wayfairCoreObj = new FinanceWayfairCoreModel();
                foreach ($list as $item) {
                    if ($item) {
                        $wayfairOrder = $wayfairCoreObj->where(['payment_id' => $item['order_no'], 'status' => 1])->find();
                        if (!empty($wayfairOrder)) {
                            $wayfairOrder['payment_collection'] = $item['amount'] + $item['ca_commission'] + $item['am_commission'] + $item['commission'];
                            $wayfairOrder['payment_date'] = $item['payment_date'];
                            $wayfairOrder['payment_batch'] = $item['payment_batch'];
                            $wayfairOrder['payment_cycle'] = ceil((strtotime($item['payment_date']) - strtotime($wayfairOrder['invoice_date']))/ 86400);
                            $wayfairCoreObj->update($wayfairOrder->toArray(), ['id' => $wayfairOrder['id']]);
                        }
                    }
                }
            }
            Cache::set('wayfairPayment', array_slice($cache, 200), 24 * 60 * 60);

            Db::commit();
            $output->writeln("success");
        } catch (\Exception $e) {
            Db::rollback();
            $output->writeln($e->getMessage());
        }
    }
}