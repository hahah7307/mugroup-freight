<?php
namespace app\Manage\command;

use app\Manage\model\DateStockConsumeModel;
use app\Manage\model\DateStockReceivingModel;
use app\Manage\model\DateStockUpdateModel;
use app\Manage\model\ReceivingItemModel;
use app\Manage\model\StockOpeningModel;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;

class DateStockCalculate extends Command
{
    protected function configure()
    {
        $this->setName('DateStockCalculate')->setDescription('Here is the DateStockCalculate');
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        Config::load(APP_PATH . 'storage.php');

        if (date('H') < 2) {
            return;
        }

        $dateStockReceiving = new DateStockReceivingModel();
        $list = $dateStockReceiving->where(['is_finished' => 0])->order('id asc')->limit(100)->select();
        foreach ($list as $item) {
            $dateStockConsume = new DateStockConsumeModel();
            $consumeWhere = [
                'product_sku'   =>  $item['product_sku'],
                'warehouse_id'  =>  $item['warehouse_id'],
                'date'          =>  $item['date']
            ];
            $consume = $dateStockConsume->where($consumeWhere)->find();

            $stockOpeningObj = new StockOpeningModel();
            $stockWhere = [
                'product_sku'   =>  $item['product_sku'],
                'warehouse_id'  =>  $item['warehouse_id'],
                'created_date'  =>  Config::get('stock_date')
            ];
            $stockOpening = $stockOpeningObj->where($stockWhere)->find();
            $stock = empty($stockOpening) ? 0 : intval($stockOpening['stock']);
            if (empty($consume) && $stock == 0) {
                $updateData = [
                    'is_finished'           =>  1
                ];
            } else {
                $updateData = [
                    'date_stock_consume_id' =>  $consume['id'],
                    'stock'                 =>  $item['quantity_sum'] - $consume['quantity_sum'] + $stock,
                    'is_finished'           =>  1
                ];
            }
            DateStockReceivingModel::update($updateData, ['id' => $item['id']]);
        }

        $output->writeln("success");
    }
}