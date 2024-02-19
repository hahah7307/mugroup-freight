<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOrderModel;
use app\Manage\model\FinanceOrderOutboundModel;
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
            $financeObj = new FinanceOrderModel();
            $list = $financeObj->where(['is_notify' => 0])->order('id asc')->limit(Config::get('finance_notify_num'))->select();
            $newData = [];
            foreach ($list as $item) {
                $finance = OrderModel::all(['refNo' => $item['payment_id']]);
                $outbound_id = [];
                foreach ($finance as $outbound) {
                    $orderDetailObj = new OrderDetailModel();
                    $orderDetail = $orderDetailObj->with('product')->where(['order_id' => $outbound['id']])->find()->toArray();
                    $outbound_id[] = $outbound['id'];
                    $newData[] = [
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
                    'is_notify'     =>  1,
                    'outbound_id'   =>  implode(',', $outbound_id)
                ];
                if (!$financeObj->update($notifyData)) {
                    throw new Exception("原始订单与出库数据关联失败！");
                }
                unset($outbound_id);
            }
            $financeOrderOutboundObj = new FinanceOrderOutboundModel();
            if (!$financeOrderOutboundObj->saveAll($newData)) {
                throw new Exception("批量新增失败！");
            }
            Db::commit();
            file_put_contents( APP_PATH . '/../runtime/log/FinanceNotify-' . date('Y-m-d') . '.log', PHP_EOL . "[" . date('Y-m-d H:i:s') . "] : " . var_export(json_encode($newData),TRUE), FILE_APPEND);
            unset($newData);
            $output->writeln("success");
        } catch (\Exception $e) {
            Db::rollback();
            $output->writeln($e->getMessage());
        }
    }
}