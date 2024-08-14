<?php

namespace app\Manage\model;

use think\Model;

class FinanceOperationExpensesModel extends Model
{
    protected $name = 'finance_operation_expenses';

    protected $resultSetType = 'collection';

    const WAREHOUSE_SKU_CHANGELIST = [
        "NFN003NL"
    ];

    static public function isChangeByWarehouseSku($warehouseSku): bool
    {
        $changeList = self::WAREHOUSE_SKU_CHANGELIST;
        return in_array($warehouseSku, $changeList);
    }

    static public function generateSellerPercentByWarehouseSku($warehouseSku, $seller): array
    {
        $isChange = self::isChangeByWarehouseSku($warehouseSku);
        if (strpos($warehouseSku, 'NF') !== false) {
            // 家具类产品分摊占比
            foreach ($seller as $key => $user) {
                if ($user['platform'] == "amazon") {
                    $seller[$key]['percent'] = $isChange ? 1 : 2;
                } elseif ($user['platform'] == "wayfair") {
                    $seller[$key]['percent'] = 2;
                } elseif ($user['platform'] == "walmart") {
                    $seller[$key]['percent'] = 1;
                }
            }
            // 转卖产品公司会承担部分
            if ($isChange) {
                $seller[] = [
                    'platform'  =>  'company',
                    'seller'    =>  'company',
                    'percent'   =>  2
                ];
            }
        } elseif (strpos($warehouseSku, 'BB') !== false) {
            // 儿童类产品分摊占比
            foreach ($seller as $key => $user) {
                if ($user['platform'] == "amazon") {
                    $seller[$key]['percent'] = $isChange ? 1 : 2;
                } elseif ($user['platform'] == "wayfair") {
                    $seller[$key]['percent'] = 1;
                } elseif ($user['platform'] == "walmart") {
                    $seller[$key]['percent'] = 1.5;
                }
            }
        } else {
            // 其他分类暂不考虑
            return [];
        }
        unset($key);
        unset($value);

        return $seller;
    }
}
