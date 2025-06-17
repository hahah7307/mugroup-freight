<?php

namespace app\Manage\model;

use think\db\exception\BindParamException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\exception\PDOException;
use think\Model;

class FinanceOrderShareModel extends Model
{
    protected $name = 'finance_order_share';

    protected $resultSetType = 'collection';

    /**
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */
    static public function sku_identify($sku): bool
    {
        $productObj = new ProductModel();
        $productObj = $productObj->where(['productSku' => $sku, 'saleStatus' => 2])->select();
        if (count($productObj) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    static public function getMainSku($sku)
    {
        if (self::sku_identify($sku)) {
            // 在售主件直接用自己
            return $sku;
        } else {
            if (!strpos($sku, '-')) {
                // 未在售主件也先用自己
                return $sku;
            } else {
                $mainSku = explode('-', $sku)[0];
                $productObj = new ProductModel();
                $product = $productObj->where(['productSku' => ['like', $mainSku . '%'], 'saleStatus' => 2])->order('productSku asc')->find();
                if (empty($product)) {
                    // 未在售配件也无未在售主件
                    return $mainSku;
                } else {
                    // 未在售配件有在售主件直接用该主件
                    return $product['productSku'];
                }
            }
        }
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    static public function generateFulfillmentByWarehouseSkuInUserAccount($warehouseSku, $report, $userAccount): array
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
	AND report_id = ' . $report['id'] . ' 
	AND a.user_account = "' . $userAccount . '"
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
	AND report_id = ' . $report['id'] . ' 
	AND user_account = "' . $userAccount . '"
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

    static public function generateRandomCode($length = 16): string
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
    static public function generatePlatformSaleQtyByWarehouseSku($warehouse_sku, $report)
    {
        $financeOrderShareObj = new FinanceOrderShareModel();
        return $financeOrderShareObj->query('
SELECT
    platform,
    warehouse_sku,
    SUM( qty ) sale_qty
FROM
    mu_finance_order_outbound 
WHERE
    warehouse_sku = "' . $warehouse_sku . '" 
    AND report_id = ' . $report['id'] . ' 
GROUP BY
    platform,
    warehouse_sku;
        ');
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    static public function generateSellerListByWarehouseSku($warehouse_sku, $report, $platform)
    {
        $financeOrderShareObj = new FinanceOrderShareModel();
        return $financeOrderShareObj->query('
SELECT DISTINCT
    a.warehouse_sku,
    b.platform,
    a.seller 
FROM
    mu_finance_sku_relation a
    LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report['id'] . ' ) b ON a.user_account = b.userAccount 
WHERE
    a.warehouse_sku = "' . $warehouse_sku . '"
    AND b.platform = "' . $platform . '"
    AND a.report_id = ' . $report['id'] . '
    AND a.type = 0;
        ');
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    static public function generateSellerListByWarehouseSkuInUserAccount($seller_sku, $report, $userAccount)
    {
        $financeOrderShareObj = new FinanceOrderShareModel();
        return $financeOrderShareObj->query('
SELECT DISTINCT
    a.warehouse_sku,
    a.user_account,
    a.seller,
    a.percent
FROM
    mu_finance_sku_relation a
    LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report['id'] . ' ) b ON a.user_account = b.userAccount 
WHERE
    a.seller_sku = "' . $seller_sku . '"
    AND a.user_account = "' . $userAccount . '"
    AND a.report_id = ' . $report['id'] . '
    AND a.type = 0;
        ');
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    static public function generateUserAccountListByWarehouseSkuAndSeller($warehouse_sku, $report, $seller, $platform)
    {
        $financeOrderShareObj = new FinanceOrderShareModel();
        return $financeOrderShareObj->query('
SELECT DISTINCT
    a.warehouse_sku,
    a.user_account,
    a.seller 
FROM
    mu_finance_sku_relation a
    LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report['id'] . ' ) b ON a.user_account = b.userAccount 
WHERE
    a.seller = "' . $seller . '" 
    AND a.warehouse_sku = "' . $warehouse_sku . '"
    AND b.platform = "' . $platform . '"
    AND a.report_id = ' . $report['id'] . '
    AND a.type = 0;
        ');
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    static public function generateSkuPercentByUserAccount($table)
    {
        $model = new FinanceOrderShareModel();
        $warehouseSaleQty = $model->query('
SELECT
	warehouse_sku,
	SUM( qty ) qty
FROM
	( SELECT DISTINCT payment_id FROM mu_finance_order_sale WHERE table_id in (SELECT id FROM mu_finance_table WHERE rid = ' . $table['rid'] . ' AND userAccount = "' . $table['userAccount'] . '")) a
	LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id
	LEFT JOIN mu_ecang_product c ON b.warehouse_sku = c.productSku 
WHERE
	b.warehouse_sku IS NOT NULL
GROUP BY
	warehouse_sku;
            ');

        $qtySum = array_sum(array_column($warehouseSaleQty, 'qty'));
        $percentSum = 0;
        foreach ($warehouseSaleQty as $key => $item) {
            if ($key + 1 == count($warehouseSaleQty)) {
                $warehouseSaleQty[$key]['percent'] = round(1 - $percentSum, 5);
            } else {
                $warehouseSaleQty[$key]['percent'] = round($item['qty'] / $qtySum, 5);
                $percentSum += round($item['qty'] / $qtySum, 5);
            }
        }

        return $warehouseSaleQty;
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    static public function generateSellerInPlatformListByWarehouseSku($warehouse_sku, $report)
    {
        $financeOrderShareObj = new FinanceOrderShareModel();
        return $financeOrderShareObj->query('
SELECT DISTINCT
	platform,
	seller 
FROM
	mu_finance_sku_relation a
	LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report['id'] . ' ) b ON a.user_account = b.userAccount 
WHERE
	warehouse_sku = "' . $warehouse_sku . '" 
	AND a.report_id = ' . $report['id'] . ' 
	AND platform IN ( "amazon", "wayfair", "walmart" )
    AND a.type = 0;
        ');
    }
}
