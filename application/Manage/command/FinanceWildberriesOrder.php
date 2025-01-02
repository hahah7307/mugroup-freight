<?php
namespace app\Manage\command;

use app\Manage\model\FinanceReportModel;
use app\Manage\model\FinanceWildberriesFeeModel;
use app\Manage\model\FinanceWildberriesOrderModel;
use app\Manage\model\FinanceWildberriesOrderNotifyModel;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class FinanceWildberriesOrder extends Command
{
    protected function configure()
    {
        $this->setName('FinanceWildberriesOrder')->setDescription('Here is the FinanceWildberriesOrder');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        Db::startTrans();
        try {
            $wildberriesOrderNotifyObj = new FinanceWildberriesOrderNotifyModel();
            $list = $wildberriesOrderNotifyObj->where(['fee_id' => null, 'is_notify' => 0])->limit(200)->select();
            $wildberriesFeeObj = new FinanceWildberriesFeeModel();
            $wildberriesOrderObj = new FinanceWildberriesOrderModel();
            foreach ($list as $item) {
                $report = FinanceReportModel::get($item['report_id']);
                $monthFee = $wildberriesFeeObj->where(['month' => date('Ym', strtotime($report['month'] . '-01'))])->select();
                if (empty($monthFee)) {
                    continue;
                }
                $order = $wildberriesOrderObj->where(['fbs_no' => $item['payment_id']])->find();
                if ($order) {
                    $fee = $wildberriesFeeObj->where(['order_no' => $order['order_no']])->find();
                    if ($fee) {
                        $wildberriesOrderNotifyObj->where(['id' => $item['id']])->setField('fee_id', $fee['id']);
                        $wildberriesFeeObj->where(['id' => $fee['id']])->setField('report_id', $item['report_id']);
                        $wildberriesFeeObj->where(['id' => $fee['id']])->setField('calculate_month', date('Ym', strtotime($report['month'] . '-01')));
                    }
                }
                $wildberriesOrderNotifyObj->where(['id' => $item['id']])->setField('is_notify', 1);
            }

            Db::commit();
            $output->writeln("success");
        } catch (\Exception $e) {
            Db::rollback();
            $output->writeln($e->getMessage());
        }
    }
}