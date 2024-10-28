<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOrderAdjustmentModel;
use app\Manage\model\FinanceOrderRefundModel;
use app\Manage\model\FinanceOrderSaleModel;
use app\Manage\model\FinanceOrderStatisticsModel;
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
                            $item['sku'] = '';
                        }
                        $newOrder[] = $item->toArray();
                    }
                }
                $orderSaleObj->saveAll($newOrder);
            }

            $orderRefundObj = new FinanceOrderRefundModel();
            $refund = $orderRefundObj->where('sku', null)->where(['payment_id' => [['like', 'GSUN%']]])->limit(100)->select();
            $newOrder = [];
            if (count($refund)) {
                foreach ($refund as $r) {
                    if ($r) {
                        $statistic = new FinanceOrderStatisticsModel();
                        $sheinOrderStatistic = $statistic->where(['payment_id' => $r['payment_id']])->find();
                        if ($sheinOrderStatistic) {
                            $r['sku'] = $sheinOrderStatistic['platform_sku'];
                        } else {
                            $r['sku'] = '';
                        }
                        $newOrder[] = $r->toArray();
                    }
                }
                $orderRefundObj->saveAll($newOrder);
            }

            $orderTemuRefundObj = new FinanceOrderRefundModel();
            $temuRefund = $orderTemuRefundObj->where('sku', null)->where(['payment_id' => [['like', 'PO-%']]])->limit(100)->select();
            $newOrderTemu = [];
            if (count($temuRefund)) {
                foreach ($temuRefund as $t) {
                    if ($t) {
                        $statistic = new FinanceOrderStatisticsModel();
                        $sheinOrderStatistic = $statistic->where(['payment_id' => $t['payment_id']])->find();
                        if ($sheinOrderStatistic) {
                            $t['sku'] = $sheinOrderStatistic['platform_sku'];
                        } else {
                            $t['sku'] = '';
                        }
                        $newOrderTemu[] = $t->toArray();
                    }
                }
                $orderTemuRefundObj->saveAll($newOrderTemu);
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