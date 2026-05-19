<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOrderSaleModel;
use app\Manage\model\FinanceOrderStatisticsModel;
use app\Manage\model\FinanceReportModel;
use app\Manage\model\FinanceTableModel;
use app\Manage\model\FinanceWayfairCoreModel;
use app\Manage\model\OrderDetailModel;
use Exception;
use think\Cache;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class FinanceOutboundAccounting extends Command
{
    protected function configure()
    {
        $this->setName('FinanceOutboundAccounting')->setDescription('Here is the FinanceOutboundAccounting');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        Db::startTrans();
        try {
            $cache = Cache::get('outboundAccounting');
            $list = array_slice($cache, 0, 2000);
            if (count($list)) {
                $orderStatisticObj = new FinanceOrderStatisticsModel();
                $updateData = [];
                foreach ($list as $item) {
                    if ($item) {
                        $orderStatistic = $orderStatisticObj->where(['saleOrderCode' => $item['saleOrderCode']])->select();
                        if (!empty($orderStatistic)) {
                            foreach ($orderStatistic as $value) {
                                $monthList = array_filter(explode(',', $value['accounting_month']));
                                if (!in_array($item['month'], $monthList)) {
                                    $monthList[] = $item['month'];
                                    $monthField = implode(',', $monthList);
                                    $updateData[] = [
                                        'id'                =>  $value['id'],
                                        'is_finished'       =>  1,
                                        'accounting_month'  =>  $monthField
                                    ];
                                }
                            }
                        }
                    }
                }
                if ($orderStatisticObj->saveAll($updateData)) {
                    Cache::set('outboundAccounting', array_slice($cache, 2000), 48 * 60 * 60);
                    Db::commit();
                    $output->writeln("success");
                } else {
                    throw new Exception('核算失败');
                }
            }
        } catch (\Exception $e) {
            Db::rollback();
            $output->writeln($e->getMessage());
        }
    }
}