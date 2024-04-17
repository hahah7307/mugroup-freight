<?php

namespace app\Manage\model;

use think\Model;

class FinanceReportModel extends Model
{
    const STATE_ACTIVE = 1;

    protected $name = 'finance_report';

    protected $resultSetType = 'collection';

    protected $insert = ['created_at', 'updated_at'];

    protected $update = ['updated_at'];

    protected function setCreatedAtAttr()
    {
        return date('Y-m-d H:i:s');
    }

    protected function setUpdatedAtAttr()
    {
        return date('Y-m-d H:i:s');
    }

    static public function getSaleRefundSql($report_id): string
    {
        return '
SELECT
	platform,
	userAccount,
	payment,
	payment_id,
	saleOrderCode,
	seller_sku,
	warehouse_sku,
	SUM( sale_qty ) sale_qty,
	SUM( refund_qty ) refund_qty,
	SUM( sale_amount ) sale_amount,
	SUM( refund_amount ) refund_amount,
	SUM( sale_selling_fees ) sale_selling_fees,
	SUM( refund_selling_fees ) refund_selling_fees,
	SUM( fba_fees ) fba_fees,
	SUM( calcuRes ) calcuRes,
	SUM( ddp ) ddp 
FROM
	(
	SELECT
		a.platform,
		a.userAccount,
		a.payment_id payment,
		b.payment_id,
		b.saleOrderCode,
		b.platform_sku seller_sku,
		b.warehouse_sku,
		b.qty sale_qty,
		NULL AS refund_qty,
		ROUND( b.sale_amount, 7 ) sale_amount,
		NULL AS refund_amount,
		ROUND( b.selling_fee, 7 ) sale_selling_fees,
		NULL AS refund_selling_fees,
		ROUND( b.fba_fee, 7 ) fba_fees,
		c.calcuRes calcuRes,
		f.sku_ddp_unit * b.qty ddp 
	FROM
		(
		SELECT DISTINCT
			payment_id,
			platform,
			userAccount 
		FROM
			mu_finance_order_sale a
			LEFT JOIN mu_finance_table b ON a.table_id = b.id 
		WHERE
			report_id = ' . $report_id . ' 
		) a
		LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id
		LEFT JOIN mu_ecang_order c ON b.saleOrderCode = c.saleOrderCode
		LEFT JOIN mu_finance_order_outbound e ON b.saleOrderCode = e.saleOrderCode
		LEFT JOIN mu_finance_store f ON e.store_id = f.id UNION ALL
	SELECT
		b.platform,
		b.userAccount,
		a.payment_id payment,
		NULL AS payment_id,
		NULL AS saleOrderCode,
		a.sku seller_sku,
		d.pcr_product_sku warehouse_sku,
		NULL AS sale_qty,
		a.quantity * d.pcr_quantity refund_qty,
		NULL AS sale_amount,
		ROUND( ( product_sales + shipping_credits + gift_wrap_credits + regulatory_fee + promotional_rebates ) * d.pcr_percent * d.pcr_quantity / 100, 7 ) refund_amount,
		NULL AS sale_selling_fees,
		ROUND( selling_fees * d.pcr_percent * d.pcr_quantity / 100, 7 ) refund_selling_fees,
		ROUND( fba_fees * d.pcr_percent * d.pcr_quantity / 100, 7 ) fba_fees,
		NULL AS calcuRes,
		NULL AS ddp 
	FROM
		mu_finance_order_refund a
		LEFT JOIN mu_finance_table b ON a.table_id = b.id
		LEFT JOIN mu_ecang_sku c ON a.sku = c.product_sku 
		AND b.userAccount = c.user_account
		LEFT JOIN mu_ecang_sku_relation d ON c.id = d.sku_id 
	WHERE
		report_id = ' . $report_id . ' 
	) a 
GROUP BY
	platform,
	userAccount,
	payment,
	payment_id,
	saleOrderCode,
	seller_sku,
	warehouse_sku;
        ';
    }

