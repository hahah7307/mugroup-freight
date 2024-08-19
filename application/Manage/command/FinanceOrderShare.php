<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOrderAdditionalModel;
use app\Manage\model\FinanceOrderAdjustmentModel;
use app\Manage\model\FinanceOrderAdjustmentWfsModel;
use app\Manage\model\FinanceOrderLiquidationModel;
use app\Manage\model\FinanceOrderShareModel;
use app\Manage\model\FinanceOrderShareValidate;
use app\Manage\model\FinanceOrderShippingServiceModel;
use app\Manage\model\FinanceOrderStatisticsModel;
use app\Manage\model\FinanceReportModel;
use app\Manage\model\FinanceTableModel;
use app\Manage\model\OrderDetailModel;
use app\Manage\model\OrderModel;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\db\exception\BindParamException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
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
        if (!FinanceOrderShareValidate::CompleteWayfairOrder() || !FinanceOrderShareValidate::CompleteSheinOrder()) {
            exit();
        }

        Db::startTrans();
        try {
            self::promotionShare();
            self::shippingServiceShare();
            self::liquidationShare();
            self::adjustmentShare();
            self::adjustmentWfsShare();
            self::warehouseAdjustmentShare();

            Db::commit();
            $output->writeln("success");
        } catch (\Exception $e) {
            dump($e->getMessage());
            Db::rollback();
            $output->writeln($e->getMessage());
        }
    }

    /**
     * @throws BindParamException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws PDOException
     * @throws DbException
     */
    static protected function promotionShare()
    {
        // 促销
        $financeOrderShareObj = new FinanceOrderShareModel();
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
LIMIT 50;
            ');
        if (count($promotion)) {
            foreach ($promotion as $item) {
                if (!FinanceOrderShareValidate::CompleteIsShare($item['report_id'])) {
                    continue;
                }
                $shareCode = FinanceOrderShareModel::generateRandomCode();
                $tableObj = new FinanceTableModel();
                $table = $tableObj->where(['rid' => $item['report_id'], 'userAccount' => $item['user_account']])->find();
                $report = FinanceReportModel::get($item['report_id']);
                $shareItem = [];
                $amount = $item['total'];
                $fulfillmentData = FinanceOrderShareModel::generateFulfillmentByWarehouseSkuInUserAccount($item['warehouse_sku'], $report, $table['userAccount']);
                if ($fulfillmentData) {
                    foreach ($fulfillmentData as $k => $v) {
                        $shareItem[] = [
                            'report_id'     =>  $item['report_id'],
                            'table_id'      =>  $table['id'],
                            'user_account'  =>  $item['user_account'],
                            'fulfillment'   =>  $k == 1 ? 'FBA' : 'FBM',
                            'cost_type'     =>  'PROMOTION',
                            'share_code'    =>  $shareCode,
                            'payment'       =>  '',
                            'seller_sku'    =>  '',
                            'warehouse_sku' =>  $item['warehouse_sku'],
                            'amount'        =>  $amount,
                            'percent'       =>  $v,
                            'total'         =>  $amount * $v
                        ];
                    }
                } else {
                    // 无listing
                    $shareItem[] = [
                        'report_id'     =>  $item['report_id'],
                        'table_id'      =>  $table['id'],
                        'user_account'  =>  $item['user_account'],
                        'fulfillment'   =>  'FBM',
                        'cost_type'     =>  'PROMOTION',
                        'share_code'    =>  $shareCode,
                        'payment'       =>  '',
                        'seller_sku'    =>  '',
                        'warehouse_sku' =>  $item['warehouse_sku'],
                        'amount'        =>  $amount,
                        'percent'       =>  1,
                        'total'         =>  $amount
                    ];
                }

                if ($financeOrderShareObj->insertAll($shareItem)) {
                    $additionalModel->update(['share_code' => $shareCode], ['report_id' => $item['report_id'], 'user_account' => $item['user_account'], 'warehouse_sku' => $item['warehouse_sku']])->where('promotion', 'NOT NULL');
                }
            }
        }
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    static protected function shippingServiceShare()
    {
        // 退运费分摊
        $financeOrderShareObj = new FinanceOrderShareModel();
        $financeOrderShippingObj = new FinanceOrderShippingServiceModel();
        $shipping = $financeOrderShippingObj->where('share_code', null)->order('id asc')->limit(100)->select();
        if (count($shipping)) {
            $orderStatisticObj = new FinanceOrderStatisticsModel();
            foreach ($shipping as $item) {
                if (!FinanceOrderShareValidate::CompleteIsShare($item['report_id'])) {
                    continue;
                }

                // 这里使用订单表作为查询表可确保数据的完成性，基本存在
                $orderModel = new OrderModel();
                $order = $orderModel->where(['refNo' => $item['payment_id']])->find();
                $fulfillment = !empty($order) && $order['fulfillmentType'] == 1 ? 'FBA' : 'FBM';
                $shareCode = FinanceOrderShareModel::generateRandomCode();
                $statistic = $orderStatisticObj->where(['payment_id' => $item['payment_id']])->select();
                $shareItem = [];
                if (count($statistic)) {
                    $amount = $item['total'];
                    $percentSum = 0;
                    foreach ($statistic as $key => $value) {
                        if (count($statistic) == $key + 1) {
                            $percent = 1 - $percentSum;
                        } else {
                            // 退运费根据出库的销售额分摊
                            $percent = round($value['sale_amount'] / array_sum(array_column($statistic->toArray(), 'sale_amount')), 4);
                            $percentSum += $percent;
                        }
                        $shareItem[] = [
                            'report_id'     =>  $item['report_id'],
                            'table_id'      =>  $item['table_id'],
                            'user_account'  =>  $value['user_account'],
                            'fulfillment'   =>  $fulfillment,
                            'cost_type'     =>  'SHIPPING',
                            'share_code'    =>  $shareCode,
                            'payment'       =>  $item['payment_id'],
                            'seller_sku'    =>  $value['platform_sku'],
                            'warehouse_sku' =>  $value['warehouse_sku'],
                            'amount'        =>  $amount,
                            'percent'       =>  $percent,
                            'total'         =>  round($amount * $percent, 7)
                        ];
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
                            'user_account'  => $order['userAccount'],
                            'fulfillment'   => $fulfillment,
                            'cost_type'     => 'SHIPPING',
                            'share_code'    => $shareCode,
                            'payment'       => $item['payment_id'],
                            'seller_sku'    => $orderDetail['productSku'],
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
    }

    /**
     * @throws BindParamException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws PDOException
     * @throws DbException
     */
    static protected function liquidationShare()
    {
        // 清算分摊
        $financeOrderShareObj = new FinanceOrderShareModel();
        $financeOrderLiquidationObj = new FinanceOrderLiquidationModel();
        $liquidation = $financeOrderLiquidationObj->where('share_code', null)->order('id asc')->limit(100)->select();
        if (count($liquidation)) {
            $orderStatisticObj = new FinanceOrderStatisticsModel();
            foreach ($liquidation as $item) {
                if (!FinanceOrderShareValidate::CompleteIsShare($item['report_id'])) {
                    continue;
                }

                $shareCode = FinanceOrderShareModel::generateRandomCode();
                $tableObj = new FinanceTableModel();
                $table = $tableObj->find($item['table_id']);
                $report = FinanceReportModel::get($item['report_id']);
                $list = $orderStatisticObj->query('
SELECT
	a.id,
	a.total,
	c.user_account,
	a.sku,
	c.asin,
	c.seller_sku,
	d.percent,
	d.warehouse_sku 
FROM
	mu_finance_order_liquidation a
	LEFT JOIN mu_finance_table b ON a.table_id = b.id
	LEFT JOIN ( SELECT asin, seller_sku, user_account, MAX( updated_time ) updated_time FROM mu_ecang_listing GROUP BY asin, seller_sku, user_account, updated_time ) c ON a.sku = c.asin 
	AND b.userAccount = c.user_account
	LEFT JOIN ( SELECT DISTINCT user_account, seller_sku, warehouse_sku, seller, product_name, percent FROM mu_finance_sku_relation WHERE report_id = 6 ) d ON c.seller_sku = d.seller_sku 
	AND d.user_account = c.user_account 
WHERE
	a.total != 0 
	AND a.id = ' . $item['id'] . ' 
ORDER BY
	c.updated_time ASC;
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
                            $percentSum += $percent;
                        }
                        $fulfillmentData = FinanceOrderShareModel::generateFulfillmentByWarehouseSkuInUserAccount($value['warehouse_sku'], $report, $table['userAccount']);
                        if ($fulfillmentData) {
                            foreach ($fulfillmentData as $k => $v) {
                                $shareItem[] = [
                                    'report_id'     =>  $item['report_id'],
                                    'table_id'      =>  $item['table_id'],
                                    'user_account'  =>  $value['user_account'],
                                    'fulfillment'   =>  $k == 1 ? 'FBA' : 'FBM',
                                    'cost_type'     =>  'LIQUIDATION',
                                    'share_code'    =>  $shareCode,
                                    'payment'       =>  $item['order_id'],
                                    'seller_sku'    =>  $value['seller_sku'],
                                    'warehouse_sku' =>  $value['warehouse_sku'],
                                    'amount'        =>  $amount,
                                    'percent'       =>  $percent * $v,
                                    'total'         =>  round($amount * min($percent, 1), 6) * $v
                                ];
                            }
                        } else {
                            $shareItem[] = [
                                'report_id'     =>  $item['report_id'],
                                'table_id'      =>  $item['table_id'],
                                'user_account'  =>  $value['user_account'],
                                'fulfillment'   =>  'FBM',
                                'cost_type'     =>  'LIQUIDATION',
                                'share_code'    =>  $shareCode,
                                'payment'       =>  $item['order_id'],
                                'seller_sku'    =>  $value['seller_sku'],
                                'warehouse_sku' =>  $value['warehouse_sku'],
                                'amount'        =>  $amount,
                                'percent'       =>  $percent * (empty($value['percent']) ? 1 : $value['percent']),
                                'total'         =>  round($amount * $percent * (empty($value['percent']) ? 1 : $value['percent']), 6)
                            ];
                        }
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
    }

    /**
     * @throws BindParamException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws PDOException
     * @throws DbException
     */
    static protected function adjustmentShare()
    {
        // 调整分摊
        $financeOrderShareObj = new FinanceOrderShareModel();
        $financeOrderAdjustmentObj = new FinanceOrderAdjustmentModel();
        $adjustment = $financeOrderAdjustmentObj->where('share_code', null)->order('id asc')->limit(100)->select();
        if (count($adjustment)) {
            $orderModel = new OrderModel();
            $orderStatisticObj = new FinanceOrderStatisticsModel();
            foreach ($adjustment as $item) {
                if (!FinanceOrderShareValidate::CompleteIsShare($item['report_id'])) {
                    continue;
                }

                $order = $orderModel->where(['saleOrderCode' => $item['payment_id']])->find();
                $fulfillment = $order['fulfillmentType'] == 1 ? 'FBA' : 'FBM';
                $shareCode = FinanceOrderShareModel::generateRandomCode();
                $tableObj = new FinanceTableModel();
                $table = FinanceTableModel::get($item['table_id']);
                $report = FinanceReportModel::get($item['report_id']);
                $shareItem = [];
                $statistic = $orderStatisticObj->where(['payment_id' => $item['payment_id']])->select();
                if (count($statistic)) {
                    $percentSum = 0;
                    foreach ($statistic as $key => $value) {
                        $amount = $item['total'];
                        if ($key == count($statistic) - 1) {
                            $percent = 1 - $percentSum;
                        } else {
                            $percent = round(1 / count($statistic), 4);
                            $percentSum += $percent;
                        }
                        $shareItem[] = [
                            'report_id'     =>  $item['report_id'],
                            'table_id'      =>  $item['table_id'],
                            'user_account'  =>  $table['userAccount'],
                            'fulfillment'   =>  $fulfillment,
                            'cost_type'     =>  'ADJUSTMENT',
                            'share_code'    =>  $shareCode,
                            'payment'       =>  $item['payment_id'],
                            'seller_sku'    =>  $item['sku'],
                            'warehouse_sku' =>  $value['warehouse_sku'],
                            'amount'        =>  $amount,
                            'percent'       =>  $percent,
                            'total'         =>  $amount * $percent
                        ];
                    }
                } else {
                    // 无出库
                    if (empty($table) || empty($table['userAccount'])) {
                        continue; // 无店铺号跳过
                    }
                    $relation = FinanceOrderShareModel::generateSellerListByWarehouseSkuInUserAccount($item['sku'], $report, $table['userAccount']);
                    if ($relation) {
                        foreach ($relation as $value) {
                            $fulfillmentData = FinanceOrderShareModel::generateFulfillmentByWarehouseSkuInUserAccount($value['warehouse_sku'], $report, $table['userAccount']);
                            $amount = $item['total'];
                            $shareItem[] = [
                                'report_id'     =>  $item['report_id'],
                                'table_id'      =>  $item['table_id'],
                                'user_account'  =>  $table['userAccount'],
                                'fulfillment'   =>  array_key_exists(0, $fulfillmentData) ? 'FBM' : 'FBA',
                                'cost_type'     =>  'ADJUSTMENT',
                                'share_code'    =>  $shareCode,
                                'payment'       =>  $item['payment_id'],
                                'seller_sku'    =>  $item['sku'],
                                'warehouse_sku' =>  $value['warehouse_sku'],
                                'amount'        =>  $amount,
                                'percent'       =>  1,
                                'total'         =>  $amount
                            ];
                        }
                    } else {
                        // 无sku映射分摊到所有产品*********************************************************
                        $table = $tableObj->find($item['table_id']);
                        $skuPercent = FinanceOrderShareModel::generateSkuPercentByUserAccount($table);
                        $percentSum = 0;
                        foreach ($skuPercent as $key => $value) {
                            $amount = $item['total'];
                            if ($key == count($skuPercent) - 1) {
                                $value['percent'] = 1 - $percentSum;
                            }
                            $fulfillmentData = FinanceOrderShareModel::generateFulfillmentByWarehouseSkuInUserAccount($value['warehouse_sku'], $report, $table['userAccount']);
                            foreach ($fulfillmentData as $k => $v) {
                                $shareItem[] = [
                                    'report_id'     =>  $item['report_id'],
                                    'table_id'      =>  $item['table_id'],
                                    'user_account'  =>  $table['userAccount'],
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
    }

    /**
     * @throws BindParamException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws PDOException
     * @throws DbException
     */
    static protected function adjustmentWfsShare()
    {
        // 调整分摊
        $financeOrderShareObj = new FinanceOrderShareModel();
        $financeOrderAdjustmentWfsObj = new FinanceOrderAdjustmentWfsModel();
        $adjustmentWfs = $financeOrderAdjustmentWfsObj->where('share_code', null)->order('id asc')->limit(100)->select();
        if (count($adjustmentWfs)) {
            $orderModel = new OrderModel();
            $orderStatisticObj = new FinanceOrderStatisticsModel();
            foreach ($adjustmentWfs as $item) {
                if (!FinanceOrderShareValidate::CompleteIsShare($item['report_id'])) {
                    continue;
                }

                if ($item['is_fulfillment']) {
                    $costType = "WFS_FULFILLMENT";
                } elseif ($item['is_return_shipping']) {
                    $costType = "WFS_RETURN_SHIPPING";
                } else {
                    $costType = "WFS";
                }

                $order = $orderModel->where(['saleOrderCode' => $item['payment_id']])->find();
                $fulfillment = $order['fulfillmentType'] == 1 ? 'FBA' : 'FBM';
                $shareCode = FinanceOrderShareModel::generateRandomCode();
                $tableObj = new FinanceTableModel();
                $table = FinanceTableModel::get($item['table_id']);
                $report = FinanceReportModel::get($item['report_id']);
                $shareItem = [];
                $statistic = $orderStatisticObj->where(['payment_id' => $item['payment_id']])->select();
                if (count($statistic)) {
                    $percentSum = 0;
                    foreach ($statistic as $key => $value) {
                        $amount = $item['total'];
                        if ($key == count($statistic) - 1) {
                            $percent = 1 - $percentSum;
                        } else {
                            $percent = round(1 / count($statistic), 4);
                            $percentSum += $percent;
                        }
                        $shareItem[] = [
                            'report_id'     =>  $item['report_id'],
                            'table_id'      =>  $item['table_id'],
                            'user_account'  =>  $table['userAccount'],
                            'fulfillment'   =>  $fulfillment,
                            'cost_type'     =>  $costType,
                            'share_code'    =>  $shareCode,
                            'payment'       =>  $item['payment_id'],
                            'seller_sku'    =>  $item['sku'],
                            'warehouse_sku' =>  $value['warehouse_sku'],
                            'amount'        =>  $amount,
                            'percent'       =>  $percent,
                            'total'         =>  $amount * $percent
                        ];
                    }
                } else {
                    // 无出库
                    if (empty($table) || empty($table['userAccount'])) {
                        continue; // 无店铺号跳过
                    }
                    $relation = FinanceOrderShareModel::generateSellerListByWarehouseSkuInUserAccount($item['sku'], $report, $table['userAccount']);
                    if ($relation) {
                        foreach ($relation as $value) {
                            $fulfillmentData = FinanceOrderShareModel::generateFulfillmentByWarehouseSkuInUserAccount($value['warehouse_sku'], $report, $table['userAccount']);
                            $amount = $item['total'];
                            $shareItem[] = [
                                'report_id'     =>  $item['report_id'],
                                'table_id'      =>  $item['table_id'],
                                'user_account'  =>  $table['userAccount'],
                                'fulfillment'   =>  array_key_exists(0, $fulfillmentData) ? 'FBM' : 'FBA',
                                'cost_type'     =>  $costType,
                                'share_code'    =>  $shareCode,
                                'payment'       =>  $item['payment_id'],
                                'seller_sku'    =>  $item['sku'],
                                'warehouse_sku' =>  $value['warehouse_sku'],
                                'amount'        =>  $amount,
                                'percent'       =>  1,
                                'total'         =>  $amount
                            ];
                        }
                    } else {
                        // 无sku映射分摊到所有产品*********************************************************
                        $table = $tableObj->find($item['table_id']);
                        $skuPercent = FinanceOrderShareModel::generateSkuPercentByUserAccount($table);
                        $percentSum = 0;
                        foreach ($skuPercent as $key => $value) {
                            $amount = $item['total'];
                            if ($key == count($skuPercent) - 1) {
                                $value['percent'] = 1 - $percentSum;
                            }
                            $fulfillmentData = FinanceOrderShareModel::generateFulfillmentByWarehouseSkuInUserAccount($value['warehouse_sku'], $report, $table['userAccount']);
                            foreach ($fulfillmentData as $k => $v) {
                                $shareItem[] = [
                                    'report_id'     =>  $item['report_id'],
                                    'table_id'      =>  $item['table_id'],
                                    'user_account'  =>  $table['userAccount'],
                                    'fulfillment'   =>  $k == 1 ? 'FBA' : 'FBM',
                                    'cost_type'     =>  $costType,
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
                    $financeOrderAdjustmentWfsObj->update(['share_code' => $shareCode], ['id' => $item['id']]);
                }
            }
        }
    }

    /**
     * @throws BindParamException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws PDOException
     * @throws DbException
     */
    static protected function warehouseAdjustmentShare()
    {
        // 海外仓调整
        $financeOrderShareObj = new FinanceOrderShareModel();
        $additionalModel = new FinanceOrderAdditionalModel();
        $warehouseAdjustment = $additionalModel
            ->where(function($query) {
                $query->where('share_code', null)
                    ->where('lc_adjustment', 'notnull');
            })
            ->whereOr(function($query) {
                $query->where('share_code', null)
                    ->where('le_adjustment', 'notnull');
            })
            ->limit(100)->select();
        if (count($warehouseAdjustment)) {
            foreach ($warehouseAdjustment as $item) {
                if (!FinanceOrderShareValidate::CompleteIsShare($item['report_id'])) {
                    continue;
                }

                $shareCode = FinanceOrderShareModel::generateRandomCode();
                $costType = empty($item['lc_adjustment']) ? 'LEADJUSTMENT' : 'LCADJUSTMENT';
                $costField = empty($item['lc_adjustment']) ? 'le_adjustment' : 'lc_adjustment';
                $report = FinanceReportModel::get($item['report_id']);
                $shareItem = [];
                $sku = FinanceOrderShareModel::getMainSku($item['warehouse_sku']);
                $userList = FinanceOrderShareModel::generateSellerListByWarehouseSku($sku, $report, $item['platform']);
                if ($userList) {
                    $total = $item[$costField];
                    $userSum = 0;
                    $percentSum = 0;
                    foreach ($userList as $k => $v) {
                        if ($k + 1 != count($userList)) {
                            $userTotal = round($total / count($userList), 6);
                            $percent = round(1 / count($userList), 4);
                            $userSum += $userTotal;
                            $percentSum += $percent;
                        } else {
                            $userTotal = $total - $userSum;
                            $percent = 1 - $percentSum;
                        }

                        $userAccountList = FinanceOrderShareModel::generateUserAccountListByWarehouseSkuAndSeller($sku, $report, $v['seller'], $item['platform']);
                        $userAccountSum = 0;
                        if ($userAccountList) {
                            foreach ($userAccountList as $ku => $userAccount) {
                                if ($ku + 1 != count($userAccountList)) {
                                    $userAccountTotal = round($userTotal / count($userAccountList), 6);
                                    $userAccountSum += $userAccountTotal;
                                } else {
                                    $userAccountTotal = $userTotal - $userAccountSum;
                                }
                                $shareItem[] = [
                                    'report_id'     =>  $item['report_id'],
                                    'user_account'  =>  $userAccount['user_account'],
                                    'fulfillment'   =>  'FBM',
                                    'cost_type'     =>  $costType,
                                    'share_code'    =>  $shareCode,
                                    'warehouse_sku' =>  $sku,
                                    'amount'        =>  $item[$costField],
                                    'percent'       =>  $percent,
                                    'total'         =>  $userAccountTotal
                                ];
                            }
                        } else {
                            $shareItem[] = [
                                'report_id'     =>  $item['report_id'],
                                'user_account'  =>  '',
                                'fulfillment'   =>  'FBM',
                                'cost_type'     =>  $costType,
                                'share_code'    =>  $shareCode,
                                'warehouse_sku' =>  $sku,
                                'amount'        =>  $item[$costField],
                                'percent'       =>  $percent,
                                'total'         =>  $userTotal
                            ];
                        }
                    }
                } else {
                    $shareItem[] = [
                        'report_id'     =>  $item['report_id'],
                        'user_account'  =>  '',
                        'fulfillment'   =>  'FBM',
                        'cost_type'     =>  $costType,
                        'share_code'    =>  $shareCode,
                        'warehouse_sku' =>  $sku,
                        'amount'        =>  $item[$costField],
                        'percent'       =>  1,
                        'total'         =>  $item[$costField]
                    ];
                }

                if ($financeOrderShareObj->insertAll($shareItem)) {
                    $additionalModel->update(['share_code' => $shareCode], ['id' => $item['id']]);
                }
            }
        }
    }
}