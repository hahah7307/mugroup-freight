<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOrderAdjustmentModel;
use app\Manage\model\FinanceOrderRefundModel;
use app\Manage\model\FinanceOrderShareModel;
use app\Manage\model\FinanceOrderShippingServiceModel;
use app\Manage\model\FinanceOrderStatisticsModel;
use app\Manage\model\FinanceTableModel;
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
                        if ($financeOrderShareObj->insertAll($shareItem)) {
                            $financeOrderShippingObj->update(['share_code' => $shareCode], ['id' => $item['id']]);
                        }
                    } else {
                        // 无出库 TODO
                        continue;

                    }
                }
            }

            // 调整分摊
            $financeOrderAdjustmentObj = new FinanceOrderAdjustmentModel();
            $adjustment = $financeOrderAdjustmentObj->where('share_code', null)->order('id asc')->limit(100)->select();
            if (count($adjustment)) {
                $orderStatisticObj = new FinanceOrderStatisticsModel();
                foreach ($adjustment as $item) {
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
                            foreach ($skuPercent as $value) {
                                $amount = $item['total'];
                                $shareItem[] = [
                                    'report_id'     =>  $item['report_id'],
                                    'table_id'      =>  $item['table_id'],
                                    'cost_type'     =>  'ADJUSTMENT',
                                    'share_code'    =>  $shareCode,
                                    'payment'       =>  $item['payment_id'],
                                    'seller_sku'    =>  $item['sku'],
                                    'warehouse_sku' =>  $value['warehouse_sku'],
                                    'amount'        =>  $amount,
                                    'percent'       =>  $value['percent'],
                                    'total'         =>  $amount * $value['percent']
                                ];
                            }
                        }
                    }
                    if ($financeOrderShareObj->insertAll($shareItem)) {
                        $financeOrderAdjustmentObj->update(['share_code' => $shareCode], ['id' => $item['id']]);
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