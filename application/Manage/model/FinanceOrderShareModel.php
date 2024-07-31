<?php

namespace app\Manage\model;

use think\db\exception\BindParamException;
use think\exception\PDOException;
use think\Model;

class FinanceOrderShareModel extends Model
{
    protected $name = 'finance_order_share';

    protected $resultSetType = 'collection';

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    static public function getWarehouseSkuFulfillment($warehouseSku, $table): array
    {
        $model = new FinanceOrderShareModel();
        $data = $model->query('
SELECT
	SUM( a.qty ) qty,
	b.fulfillmentType 
FROM
	mu_finance_order_outbound a
	LEFT JOIN mu_ecang_order b ON a.saleOrderCode = b.saleOrderCode 
WHERE
	a.warehouse_sku = "' . $warehouseSku . '" 
	AND report_id = ' . $table['rid'] . ' 
	AND a.user_account = "' . $table['userAccount'] . '"
GROUP BY
	fulfillmentType;
        ');

        $sum = $model->query('
SELECT
	SUM( qty ) qty
FROM
	mu_finance_order_outbound
WHERE
	warehouse_sku = "' . $warehouseSku . '" 
	AND report_id = ' . $table['rid'] . ' 
	AND user_account = "' . $table['userAccount'] . '"
        ');

        $returnData = [];
        $percentSum = 0;
        foreach ($data as $key => $item) {
            if ($key == 0) {
                $percent = round($item['qty'] / $sum[0]['qty'], 4);
                if ($percent != 1) {
                    $percentSum += $percent;
                }
            } else {
                $percent = 1 - $percentSum;
            }
            $returnData[$item['fulfillmentType']] = $percent;
        }

        return $returnData;
    }
}
