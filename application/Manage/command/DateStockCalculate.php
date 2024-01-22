<?php
namespace app\Manage\command;

use app\Manage\model\ApiClient;
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
     * @throws \SoapFault
     */
    protected function execute(Input $input, Output $output)
    {
        Config::load(APP_PATH . 'storage.php');

        if (date('H') < 2) {
            return;
        }

        $dateStockReceiving = new DateStockReceivingModel();
        $list = $dateStockReceiving->where(['is_finished' => 0])->order('id asc')->limit(20)->select();
        foreach ($list as $item) {
            $dateStockConsume = new DateStockConsumeModel();
            $consumeWhere = [
                'product_sku'   =>  $item['product_sku'],
                'warehouse_id'  =>  $item['warehouse_id'],
                'date'          =>  $item['date']
            ];
            $consume = $dateStockConsume->where($consumeWhere)->find();

            // 当前海外仓真是库存
            if (in_array($item['warehouse_id'], [28, 29, 32, 34, 36])) {
                $arr = [
                    28  =>  'USATL06',
                    34  =>  'USLAX05',
                    29  =>  'USLAX08',
                    32  =>  'USLAX09',
                    36  =>  'USNJ06'
                ];
                $apiRes = ApiClient::LcWarehouseApi("getProductInventory", '{"pageSize":10,"page":1,"product_sku":"' . $item['product_sku'] . '","warehouse_code":"' . $arr[$item['warehouse_id']] . '"}');
                $stockNow = empty($apiRes['data']) ? 0 : intval($apiRes['data'][0]['sellable']);
            } elseif (in_array($item['warehouse_id'], [24, 26, 33, 37])) {
                $arr = [
                    24  =>  'CAP',
                    26  =>  'CAP',
                    33  =>  'MEM',
                    37  =>  'PAW'
                ];
                $requestParam = [
                    'pageNum'       =>  1,
                    'pageSize'      =>  10,
                    'goodsCode'     =>  $item['product_sku'],
                    'warehouseCode' =>  $arr[$item['warehouse_id']]
                ];
                $apiRes = ApiClient::LeWarehouseApi("https://app.lecangs.com/api/oms/inventoryOverview/apiPage", "POST", $requestParam);
                $stockNow = empty($apiRes['data']) ? 0 : intval($apiRes['data']['list'][0]['uesNum']);
            } else {
                $stockNow = 0;
            }
            if (empty($consume)) {
                $updateData = [
                    'storage_stock'         =>  $stockNow,
                    'stock'                 =>  $item['quantity_sum'],
                    'is_finished'           =>  1
                ];
            } else {
                $updateData = [
                    'date_stock_consume_id' =>  $consume['id'],
                    'stock'                 =>  $item['quantity_sum'] - $consume['quantity_sum'],
                    'storage_stock'         =>  $stockNow,
                    'is_finished'           =>  1
                ];
            }
            DateStockReceivingModel::update($updateData, ['id' => $item['id']]);
        }

        $output->writeln("success");
    }
}