<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
use app\Manage\model\OrderCalculateModel;
use app\Manage\model\OrderModel;
use app\Manage\model\OrderPageModel;
use SoapFault;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;

class OrderCalculate extends Command
{
    protected function configure()
    {
        $this->setName('OrderCalculate')->setDescription('Here is the OrderCalculate');
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception|SoapFault
     */
    protected function execute(Input $input, Output $output)
    {
        // 订单查询当前页数
        $data = OrderCalculateModel::find()->toArray();

        $orderObj = new OrderModel();
        $list = $orderObj->order('id asc')->limit($data['offset'] . ", " . $data['page_num'])->select();
        foreach ($list as $item) {
            OrderModel::orderId2DeliverParams($item['id']);
        }
        if (count($list) > 0) {
            OrderCalculateModel::update(['id' => $data['id'], 'offset' => $data['offset'] + $data['page_num']]);
        }

        $output->writeln("success");
    }
}