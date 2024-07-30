<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOrderAdjustmentModel;
use app\Manage\model\FinanceOrderOutboundModel;
use app\Manage\model\FinanceOrderRefundModel;
use app\Manage\model\FinanceOrderSaleModel;
use app\Manage\model\FinanceOrderShareModel;
use app\Manage\model\FinanceStoreModel;
use app\Manage\model\FinanceWarehouseFbmModel;
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

        // 检测shein订单是否校验完毕
        $orderSaleObj = new FinanceOrderSaleModel();
        $sheinOrder = $orderSaleObj->where('sku', null)->where(['payment_id' => [['like', 'GSUN%']]])->select();
        $financeOrderRefundObj = new FinanceOrderRefundModel();
        $sheinRefund = $financeOrderRefundObj->where('sku', null)->where(['payment_id' => [['like', 'GSUN%']]])->select();
        $financeOrderAdjustmentObj = new FinanceOrderAdjustmentModel();
        $sheinAdjustment = $financeOrderAdjustmentObj->where('sku', null)->where(['payment_id' => [['like', 'GSUN%']]])->select();
        if (count($sheinOrder) + count($sheinRefund) + count($sheinAdjustment) > 0) {
            $output->writeln("Shein Unready");exit();
        }

        $financeOutboundObj = new FinanceOrderOutboundModel();
        $financeWarehouseFbmObj = new FinanceWarehouseFbmModel();
        $outbound = $financeOutboundObj->where(['is_notify' => 0])->select();
