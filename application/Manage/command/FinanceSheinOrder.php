<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOrderAdjustmentModel;
use app\Manage\model\FinanceOrderRefundModel;
use app\Manage\model\FinanceOrderSaleModel;
use app\Manage\model\FinanceOrderStatisticsModel;
use app\Manage\model\OrderDetailModel;
use app\Manage\model\OrderModel;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class FinanceSheinOrder extends Command
{
    protected function configure()
    {
        $this->setName('FinanceSheinOrder')->setDescription('Here is the FinanceSheinOrder');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        Db::startTrans();
        try {
            $orderSaleObj = new FinanceOrderSaleModel();
            $list = $orderSaleObj->where('sku', null)->limit(100)->select();
            $newOrder = [];
            if (count($list)) {
                foreach ($list as $item) {
                    if ($item) {
                        $statistic = new FinanceOrderStatisticsModel();
                        $sheinOrderStatistic = $statistic->where(['payment_id' => $item['payment_id']])->find();
                        if ($sheinOrderStatistic) {
                            $item['sku'] = $sheinOrderStatistic['platform_sku'];
                        } else {
                            $orderObj = new OrderModel();
                            $order = $orderObj->with(['details'])->where(['refNo' => $item['payment_id']])->find();
                            if ($order) {
                                $item['sku'] = $order['details'][0]['productSku'];
                            } else {
                                $item['sku'] = '';
                            }
                        }
                        $newOrder[] = $item->toArray();
                    }
                }
                unset($item);
                $orderSaleObj->saveAll($newOrder);
            }

            $orderRefundObj = new FinanceOrderRefundModel();
            $orderRefund = $orderRefundObj->where('sku', null)->limit(100)->select();
            $newOrderRefund = [];
            if (count($orderRefund)) {
                foreach ($orderRefund as $item) {
                    if ($item) {
                        $statistic = new FinanceOrderStatisticsModel();
                        $tkOrderStatistic = $statistic->where(['payment_id' => $item['payment_id']])->find();
                        if ($tkOrderStatistic) {
                            $item['sku'] = $tkOrderStatistic['platform_sku'];
                        } else {
                            $orderObj = new OrderModel();
                            $order = $orderObj->with(['details'])->where(['refNo' => $item['payment_id']])->find();
                            if ($order) {
                                $item['sku'] = $order['details'][0]['productSku'];
                            } else {
                                $item['sku'] = '';
                            }
                        }
                        $newOrderRefund[] = $item->toArray();
                    }
                }
                unset($item);
                $orderRefundObj->saveAll($newOrderRefund);
            }

            $orderAdjustmentObj = new FinanceOrderAdjustmentModel();
            $adjustment = $orderAdjustmentObj->where('sku', null)->limit(100)->select();
            $newOrder = [];
            if (count($adjustment)) {
                foreach ($adjustment as $a) {
                    if ($a) {
                        $statistic = new FinanceOrderStatisticsModel();
                        $sheinOrderStatistic = $statistic->where(['payment_id' => $a['payment_id']])->find();
                        if ($sheinOrderStatistic) {
                            $a['sku'] = $sheinOrderStatistic['platform_sku'];
                        } else {
                            $orderObj = new OrderModel();
                            $order = $orderObj->with(['details'])->where(['refNo' => $a['payment_id']])->find();
                            if ($order) {
                                $a['sku'] = $order['details'][0]['productSku'];
                            } else {
                                $a['sku'] = '';
                            }
                        }
                        $newOrder[] = $a->toArray();
                    }
                }
                $orderAdjustmentObj->saveAll($newOrder);
            }

            Db::commit();
            $output->writeln("success");
        } catch (\Exception $e) {
            Db::rollback();
            $output->writeln($e->getMessage());
        }
    }
}