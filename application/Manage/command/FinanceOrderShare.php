<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOrderAdditionalModel;
use app\Manage\model\FinanceOrderAdjustmentModel;
use app\Manage\model\FinanceOrderLiquidationModel;
use app\Manage\model\FinanceOrderRefundModel;
use app\Manage\model\FinanceOrderShareModel;
use app\Manage\model\FinanceOrderShippingServiceModel;
use app\Manage\model\FinanceOrderStatisticsModel;
use app\Manage\model\FinanceTableModel;
use app\Manage\model\OrderDetailModel;
use app\Manage\model\OrderModel;
use app\Manage\model\SkuModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\db\exception\BindParamException;
use think\exception\PDOException;

class FinanceOrderShare extends Command
{
    protected function configure()
    {
        $this->setName('FinanceOrderShare')->setDescription('Here is the FinanceOrderShare');
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
            $financeOrderShareObj = new FinanceOrderShareModel();
            // 退款分摊
            $financeOrderRefundObj = new FinanceOrderRefundModel();
            $list = $financeOrderRefundObj->where('share_code', null)->order('id asc')->limit(100)->select();
            if (count($list)) {
                // 判断是否出库
                $orderStatisticObj = new FinanceOrderStatisticsModel();
                foreach ($list as $item) {
                    $fulfillment = $item['fulfillment'] == 'Amazon' ? 'FBA' : 'FBM';
                    $shareCode = self::generateRandomCode(16);
                    $statistic = $orderStatisticObj->where(['payment_id' => $item['payment_id'], 'platform_sku' => $item['sku']])->select();
                    if (count($statistic)) {
                        // 有实际出库信息，且出库sku对应
                        $shareItem = [];
                        $amountSum = 0;
                        foreach ($statistic as $value) {
                            $amount = $item['product_sales'] + $item['shipping_credits'] + $item['gift_wrap_credits'] + $item['regulatory_fee'] + $item['promotional_rebates'];
                            $restAmount = $amount - $amountSum;
                            $total = count($statistic) == 1 ? $restAmount : min($value['sale_amount'], $restAmount);
                            $percent = $amount == 0 ? 1 : round($total / $amount, 5);
                            $shareItem[] = [
                                'report_id'     =>  $item['report_id'],
                                'table_id'      =>  $item['table_id'],
                                'fulfillment'   =>  $fulfillment,
                                'cost_type'     =>  'REFUND',
                                'share_code'    =>  $shareCode,
                                'payment'       =>  $item['payment_id'],
                                'seller_sku'    =>  $item['sku'],
                                'warehouse_sku' =>  $value['warehouse_sku'],
                                'amount'        =>  $amount,
                                'percent'       =>  min($percent, 1),
                                'total'         =>  $total
                            ];
                            $amountSum += $total;
                        }
                        if ($financeOrderShareObj->insertAll($shareItem)) {
                            $financeOrderRefundObj->update(['share_code' => $shareCode], ['id' => $item['id']]);
                        }
                    } else {
                        $statistic = $orderStatisticObj->where(['payment_id' => $item['payment_id']])->select();
                        if (count($statistic)) {
                            // 有实际出库，但seller sku不对应
                            $shareItem = [];
                            $amountSum = 0;
                            foreach ($statistic as $value) {
                                $amount = $item['product_sales'] + $item['shipping_credits'] + $item['gift_wrap_credits'] + $item['regulatory_fee'] + $item['promotional_rebates'];
                                $restAmount = $amount - $amountSum;
                                $total = count($statistic) == 1 ? $restAmount : min($value['sale_amount'], $restAmount);
                                $percent = $amount == 0 ? 1 : round($total / $amount, 5);
                                $shareItem[] = [
                                    'report_id'     =>  $item['report_id'],
                                    'table_id'      =>  $item['table_id'],
                                    'fulfillment'   =>  $fulfillment,
                                    'cost_type'     =>  'REFUND',
                                    'share_code'    =>  $shareCode,
                                    'payment'       =>  $item['payment_id'],
                                    'seller_sku'    =>  $item['sku'],
                                    'warehouse_sku' =>  $value['warehouse_sku'],
                                    'amount'        =>  $amount,
                                    'percent'       =>  min($percent, 1),
                                    'total'         =>  $total
                                ];
                                $amountSum += $total;
                            }
                            if ($financeOrderShareObj->insertAll($shareItem)) {
                                $financeOrderRefundObj->update(['share_code' => $shareCode], ['id' => $item['id']]);
                            }
                        } else {
                            // 无出库记录
                            $tableObj = new FinanceTableModel();
                            $userAccount = $tableObj->where(['id' => $item['table_id']])->find();
                            if (empty($userAccount) || empty($userAccount['userAccount'])) {
                                continue; // 无店铺号跳过
                            }
                            $relationObj = new SkuModel();
                            $skuRelation = $relationObj->with('warehouseSku')->where(['product_sku' => $item['sku'], 'user_account' => $userAccount['userAccount']])->find();
                            if (!empty($skuRelation) && count($skuRelation)) {
                                $shareItem = [];
                                foreach ($skuRelation['warehouse_sku'] as $value) {
                                    $amount = $item['product_sales'] + $item['shipping_credits'] + $item['gift_wrap_credits'] + $item['regulatory_fee'] + $item['promotional_rebates'];
                                    $shareItem[] = [
                                        'report_id'     =>  $item['report_id'],
                                        'table_id'      =>  $item['table_id'],
                                        'fulfillment'   =>  $fulfillment,
                                        'cost_type'     =>  'REFUND',
                                        'share_code'    =>  $shareCode,
                                        'payment'       =>  $item['payment_id'],
                                        'seller_sku'    =>  $item['sku'],
                                        'warehouse_sku' =>  $value['pcr_product_sku'],
                                        'amount'        =>  $amount,
                                        'percent'       =>  $value['pcr_percent'] / 100,
                                        'total'         =>  $amount * $value['pcr_percent'] / 100
                                    ];
                                }
                                if ($financeOrderShareObj->insertAll($shareItem)) {
                                    $financeOrderRefundObj->update(['share_code' => $shareCode], ['id' => $item['id']]);
                                }
                            } else {
                                // 无sku映射先跳过 TODO
                                continue;

                            }
                        }
                    }
                }
            }

            // 退运费分摊
            $financeOrderShippingObj = new FinanceOrderShippingServiceModel();
            $shipping = $financeOrderShippingObj->where('share_code', null)->order('id asc')->limit(100)->select();
            if (count($shipping)) {
                $orderStatisticObj = new FinanceOrderStatisticsModel();
                foreach ($shipping as $item) {
                    $orderModel = new OrderModel();
                    $order = $orderModel->where(['saleOrderCode' => $item['payment_id']])->find();
                    $fulfillment = $order['fulfillmentType'] == 1 ? 'FBA' : 'FBM';
                    $shareCode = self::generateRandomCode(16);
                    $statistic = $orderStatisticObj->where(['payment_id' => $item['payment_id']])->select();
                    $shareItem = [];
                    if (count($statistic)) {
                        $amountSum = 0;
                        foreach ($statistic as $value) {
                            $amount = $item['total'];
                            $restAmount = $amount - $amountSum;
                            $total = count($statistic) == 1 ? $restAmount : min($value['sale_amount'], $restAmount);
                            $percent = $amount == 0 ? 1 : round($total / $amount, 5);
                            $shareItem[] = [
                                'report_id'     =>  $item['report_id'],
                                'table_id'      =>  $item['table_id'],
                                'fulfillment'   =>  $fulfillment,
                                'cost_type'     =>  'SHIPPING',
                                'share_code'    =>  $shareCode,
                                'payment'       =>  $item['payment_id'],
                                'seller_sku'    =>  $item['sku'],
                                'warehouse_sku' =>  $value['warehouse_sku'],
                                'amount'        =>  $amount,
                                'percent'       =>  min($percent, 1),
                                'total'         =>  $total
                            ];
                            $amountSum += $total;
                        }
                    } else {
                        // 无出库
                        $orderDetailObj = new OrderDetailModel();
                        $orderDetail = $orderDetailObj->where(['order_id' => $order['id']])->find();
                        if ($orderDetail) {
                            $amount = $item['total'];
                            $shareItem[] = [
                                'report_id'     => $item['report_id'],
                                'table_id'      => $item['table_id'],
                                'fulfillment'   => 'FBM',
                                'cost_type'     => 'SHIPPING',
                                'share_code'    => $shareCode,
                                'payment'       => $item['payment_id'],
                                'seller_sku'    => $item['sku'],
                                'warehouse_sku' => $orderDetail['warehouseSku'],
                                'amount'        => $amount,
                                'percent'       => 1,
                                'total'         => $amount
                            ];
                        }
                    }
                    if ($financeOrderShareObj->insertAll($shareItem)) {
                        $financeOrderShippingObj->update(['share_code' => $shareCode], ['id' => $item['id']]);
                    }
                }
            }

            // 调整分摊
            $financeOrderAdjustmentObj = new FinanceOrderAdjustmentModel();
            $adjustment = $financeOrderAdjustmentObj->where('share_code', null)->order('id asc')->limit(100)->select();
            if (count($adjustment)) {
                $orderModel = new OrderModel();
                $orderStatisticObj = new FinanceOrderStatisticsModel();
                foreach ($adjustment as $item) {
                    $order = $orderModel->where(['saleOrderCode' => $item['payment_id']])->find();
                    $fulfillment = $order['fulfillmentType'] == 1 ? 'FBA' : 'FBM';
                    $shareCode = self::generateRandomCode(16);
                    $statistic = $orderStatisticObj->where(['payment_id' => $item['payment_id']])->select();
                    if (count($statistic)) {
                        $shareItem = [];
                        $amountSum = 0;
                        foreach ($statistic as $value) {
                            $amount = $item['total'];
                            $restAmount = $amount - $amountSum;
                            $total = count($statistic) == 1 ? $restAmount : min($value['sale_amount'], $restAmount);
                            $percent = $amount == 0 ? 1 : round($total / $amount, 5);
                            $shareItem[] = [
                                'report_id'     =>  $item['report_id'],
                                'table_id'      =>  $item['table_id'],
                                'fulfillment'   =>  $fulfillment,
                                'cost_type'     =>  'ADJUSTMENT',
                                'share_code'    =>  $shareCode,
                                'payment'       =>  $item['payment_id'],
                                'seller_sku'    =>  $item['sku'],
                                'warehouse_sku' =>  $value['warehouse_sku'],
                                'amount'        =>  $amount,
                                'percent'       =>  min($percent, 1),
                                'total'         =>  $total
                            ];
                            $amountSum += $total;
                        }
                    } else {
                        // 无出库
                        $tableObj = new FinanceTableModel();
                        $userAccount = $tableObj->where(['id' => $item['table_id']])->find();
                        if (empty($userAccount) || empty($userAccount['userAccount'])) {
                            continue; // 无店铺号跳过
                        }
                        $relationObj = new SkuModel();
                        $skuRelation = $relationObj->with('warehouseSku')->where(['product_sku' => $item['sku'], 'user_account' => $userAccount['userAccount']])->find();
                        if (!empty($skuRelation) && count($skuRelation)) {
                            $shareItem = [];
                            foreach ($skuRelation['warehouse_sku'] as $value) {
                                $amount = $item['total'];
                                $shareItem[] = [
                                    'report_id'     =>  $item['report_id'],
                                    'table_id'      =>  $item['table_id'],
                                    'fulfillment'   =>  'FBM',
                                    'cost_type'     =>  'ADJUSTMENT',
                                    'share_code'    =>  $shareCode,
                                    'payment'       =>  $item['payment_id'],
                                    'seller_sku'    =>  $item['sku'],
                                    'warehouse_sku' =>  $value['pcr_product_sku'],
                                    'amount'        =>  $amount,
                                    'percent'       =>  $value['pcr_percent'] / 100,
                                    'total'         =>  $amount * $value['pcr_percent'] / 100
                                ];
                            }
                        } else {
                            // 无sku映射分摊到所有产品*********************************************************
                            $skuPercent = self::generateSkuPercent($item['table_id']);
                            $shareItem = [];
                            $percentSum = 0;
                            foreach ($skuPercent as $key => $value) {
                                $amount = $item['total'];
                                if ($key == count($skuPercent) - 1) {
                                    $value['percent'] = 1 - $percentSum;
                                }
                                $fulfillmentData = FinanceOrderShareModel::getWarehouseSkuFulfillment($value['warehouse_sku'], $item['report_id']);
                                foreach ($fulfillmentData as $k => $v) {
                                    $shareItem[] = [
                                        'report_id'     =>  $item['report_id'],
                                        'table_id'      =>  $item['table_id'],
                                        'fulfillment'   =>  $k == 1 ? 'FBA' : 'FBM',
                                        'cost_type'     =>  'ADJUSTMENT',
                                        'share_code'    =>  $shareCode,
                                        'payment'       =>  $item['payment_id'],
                                        'seller_sku'    =>  $item['sku'],
                                        'warehouse_sku' =>  $value['warehouse_sku'],
                                        'amount'        =>  $amount,
                                        'percent'       =>  $value['percent'] * $v,
                                        'total'         =>  $amount * $value['percent'] * $v
                                    ];
                                }
                                $percentSum += $value['percent'];
                            }
                        }
                    }
                    if ($financeOrderShareObj->insertAll($shareItem)) {
                        $financeOrderAdjustmentObj->update(['share_code' => $shareCode], ['id' => $item['id']]);
                    }
                }
            }

            // 清算分摊
            $financeOrderLiquidationObj = new FinanceOrderLiquidationModel();
            $liquidation = $financeOrderLiquidationObj->where('share_code', null)->order('id asc')->limit(100)->select();
            if (count($liquidation)) {
                $orderStatisticObj = new FinanceOrderStatisticsModel();
                foreach ($liquidation as $item) {
                    $shareCode = self::generateRandomCode(16);
                    $list = $orderStatisticObj->query('
SELECT
	a.id,
	a.total,
	c.user_account,
	a.sku,
	c.asin,
	c.seller_sku,
	e.pcr_product_sku 
FROM
	mu_finance_order_liquidation a
	LEFT JOIN mu_finance_table b ON a.table_id = b.id
	LEFT JOIN mu_ecang_listing c ON a.sku = c.asin 
	AND b.userAccount = c.user_account
	LEFT JOIN mu_ecang_sku d ON c.seller_sku = d.product_sku
	LEFT JOIN mu_ecang_sku_relation e ON d.id = e.sku_id 
WHERE
	a.total != 0 
	AND a.id = ' . $item['id'] . ' 
ORDER BY
	id ASC;
                    ');
                    if (count($list)) {
                        $shareItem = [];
                        $percentSum = 0;
                        foreach ($list as $key => $value) {
                            $amount = $item['total'];
                            if ($key == count($list) - 1) {
                                $percent = 1 - $percentSum;
                            } else {
                                $percent = round(1 / count($list), 4);
                            }
                            $fulfillmentData = FinanceOrderShareModel::getWarehouseSkuFulfillment($value['pcr_product_sku'], $item['report_id']);
                            if ($fulfillmentData) {
                                foreach ($fulfillmentData as $k => $v) {
                                    $shareItem[] = [
                                        'report_id'     =>  $item['report_id'],
                                        'table_id'      =>  $item['table_id'],
                                        'fulfillment'   =>  $k == 1 ? 'FBA' : 'FBM',
                                        'cost_type'     =>  'LIQUIDATION',
                                        'share_code'    =>  $shareCode,
                                        'payment'       =>  $item['order_id'],
                                        'seller_sku'    =>  $value['seller_sku'],
                                        'warehouse_sku' =>  $value['pcr_product_sku'],
                                        'amount'        =>  $amount,
                                        'percent'       =>  min($percent, 1) * $v,
                                        'total'         =>  round($amount * min($percent, 1), 6) * $v
                                    ];
                                }
                            } else {
                                $percent = round(1 / count($list), 4);
                                $shareItem[] = [
                                    'report_id'     =>  $item['report_id'],
                                    'table_id'      =>  $item['table_id'],
                                    'fulfillment'   =>  'FBM',
                                    'cost_type'     =>  'LIQUIDATION',
                                    'share_code'    =>  $shareCode,
                                    'payment'       =>  $item['order_id'],
                                    'seller_sku'    =>  $value['seller_sku'],
                                    'warehouse_sku' =>  $value['pcr_product_sku'],
                                    'amount'        =>  $amount,
                                    'percent'       =>  min($percent, 1),
                                    'total'         =>  round($amount * min($percent, 1), 6)
                                ];
                            }
                            $percentSum += $percent;
                        }
                        if ($financeOrderShareObj->insertAll($shareItem)) {
                            $financeOrderLiquidationObj->update(['share_code' => $shareCode], ['id' => $item['id']]);
                        }
                    } else {
                        // 无listing TODO
                        continue;

                    }
                }
            }

            // 促销
            $additionalModel = new FinanceOrderAdditionalModel();
            $promotion = $additionalModel->query('
SELECT
	report_id,
	user_account,
	warehouse_sku,
	SUM( promotion ) total
FROM
	mu_finance_order_additional 
WHERE
	promotion IS NOT NULL 
	AND share_code IS NULL
GROUP BY
	report_id,
	user_account,
	warehouse_sku
ORDER BY
	report_id,
	user_account,
	warehouse_sku
LIMIT 20;
            ');
            if (count($promotion)) {
                foreach ($promotion as $item) {
                    $shareCode = self::generateRandomCode(16);
                    $tableObj = new FinanceTableModel();
                    $table = $tableObj->where(['rid' => $item['report_id'], 'userAccount' => $item['user_account']])->find();
                    $skuPercent = self::generateSkuPercent($table['id']);
                    $shareItem = [];
                    $percentSum = 0;
                    foreach ($skuPercent as $key => $value) {
                        $amount = $item['total'];
                        if ($key == count($skuPercent) - 1) {
                            $value['percent'] = 1 - $percentSum;
                        }
                        $fulfillmentData = FinanceOrderShareModel::getWarehouseSkuFulfillment($value['warehouse_sku'], $item['report_id']);
                        if ($fulfillmentData) {
                            foreach ($fulfillmentData as $k => $v) {
                                $shareItem[] = [
                                    'report_id'     =>  $item['report_id'],
                                    'table_id'      =>  $table['id'],
                                    'fulfillment'   =>  $k == 1 ? 'FBA' : 'FBM',
                                    'cost_type'     =>  'PROMOTION',
                                    'share_code'    =>  $shareCode,
                                    'payment'       =>  '',
                                    'seller_sku'    =>  '',
                                    'warehouse_sku' =>  $value['warehouse_sku'],
                                    'amount'        =>  $amount,
                                    'percent'       =>  $value['percent'] * $v,
                                    'total'         =>  $amount * $value['percent'] * $v
                                ];
                            }
                        } else {
                            // 无listing TODO
                            continue;

                        }
                        $percentSum += $value['percent'];
                    }

                    if ($financeOrderShareObj->insertAll($shareItem)) {
                        $additionalModel->update(['share_code' => $shareCode], ['report_id' => $item['report_id'], 'user_account' => $item['user_account'], 'warehouse_sku' => $item['warehouse_sku']])->where('promotion', 'NOT NULL');
                    }
                }
            }



            Db::commit();
            $output->writeln("success");
        } catch (\Exception $e) {
            dump($e->getMessage());
            Db::rollback();
            $output->writeln($e->getMessage());
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

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    static protected function generateSkuPercent($table_id)
    {
        $model = new FinanceOrderShareModel();
        return $model->query('
SELECT
	warehouse_sku,
	SUM( qty ) qty,
	ROUND(SUM( qty ) / (
	SELECT
		SUM( qty ) 
	FROM
		( SELECT DISTINCT payment_id FROM mu_finance_order_sale WHERE table_id = ' . $table_id . ' ) a
		LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id
		LEFT JOIN mu_ecang_product c ON b.warehouse_sku = c.productSku 
	WHERE
		c.saleStatus = 2 
	) , 4) percent
FROM
	( SELECT DISTINCT payment_id FROM mu_finance_order_sale WHERE table_id = ' . $table_id . ' ) a
	LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id
	LEFT JOIN mu_ecang_product c ON b.warehouse_sku = c.productSku 
WHERE
	c.saleStatus = 2 
GROUP BY
	warehouse_sku;
        ');
    }
}