    static public function getWarehouseSkuSql($report_id, $month): string
    {
        return '
SELECT
	platform,
	userAccount,
	warehouse_sku,
	SUM( sale_qty ) sale_qty,
	SUM( refund_qty ) refund_qty,
	SUM( ROUND( sale_amount, 2 ) ) sale_amount,
	SUM( ROUND( refund_amount, 2 ) ) refund_amount,
	SUM( ROUND( sale_selling_fees, 2 ) ) sale_selling_fees,
	SUM( ROUND( refund_selling_fees, 2 ) ) refund_selling_fees,
	SUM( ROUND( fba_fees, 2 ) ) fba_fees,
	SUM( ROUND( calcuRes, 2 ) ) calcuRes,
	SUM( ROUND( ddp, 2 ) ) ddp,
	SUM( ROUND( adCost, 7 ) ) adCost,
	SUM( ROUND( warehouse_rent, 6 ) ) warehouse_rent,
	SUM( ROUND( promotion, 2 ) ) promotion,
	SUM( ROUND( shipping_service, 2 ) ) shipping_service,
	SUM( ROUND( liquidation, 2 ) ) liquidation,
	SUM( ROUND( ajustment, 2 ) ) ajustment,
	SUM( ROUND( fba_inventory, 2 ) ) fba_inventory,
	SUM( ROUND( transfer, 2 ) ) transfer 
FROM
	(
	SELECT
		platform,
		userAccount,
		warehouse_sku,
		SUM( sale_qty ) sale_qty,
		SUM( refund_qty ) refund_qty,
		SUM( ROUND( sale_amount, 2 ) ) sale_amount,
		SUM( ROUND( refund_amount, 2 ) ) refund_amount,
		SUM( ROUND( sale_selling_fees, 2 ) ) sale_selling_fees,
		SUM( ROUND( refund_selling_fees, 2 ) ) refund_selling_fees,
		SUM( ROUND( fba_fees, 2 ) ) fba_fees,
		SUM( ROUND( calcuRes, 2 ) ) calcuRes,
		SUM( ROUND( ddp, 2 ) ) ddp,
		SUM( ROUND( adCost, 7 ) ) adCost,
		SUM( ROUND( warehouse_rent, 6 ) ) warehouse_rent,
		SUM( ROUND( promotion, 2 ) ) promotion,
		SUM( ROUND( shipping_service, 2 ) ) shipping_service,
		SUM( ROUND( liquidation, 2 ) ) liquidation,
		SUM( ROUND( ajustment, 2 ) ) ajustment,
		SUM( ROUND( fba_inventory, 2 ) ) fba_inventory,
		SUM( ROUND( transfer, 2 ) ) transfer 
	FROM
		(-- 销售sql
		SELECT
			a.platform,
			a.userAccount,
			a.payment_id payment,
			b.payment_id,
			b.saleOrderCode,
			b.platform_sku seller_sku,
			b.warehouse_sku,
			b.qty sale_qty,
			NULL AS refund_qty,
			ROUND( b.sale_amount, 7 ) sale_amount,
			NULL AS refund_amount,
			ROUND( b.selling_fee, 7 ) sale_selling_fees,
			NULL AS refund_selling_fees,
			ROUND( b.fba_fee, 7 ) fba_fees,
			c.calcuRes calcuRes,
			f.sku_ddp_unit * b.qty ddp,
			NULL AS adCost,
			e.warehouse_rent,
			NULL AS promotion,
			NULL AS shipping_service,
			NULL AS liquidation,
			NULL AS ajustment,
			NULL AS fba_inventory,
			NULL AS transfer 
		FROM
			(
			SELECT DISTINCT
				payment_id,
				platform,
				userAccount 
			FROM
				mu_finance_order_sale a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id 
			WHERE
				report_id = ' . $report_id . ' 
			) a
			LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id
			LEFT JOIN mu_ecang_order c ON b.saleOrderCode = c.saleOrderCode
			LEFT JOIN mu_finance_order_outbound e ON b.saleOrderCode = e.saleOrderCode
			LEFT JOIN mu_finance_store f ON e.store_id = f.id UNION ALL
		SELECT
			b.platform,
			b.userAccount,
			a.payment_id payment,
			NULL AS payment_id,
			NULL AS saleOrderCode,
			a.sku seller_sku,
			d.pcr_product_sku warehouse_sku,
			NULL AS sale_qty,
			a.quantity * d.pcr_quantity refund_qty,
			NULL AS sale_amount,
			ROUND( ( product_sales + shipping_credits + gift_wrap_credits + regulatory_fee + promotional_rebates ) * d.pcr_percent * d.pcr_quantity / 100, 7 ) refund_amount,
			NULL AS sale_selling_fees,
			ROUND( selling_fees * d.pcr_percent * d.pcr_quantity / 100, 7 ) refund_selling_fees,
			ROUND( fba_fees * d.pcr_percent * d.pcr_quantity / 100, 7 ) fba_fees,
			NULL AS calcuRes,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS promotion,
			NULL AS shipping_service,
			NULL AS liquidation,
			NULL AS ajustment,
			NULL AS fba_inventory,
			NULL AS transfer 
		FROM
			mu_finance_order_refund a
			LEFT JOIN mu_finance_table b ON a.table_id = b.id
			LEFT JOIN mu_ecang_sku c ON a.sku = c.product_sku 
			AND b.userAccount = c.user_account
			LEFT JOIN mu_ecang_sku_relation d ON c.id = d.sku_id 
		WHERE
			report_id = ' . $report_id . ' UNION ALL
		SELECT
			b.platform,
			b.userAccount,
			NULL AS payment,
			NULL AS payment_id,
			NULL AS saleOrderCode,
			NULL AS seller_sku,
			NULL AS warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS fba_fees,
			NULL AS calcuRes,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			total promotion,
			NULL AS shipping_service,
			NULL AS liquidation,
			NULL AS ajustment,
			NULL AS fba_inventory,
			NULL AS transfer 
		FROM
			mu_finance_order_promotion a
			LEFT JOIN mu_finance_table b ON a.table_id = b.id 
		WHERE
			report_id = ' . $report_id . ' 
			AND description LIKE "%Coupon Redemption Fee%" 
			OR description LIKE "%Vine Enrollment Fee%" UNION ALL
		SELECT
			b.platform,
			b.userAccount,
			a.payment_id payment,
			NULL AS payment_id,
			NULL AS saleOrderCode,
			NULL AS seller_sku,
			NULL AS warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS fba_fees,
			NULL AS calcuRes,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS promotion,
			total shipping_service,
			NULL AS liquidation,
			NULL AS ajustment,
			NULL AS fba_inventory,
			NULL AS transfer 
		FROM
			mu_finance_order_shipping_service a
			LEFT JOIN mu_finance_table b ON a.table_id = b.id 
		WHERE
			report_id = ' . $report_id . ' UNION ALL
		SELECT
			b.platform,
			b.userAccount,
			NULL AS payment,
			NULL AS payment_id,
			NULL AS saleOrderCode,
			NULL AS seller_sku,
			NULL AS warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS fba_fees,
			NULL AS calcuRes,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS promotion,
			NULL AS shipping_service,
			total liquidation,
			NULL AS ajustment,
			NULL AS fba_inventory,
			NULL AS transfer 
		FROM
			mu_finance_order_liquidation a
			LEFT JOIN mu_finance_table b ON a.table_id = b.id 
		WHERE
			report_id = ' . $report_id . ' UNION ALL
		SELECT
			b.platform,
			b.userAccount,
			NULL AS payment,
			NULL AS payment_id,
			NULL AS saleOrderCode,
			NULL AS seller_sku,
			NULL AS warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS fba_fees,
			NULL AS calcuRes,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS promotion,
			NULL AS shipping_service,
			NULL AS liquidation,
			total ajustment,
			NULL AS fba_inventory,
			NULL AS transfer 
		FROM
			mu_finance_order_adjustment a
			LEFT JOIN mu_finance_table b ON a.table_id = b.id 
		WHERE
			report_id = ' . $report_id . ' UNION ALL
		SELECT
			b.platform,
			b.userAccount,
			NULL AS payment,
			NULL AS payment_id,
			NULL AS saleOrderCode,
			NULL AS seller_sku,
			NULL AS warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS fba_fees,
			NULL AS calcuRes,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS promotion,
			NULL AS shipping_service,
			NULL AS liquidation,
			NULL AS ajustment,
			NULL AS fba_inventory,
			total transfer 
		FROM
			mu_finance_order_transfer a
			LEFT JOIN mu_finance_table b ON a.table_id = b.id 
		WHERE
			report_id = ' . $report_id . ' UNION ALL
		SELECT
			a.platform,
			a.user_account userAccount,
			NULL AS payment,
			NULL AS payment_id,
			NULL AS saleOrderCode,
			NULL AS seller_sku,
			a.sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS fba_fees,
			NULL AS calcuRes,
			NULL AS ddp,
			NULL AS adCost,
			SUM( ROUND( a.total / b.qty, 7 ) ) warehouse_rent,
			NULL AS promotion,
			NULL AS shipping_service,
			NULL AS liquidation,
			NULL AS ajustment,
			NULL AS fba_inventory,
			NULL AS transfer 
		FROM
			(
			SELECT
				sku,
				pcr_product_sku,
				sku_id,
				user_account,
				platform,
				total 
			FROM
				(
				SELECT DISTINCT
					sku,
					SUM( total ) total 
				FROM
					mu_finance_warehouse 
				WHERE
					is_sale = 0 
					AND total > 0 
					AND report_id = ' . $report_id . ' 
				GROUP BY
					sku 
				) a
				LEFT JOIN mu_ecang_sku_relation b ON a.sku = b.pcr_product_sku
				LEFT JOIN mu_ecang_sku c ON b.sku_id = c.id
				LEFT JOIN mu_finance_table d ON c.user_account = d.userAccount 
			) a
			LEFT JOIN (
			SELECT
				sku,
				COUNT( sku ) qty 
			FROM
				( SELECT DISTINCT sku FROM mu_finance_warehouse WHERE is_sale = 0 AND total > 0 AND report_id = ' . $report_id . ' ) a
				LEFT JOIN mu_ecang_sku_relation b ON a.sku = b.pcr_product_sku 
			GROUP BY
				sku 
			) b ON a.sku = b.sku 
		GROUP BY
			platform,
			userAccount,
			warehouse_sku 
		) a 
	GROUP BY
		platform,
		userAccount,
		warehouse_sku UNION ALL
	SELECT
		"amazon" AS platform,
		c.ecang_user_account userAccount,
		e.pcr_product_sku warehouse_sku,
		NULL AS sale_qty,
		NULL AS refund_qty,
		NULL AS sale_amount,
		NULL AS refund_amount,
		NULL AS sale_selling_fees,
		NULL AS refund_selling_fees,
		NULL AS fba_fees,
		NULL AS calcuRes,
		NULL AS ddp,
		SUM( a.totalAdsCost * e.pcr_percent * e.pcr_quantity ) * 0.01 adCost,
		NULL AS warehouse_rent,
		NULL AS promotion,
		NULL AS shipping_service,
		NULL AS liquidation,
		NULL AS ajustment,
		NULL AS fba_inventory,
		NULL AS transfer 
	FROM
		mu_ak_ad_cost a
		LEFT JOIN mu_ak_seller b ON b.sid = a.sid
		LEFT JOIN mu_ecang_ak_user_account_relation c ON c.ak_user_account = b.`name`
		LEFT JOIN mu_ecang_sku d ON d.user_account = c.ecang_user_account 
		AND a.msku = d.product_sku
		LEFT JOIN mu_ecang_sku_relation e ON e.sku_id = d.id 
	WHERE
		reportDateMonth = "' . $month . '" 
	GROUP BY
		platform,
		userAccount,
		warehouse_sku  UNION ALL
	SELECT
		"amazon" AS platform,
		c.ecang_user_account userAccount,
		e.pcr_product_sku warehouse_sku,
		NULL AS sale_qty,
		NULL AS refund_qty,
		NULL AS sale_amount,
		NULL AS refund_amount,
		NULL AS sale_selling_fees,
		NULL AS refund_selling_fees,
		NULL AS fba_fees,
		NULL AS calcuRes,
		NULL AS ddp,
		NULL AS adCost,
		NULL AS warehouse_rent,
		NULL AS promotion,
		NULL AS shipping_service,
		NULL AS liquidation,
		NULL AS ajustment,
		SUM( a.fbaStorageFee * e.pcr_percent * e.pcr_quantity ) * 0.01 fba_inventory,
		NULL AS transfer 
	FROM
		mu_ak_ad_cost a
		LEFT JOIN mu_ak_seller b ON b.sid = a.sid
		LEFT JOIN mu_ecang_ak_user_account_relation c ON c.ak_user_account = b.`name`
		LEFT JOIN mu_ecang_sku d ON d.user_account = c.ecang_user_account 
		AND a.msku = d.product_sku
		LEFT JOIN mu_ecang_sku_relation e ON e.sku_id = d.id 
	WHERE
		reportDateMonth = "' . $month . '" 
	GROUP BY
		platform,
		userAccount,
		warehouse_sku 
	) a 
GROUP BY
	platform,
	userAccount,
	warehouse_sku 
ORDER BY
	platform,
	userAccount,
	warehouse_sku;
        ';
    }

    static public function getWarehouseRentSql($report_id): string
    {
        return '
SELECT
	sku,
	c.user_name user_name,
	SUM( total ) total 
FROM
	mu_finance_warehouse a
	LEFT JOIN mu_ecang_product b ON a.sku = b.productSku
	LEFT JOIN mu_ecang_user c ON b.personSellerId = c.id 
WHERE
	report_id = ' . $report_id . ' 
GROUP BY
	sku,
	user_name;    
        ';
    }
}
