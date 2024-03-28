<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOrderOutboundModel;
use app\Manage\model\FinanceOrderSaleModel;
use app\Manage\model\FinanceStoreModel;
use app\Manage\model\OrderModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class FinanceWayfairProduct extends Command
{
    protected function configure()
    {
        $this->setName('FinanceWayfairProduct')->setDescription('Here is the FinanceWayfairProduct');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        Db::startTrans();
        try {
            $financeOrderSaleObj = new FinanceOrderSaleModel();
            $list = $financeOrderSaleObj->where('sku', null)->order('id asc')->limit(Config::get('finance_notify_num'))->select();
            if (count($list)) {
                $orderObj = new OrderModel();
                foreach ($list as $item) {
                    // wayfair订单一拆到底，按每一个sku发货所以数量可以恒定为1
                    $orderDetails = $orderObj->with('details')->where(['refNo|saleOrderCode' => $item['payment_id'], 'status' => 0])->find();
                    if (count($orderDetails['details'])) {
                        foreach ($orderDetails['details'] as $detailItem) {
                            if ($financeOrderSaleObj->update(['sku' => $detailItem['productSku'], 'quantity' => 1], ['id' => $item['id']])) {
                                break;
                            }
                        }
                    } else {
                        // 未拆单
                        $orderDetails = $orderObj->with('details')->where(['refNo|saleOrderCode' => $item['payment_id'], 'status' => 4])->find();
                        if (count($orderDetails['details'])) {
                            foreach ($orderDetails['details'] as $detailItem) {
                                if ($financeOrderSaleObj->update(['sku' => $detailItem['productSku'], 'quantity' => 1], ['id' => $item['id']])) {
                                    break;
                                }
                            }
                        } else {
                            $financeOrderSaleObj->update(['sku' => ''], ['id' => $item['id']]);
                        }
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