//        if (count($outbound) > 0) {
//            $output->writeln("OutboundNotify Unready");exit();
//        } else {
            Db::startTrans();
            try {
                $list = $financeWarehouseFbmObj->where('share_code', null)->limit(50)->order('id asc')->select();
                if (count($list)) {
                    $updateData = [];
                    $shareData = [];
                    foreach ($list as $item) {
                        $shareCode = self::generateRandomCode(16);
                        $sku = $item['sku'];
                        if (self::sku_identify($sku)) {
                            // 在售主件直接用自己
                            $mainSku = empty($item['main_sku']) ? $sku : $item['main_sku'];
                        } else {
                            $mainSku = explode('-', $sku)[0];
                            $productObj = new ProductModel();
                            $product = $productObj->where(['productSku' => ['like', $mainSku . '%'], 'saleStatus' => 2])->order('productSku asc')->find();
                            if (!empty($product)) {
                                // 未在售配件有在售主件直接用该主件
                                $mainSku = $product['productSku'];
                            }

                            $mainSku = empty($item['main_sku']) ? $mainSku : $item['main_sku'];
                        }

                        //
                        $saleList = $financeWarehouseFbmObj->query('
SELECT
	platform,
	warehouse_sku,
	SUM( qty ) sale_qty
FROM
	mu_finance_order_outbound 
WHERE
	warehouse_sku = "' . $mainSku . '" 
	AND report_id = ' . $item['report_id'] . ' 
GROUP BY
	platform,
	warehouse_sku;
                        ');

                        $saleCount = array_sum(array_column($saleList, 'sale_qty'));
                        $sum = 0;
                        if ($saleList) {
                            $isMainPlatform = false;
                            foreach ($saleList as $v) {
                                if ($v['platform'] == 'wayfairnew') {
                                    $v['platform'] = 'wayfair';
                                }
                                if ($v['platform'] == $item['main_platform']) {
                                    $isMainPlatform = true;
                                }
                            }
                            if (!$isMainPlatform) {
                                $saleList[] = [
                                    'platform'  =>  $item['main_platform'],
                                    'warehouse' =>  $mainSku,
                                    'sale_qty'  =>  0
                                ];
                            }
                            foreach ($saleList as $key => $platformData) {
                                if ($platformData['platform'] == 'wayfairnew') {
                                    $platformData['platform'] = 'wayfair';
                                }
                                $userAccountList = $financeWarehouseFbmObj->query('
SELECT
    DISTINCT
	a.pcr_product_sku,
	b.user_account,
	c.platform 
FROM
	mu_ecang_sku_relation a
	LEFT JOIN mu_ecang_sku b ON a.sku_id = b.id
	LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = 6 ) c ON b.user_account = c.userAccount 
WHERE
	a.pcr_product_sku = "' . $mainSku . '" 
	AND c.platform = "' . $platformData['platform'] . '";
                            ');
                                if ($key + 1 != count($saleList)) {
                                    if ($mainSku == $item['sku']) {
                                        $restQty = $item['quantity'];
                                    } else {
                                        $warehouseFbmData = $financeWarehouseFbmObj->where(['sku' => $mainSku, 'report_id' => $item['report_id']])->find();
                                        $restQty = empty($warehouseFbmData) ? $item['quantity'] : $warehouseFbmData['quantity'];
                                    }
                                    if ($item['main_platform'] == $platformData['platform']) {
                                        $platformTotal = round(($platformData['sale_qty'] + $restQty) / ($saleCount + $restQty) * $item['total'], 6);
                                    } else {
                                        $platformTotal = round(($platformData['sale_qty']) / ($saleCount + $restQty) * $item['total'], 6);
                                    }
                                    $sum += $platformTotal;
                                } else {
                                    $platformTotal = $item['total'] - $sum;
                                }

                                $userAccountSum = 0;
                                if ($userAccountList) {
                                    foreach ($userAccountList as $k => $userAccount) {
                                        if ($k + 1 != count($userAccountList)) {
                                            $total = round($platformTotal / count($userAccountList), 6);
                                            $userAccountSum += $total;
                                        } else {
                                            $total = $platformTotal - $userAccountSum;
                                        }
                                        $shareData[] = [
                                            'report_id'     =>  $item['report_id'],
                                            'user_account'  =>  $userAccount['user_account'],
                                            'fulfillment'   =>  'FBM',
                                            'cost_type'     =>  'WAREHOUSE_FBM',
                                            'share_code'    =>  $shareCode,
                                            'warehouse_sku' =>  $mainSku,
                                            'amount'        =>  $item['total'],
                                            'total'         =>  $total
                                        ];
                                    }
                                } else {
                                    $shareData[] = [
                                        'report_id'     =>  $item['report_id'],
                                        'user_account'  =>  '',
                                        'fulfillment'   =>  'FBM',
                                        'cost_type'     =>  'WAREHOUSE_FBM',
                                        'share_code'    =>  $shareCode,
                                        'warehouse_sku' =>  $mainSku,
                                        'amount'        =>  $item['total'],
                                        'total'         =>  $platformTotal
                                    ];
                                }
                            }
                        } else {
                            $userAccountList = $financeWarehouseFbmObj->query('
SELECT
    DISTINCT
	a.pcr_product_sku,
	b.user_account,
	c.platform 
FROM
	mu_ecang_sku_relation a
	LEFT JOIN mu_ecang_sku b ON a.sku_id = b.id
	LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = 6 ) c ON b.user_account = c.userAccount 
WHERE
	a.pcr_product_sku = "' . $mainSku . '" 
	AND c.platform = "' . $item['main_platform'] . '";
                            ');

                            $userAccountSum = 0;
                            if ($userAccountList) {
                                foreach ($userAccountList as $k => $userAccount) {
                                    if ($k + 1 != count($userAccountList)) {
                                        $total = round($item['total'] / count($userAccountList), 6);
                                        $userAccountSum += $total;
                                    } else {
                                        $total = $item['total'] - $userAccountSum;
                                    }
                                    $shareData[] = [
                                        'report_id'     =>  $item['report_id'],
                                        'user_account'  =>  $userAccount['user_account'],
                                        'fulfillment'   =>  'FBM',
                                        'cost_type'     =>  'WAREHOUSE_FBM',
                                        'share_code'    =>  $shareCode,
                                        'warehouse_sku' =>  $mainSku,
                                        'amount'        =>  $item['total'],
                                        'total'         =>  $total
                                    ];
                                }
                            } else {
                                $shareData[] = [
                                    'report_id'     =>  $item['report_id'],
                                    'user_account'  =>  '',
                                    'fulfillment'   =>  'FBM',
                                    'cost_type'     =>  'WAREHOUSE_FBM',
                                    'share_code'    =>  $shareCode,
                                    'warehouse_sku' =>  $mainSku,
                                    'amount'        =>  $item['total'],
                                    'total'         =>  $item['total']
                                ];
                            }
                        }

                        $updateData[] = [
                            'id'            =>  $item['id'],
                            'main_sku'      =>  $mainSku,
                            'share_code'    =>  $shareCode
                        ];
                    }

                    if ($financeWarehouseFbmObj->saveAll($updateData)) {
                        $orderShareObj = new FinanceOrderShareModel();
                        $orderShareObj->insertAll($shareData);
                    } else {
                        throw new \think\Exception("更新失败！");
                    }
                }

                Db::commit();
                $output->writeln("success");
            } catch (\Exception $e) {
                Db::rollback();
                $output->writeln($e->getMessage());
            }
//        }
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

    static protected function generateRandomCode($length): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        $financeOrderShareObj = new FinanceOrderShareModel();
        $codeList = $financeOrderShareObj->column('share_code');
        if (in_array($randomString, $codeList)) {
            return self::generateRandomCode($length);
        } else {
            return $randomString;
        }
    }
}