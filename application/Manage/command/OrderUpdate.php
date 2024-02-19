<?php
namespace app\Manage\command;

use app\Manage\model\OrderModel;
use app\Manage\model\OrderUpdateModel;
use SoapFault;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;

class OrderUpdate extends Command
{
    protected function configure()
    {
        $this->setName('OrderUpdate')->setDescription('Here is the OrderUpdate');
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception|SoapFault
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        // 订单查询当前页数
        $data = OrderUpdateModel::find()->toArray();

        $orderObj = new OrderModel();
        $orders = $orderObj
            ->where(['status' => ['neq', 4]])
            ->order('id asc')
            ->limit($data['offset'], $data['page_num'])
            ->select();
        foreach ($orders as $item) {
            $orderNew = OrderModel::saleOrderCodes2Order($item['saleOrderCode']);
            OrderModel::orderUpdate($orderNew[0]);
        }
        $data['offset'] = count($orders) < $data['page_num'] ? 0 : $data['offset'] + count($orders);
        OrderUpdateModel::update($data);

        $output->writeln("success");
    }
}