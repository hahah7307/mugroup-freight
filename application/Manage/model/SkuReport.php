<?php

namespace app\Manage\model;

use think\Model;

class SkuReport extends Model
{
    static public function four_warehouse_sku($date): string
    {
        return '
SELECT
	COUNT( lecangsCode ) count,
	warehouseCount,
	a.user_name,
	b.userCount 
FROM
	(
	SELECT
		COUNT( a.lecangsCode ) warehouseCount,
		lecangsCode,
		c.user_name 
	FROM
		(
		SELECT DISTINCT
			SUBSTR( lecangsCode FROM 7 ) lecangsCode,
			warehouseBelong 
		FROM
			mu_le_inventory_batch a
			LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode 
		WHERE
			created_date = ' . $date . ' 
			AND a.warehouseCode IN ( "CAP", "PAW", "SAV", "HOU03", "HOU07", "HOU05" ) 
		) a
		LEFT JOIN mu_ecang_product b ON a.lecangsCode = b.productSku
		LEFT JOIN mu_ecang_user c ON b.personSellerId = c.user_id 
	WHERE
		b.saleStatus = 2 
	GROUP BY
		lecangsCode,
		user_name 
	) a
	LEFT JOIN (
	SELECT
		COUNT( a.lecangsCode ) userCount,
		c.user_name 
	FROM
		(
		SELECT DISTINCT
			SUBSTR( lecangsCode FROM 7 ) lecangsCode 
		FROM
			mu_le_inventory_batch a
			LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode 
		WHERE
			created_date = ' . $date . ' 
			AND a.warehouseCode IN ( "CAP", "PAW", "SAV", "HOU03", "HOU07", "HOU05" ) 
		) a
		LEFT JOIN mu_ecang_product b ON a.lecangsCode = b.productSku
		LEFT JOIN mu_ecang_user c ON b.personSellerId = c.user_id 
	WHERE
		b.saleStatus = 2 
	GROUP BY
		user_name 
	) b ON a.user_name = b.user_name 
GROUP BY
	warehouseCount,
	user_name,
	userCount 
ORDER BY
	warehouseCount DESC,
	count DESC;
            ';
    }

    static public function four_warehouse_sku_sum($date): string
    {
        return '
SELECT
	SUM( goodsNum ) sum,
	warehouseCount,
	a.user_name,
	b.goodsSumAll 
FROM
	(
	SELECT
		COUNT( a.lecangsCode ) warehouseCount,
		SUM( a.goodsNum ) goodsNum,
		lecangsCode,
		c.user_name 
	FROM
		(
		SELECT
			SUM( goodsNum ) goodsNum,
			SUBSTR( lecangsCode FROM 7 ) lecangsCode,
			warehouseBelong 
		FROM
			mu_le_inventory_batch a
			LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode 
		WHERE
			created_date = ' . $date . ' 
			AND a.warehouseCode IN ( "CAP", "PAW", "SAV", "HOU03", "HOU07", "HOU05" ) 
		GROUP BY
			lecangsCode,
			warehouseBelong 
		) a
		LEFT JOIN mu_ecang_product b ON a.lecangsCode = b.productSku
		LEFT JOIN mu_ecang_user c ON b.personSellerId = c.user_id 
	WHERE
		b.saleStatus = 2 
	GROUP BY
		lecangsCode,
		user_name 
	) a
	LEFT JOIN (
	SELECT
		SUM( a.goodsNum ) goodsSumAll,
		c.user_name 
	FROM
		(
		SELECT
			SUM( goodsNum ) goodsNum,
			SUBSTR( lecangsCode FROM 7 ) lecangsCode,
			warehouseBelong 
		FROM
			mu_le_inventory_batch a
			LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode 
		WHERE
			created_date = ' . $date . ' 
			AND a.warehouseCode IN ( "CAP", "PAW", "SAV", "HOU03", "HOU07", "HOU05" ) 
		GROUP BY
			lecangsCode,
			warehouseBelong 
		) a
		LEFT JOIN mu_ecang_product b ON a.lecangsCode = b.productSku
		LEFT JOIN mu_ecang_user c ON b.personSellerId = c.user_id 
	WHERE
		b.saleStatus = 2 
	GROUP BY
		user_name 
	) b ON a.user_name = b.user_name 
GROUP BY
	warehouseCount,
	user_name,
	goodsSumAll 
ORDER BY
	warehouseCount DESC,
	sum DESC;
            ';
    }
}
