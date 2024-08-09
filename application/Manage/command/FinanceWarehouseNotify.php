<?php
namespace app\Manage\command;

use app\Manage\model\FinanceOrderOutboundModel;
use app\Manage\model\FinanceOrderShareModel;
use app\Manage\model\FinanceOrderShareValidate;
use app\Manage\model\FinanceReportModel;
use app\Manage\model\FinanceWarehouseFbmModel;
use app\Manage\model\ProductModel;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

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
        if (!FinanceOrderShareValidate::CompleteWayfairOrder() || !FinanceOrderShareValidate::CompleteSheinOrder()) {
            exit();
        }

        $financeOutboundObj = new FinanceOrderOutboundModel();
        $financeWarehouseFbmObj = new FinanceWarehouseFbmModel();
        Db::startTrans();
        try {
            $list = $financeWarehouseFbmObj->where('share_code', null)->limit(50)->order('id asc')->select();
            if (count($list)) {
                $updateData = [];
                $shareData = [];
                foreach ($list as $item) {
                    // 无出库数据先不分摊费用
                    $outbound = $financeOutboundObj->where(['report_id' => $item['report_id']])->select();
                    if (count($outbound) <= 0) {
                        continue;
                    }
                    $shareCode = FinanceOrderShareModel::generateRandomCode();
                    $report = FinanceReportModel::get($item['report_id']);
                    // 自定义仓储费的主件sku，如果已经有或者已调整，按已存在的sku计算逻辑
                    $sku = $item['sku'];
                    if (FinanceOrderShareModel::sku_identify($sku)) {
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

                    // 查询该主件sku各平台的销售情况
                    $saleList = FinanceOrderShareModel::generatePlatformSaleQtyByWarehouseSku($mainSku, $report);

                    // 如该主件sku在主销售平台无销售数据，新增销售为0的该平台数据
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

                    // 统计销售总个数
                    $saleCount = array_sum(array_column($saleList, 'sale_qty'));
                    $sum = 0;
                    if ($saleList) {
                        foreach ($saleList as $key => $platformData) {
                            // 销售平台为wayfair的情况需要调整平台名称
                            if ($platformData['platform'] == 'wayfairnew') {
                                $platformData['platform'] = 'wayfair';
                            }

                            // 查询该主件sku在该平台的销售人员，根据销售人员数量均摊仓储费
                            $userList = FinanceOrderShareModel::generateSellerListByWarehouseSku($mainSku, $report, $platformData['platform']);

                            if ($key + 1 != count($saleList)) {
                                // 销售数量和剩余库存的逻辑跟随主件sku，而不是当前的配件。费用使用配件
                                if ($mainSku == $item['sku']) {
                                    $restQty = $item['quantity'];
                                } else {
                                    $warehouseFbmData = $financeWarehouseFbmObj->where(['sku' => $mainSku, 'report_id' => $item['report_id']])->find();
                                    $restQty = empty($warehouseFbmData) ? $item['quantity'] : $warehouseFbmData['quantity'];
                                }
                                // 主销售平台承担剩余库存应当分摊的仓储费和销售部分，其余平台只承担销量部分（意为帮卖）
                                if ($item['main_platform'] == $platformData['platform']) {
                                    $platformTotal = round(($platformData['sale_qty'] + $restQty) / ($saleCount + $restQty) * $item['total'], 6);
                                } else {
                                    $platformTotal = round(($platformData['sale_qty']) / ($saleCount + $restQty) * $item['total'], 6);
                                }
                                $sum += $platformTotal;
                            } else {
                                $platformTotal = $item['total'] - $sum;
                            }

                            $userSum = 0;
                            if ($userList) {
                                foreach ($userList as $k => $user) {
                                    if ($k + 1 != count($userList)) {
                                        $userTotal = round($platformTotal / count($userList), 6);
                                        $userSum += $userTotal;
                                    } else {
                                        $userTotal = $platformTotal - $userSum;
                                    }

                                    // 查询该主件sku该销售人员在该平台的所有店铺，根据店铺数量均摊仓储费
                                    $userAccountList = FinanceOrderShareModel::generateUserAccountListByWarehouseSkuAndSeller($mainSku, $report, $user['seller'], $platformData['platform']);

                                    $userAccountSum = 0;
                                    if ($userAccountList) {
                                        foreach ($userAccountList as $ku => $userAccount) {
                                            if ($ku + 1 != count($userAccountList)) {
                                                $userAccountTotal = round($userTotal / count($userAccountList), 6);
                                                $userAccountSum += $userAccountTotal;
                                            } else {
                                                $userAccountTotal = $userTotal - $userAccountSum;
                                            }
                                            $shareData[] = [
                                                'report_id'     =>  $item['report_id'],
                                                'user_account'  =>  $userAccount['user_account'],
                                                'fulfillment'   =>  'FBM',
                                                'cost_type'     =>  'WAREHOUSE_FBM',
                                                'share_code'    =>  $shareCode,
                                                'warehouse_sku' =>  $mainSku,
                                                'amount'        =>  $item['total'],
                                                'total'         =>  $userAccountTotal
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
                                            'total'         =>  $userTotal
                                        ];
                                    }
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
                        // 无销售的主件sku只分摊到主销售平台
                        $userList = FinanceOrderShareModel::generateSellerListByWarehouseSku($mainSku, $report, $item['main_platform']);

                        $userSum = 0;
                        if ($userList) {
                            foreach ($userList as $k => $user) {
                                if ($k + 1 != count($userList)) {
                                    $userTotal = round($item['total'] / count($userList), 6);
                                    $userSum += $userTotal;
                                } else {
                                    $userTotal = $item['total'] - $userSum;
                                }

                                // 平台内分摊逻辑同上
                                $userAccountList = FinanceOrderShareModel::generateUserAccountListByWarehouseSkuAndSeller($mainSku, $report, $user['seller'], $user['platform']);

                                $userAccountSum = 0;
                                if ($userAccountList) {
                                    foreach ($userAccountList as $ku => $userAccount) {
                                        if ($ku + 1 != count($userAccountList)) {
                                            $userAccountTotal = round($userTotal / count($userAccountList), 6);
                                            $userAccountSum += $userAccountTotal;
                                        } else {
                                            $userAccountTotal = $userTotal - $userAccountSum;
                                        }
                                        $shareData[] = [
                                            'report_id'     =>  $item['report_id'],
                                            'user_account'  =>  $userAccount['user_account'],
                                            'fulfillment'   =>  'FBM',
                                            'cost_type'     =>  'WAREHOUSE_FBM',
                                            'share_code'    =>  $shareCode,
                                            'warehouse_sku' =>  $mainSku,
                                            'amount'        =>  $item['total'],
                                            'total'         =>  $userAccountTotal
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
                                        'total'         =>  $userTotal
                                    ];
                                }
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
    }
}