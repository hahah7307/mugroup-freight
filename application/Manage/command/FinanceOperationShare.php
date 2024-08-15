<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOperationDeliveryModel;
use app\Manage\model\FinanceOperationExpensesModel;
use app\Manage\model\FinanceOperationFactoryModel;
use app\Manage\model\FinanceOrderOutboundModel;
use app\Manage\model\FinanceOrderShareModel;
use app\Manage\model\FinanceReportModel;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class FinanceOperationShare extends Command
{
    protected function configure()
    {
        $this->setName('FinanceOperationShare')->setDescription('Here is the FinanceOperationShare');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        Db::startTrans();
        try {
            $financeOrderShareObj = new FinanceOrderShareModel();
            $reportObj = new FinanceReportModel();
            $reportItem = $reportObj->where(['is_operation' => 1])->find();
            if ($reportItem) {
                $outboundObj = new FinanceOrderOutboundModel();
                $SkuList = $outboundObj->where(['report_id' => $reportItem['id']])->distinct(true)->column('warehouse_sku');

                // 国内广告费其他分摊
                $expensesObj = new FinanceOperationExpensesModel();
                $expensesList = $expensesObj->where('report_id', null)->where('type', null)->order('id asc')->select();
                foreach ($expensesList as $item) {
                    if (!in_array($item['sku'], $SkuList)) {
                        continue;
                    }

//                    // 是否有销售模糊搜索版
//                    $sku = $item['sku'];
//                    $filter = array_filter($SkuList, function($i) use ($sku) {
//                        return strpos($i, $sku) !== false;
//                    });
//                    if (empty($filter)) {
//                        continue;
//                    }

                    $seller = FinanceOrderShareModel::generateSellerListByWarehouseSku($item['sku'], $reportItem, 'amazon');
                    $shareCode = FinanceOrderShareModel::generateRandomCode();
                    if ($seller) {
                        $shareItem = [];
                        $total = $item['total'];
                        $userSum = 0;
                        $percentSum = 0;
                        foreach ($seller as $k => $v) {
                            if ($k + 1 != count($seller)) {
                                $userTotal = round($total / count($seller), 6);
                                $percent = round(1 / count($seller), 4);
                                $userSum += $userTotal;
                                $percentSum += $percent;
                            } else {
                                $userTotal = $total - $userSum;
                                $percent = 1 - $percentSum;
                            }

                            $userAccountList = FinanceOrderShareModel::generateUserAccountListByWarehouseSkuAndSeller($item['sku'], $reportItem, $v['seller'], 'amazon');
                            $userAccountSum = 0;
                            $userAccountPercent = 0;
                            $userAccountPercentSum = 0;
                            if ($userAccountList) {
                                foreach ($userAccountList as $ku => $userAccount) {
                                    if ($ku + 1 != count($userAccountList)) {
                                        $userAccountTotal = round($userTotal / count($userAccountList), 6);
                                        $userAccountPercent = round(1 / count($userAccountList), 4);
                                        $userAccountSum += $userAccountTotal;
                                        $userAccountPercentSum += $userAccountPercent;
                                    } else {
                                        $userAccountTotal = $userTotal - $userAccountSum;
                                        $userAccountPercent = 1 - $userAccountPercentSum;
                                    }

                                    $fulfillmentData = FinanceOrderShareModel::generateFulfillmentByWarehouseSkuInUserAccount($item['sku'], $reportItem, $userAccount['user_account']);
                                    if ($fulfillmentData) {
                                        foreach ($fulfillmentData as $k => $v) {
                                            $shareItem[] = [
                                                'report_id'     =>  $reportItem['id'],
                                                'user_account'  =>  $userAccount['user_account'],
                                                'fulfillment'   =>  $k == 1 ? 'FBA' : 'FBM',
                                                'cost_type'     =>  'OPERATION_EXPENSES_OTHER',
                                                'share_code'    =>  $shareCode,
                                                'warehouse_sku' =>  $item['sku'],
                                                'amount'        =>  $item['total'],
                                                'percent'       =>  $percent * $userAccountPercent * $v,
                                                'total'         =>  $userAccountTotal * $v
                                            ];
                                        }
                                    } else {
                                        $shareItem[] = [
                                            'report_id'     =>  $reportItem['id'],
                                            'user_account'  =>  $userAccount['user_account'],
                                            'fulfillment'   =>  'FBM',
                                            'cost_type'     =>  'OPERATION_EXPENSES_OTHER',
                                            'share_code'    =>  $shareCode,
                                            'warehouse_sku' =>  $item['sku'],
                                            'amount'        =>  $item['total'],
                                            'percent'       =>  $percent * $userAccountPercent,
                                            'total'         =>  $userAccountTotal
                                        ];
                                    }
                                }
                            } else {
                                $shareItem[] = [
                                    'report_id'     =>  $reportItem['id'],
                                    'user_account'  =>  '',
                                    'fulfillment'   =>  'FBM',
                                    'cost_type'     =>  'OPERATION_EXPENSES_OTHER',
                                    'share_code'    =>  $shareCode,
                                    'warehouse_sku' =>  $item['sku'],
                                    'amount'        =>  $item['total'],
                                    'percent'       =>  $percent * $userAccountPercent,
                                    'total'         =>  $userTotal
                                ];
                            }
                        }
                    } else {
                        continue;
                    }

                    if ($expensesObj->update(['report_id' => $reportItem['id'], 'calculate_month' => date('Ym', strtotime($reportItem['month'] . '-01')), 'share_code' => $shareCode], ['id' => $item['id']])) {
                        $financeOrderShareObj->insertAll($shareItem);
                    }
                }

                // 国内广告专利费分摊
                $expensesPatentObj = new FinanceOperationExpensesModel();
                $expensesPatent = $expensesPatentObj->where('report_id', null)->where('type', '专利费用')->order('id asc')->select();
                foreach ($expensesPatent as $item) {
                    if (!in_array($item['sku'], $SkuList)) {
                        continue;
                    }

                    $shareItem = [];
                    $shareCode = FinanceOrderShareModel::generateRandomCode();
                    $seller = FinanceOrderShareModel::generateSellerInPlatformListByWarehouseSku($item['sku'], $reportItem);
                    if ($seller) {
                        $seller = FinanceOperationExpensesModel::generateSellerPercentByWarehouseSku($item['sku'], $seller);
                        $percentSum = array_sum(array_column($seller, 'percent'));
                        $userPercentSum = 0;
                        foreach ($seller as $key => $user) {
                            if ($key + 1 == count($seller)) {
                                $userPercent = 1 - $userPercentSum;
                            } else {
                                $userPercent = round($user['percent'] / $percentSum, 2);
                                $userPercentSum += $userPercent;
                            }

                            if ($user['platform'] == 'company') {
                                // 有公司承担部分店铺名定义为company
                                $shareItem[] = [
                                    'report_id'     =>  $reportItem['id'],
                                    'user_account'  =>  'company',
                                    'fulfillment'   =>  'FBM',
                                    'cost_type'     =>  'OPERATION_EXPENSES_PATENT',
                                    'share_code'    =>  $shareCode,
                                    'warehouse_sku' =>  $item['sku'],
                                    'amount'        =>  $item['total'],
                                    'percent'       =>  $userPercent,
                                    'total'         =>  $userPercent * $item['total']
                                ];
                            } else {
                                $userAccountList = FinanceOrderShareModel::generateUserAccountListByWarehouseSkuAndSeller($item['sku'], $reportItem, $user['seller'], $user['platform']);
                                $userAccountPercentSum = 0;
                                if ($userAccountList) {
                                    foreach ($userAccountList as $ku => $userAccount) {
                                        if ($ku + 1 != count($userAccountList)) {
                                            $userAccountPercent = round(1 / count($userAccountList), 4);
                                            $userAccountPercentSum += $userAccountPercent;
                                        } else {
                                            $userAccountPercent = 1 - $userAccountPercentSum;
                                        }

                                        $fulfillmentData = FinanceOrderShareModel::generateFulfillmentByWarehouseSkuInUserAccount($item['sku'], $reportItem, $userAccount['user_account']);
                                        if ($fulfillmentData) {
                                            foreach ($fulfillmentData as $k => $v) {
                                                $shareItem[] = [
                                                    'report_id'     =>  $reportItem['id'],
                                                    'user_account'  =>  $userAccount['user_account'],
                                                    'fulfillment'   =>  $k == 1 ? 'FBA' : 'FBM',
                                                    'cost_type'     =>  'OPERATION_EXPENSES_PATENT',
                                                    'share_code'    =>  $shareCode,
                                                    'warehouse_sku' =>  $item['sku'],
                                                    'amount'        =>  $item['total'],
                                                    'percent'       =>  $userPercent * $userAccountPercent * $v,
                                                    'total'         =>  $userPercent * $userAccountPercent * $v * $item['total']
                                                ];
                                            }
                                        } else {
                                            $shareItem[] = [
                                                'report_id'     =>  $reportItem['id'],
                                                'user_account'  =>  $userAccount['user_account'],
                                                'fulfillment'   =>  'FBM',
                                                'cost_type'     =>  'OPERATION_EXPENSES_PATENT',
                                                'share_code'    =>  $shareCode,
                                                'warehouse_sku' =>  $item['sku'],
                                                'amount'        =>  $item['total'],
                                                'percent'       =>  $userPercent * $userAccountPercent,
                                                'total'         =>  $userPercent * $userAccountPercent * $item['total']
                                            ];
                                        }
                                    }
                                } else {
                                    $shareItem[] = [
                                        'report_id'     =>  $reportItem['id'],
                                        'user_account'  =>  '',
                                        'fulfillment'   =>  'FBM',
                                        'cost_type'     =>  'OPERATION_EXPENSES_PATENT',
                                        'share_code'    =>  $shareCode,
                                        'warehouse_sku' =>  $item['sku'],
                                        'amount'        =>  $item['total'],
                                        'percent'       =>  $userPercent,
                                        'total'         =>  $userPercent * $item['total']
                                    ];
                                }
                            }
                        }
                    } else {
                        continue;
                    }

                    if ($expensesPatentObj->update(['report_id' => $reportItem['id'], 'calculate_month' => date('Ym', strtotime($reportItem['month'] . '-01')), 'share_code' => $shareCode], ['id' => $item['id']])) {
                        $financeOrderShareObj->insertAll($shareItem);
                    }
                }

                // 工厂费用分摊
                $factoryObj = new FinanceOperationFactoryModel();
                $factoryList = $factoryObj->where('report_id', null)->order('id asc')->select();
                foreach ($factoryList as $item) {
                    if (!in_array($item['sku'], $SkuList)) {
                        continue;
                    }

                    $seller = FinanceOrderShareModel::generateSellerListByWarehouseSku($item['sku'], $reportItem, 'amazon');
                    $shareCode = FinanceOrderShareModel::generateRandomCode();
                    if ($seller) {
                        $shareItem = [];
                        $total = $item['total'];
                        $userSum = 0;
                        $percentSum = 0;
                        foreach ($seller as $k => $v) {
                            if ($k + 1 != count($seller)) {
                                $userTotal = round($total / count($seller), 6);
                                $percent = round(1 / count($seller), 4);
                                $userSum += $userTotal;
                                $percentSum += $percent;
                            } else {
                                $userTotal = $total - $userSum;
                                $percent = 1 - $percentSum;
                            }

                            $userAccountList = FinanceOrderShareModel::generateUserAccountListByWarehouseSkuAndSeller($item['sku'], $reportItem, $v['seller'], 'amazon');
                            $userAccountSum = 0;
                            $userAccountPercent = 0;
                            $userAccountPercentSum = 0;
                            if ($userAccountList) {
                                foreach ($userAccountList as $ku => $userAccount) {
                                    if ($ku + 1 != count($userAccountList)) {
                                        $userAccountTotal = round($userTotal / count($userAccountList), 6);
                                        $userAccountPercent = round(1 / count($userAccountList), 4);
                                        $userAccountSum += $userAccountTotal;
                                        $userAccountPercentSum += $userAccountPercent;
                                    } else {
                                        $userAccountTotal = $userTotal - $userAccountSum;
                                        $userAccountPercent = 1 - $userAccountPercentSum;
                                    }

                                    $fulfillmentData = FinanceOrderShareModel::generateFulfillmentByWarehouseSkuInUserAccount($item['sku'], $reportItem, $userAccount['user_account']);
                                    if ($fulfillmentData) {
                                        foreach ($fulfillmentData as $k => $v) {
                                            $shareItem[] = [
                                                'report_id'     =>  $reportItem['id'],
                                                'user_account'  =>  $userAccount['user_account'],
                                                'fulfillment'   =>  $k == 1 ? 'FBA' : 'FBM',
                                                'cost_type'     =>  'OPERATION_FACTORY',
                                                'share_code'    =>  $shareCode,
                                                'warehouse_sku' =>  $item['sku'],
                                                'amount'        =>  $item['total'],
                                                'percent'       =>  $percent * $userAccountPercent * $v,
                                                'total'         =>  $userAccountTotal * $v
                                            ];
                                        }
                                    } else {
                                        $shareItem[] = [
                                            'report_id'     =>  $reportItem['id'],
                                            'user_account'  =>  $userAccount['user_account'],
                                            'fulfillment'   =>  'FBM',
                                            'cost_type'     =>  'OPERATION_FACTORY',
                                            'share_code'    =>  $shareCode,
                                            'warehouse_sku' =>  $item['sku'],
                                            'amount'        =>  $item['total'],
                                            'percent'       =>  $percent * $userAccountPercent,
                                            'total'         =>  $userAccountTotal
                                        ];
                                    }
                                }
                            } else {
                                $shareItem[] = [
                                    'report_id'     =>  $reportItem['id'],
                                    'user_account'  =>  '',
                                    'fulfillment'   =>  'FBM',
                                    'cost_type'     =>  'OPERATION_FACTORY',
                                    'share_code'    =>  $shareCode,
                                    'warehouse_sku' =>  $item['sku'],
                                    'amount'        =>  $item['total'],
                                    'percent'       =>  $percent * $userAccountPercent,
                                    'total'         =>  $userTotal
                                ];
                            }
                        }
                    } else {
                        continue;
                    }

                    if ($factoryObj->update(['report_id' => $reportItem['id'], 'calculate_month' => date('Ym', strtotime($reportItem['month'] . '-01')), 'share_code' => $shareCode], ['id' => $item['id']])) {
                        $financeOrderShareObj->insertAll($shareItem);
                    }
                }

                // 国内快递费用分摊
                $deliveryObj = new FinanceOperationDeliveryModel();
                $deliveryList = $deliveryObj->where('report_id', null)->order('id asc')->select();
                foreach ($deliveryList as $item) {
                    if (!in_array($item['sku'], $SkuList)) {
                        continue;
                    }

                    $shareItem = [];
                    $shareCode = FinanceOrderShareModel::generateRandomCode();
                    $fulfillmentData = FinanceOrderShareModel::generateFulfillmentByWarehouseSkuInUserAccount($item['sku'], $reportItem, $item['user_account']);
                    if ($fulfillmentData) {
                        foreach ($fulfillmentData as $k => $v) {
                            $shareItem[] = [
                                'report_id'     =>  $reportItem['id'],
                                'user_account'  =>  $item['user_account'],
                                'fulfillment'   =>  $k == 1 ? 'FBA' : 'FBM',
                                'cost_type'     =>  'OPERATION_DELIVERY',
                                'share_code'    =>  $shareCode,
                                'warehouse_sku' =>  $item['sku'],
                                'amount'        =>  $item['total'],
                                'percent'       =>  $v,
                                'total'         =>  $item['total'] * $v
                            ];
                        }
                    } else {
                        $shareItem[] = [
                            'report_id'     =>  $reportItem['id'],
                            'user_account'  =>  $item['user_account'],
                            'fulfillment'   =>  'FBM',
                            'cost_type'     =>  'OPERATION_DELIVERY',
                            'share_code'    =>  $shareCode,
                            'warehouse_sku' =>  $item['sku'],
                            'amount'        =>  $item['total'],
                            'percent'       =>  1,
                            'total'         =>  $item['total']
                        ];
                    }
                    if ($deliveryObj->update(['report_id' => $reportItem['id'], 'calculate_month' => date('Ym', strtotime($reportItem['month'] . '-01')), 'share_code' => $shareCode], ['id' => $item['id']])) {
                        $financeOrderShareObj->insertAll($shareItem);
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