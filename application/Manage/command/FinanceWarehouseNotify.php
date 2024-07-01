<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOrderOutboundModel;
use app\Manage\model\FinanceStoreModel;
use app\Manage\model\FinanceWarehouseModel;
use app\Manage\model\ProductModel;
use Exception;
use think\Cache;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;

class FinanceWarehouseNotify extends Command
{
    protected function configure()
    {
        $this->setName('FinanceWarehouseNotify')->setDescription('Here is the FinanceWarehouseNotify');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        // 检测wayfair订单是否校验完毕
        $wayfairOrder = Cache::get('wayfairOrder');
        if (!empty($wayfairOrder)) {
            $output->writeln("Wayfair Unready");exit();
        }

        $financeOutboundObj = new FinanceOrderOutboundModel();
        $financeWarehouseObj = new FinanceWarehouseModel();
        $outbound = $financeOutboundObj->where(['is_notify' => 0])->select();
        if (count($outbound) > 0) {
            $output->writeln("OutboundNotify Unready");exit();
        } else {
            Db::startTrans();
            try {
                $list = $financeWarehouseObj->where(['is_sale' => 0])->where('main_sku', null)->limit(Config::get('finance_notify_num'))->order('id asc')->select();
                if (count($list)) {
                    foreach ($list as $item) {
                        $sku = $item['sku'];
                        if (self::sku_identify($sku)) {
                            // 在售主件直接用自己
                            $financeWarehouseObj->update(['main_sku' => $sku], ['id' => $item['id']]);
                        } else {
                            if (!strpos($sku, '-')) {
                                // 未在售主件也先用自己
                                $financeWarehouseObj->update(['main_sku' => $sku], ['id' => $item['id']]);
                            } else {
                                $mainSku = explode('-', $sku)[0];
                                $productObj = new ProductModel();
                                $product = $productObj->where(['productSku' => ['like', $mainSku . '%'], 'saleStatus' => 2])->order('productSku asc')->find();
                                if (empty($product)) {
                                    // 未在售配件也无未在售主件
                                    $financeWarehouseObj->update(['main_sku' => $mainSku], ['id' => $item['id']]);
                                } else {
                                    // 未在售配件有在售主件直接用该主件
                                    $financeWarehouseObj->update(['main_sku' => $product['productSku']], ['id' => $item['id']]);
                                }
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

    /**
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */
    static protected function sku_identify($sku): bool
    {
        $productObj = new ProductModel();
        $productObj = $productObj->where(['productSku' => $sku, 'saleStatus' => 2])->select();
        if (count($productObj) > 0) {
            return true;
        } else {
            return false;
        }
    }
}