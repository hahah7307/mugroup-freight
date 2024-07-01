<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOrderStatisticsEditModel;
use app\Manage\model\FinanceOrderStatisticsModel;
use Exception;
use think\Cache;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class FinanceOrderStatisticsEdit extends Command
{
    protected function configure()
    {
        $this->setName('FinanceOrderStatisticsEdit')->setDescription('Here is the FinanceOrderStatisticsEdit');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 检测wayfair订单是否校验完毕
        $wayfairOrder = Cache::get('wayfairOrder');
        if (!empty($wayfairOrder)) {
            $output->writeln("Wayfair Unready");exit();
        }

        Db::startTrans();
        try {
            $editObj = new FinanceOrderStatisticsEditModel();
            $orderStatisticsObj = new FinanceOrderStatisticsModel();
            $list = $editObj->where(['is_finished' => 0])->limit(500)->order('id asc')->select();
            if (count($list)) {
                foreach ($list as $item) {
                    $editData = $editObj->find($item['id']);
                    if ($editData['is_finished'] == 1) {
                        continue;
                    }
                    $editList = $editObj->where(['payment' => $item['payment'], 'type' => $item['type'], 'report_id' => $item['report_id']])->select();
                    if ($item['type'] == "SELLING_FEE") {
                        if (count($editList) <= 1) {
                            $orderStatisticsObj->where(['id' => $item['order_statistics_id']])->setField('selling_fee', -$item['payment_selling_fees']);
                            $editObj->where(['id' => $item['id']])->setField('is_finished', 1);
                        } else {
                            $selling_sum = 0;
                            foreach ($editList as $key => $value) {
                                $sale_amount = array_sum(array_column($editList->toArray(), 'sale_amount'));
                                if ($key + 1 == count($editList)) {
                                    $selling_fee = -$value['payment_selling_fees'] - $selling_sum;
                                } else {
                                    $selling_fee = round($value['sale_amount'] / $sale_amount * $value['payment_selling_fees'] * -1, 2);
                                    $selling_sum += $selling_fee;
                                }
                                $orderStatisticsObj->where(['id' => $value['order_statistics_id']])->setField('selling_fee', $selling_fee);
                                $editObj->where(['id' => $value['id']])->setField('is_finished', 1);
                            }
                        }
                    } elseif ($item['type'] == "FBA_FEE") {
                        if (count($editList) <= 1) {
                            $orderStatisticsObj->where(['id' => $item['order_statistics_id']])->setField('fba_fee', -$item['payment_fba_fees']);
                            $editObj->where(['id' => $item['id']])->setField('is_finished', 1);
                        } else {
                            $fba_sum = 0;
                            foreach ($editList as $key => $value) {
                                $sale_amount = array_sum(array_column($editList->toArray(), 'sale_amount'));
                                if ($key + 1 == count($editList)) {
                                    $fba_fee = -$value['payment_fba_fees'] - $fba_sum;
                                } else {
                                    $fba_fee = round($value['sale_amount'] / $sale_amount * $value['payment_fba_fees'] * -1, 2);
                                    $fba_sum += $fba_fee;
                                }
                                $orderStatisticsObj->where(['id' => $value['order_statistics_id']])->setField('fba_fee', $fba_fee);
                                $editObj->where(['id' => $value['id']])->setField('is_finished', 1);
                            }
                        }
                    } else {
                        continue;
                    }
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