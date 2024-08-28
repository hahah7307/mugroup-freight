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
	type,
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
	SUM( ddp ) ddp,
	SUM( tail ) tail 
FROM
	(
	SELECT
		"Order" AS type,
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
		f.sku_ddp_unit * b.qty ddp,
		NULL AS tail 
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
		LEFT JOIN mu_finance_order_outbound e ON b.saleOrderCode = e.saleOrderCode 
		AND b.warehouse_sku = e.warehouse_sku
		LEFT JOIN mu_finance_store f ON e.store_id = f.id UNION ALL
	SELECT
		"Order" AS type,
		a.platform,
		a.userAccount,
		a.payment_id payment,
		b.payment_id,
		b.saleOrderCode,
		b.platform_sku seller_sku,
		b.warehouse_sku,
		NULL AS sale_qty,
		NULL AS refund_qty,
		NULL AS sale_amount,
		NULL AS refund_amount,
		NULL AS sale_selling_fees,
		NULL AS refund_selling_fees,
		NULL AS fba_fees,
		NULL AS ddp,
		c.calcuRes tail 
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
		LEFT JOIN (
		SELECT
			a.saleOrderCode,
			a.calcuRes,
			b.warehouseSku 
		FROM
			mu_ecang_order a
			LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id 
		) c ON b.saleOrderCode = c.saleOrderCode 
		AND b.warehouse_sku = c.warehouseSku UNION ALL
	SELECT
		"Refund" AS type,
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
		NULL AS ddp,
		NULL AS tail 
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
	type,
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
	SUM( ROUND( sale_amount, 7 ) ) sale_amount,
	SUM( ROUND( refund_amount, 7 ) ) refund_amount,
	SUM( ROUND( sale_selling_fees, 7 ) ) sale_selling_fees,
	SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
	SUM( ROUND( fba_fees, 7 ) ) fba_fees,
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
		SUM( ROUND( sale_amount, 7 ) ) sale_amount,
		SUM( ROUND( refund_amount, 7 ) ) refund_amount,
		SUM( ROUND( sale_selling_fees, 7 ) ) sale_selling_fees,
		SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
		SUM( ROUND( fba_fees, 7 ) ) fba_fees,
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
			AND (description LIKE "%Coupon Redemption Fee%" 
			OR description LIKE "%Vine Enrollment Fee%") UNION ALL
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
		SUM(
			( a.fbaStorageFee + a.longTermStorageFee + a.sharedFbaDisposalFee + a.sharedAmazonPartneredCarrierShipmentFee + a.sharedFbaInboundConvenienceFee ) * e.pcr_percent * e.pcr_quantity 
		) * 0.01 fba_inventory,
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

    static public function getPaymentNoOutboundSql($report_id): string
    {
        return '
SELECT
	a.platform,
	a.userAccount,
	a.payment_id,
	a.fulfillment,
	a.sku,
	c.pcr_product_sku warehouse_sku,
	a.quantity * c.pcr_quantity quantity,
	a.payment_amount * c.pcr_percent * c.pcr_quantity / 100 payment_amount,
	a.payment_selling_fees * c.pcr_percent * c.pcr_quantity / 100 payment_selling_fees,
	a.payment_fba_fees * c.pcr_percent * c.pcr_quantity / 100 payment_fba_fees,
	a.outbound_amount * c.pcr_percent * c.pcr_quantity / 100 * - 1 outbound_amount,
	a.outbound_selling_fee * c.pcr_percent * c.pcr_quantity / 100 outbound_selling_fee,
	a.outbound_fba_fee * c.pcr_percent * c.pcr_quantity / 100 outbound_fba_fee 
FROM
	(
	SELECT
		c.platform,
		c.userAccount,
		a.payment_id,
		a.fulfillment,
		a.sku,
		a.quantity,
		b.warehouse_sku,
		a.payment_amount,
		a.selling_fees payment_selling_fees,
		a.fba_fees payment_fba_fees,
		b.sale_amount * - 1 outbound_amount,
		b.selling_fee outbound_selling_fee,
		b.fba_fee outbound_fba_fee 
	FROM
		(
		SELECT
			report_id,
			table_id,
			payment_id,
			fulfillment,
			sku,
			SUM( quantity ) quantity,
			SUM( product_sales + shipping_credits + gift_wrap_credits + regulatory_fee + promotional_rebates ) payment_amount,
			SUM( selling_fees ) selling_fees,
			SUM( fba_fees ) fba_fees 
		FROM
			mu_finance_order_sale 
		WHERE
			report_id = ' . $report_id . ' 
		GROUP BY
			report_id,
			table_id,
			payment_id,
			fulfillment,
			sku 
		) a
		LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id 
		AND a.sku = b.platform_sku
		LEFT JOIN mu_finance_table c ON a.table_id = c.id 
	) a
	LEFT JOIN mu_ecang_sku b ON a.sku = b.product_sku 
	AND a.userAccount = b.user_account
	LEFT JOIN mu_ecang_sku_relation c ON b.id = c.sku_id 
WHERE
	a.warehouse_sku IS NULL 
	OR a.payment_amount = 0 
	AND payment_amount != outbound_amount;
        ';
    }

    static public function getFbaWarehouseSkuSql($report_id, $month): string
    {
        return '
SELECT
	platform,
	userAccount,
	a.warehouse_sku,
	SUM( fba_sale_qty ) fba_sale_qty,
	SUM( fba_refund_qty ) fba_refund_qty,
	SUM( ROUND( fba_sale_amount, 7 ) ) fba_sale_amount,
	SUM( ROUND( fba_sale_tax, 7 ) ) fba_sale_tax,
	SUM( ROUND( fba_refund_amount, 7 ) ) fba_refund_amount,
	SUM( ROUND( fba_sale_selling_fees, 7 ) ) fba_sale_selling_fees,
	SUM( ROUND( fba_refund_selling_fees, 7 ) ) fba_refund_selling_fees,
	SUM( ROUND( fba_fees, 7 ) ) fba_fees,
	SUM( ROUND( fba_refund_fees, 7 ) ) fba_refund_fees,
	SUM( ROUND( fba_refund_other, 7 ) ) fba_refund_other,
	SUM( ROUND( fba_ddp, 2 ) ) fba_ddp,
	SUM( ROUND( fba_adCost, 7 ) ) fba_adCost,
	SUM( ROUND( fba_inventory, 4 ) ) fba_inventory,
	SUM( ROUND( adjustment, 6 ) ) adjustment,
	SUM( ROUND( liquidation, 6 ) ) liquidation,
	SUM( ROUND( promotion, 6 ) ) promotion,
	SUM( ROUND( shipping_service, 6 ) ) shipping_service,
	SUM( ROUND( operation_expenses, 6 ) ) operation_expenses,
	SUM( ROUND( operation_factory, 6 ) ) operation_factory,
	SUM( ROUND( operation_delivery, 6 ) ) operation_delivery,
	ROUND( SUM( IFNULL( fba_adCost, 0 ) ) / SUM( IFNULL( fba_sale_amount, 0 ) ) * - 1, 4 ) ad_percent,
	ROUND( SUM( IFNULL( fba_inventory, 0 ) ) / SUM( IFNULL( fba_sale_amount, 0 ) ) * - 1, 4 ) inventory_percent,
	ROUND( SUM( IFNULL( fba_fees, 0 ) ) / SUM( IFNULL( fba_sale_amount, 0 ) ) * - 1, 4 ) tail_percent,
	ROUND( SUM( IFNULL( fba_ddp, 0 ) ) / SUM( IFNULL( fba_sale_amount, 0 ) ) * - 1, 4 ) ddp_percent,
	ROUND(
		SUM( IFNULL( fba_sale_amount, 0 ) ) + SUM( IFNULL( fba_sale_tax, 0 ) ) + SUM( IFNULL( fba_refund_amount, 0 ) ) + SUM( IFNULL( fba_sale_selling_fees, 0 ) ) + SUM( IFNULL( fba_refund_selling_fees, 0 ) ) + SUM( IFNULL( fba_fees, 0 ) ) + SUM( IFNULL( fba_refund_fees, 0 ) ) + SUM( IFNULL( fba_refund_other, 0 ) ) + SUM( IFNULL( fba_ddp, 0 ) ) + SUM( IFNULL( fba_adCost, 0 ) ) + SUM( IFNULL( fba_inventory, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( liquidation, 0 ) ) + SUM( IFNULL( promotion, 0 ) ) + SUM( IFNULL( shipping_service, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ),
		2 
	) profit,
	ROUND(
		(
			SUM( IFNULL( fba_sale_amount, 0 ) ) + SUM( IFNULL( fba_sale_tax, 0 ) ) + SUM( IFNULL( fba_refund_amount, 0 ) ) + SUM( IFNULL( fba_sale_selling_fees, 0 ) ) + SUM( IFNULL( fba_refund_selling_fees, 0 ) ) + SUM( IFNULL( fba_fees, 0 ) ) + SUM( IFNULL( fba_refund_fees, 0 ) ) + SUM( IFNULL( fba_refund_other, 0 ) ) + SUM( IFNULL( fba_ddp, 0 ) ) + SUM( IFNULL( fba_adCost, 0 ) ) + SUM( IFNULL( fba_inventory, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( liquidation, 0 ) ) + SUM( IFNULL( promotion, 0 ) ) + SUM( IFNULL( shipping_service, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ) 
		) / SUM( IFNULL( fba_sale_amount, 0 ) ),
		4 
	) gross_profit_margin,
	SUM( ROUND( evaluation_qty, 3 ) ) evaluation_qty,
	SUM( ROUND( evaluation_amount, 2 ) ) evaluation_amount,
	ROUND(
		SUM( IFNULL( fba_sale_amount, 0 ) ) + SUM( IFNULL( fba_sale_tax, 0 ) ) + SUM( IFNULL( fba_refund_amount, 0 ) ) + SUM( IFNULL( fba_sale_selling_fees, 0 ) ) + SUM( IFNULL( fba_refund_selling_fees, 0 ) ) + SUM( IFNULL( fba_fees, 0 ) ) + SUM( IFNULL( fba_refund_fees, 0 ) ) + SUM( IFNULL( fba_refund_other, 0 ) ) + SUM( IFNULL( fba_ddp, 0 ) ) + SUM( IFNULL( fba_adCost, 0 ) ) + SUM( IFNULL( fba_inventory, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( liquidation, 0 ) ) + SUM( IFNULL( promotion, 0 ) ) + SUM( IFNULL( shipping_service, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ) + SUM( IFNULL( evaluation_amount, 0 ) ),
		2 
	) profit_include_evaluation,
	ROUND(
		(
			SUM( IFNULL( fba_sale_amount, 0 ) ) + SUM( IFNULL( fba_sale_tax, 0 ) ) + SUM( IFNULL( fba_refund_amount, 0 ) ) + SUM( IFNULL( fba_sale_selling_fees, 0 ) ) + SUM( IFNULL( fba_refund_selling_fees, 0 ) ) + SUM( IFNULL( fba_fees, 0 ) ) + SUM( IFNULL( fba_refund_fees, 0 ) ) + SUM( IFNULL( fba_refund_other, 0 ) ) + SUM( IFNULL( fba_ddp, 0 ) ) + SUM( IFNULL( fba_adCost, 0 ) ) + SUM( IFNULL( fba_inventory, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( liquidation, 0 ) ) + SUM( IFNULL( promotion, 0 ) ) + SUM( IFNULL( shipping_service, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ) + SUM( IFNULL( evaluation_amount, 0 ) ) 
		) / SUM( IFNULL( fba_sale_amount, 0 ) ),
		2 
	) gross_profit_margin_include_evaluation,
	b.product_name product_name,
	b.seller seller 
FROM
	(
	SELECT
		platform,
		userAccount,
		warehouse_sku,
		SUM( fba_sale_qty ) fba_sale_qty,
		SUM( fba_refund_qty ) * - 1 fba_refund_qty,
		SUM( ROUND( fba_sale_amount, 7 ) ) fba_sale_amount,
		SUM( ROUND( fba_sale_tax, 7 ) ) * - 1 fba_sale_tax,
		SUM( ROUND( fba_refund_amount, 7 ) ) fba_refund_amount,
		SUM( ROUND( fba_sale_selling_fees, 7 ) ) * - 1 fba_sale_selling_fees,
		SUM( ROUND( fba_refund_selling_fees, 7 ) ) fba_refund_selling_fees,
		SUM( ROUND( fba_fees, 7 ) ) * - 1 fba_fees,
		SUM( ROUND( fba_refund_fees, 7 ) ) fba_refund_fees,
		SUM( ROUND( fba_refund_other, 7 ) ) fba_refund_other,
		SUM( ROUND( fba_ddp, 2 ) ) * - 1 fba_ddp,
		SUM( ROUND( fba_adCost, 7 ) ) fba_adCost,
		SUM( ROUND( fba_inventory, 4 ) ) fba_inventory,
		SUM( ROUND( adjustment, 6 ) ) adjustment,
		SUM( ROUND( liquidation, 6 ) ) liquidation,
		SUM( ROUND( promotion, 6 ) ) promotion,
		SUM( ROUND( shipping_service, 6 ) ) shipping_service,
		SUM( ROUND( operation_expenses, 6 ) ) operation_expenses,
		SUM( ROUND( operation_factory, 6 ) ) operation_factory,
		SUM( ROUND( operation_delivery, 6 ) ) operation_delivery,
		SUM( ROUND( evaluation_qty, 3 ) ) evaluation_qty,
		SUM( ROUND( evaluation_amount, 2 ) ) * - 1 evaluation_amount 
	FROM
		(
		SELECT
			platform,
			userAccount,
			warehouse_sku,
			SUM( fba_sale_qty ) fba_sale_qty,
			SUM( fba_refund_qty ) fba_refund_qty,
			SUM( ROUND( fba_sale_amount, 7 ) ) fba_sale_amount,
			SUM( ROUND( fba_sale_tax, 7 ) ) fba_sale_tax,
			SUM( ROUND( fba_refund_amount, 7 ) ) fba_refund_amount,
			SUM( ROUND( fba_sale_selling_fees, 7 ) ) fba_sale_selling_fees,
			SUM( ROUND( fba_refund_selling_fees, 7 ) ) fba_refund_selling_fees,
			SUM( ROUND( fba_fees, 7 ) ) fba_fees,
			SUM( ROUND( fba_refund_fees, 7 ) ) fba_refund_fees,
			SUM( ROUND( fba_refund_other, 7 ) ) fba_refund_other,
			SUM( ROUND( fba_ddp, 2 ) ) fba_ddp,
			NULL AS fba_adCost,
			SUM( ROUND( fba_inventory, 4 ) ) fba_inventory,
			SUM( ROUND( adjustment, 6 ) ) adjustment,
			SUM( ROUND( liquidation, 6 ) ) liquidation,
			SUM( ROUND( promotion, 6 ) ) promotion,
			SUM( ROUND( shipping_service, 6 ) ) shipping_service,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
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
				b.qty fba_sale_qty,
				NULL AS fba_refund_qty,
				ROUND( b.sale_amount, 7 ) fba_sale_amount,
				NULL AS fba_sale_tax,
				NULL AS fba_refund_amount,
				ROUND( b.selling_fee, 7 ) fba_sale_selling_fees,
				NULL AS fba_refund_selling_fees,
				ROUND( b.fba_fee, 7 ) fba_fees,
				NULL AS fba_refund_fees,
				NULL AS fba_refund_other,
				NULL AS fba_ddp,
				NULL AS adCost,
				NULL AS fba_inventory,
				NULL AS adjustment,
				NULL AS liquidation,
				NULL AS promotion,
				NULL AS shipping_service 
			FROM
				(
				SELECT DISTINCT
					report_id,
					payment_id,
					platform,
					userAccount 
				FROM
					mu_finance_order_sale a
					LEFT JOIN mu_finance_table b ON a.table_id = b.id 
				WHERE
					report_id = ' . $report_id . ' 
					AND a.fulfillment = "Amazon" 
					AND b.platform = "amazon" 
				) a
				LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id 
			WHERE
				b.payment_id IS NOT NULL UNION ALL
			SELECT
				a.platform,
				a.userAccount,
				a.payment_id payment,
				b.payment_id,
				b.saleOrderCode,
				b.seller_sku,
				b.warehouse_sku,
				NULL AS fba_sale_qty,
				NULL AS fba_refund_qty,
				NULL AS fba_sale_amount,
				ROUND( b.tax, 7 ) fba_sale_tax,
				NULL AS fba_refund_amount,
				NULL AS fba_sale_selling_fees,
				NULL AS fba_refund_selling_fees,
				NULL AS fba_fees,
				NULL AS fba_refund_fees,
				NULL AS fba_refund_other,
				NULL AS fba_ddp,
				NULL AS adCost,
				NULL AS fba_inventory,
				NULL AS adjustment,
				NULL AS liquidation,
				NULL AS promotion,
				NULL AS shipping_service 
			FROM
				(
				SELECT DISTINCT
					report_id,
					payment_id,
					platform,
					userAccount 
				FROM
					mu_finance_order_sale a
					LEFT JOIN mu_finance_table b ON a.table_id = b.id 
				WHERE
					report_id = ' . $report_id . ' 
					AND a.fulfillment = "Amazon" 
					AND b.platform = "amazon" 
					AND b.country = "EUROPE" 
				) a
				LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id UNION ALL
			SELECT
				a.platform,
				a.userAccount,
				a.payment_id payment,
				b.payment_id,
				b.saleOrderCode,
				b.seller_sku,
				b.warehouse_sku,
				NULL AS fba_sale_qty,
				NULL AS fba_refund_qty,
				NULL AS fba_sale_amount,
				NULL AS fba_sale_tax,
				NULL AS fba_refund_amount,
				NULL AS fba_sale_selling_fees,
				NULL AS fba_refund_selling_fees,
				NULL AS fba_fees,
				NULL AS fba_refund_fees,
				NULL AS fba_refund_other,
				c.sku_ddp_unit * b.qty / d.USD fba_ddp,
				NULL AS adCost,
				NULL AS fba_inventory,
				NULL AS adjustment,
				NULL AS liquidation,
				NULL AS promotion,
				NULL AS shipping_service 
			FROM
				(
				SELECT DISTINCT
					report_id,
					payment_id,
					platform,
					userAccount 
				FROM
					mu_finance_order_sale a
					LEFT JOIN mu_finance_table b ON a.table_id = b.id 
				WHERE
					report_id = ' . $report_id . ' 
					AND a.fulfillment = "Amazon" 
					AND b.platform = "amazon" 
				) a
				LEFT JOIN mu_finance_order_outbound b ON b.payment_id = a.payment_id 
				AND b.report_id = ' . $report_id . '
				LEFT JOIN mu_finance_store c ON b.store_id = c.id
				LEFT JOIN mu_finance_report d ON b.report_id = d.id 
			WHERE
				b.payment_id IS NOT NULL UNION ALL
			SELECT
				b.platform,
				b.userAccount,
				a.payment_id payment,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				a.sku seller_sku,
				d.pcr_product_sku warehouse_sku,
				NULL AS fba_sale_qty,
				a.quantity * d.pcr_quantity fba_refund_qty,
				NULL AS fba_sale_amount,
				NULL AS fba_sale_tax,
				ROUND( ( product_sales + shipping_credits + gift_wrap_credits + regulatory_fee + promotional_rebates ) * d.pcr_percent * d.pcr_quantity / 100, 7 ) fba_refund_amount,
				NULL AS fba_sale_selling_fees,
				ROUND( selling_fees * d.pcr_percent * d.pcr_quantity / 100, 7 ) fba_refund_selling_fees,
				NULL AS fba_fees,
				ROUND( fba_fees * d.pcr_percent * d.pcr_quantity / 100, 7 ) fba_refund_fees,
				ROUND( other * d.pcr_percent * d.pcr_quantity / 100, 7 ) fba_refund_other,
				NULL AS fba_ddp,
				NULL AS adCost,
				NULL AS fba_inventory,
				NULL AS adjustment,
				NULL AS liquidation,
				NULL AS promotion,
				NULL AS shipping_service 
			FROM
				mu_finance_order_refund a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN mu_ecang_sku c ON a.sku = c.product_sku 
				AND b.userAccount = c.user_account
				LEFT JOIN mu_ecang_sku_relation d ON c.id = d.sku_id 
			WHERE
				report_id = ' . $report_id . ' 
				AND a.fulfillment = "Amazon" 
				AND b.platform = "amazon" UNION ALL
			SELECT
				b.platform,
				b.userAccount,
				a.payment_id payment,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				a.sku seller_sku,
				c.warehouse_sku warehouse_sku,
				NULL AS fba_sale_qty,
				NULL AS fba_refund_qty,
				NULL AS fba_sale_amount,
				NULL AS fba_sale_tax,
				NULL AS fba_refund_amount,
				NULL AS fba_sale_selling_fees,
				NULL AS fba_refund_selling_fees,
				NULL AS fba_fees,
				NULL AS fba_ddp,
				NULL AS fba_refund_fees,
				NULL AS fba_refund_other,
				NULL AS adCost,
				NULL AS fba_inventory,
				NULL AS adjustment,
				NULL AS liquidation,
				NULL AS promotion,
				c.total shipping_service 
			FROM
				mu_finance_order_shipping_service a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN mu_finance_order_share c ON a.share_code = c.share_code 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.fulfillment = "FBA" 
				AND b.platform = "amazon" UNION ALL
			SELECT
				b.platform,
				b.userAccount,
				a.payment_id payment,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				a.sku seller_sku,
				c.warehouse_sku warehouse_sku,
				NULL AS fba_sale_qty,
				NULL AS fba_refund_qty,
				NULL AS fba_sale_amount,
				NULL AS fba_sale_tax,
				NULL AS fba_refund_amount,
				NULL AS fba_sale_selling_fees,
				NULL AS fba_refund_selling_fees,
				NULL AS fba_fees,
				NULL AS fba_refund_fees,
				NULL AS fba_refund_other,
				NULL AS fba_ddp,
				NULL AS adCost,
				NULL AS fba_inventory,
				c.total adjustment,
				NULL AS liquidation,
				NULL AS promotion,
				NULL AS shipping_service 
			FROM
				mu_finance_order_adjustment a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN mu_finance_order_share c ON a.share_code = c.share_code 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.fulfillment = "FBA" 
				AND b.platform = "amazon" UNION ALL
			SELECT
				b.platform,
				b.userAccount,
				NULL AS payment,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				c.seller_sku seller_sku,
				c.warehouse_sku warehouse_sku,
				NULL AS fba_sale_qty,
				NULL AS fba_refund_qty,
				NULL AS fba_sale_amount,
				NULL AS fba_sale_tax,
				NULL AS fba_refund_amount,
				NULL AS fba_sale_selling_fees,
				NULL AS fba_refund_selling_fees,
				NULL AS fba_fees,
				NULL AS fba_refund_fees,
				NULL AS fba_refund_other,
				NULL AS fba_ddp,
				NULL AS adCost,
				NULL AS fba_inventory,
				NULL AS adjustment,
				c.total liquidation,
				NULL AS promotion,
				NULL AS shipping_service 
			FROM
				mu_finance_order_liquidation a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN mu_finance_order_share c ON a.share_code = c.share_code 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.fulfillment = "FBA" 
				AND b.platform = "amazon" UNION ALL
			SELECT
				"amazon" AS platform,
				a.user_account userAccount,
				NULL AS payment,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				a.warehouse_sku warehouse_sku,
				NULL AS fba_sale_qty,
				NULL AS fba_refund_qty,
				NULL AS fba_sale_amount,
				NULL AS fba_sale_tax,
				NULL AS fba_refund_amount,
				NULL AS fba_sale_selling_fees,
				NULL AS fba_refund_selling_fees,
				NULL AS fba_fees,
				NULL AS fba_ddp,
				NULL AS fba_refund_fees,
				NULL AS fba_refund_other,
				NULL AS adCost,
				NULL AS fba_inventory,
				NULL AS adjustment,
				NULL AS liquidation,
				b.total promotion,
				NULL AS shipping_service 
			FROM
				(
				SELECT
					report_id,
					user_account,
					warehouse_sku,
					share_code,
					SUM( promotion ) total 
				FROM
					mu_finance_order_additional 
				WHERE
					promotion IS NOT NULL 
					AND report_id = ' . $report_id . ' 
				GROUP BY
					report_id,
					user_account,
					warehouse_sku,
					share_code 
				ORDER BY
					report_id,
					user_account,
					warehouse_sku,
					share_code 
				) a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
			WHERE
				b.fulfillment = "FBA" 
			) a 
		GROUP BY
			platform,
			userAccount,
			warehouse_sku UNION ALL
		SELECT
			"amazon" AS platform,
			c.ecang_user_account userAccount,
			d.warehouse_sku,
			NULL AS fba_sale_qty,
			NULL AS fba_refund_qty,
			NULL AS fba_sale_amount,
			NULL AS fba_sale_tax,
			NULL AS fba_refund_amount,
			NULL AS fba_sale_selling_fees,
			NULL AS fba_refund_selling_fees,
			NULL AS fba_fees,
			NULL AS fba_refund_fees,
			NULL AS fba_refund_other,
			NULL AS fba_ddp,
			SUM( a.totalAdsCost * d.percent * d.qty ) fba_adCost,
			NULL AS fba_inventory,
			NULL AS adjustment,
			NULL AS liquidation,
			NULL AS promotion,
			NULL AS shipping_service,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_ak_ad_cost a
			LEFT JOIN mu_ak_seller b ON b.sid = a.sid
			LEFT JOIN mu_ecang_ak_user_account_relation c ON c.ak_user_account = b.`name`
			LEFT JOIN ( SELECT DISTINCT user_account, seller_sku, warehouse_sku, percent, qty FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) d ON d.user_account = c.ecang_user_account 
			AND a.msku = d.seller_sku 
		WHERE
			reportDateMonth = "' . $month . '" 
			AND a.totalAdsCost != 0 
			AND is_fba = 1 
		GROUP BY
			platform,
			userAccount,
			warehouse_sku UNION ALL
		SELECT
			"amazon" AS platform,
			c.ecang_user_account userAccount,
			d.warehouse_sku,
			NULL AS fba_sale_qty,
			NULL AS fba_refund_qty,
			NULL AS fba_sale_amount,
			NULL AS fba_sale_tax,
			NULL AS fba_refund_amount,
			NULL AS fba_sale_selling_fees,
			NULL AS fba_refund_selling_fees,
			NULL AS fba_fees,
			NULL AS fba_refund_fees,
			NULL AS fba_refund_other,
			NULL AS fba_ddp,
			NULL AS fba_adCost,
			SUM(
				(
					a.sharedLabelingFee + a.fbaStorageFee + a.longTermStorageFee + a.sharedFbaDisposalFee + a.sharedAmazonPartneredCarrierShipmentFee + a.sharedFbaInboundConvenienceFee + a.sharedFbaInboundDefectFee 
				) * d.percent * d.qty 
			) fba_inventory,
			NULL AS adjustment,
			NULL AS liquidation,
			NULL AS promotion,
			NULL AS shipping_service,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_ak_ad_cost a
			LEFT JOIN mu_ak_seller b ON b.sid = a.sid
			LEFT JOIN mu_ecang_ak_user_account_relation c ON c.ak_user_account = b.`name`
			LEFT JOIN ( SELECT DISTINCT user_account, seller_sku, warehouse_sku, percent, qty FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) d ON d.user_account = c.ecang_user_account 
			AND a.msku = d.seller_sku 
		WHERE
			reportDateMonth = "' . $month . '" 
			AND a.sharedLabelingFee + a.fbaStorageFee + a.longTermStorageFee + a.sharedFbaDisposalFee + a.sharedAmazonPartneredCarrierShipmentFee + a.sharedFbaInboundConvenienceFee + a.sharedFbaInboundDefectFee != 0 
			AND d.warehouse_sku IS NOT NULL 
		GROUP BY
			platform,
			userAccount,
			warehouse_sku UNION ALL
		SELECT
			"amazon" AS platform,
			d.userAccount userAccount,
			a.warehouse_sku warehouse_sku,
			NULL AS fba_sale_qty,
			NULL AS fba_refund_qty,
			NULL AS fba_sale_amount,
			NULL AS fba_sale_tax,
			NULL AS fba_refund_amount,
			NULL AS fba_sale_selling_fees,
			NULL AS fba_refund_selling_fees,
			NULL AS fba_fees,
			NULL AS fba_refund_fees,
			NULL AS fba_refund_other,
			NULL AS fba_ddp,
			NULL AS fba_adCost,
			NULL AS fba_inventory,
			NULL AS adjustment,
			NULL AS liquidation,
			NULL AS promotion,
			NULL AS shipping_service,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			b.qty evaluation_qty,
			ROUND( a.cny_actual_paid / c.USD, 2 ) evaluation_amount 
		FROM
			mu_finance_evaluation a
			LEFT JOIN mu_finance_order_statistics b ON a.payment = b.saleOrderCode
			LEFT JOIN mu_finance_report c ON a.report_id = c.id
			LEFT JOIN mu_ecang_order d ON a.payment = d.saleOrderCode 
		WHERE
			d.fulfillmentType = 1 
			AND a.report_id = ' . $report_id . ' 
			AND b.platform = "amazon" UNION ALL
		SELECT
			c.platform AS platform,
			c.userAccount userAccount,
			b.warehouse_sku warehouse_sku,
			NULL AS fba_sale_qty,
			NULL AS fba_refund_qty,
			NULL AS fba_sale_amount,
			NULL AS fba_sale_tax,
			NULL AS fba_refund_amount,
			NULL AS fba_sale_selling_fees,
			NULL AS fba_refund_selling_fees,
			NULL AS fba_fees,
			NULL AS fba_refund_fees,
			NULL AS fba_refund_other,
			NULL AS fba_ddp,
			NULL AS fba_adCost,
			NULL AS fba_inventory,
			NULL AS adjustment,
			NULL AS liquidation,
			NULL AS promotion,
			NULL AS shipping_service,
			ROUND( b.total / d.USD, 2) operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_expenses a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			LEFT JOIN mu_finance_report d ON a.report_id = d.id
		WHERE
			a.report_id = ' . $report_id . ' 
			AND b.fulfillment = "FBA" 
			AND c.platform = "amazon" UNION ALL
		SELECT
			c.platform AS platform,
			c.userAccount userAccount,
			b.warehouse_sku warehouse_sku,
			NULL AS fba_sale_qty,
			NULL AS fba_refund_qty,
			NULL AS fba_sale_amount,
			NULL AS fba_sale_tax,
			NULL AS fba_refund_amount,
			NULL AS fba_sale_selling_fees,
			NULL AS fba_refund_selling_fees,
			NULL AS fba_fees,
			NULL AS fba_refund_fees,
			NULL AS fba_refund_other,
			NULL AS fba_ddp,
			NULL AS fba_adCost,
			NULL AS fba_inventory,
			NULL AS adjustment,
			NULL AS liquidation,
			NULL AS promotion,
			NULL AS shipping_service,
			NULL AS operation_expenses,
			ROUND( b.total / d.USD, 2) operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_factory a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			LEFT JOIN mu_finance_report d ON a.report_id = d.id
		WHERE
			a.report_id = ' . $report_id . ' 
			AND b.fulfillment = "FBA" 
			AND c.platform = "amazon" UNION ALL
		SELECT
			c.platform AS platform,
			c.userAccount userAccount,
			b.warehouse_sku warehouse_sku,
			NULL AS fba_sale_qty,
			NULL AS fba_refund_qty,
			NULL AS fba_sale_amount,
			NULL AS fba_sale_tax,
			NULL AS fba_refund_amount,
			NULL AS fba_sale_selling_fees,
			NULL AS fba_refund_selling_fees,
			NULL AS fba_fees,
			NULL AS fba_refund_fees,
			NULL AS fba_refund_other,
			NULL AS fba_ddp,
			NULL AS fba_adCost,
			NULL AS fba_inventory,
			NULL AS adjustment,
			NULL AS liquidation,
			NULL AS promotion,
			NULL AS shipping_service,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			ROUND( b.total / d.USD, 2) operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_delivery a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			LEFT JOIN mu_finance_report d ON a.report_id = d.id
		WHERE
			a.report_id = ' . $report_id . ' 
			AND b.fulfillment = "FBA" 
			AND c.platform = "amazon" 
		) a 
	GROUP BY
		platform,
		userAccount,
		warehouse_sku 
	ORDER BY
		platform,
		userAccount,
		warehouse_sku 
	) a
	LEFT JOIN ( SELECT DISTINCT user_account, warehouse_sku, seller, product_name FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) b ON a.userAccount = b.user_account 
	AND a.warehouse_sku = b.warehouse_sku 
GROUP BY
	platform,
	userAccount,
	warehouse_sku,
	fba_sale_amount,
	product_name,
	seller;
        ';
    }

    static public function getFbmWarehouseSkuSql($report_id, $month): string
    {
        return '
SELECT
	platform,
	userAccount,
	a.warehouse_sku,
	SUM( fbm_sale_qty ) fbm_sale_qty,
	SUM( fbm_refund_qty ) fbm_refund_qty,
	SUM( ROUND( fbm_sale_amount, 7 ) ) fbm_sale_amount,
	SUM( ROUND( fbm_sale_tax, 7 ) ) fbm_sale_tax,
	SUM( ROUND( fbm_refund_amount, 7 ) ) fbm_refund_amount,
	SUM( ROUND( fbm_sale_selling_fees, 7 ) ) fbm_sale_selling_fees,
	SUM( ROUND( fbm_refund_selling_fees, 7 ) ) fbm_refund_selling_fees,
	SUM( ROUND( fbm_refund_other, 7 ) ) fbm_refund_other,
	SUM( ROUND( calcuRes, 2 ) ) calcuRes,
	SUM( ROUND( fbm_ddp, 2 ) ) fbm_ddp,
	SUM( ROUND( fbm_adCost, 7 ) ) fbm_adCost,
	SUM( ROUND( warehouse_rent, 6 ) ) warehouse_rent,
	SUM( ROUND( adjustment, 6 ) ) adjustment,
	SUM( ROUND( liquidation, 6 ) ) liquidation,
	SUM( ROUND( promotion, 6 ) ) promotion,
	SUM( ROUND( shipping_service, 6 ) ) shipping_service,
	SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
	SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
	SUM( ROUND( operation_expenses, 6 ) ) operation_expenses,
	SUM( ROUND( operation_factory, 6 ) ) operation_factory,
	SUM( ROUND( operation_delivery, 6 ) ) operation_delivery,
	ROUND( SUM( IFNULL( fbm_adCost, 0 ) ) / SUM( IFNULL( fbm_sale_amount, 0 ) ) * - 1, 4 ) ad_percent,
	ROUND( SUM( IFNULL( warehouse_rent, 0 ) ) / SUM( IFNULL( fbm_sale_amount, 0 ) ) * - 1, 4 ) inventory_percent,
	ROUND( SUM( IFNULL( calcuRes, 0 ) ) / SUM( IFNULL( fbm_sale_amount, 0 ) ) * - 1, 4 ) tail_percent,
	ROUND( SUM( IFNULL( fbm_ddp, 0 ) ) / SUM( IFNULL( fbm_sale_amount, 0 ) ) * - 1, 4 ) ddp_percent,
	ROUND(
		SUM( IFNULL( fbm_sale_amount, 0 ) ) + SUM( IFNULL( fbm_sale_tax, 0 ) ) + SUM( IFNULL( fbm_refund_amount, 0 ) ) + SUM( IFNULL( fbm_sale_selling_fees, 0 ) ) + SUM( IFNULL( fbm_refund_selling_fees, 0 ) ) + SUM( IFNULL( fbm_refund_other, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( fbm_ddp, 0 ) ) + SUM( IFNULL( fbm_adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( liquidation, 0 ) ) + SUM( IFNULL( promotion, 0 ) ) + SUM( IFNULL( shipping_service, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ),
		2 
	) profit,
	ROUND(
		(
			SUM( IFNULL( fbm_sale_amount, 0 ) ) + SUM( IFNULL( fbm_sale_tax, 0 ) ) + SUM( IFNULL( fbm_refund_amount, 0 ) ) + SUM( IFNULL( fbm_sale_selling_fees, 0 ) ) + SUM( IFNULL( fbm_refund_selling_fees, 0 ) ) + SUM( IFNULL( fbm_refund_other, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( fbm_ddp, 0 ) ) + SUM( IFNULL( fbm_adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( liquidation, 0 ) ) + SUM( IFNULL( promotion, 0 ) ) + SUM( IFNULL( shipping_service, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ) 
		) / SUM( IFNULL( fbm_sale_amount, 0 ) ),
		4 
	) gross_profit_margin,
	SUM( ROUND( evaluation_qty, 3 ) ) evaluation_qty,
	SUM( ROUND( evaluation_amount, 2 ) ) evaluation_amount,
	ROUND(
		SUM( IFNULL( fbm_sale_amount, 0 ) ) + SUM( IFNULL( fbm_sale_tax, 0 ) ) + SUM( IFNULL( fbm_refund_amount, 0 ) ) + SUM( IFNULL( fbm_sale_selling_fees, 0 ) ) + SUM( IFNULL( fbm_refund_selling_fees, 0 ) ) + SUM( IFNULL( fbm_refund_other, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( fbm_ddp, 0 ) ) + SUM( IFNULL( fbm_adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( liquidation, 0 ) ) + SUM( IFNULL( promotion, 0 ) ) + SUM( IFNULL( shipping_service, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( evaluation_amount, 0 ) ),
		2 
	) profit_include_evaluation,
	ROUND(
		(
			SUM( IFNULL( fbm_sale_amount, 0 ) ) + SUM( IFNULL( fbm_sale_tax, 0 ) ) + SUM( IFNULL( fbm_refund_amount, 0 ) ) + SUM( IFNULL( fbm_sale_selling_fees, 0 ) ) + SUM( IFNULL( fbm_refund_selling_fees, 0 ) ) + SUM( IFNULL( fbm_refund_other, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( fbm_ddp, 0 ) ) + SUM( IFNULL( fbm_adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( liquidation, 0 ) ) + SUM( IFNULL( promotion, 0 ) ) + SUM( IFNULL( shipping_service, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ) + SUM( IFNULL( evaluation_amount, 0 ) ) 
		) / SUM( IFNULL( fbm_sale_amount, 0 ) ),
		4 
	) gross_profit_margin_include_evaluation,
	b.product_name product_name,
	b.seller seller 
FROM
	(
	SELECT
		platform,
		userAccount,
		warehouse_sku,
		SUM( fbm_sale_qty ) fbm_sale_qty,
		SUM( fbm_refund_qty ) fbm_refund_qty,
		SUM( ROUND( fbm_sale_amount, 7 ) ) fbm_sale_amount,
		SUM( ROUND( fbm_sale_tax, 7 ) ) fbm_sale_tax,
		SUM( ROUND( fbm_refund_amount, 7 ) ) fbm_refund_amount,
		SUM( ROUND( fbm_sale_selling_fees, 7 ) ) fbm_sale_selling_fees,
		SUM( ROUND( fbm_refund_selling_fees, 7 ) ) fbm_refund_selling_fees,
		SUM( ROUND( fbm_refund_other, 7 ) ) fbm_refund_other,
		SUM( ROUND( calcuRes, 2 ) ) calcuRes,
		SUM( ROUND( fbm_ddp, 2 ) ) fbm_ddp,
		SUM( ROUND( fbm_adCost, 7 ) ) fbm_adCost,
		SUM( ROUND( warehouse_rent, 6 ) ) warehouse_rent,
		SUM( ROUND( adjustment, 6 ) ) adjustment,
		SUM( ROUND( liquidation, 6 ) ) liquidation,
		SUM( ROUND( promotion, 6 ) ) promotion,
		SUM( ROUND( shipping_service, 6 ) ) shipping_service,
		SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
		SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
		SUM( ROUND( operation_expenses, 6 ) ) operation_expenses,
		SUM( ROUND( operation_factory, 6 ) ) operation_factory,
		SUM( ROUND( operation_delivery, 6
		     ) ) operation_delivery,
		SUM( ROUND( evaluation_qty, 3 ) ) evaluation_qty,
		SUM( ROUND( evaluation_amount, 2 ) ) * - 1 evaluation_amount 
	FROM
		(
		SELECT
			platform,
			userAccount,
			warehouse_sku,
			SUM( fbm_sale_qty ) fbm_sale_qty,
			SUM( fbm_refund_qty ) * - 1 fbm_refund_qty,
			SUM( ROUND( fbm_sale_amount, 7 ) ) fbm_sale_amount,
			SUM( ROUND( fbm_sale_tax, 7 ) ) fbm_sale_tax,
			SUM( ROUND( fbm_refund_amount, 7 ) ) fbm_refund_amount,
			SUM( ROUND( fbm_sale_selling_fees, 7 ) ) * - 1 fbm_sale_selling_fees,
			SUM( ROUND( fbm_refund_selling_fees, 7 ) ) fbm_refund_selling_fees,
			SUM( ROUND( fbm_refund_other, 7 ) ) fbm_refund_other,
			SUM( ROUND( calcuRes, 2 ) ) * - 1 calcuRes,
			SUM( ROUND( fbm_ddp, 2 ) ) * - 1 fbm_ddp,
			NULL AS fbm_adCost,
			SUM( ROUND( warehouse_rent, 6 ) ) * - 1 warehouse_rent,
			SUM( ROUND( adjustment, 6 ) ) adjustment,
			SUM( ROUND( liquidation, 6 ) ) liquidation,
			SUM( ROUND( promotion, 6 ) ) promotion,
			SUM( ROUND( shipping_service, 6 ) ) shipping_service,
			SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
			SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
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
				b.qty fbm_sale_qty,
				NULL AS fbm_refund_qty,
				ROUND( b.sale_amount, 7 ) fbm_sale_amount,
				NULL AS fbm_sale_tax,
				NULL AS fbm_refund_amount,
				ROUND( b.selling_fee, 7 ) fbm_sale_selling_fees,
				NULL AS fbm_refund_selling_fees,
				NULL AS fbm_refund_other,
				c.calcuRes calcuRes,
				NULL AS fbm_ddp,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS liquidation,
				NULL AS promotion,
				NULL AS shipping_service,
				NULL AS lc_adjustment,
				NULL AS le_adjustment 
			FROM
				(
				SELECT DISTINCT
					report_id,
					payment_id,
					platform,
					userAccount 
				FROM
					mu_finance_order_sale a
					LEFT JOIN mu_finance_table b ON a.table_id = b.id 
				WHERE
					report_id = ' . $report_id . ' 
					AND b.platform = "amazon" 
					AND ( a.fulfillment = "Seller" OR a.fulfillment IS NULL ) 
				) a
				LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id
				LEFT JOIN mu_ecang_order c ON b.saleOrderCode = c.saleOrderCode 
			WHERE
				b.payment_id IS NOT NULL UNION ALL
			SELECT
				a.platform,
				a.userAccount,
				a.payment_id payment,
				b.payment_id,
				b.saleOrderCode,
				b.seller_sku,
				b.warehouse_sku,
				NULL AS fbm_sale_qty,
				NULL AS fbm_refund_qty,
				NULL AS fbm_sale_amount,
				ROUND( b.tax, 7 ) fbm_sale_tax,
				NULL AS fbm_refund_amount,
				NULL AS fbm_sale_selling_fees,
				NULL AS fbm_refund_selling_fees,
				NULL AS fbm_refund_other,
				NULL AS calcuRes,
				NULL AS fbm_ddp,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS liquidation,
				NULL AS promotion,
				NULL AS shipping_service,
				NULL AS lc_adjustment,
				NULL AS le_adjustment 
			FROM
				(
				SELECT DISTINCT
					report_id,
					payment_id,
					platform,
					userAccount 
				FROM
					mu_finance_order_sale a
					LEFT JOIN mu_finance_table b ON a.table_id = b.id 
				WHERE
					report_id = ' . $report_id . ' 
					AND b.platform = "amazon" 
					AND b.country = "EUROPE" 
					AND ( a.fulfillment = "Seller" OR a.fulfillment IS NULL ) 
				) a
				LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id
				LEFT JOIN mu_ecang_order c ON b.saleOrderCode = c.saleOrderCode 
			WHERE
				b.payment_id IS NOT NULL UNION ALL
			SELECT
				a.platform,
				a.userAccount,
				a.payment_id payment,
				b.payment_id,
				b.saleOrderCode,
				b.seller_sku,
				b.warehouse_sku,
				NULL AS fbm_sale_qty,
				NULL AS fbm_refund_qty,
				NULL AS fbm_sale_amount,
				NULL AS fbm_sale_tax,
				NULL AS fbm_refund_amount,
				NULL AS fbm_sale_selling_fees,
				NULL AS fbm_refund_selling_fees,
				NULL AS fbm_refund_other,
				NULL AS calcuRes,
				c.sku_ddp_unit * b.qty / d.USD fbm_ddp,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS liquidation,
				NULL AS promotion,
				NULL AS shipping_service,
				NULL AS lc_adjustment,
				NULL AS le_adjustment 
			FROM
				(
				SELECT DISTINCT
					report_id,
					payment_id,
					platform,
					userAccount 
				FROM
					mu_finance_order_sale a
					LEFT JOIN mu_finance_table b ON a.table_id = b.id 
				WHERE
					report_id = ' . $report_id . ' 
					AND b.platform = "amazon" 
					AND ( a.fulfillment = "Seller" OR a.fulfillment IS NULL ) 
				) a
				LEFT JOIN mu_finance_order_outbound b ON b.payment_id = a.payment_id 
				AND b.report_id = ' . $report_id . '
				LEFT JOIN mu_finance_store c ON b.store_id = c.id
				LEFT JOIN mu_finance_report d ON b.report_id = d.id 
			WHERE
				b.payment_id IS NOT NULL UNION ALL
			SELECT
				b.platform,
				b.userAccount,
				a.payment_id payment,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				a.sku seller_sku,
				d.pcr_product_sku warehouse_sku,
				NULL AS fbm_sale_qty,
				a.quantity * d.pcr_quantity fbm_refund_qty,
				NULL AS fbm_sale_amount,
				NULL AS fbm_sale_tax,
				ROUND( ( product_sales + shipping_credits + gift_wrap_credits + regulatory_fee + promotional_rebates ) * d.pcr_percent * d.pcr_quantity / 100, 7 ) fbm_refund_amount,
				NULL AS fbm_sale_selling_fees,
				ROUND( selling_fees * d.pcr_percent * d.pcr_quantity / 100, 7 ) fbm_refund_selling_fees,
				ROUND( other * d.pcr_percent * d.pcr_quantity / 100, 7 ) fbm_refund_other,
				NULL AS calcuRes,
				NULL AS fbm_ddp,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS liquidation,
				NULL AS promotion,
				NULL AS shipping_service,
				NULL AS lc_adjustment,
				NULL AS le_adjustment 
			FROM
				mu_finance_order_refund a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN mu_ecang_sku c ON a.sku = c.product_sku 
				AND b.userAccount = c.user_account
				LEFT JOIN mu_ecang_sku_relation d ON c.id = d.sku_id 
			WHERE
				report_id = ' . $report_id . ' 
				AND b.platform = "amazon" 
				AND ( a.fulfillment = "Seller" OR a.fulfillment IS NULL ) UNION ALL
			SELECT
				"amazon" AS platform,
				userAccount userAccount,
				NULL AS payment,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				warehouse_sku warehouse_sku,
				NULL AS fbm_sale_qty,
				NULL AS fbm_refund_qty,
				NULL AS fbm_sale_amount,
				NULL AS fbm_sale_tax,
				NULL AS fbm_refund_amount,
				NULL AS fbm_sale_selling_fees,
				NULL AS fbm_refund_selling_fees,
				NULL AS fbm_refund_other,
				NULL AS calcuRes,
				NULL AS fbm_ddp,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS liquidation,
				NULL AS promotion,
				c.total shipping_service,
				NULL AS lc_adjustment,
				NULL AS le_adjustment 
			FROM
				mu_finance_order_shipping_service a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN mu_finance_order_share c ON a.share_code = c.share_code 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.fulfillment = "FBM" 
				AND b.platform = "amazon" UNION ALL
			SELECT
				"amazon" AS platform,
				userAccount userAccount,
				NULL AS payment,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				warehouse_sku warehouse_sku,
				NULL AS fbm_sale_qty,
				NULL AS fbm_refund_qty,
				NULL AS fbm_sale_amount,
				NULL AS fbm_sale_tax,
				NULL AS fbm_refund_amount,
				NULL AS fbm_sale_selling_fees,
				NULL AS fbm_refund_selling_fees,
				NULL AS fbm_refund_other,
				NULL AS calcuRes,
				NULL AS fbm_ddp,
				NULL AS warehouse_rent,
				c.total adjustment,
				NULL AS liquidation,
				NULL AS promotion,
				NULL AS shipping_service,
				NULL AS lc_adjustment,
				NULL AS le_adjustment 
			FROM
				mu_finance_order_adjustment a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN mu_finance_order_share c ON a.share_code = c.share_code 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.fulfillment = "FBM" 
				AND b.platform = "amazon" UNION ALL
			SELECT
				"amazon" AS platform,
				userAccount userAccount,
				NULL AS payment,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				warehouse_sku warehouse_sku,
				NULL AS fbm_sale_qty,
				NULL AS fbm_refund_qty,
				NULL AS fbm_sale_amount,
				NULL AS fbm_sale_tax,
				NULL AS fbm_refund_amount,
				NULL AS fbm_sale_selling_fees,
				NULL AS fbm_refund_selling_fees,
				NULL AS fbm_refund_other,
				NULL AS calcuRes,
				NULL AS fbm_ddp,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				c.total liquidation,
				NULL AS promotion,
				NULL AS shipping_service,
				NULL AS lc_adjustment,
				NULL AS le_adjustment 
			FROM
				mu_finance_order_liquidation a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN mu_finance_order_share c ON a.share_code = c.share_code 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.fulfillment = "FBM" 
				AND b.platform = "amazon" UNION ALL
			SELECT
				"amazon" AS platform,
				a.user_account userAccount,
				NULL AS payment,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				a.warehouse_sku warehouse_sku,
				NULL AS fbm_sale_qty,
				NULL AS fbm_refund_qty,
				NULL AS fbm_sale_amount,
				NULL AS fbm_sale_tax,
				NULL AS fbm_refund_amount,
				NULL AS fbm_sale_selling_fees,
				NULL AS fbm_refund_selling_fees,
				NULL AS fbm_refund_other,
				NULL AS calcuRes,
				NULL AS fbm_ddp,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS liquidation,
				b.total promotion,
				NULL AS shipping_service,
				NULL AS lc_adjustment,
				NULL AS le_adjustment 
			FROM
				(
				SELECT
					report_id,
					user_account,
					warehouse_sku,
					share_code,
					SUM( promotion ) total 
				FROM
					mu_finance_order_additional 
				WHERE
					promotion IS NOT NULL 
					AND report_id = ' . $report_id . ' 
				GROUP BY
					report_id,
					user_account,
					warehouse_sku,
					share_code 
				ORDER BY
					report_id,
					user_account,
					warehouse_sku,
					share_code 
				) a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
			WHERE
				b.fulfillment = "FBM" UNION ALL
			SELECT
				"amazon" AS platform,
				b.user_account userAccount,
				NULL AS payment,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				b.warehouse_sku warehouse_sku,
				NULL AS fbm_sale_qty,
				NULL AS fbm_refund_qty,
				NULL AS fbm_sale_amount,
				NULL AS fbm_sale_tax,
				NULL AS fbm_refund_amount,
				NULL AS fbm_sale_selling_fees,
				NULL AS fbm_refund_selling_fees,
				NULL AS fbm_refund_other,
				NULL AS calcuRes,
				NULL AS fbm_ddp,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS liquidation,
				NULL AS promotion,
				NULL AS shipping_service,
				b.total lc_adjustment,
				NULL AS le_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND a.platform = "amazon" 
				AND lc_adjustment IS NOT NULL UNION ALL
			SELECT
				"amazon" AS platform,
				b.user_account userAccount,
				NULL AS payment,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				b.warehouse_sku warehouse_sku,
				NULL AS fbm_sale_qty,
				NULL AS fbm_refund_qty,
				NULL AS fbm_sale_amount,
				NULL AS fbm_sale_tax,
				NULL AS fbm_refund_amount,
				NULL AS fbm_sale_selling_fees,
				NULL AS fbm_refund_selling_fees,
				NULL AS fbm_refund_other,
				NULL AS calcuRes,
				NULL AS fbm_ddp,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS liquidation,
				NULL AS promotion,
				NULL AS shipping_service,
				NULL AS lc_adjustment,
				b.total le_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND a.platform = "amazon" 
				AND le_adjustment IS NOT NULL UNION ALL
			SELECT
				"amazon" AS platform,
				b.user_account userAccount,
				NULL AS payment,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				b.warehouse_sku warehouse_sku,
				NULL AS fbm_sale_qty,
				NULL AS fbm_refund_qty,
				NULL AS fbm_sale_amount,
				NULL AS fbm_sale_tax,
				NULL AS fbm_refund_amount,
				NULL AS fbm_sale_selling_fees,
				NULL AS fbm_refund_selling_fees,
				NULL AS fbm_refund_other,
				NULL AS calcuRes,
				NULL AS fbm_ddp,
				ROUND( SUM( b.total ), 6 ) warehouse_rent,
				NULL AS adjustment,
				NULL AS liquidation,
				NULL AS promotion,
				NULL AS shipping_service,
				NULL AS lc_adjustment,
				NULL AS le_adjustment 
			FROM
				mu_finance_warehouse_fbm a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND b.report_id = ' . $report_id . ' 
				AND ( c.platform = "amazon" OR c.platform IS NULL ) 
			GROUP BY
				platform,
				user_account,
				warehouse_sku 
			) a 
		GROUP BY
			platform,
			userAccount,
			warehouse_sku UNION ALL
		SELECT
			"amazon" AS platform,
			c.ecang_user_account userAccount,
			d.warehouse_sku,
			NULL AS fbm_sale_qty,
			NULL AS fbm_refund_qty,
			NULL AS fbm_sale_amount,
			NULL AS fbm_sale_tax,
			NULL AS fbm_refund_amount,
			NULL AS fbm_sale_selling_fees,
			NULL AS fbm_refund_selling_fees,
			NULL AS fbm_refund_other,
			NULL AS calcuRes,
			NULL AS fbm_ddp,
			SUM( a.totalAdsCost * d.percent * d.qty ) fbm_adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS liquidation,
			NULL AS promotion,
			NULL AS shipping_service,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_ak_ad_cost a
			LEFT JOIN mu_ak_seller b ON b.sid = a.sid
			LEFT JOIN mu_ecang_ak_user_account_relation c ON c.ak_user_account = b.`name`
			LEFT JOIN ( SELECT DISTINCT user_account, seller_sku, warehouse_sku, percent, qty FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) d ON d.user_account = c.ecang_user_account 
			AND a.msku = d.seller_sku 
		WHERE
			reportDateMonth = "' . $month . '" 
			AND a.totalAdsCost != 0 
			AND is_fba = 0 
		GROUP BY
			platform,
			userAccount,
			warehouse_sku UNION ALL
		SELECT
			"amazon" AS platform,
			b.user_account,
			b.warehouse_sku,
			NULL AS fbm_sale_qty,
			NULL AS fbm_refund_qty,
			NULL AS fbm_sale_amount,
			NULL AS fbm_sale_tax,
			NULL AS fbm_refund_amount,
			NULL AS fbm_sale_selling_fees,
			NULL AS fbm_refund_selling_fees,
			NULL AS fbm_refund_other,
			NULL AS calcuRes,
			NULL AS fbm_ddp,
			NULL AS fbm_adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS liquidation,
			NULL AS promotion,
			NULL AS shipping_service,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			b.qty evaluation_qty,
			ROUND( a.cny_actual_paid / c.USD, 2 ) evaluation_amount 
		FROM
			mu_finance_evaluation a
			LEFT JOIN mu_finance_order_statistics b ON a.payment = b.saleOrderCode
			LEFT JOIN mu_finance_report c ON a.report_id = c.id
			LEFT JOIN mu_ecang_order d ON a.payment = d.saleOrderCode 
		WHERE
			d.fulfillmentType = 0 
			AND a.report_id = ' . $report_id . ' 
			AND b.platform = "amazon" UNION ALL
		SELECT
			c.platform AS platform,
			c.userAccount userAccount,
			b.warehouse_sku warehouse_sku,
			NULL AS fbm_sale_qty,
			NULL AS fbm_refund_qty,
			NULL AS fbm_sale_amount,
			NULL AS fbm_sale_tax,
			NULL AS fbm_refund_amount,
			NULL AS fbm_sale_selling_fees,
			NULL AS fbm_refund_selling_fees,
			NULL AS fbm_refund_other,
			NULL AS calcuRes,
			NULL AS fbm_ddp,
			NULL AS fbm_adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS liquidation,
			NULL AS promotion,
			NULL AS shipping_service,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			ROUND( b.total / d.USD, 2) operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_expenses a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			LEFT JOIN mu_finance_report d ON a.report_id = d.id
		WHERE
			a.report_id = ' . $report_id . ' 
			AND b.fulfillment = "FBM" 
			AND c.platform = "amazon" UNION ALL
		SELECT
			c.platform AS platform,
			c.userAccount userAccount,
			b.warehouse_sku warehouse_sku,
			NULL AS fbm_sale_qty,
			NULL AS fbm_refund_qty,
			NULL AS fbm_sale_amount,
			NULL AS fbm_sale_tax,
			NULL AS fbm_refund_amount,
			NULL AS fbm_sale_selling_fees,
			NULL AS fbm_refund_selling_fees,
			NULL AS fbm_refund_other,
			NULL AS calcuRes,
			NULL AS fbm_ddp,
			NULL AS fbm_adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS liquidation,
			NULL AS promotion,
			NULL AS shipping_service,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS operation_expenses,
			ROUND( b.total / d.USD, 2) operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_factory a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			LEFT JOIN mu_finance_report d ON a.report_id = d.id
		WHERE
			a.report_id = ' . $report_id . ' 
			AND b.fulfillment = "FBM" 
			AND c.platform = "amazon" UNION ALL
		SELECT
			c.platform AS platform,
			c.userAccount userAccount,
			b.warehouse_sku warehouse_sku,
			NULL AS fbm_sale_qty,
			NULL AS fbm_refund_qty,
			NULL AS fbm_sale_amount,
			NULL AS fbm_sale_tax,
			NULL AS fbm_refund_amount,
			NULL AS fbm_sale_selling_fees,
			NULL AS fbm_refund_selling_fees,
			NULL AS fbm_refund_other,
			NULL AS calcuRes,
			NULL AS fbm_ddp,
			NULL AS fbm_adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS liquidation,
			NULL AS promotion,
			NULL AS shipping_service,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			ROUND( b.total / d.USD, 2) operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_delivery a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			LEFT JOIN mu_finance_report d ON a.report_id = d.id
		WHERE
			a.report_id = ' . $report_id . ' 
			AND b.fulfillment = "FBM" 
			AND c.platform = "amazon" 
		) a 
	GROUP BY
		platform,
		userAccount,
		warehouse_sku 
	ORDER BY
		platform,
		userAccount,
		warehouse_sku 
	) a
	LEFT JOIN ( SELECT DISTINCT user_account, warehouse_sku, seller, product_name FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) b ON a.userAccount = b.user_account 
	AND a.warehouse_sku = b.warehouse_sku 
GROUP BY
	platform,
	userAccount,
	warehouse_sku,
	fbm_sale_amount,
	product_name,
	seller;
        ';
    }

    static public function getWalmartWarehouseSkuSql($report_id): string
    {
        return '
SELECT
	platform,
	userAccount,
	a.warehouse_sku,
	SUM( sale_qty ) sale_qty,
	SUM( refund_qty ) refund_qty,
	SUM( ROUND( sale_amount, 7 ) ) sale_amount,
	SUM( ROUND( refund_amount, 7 ) ) refund_amount,
	SUM( ROUND( sale_selling_fees, 7 ) ) sale_selling_fees,
	SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
	SUM( ROUND( calcuRes, 7 ) ) calcuRes,
	SUM( ROUND( wfs_fulfillment, 6 ) ) wfs_fulfillment,
	SUM( ROUND( ddp, 2 ) ) ddp,
	SUM( ROUND( adCost, 7 ) ) adCost,
	SUM( ROUND( warehouse_rent, 4 ) ) warehouse_rent,
	SUM( ROUND( wfs_warehouse, 8 ) ) wfs_warehouse,
	SUM( ROUND( wfs_return_shipping, 6 ) ) wfs_return_shipping,
	SUM( ROUND( adjustment, 6 ) ) adjustment,
	SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
	SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
	SUM( ROUND( wfs_adjustment, 6 ) ) wfs_adjustment,
	SUM( ROUND( operation_expenses, 6 ) ) operation_expenses,
	SUM( ROUND( operation_factory, 6 ) ) operation_factory,
	SUM( ROUND( operation_delivery, 6 ) ) operation_delivery,
	ROUND( SUM( IFNULL( adCost, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ad_percent,
	ROUND( SUM( IFNULL( warehouse_rent, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) warehouse_percent,
	ROUND( SUM( IFNULL( calcuRes, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) tail_percent,
	ROUND( SUM( IFNULL( ddp, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ddp_percent,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( wfs_fulfillment, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( wfs_warehouse, 0 ) ) + SUM( IFNULL( wfs_return_shipping, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wfs_adjustment, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ),
		2 
	) profit,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( wfs_fulfillment, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( wfs_warehouse, 0 ) ) + SUM( IFNULL( wfs_return_shipping, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wfs_adjustment, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		4 
	) gross_profit_margin,
	SUM( ROUND( evaluation_qty, 3 ) ) evaluation_qty,
	SUM( ROUND( evaluation_amount, 2 ) ) evaluation_amount,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( wfs_fulfillment, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( wfs_warehouse, 0 ) ) + SUM( IFNULL( wfs_return_shipping, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wfs_adjustment, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ) + SUM( IFNULL( evaluation_amount, 0 ) ),
		2 
	) profit_include_evaluation,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( wfs_fulfillment, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( wfs_warehouse, 0 ) ) + SUM( IFNULL( wfs_return_shipping, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wfs_adjustment, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ) + SUM( IFNULL( evaluation_amount, 0 ) ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		2 
	) gross_profit_margin_include_evaluation,
	b.product_name product_name,
	b.seller seller 
FROM
	(
	SELECT
		platform,
		userAccount,
		warehouse_sku,
		SUM( sale_qty ) sale_qty,
		SUM( refund_qty ) * - 1 refund_qty,
		SUM( ROUND( sale_amount, 7 ) ) sale_amount,
		SUM( ROUND( refund_amount, 7 ) ) refund_amount,
		SUM( ROUND( sale_selling_fees, 7 ) ) * - 1 sale_selling_fees,
		SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
		SUM( ROUND( calcuRes, 7 ) ) * - 1 calcuRes,
		SUM( ROUND( wfs_fulfillment, 6 ) ) wfs_fulfillment,
		SUM( ROUND( ddp, 2 ) ) * - 1 ddp,
		SUM( ROUND( adCost, 7 ) ) adCost,
		SUM( ROUND( warehouse_rent, 4 ) ) * - 1 warehouse_rent,
		SUM( ROUND( wfs_warehouse, 8 ) ) * - 1 wfs_warehouse,
		SUM( ROUND( wfs_return_shipping, 6 ) ) wfs_return_shipping,
		SUM( ROUND( adjustment, 6 ) ) adjustment,
		SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
		SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
		SUM( ROUND( wfs_adjustment, 6 ) ) wfs_adjustment,
		SUM( ROUND( operation_expenses, 6 ) ) operation_expenses,
		SUM( ROUND( operation_factory, 6 ) ) operation_factory,
		SUM( ROUND( operation_delivery, 6 ) ) operation_delivery,
		SUM( ROUND( evaluation_qty, 3 ) ) evaluation_qty,
		SUM( ROUND( evaluation_amount, 2 ) ) * - 1 evaluation_amount 
	FROM
		(
		SELECT
			platform,
			userAccount,
			warehouse_sku,
			SUM( sale_qty ) sale_qty,
			SUM( refund_qty ) refund_qty,
			SUM( ROUND( sale_amount, 7 ) ) sale_amount,
			SUM( ROUND( refund_amount, 7 ) ) refund_amount,
			SUM( ROUND( sale_selling_fees, 7 ) ) sale_selling_fees,
			SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
			SUM( ROUND( calcuRes, 7 ) ) calcuRes,
			SUM( ROUND( wfs_fulfillment, 6 ) ) wfs_fulfillment,
			SUM( ROUND( ddp, 2 ) ) ddp,
			NULL AS adCost,
			SUM( ROUND( warehouse_rent, 4 ) ) warehouse_rent,
			SUM( ROUND( wfs_warehouse, 8 ) ) wfs_warehouse,
			SUM( ROUND( wfs_return_shipping, 6 ) ) wfs_return_shipping,
			SUM( ROUND( adjustment, 6 ) ) adjustment,
			SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
			SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
			SUM( ROUND( wfs_adjustment, 6 ) ) wfs_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			(
			SELECT
				a.platform,
				a.userAccount,
				a.payment_id,
				b.saleOrderCode,
				b.platform_sku seller_sku,
				b.warehouse_sku,
				b.qty sale_qty,
				NULL AS refund_qty,
				ROUND( b.sale_amount, 7 ) sale_amount,
				NULL AS refund_amount,
				ROUND( b.selling_fee, 7 ) sale_selling_fees,
				NULL AS refund_selling_fees,
				c.calcuRes calcuRes,
				NULL AS wfs_fulfillment,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS wfs_warehouse,
				NULL AS wfs_return_shipping,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wfs_adjustment 
			FROM
				(
				SELECT DISTINCT
					report_id,
					payment_id,
					platform,
					userAccount 
				FROM
					mu_finance_order_sale a
					LEFT JOIN mu_finance_table b ON a.table_id = b.id 
				WHERE
					report_id = ' . $report_id . ' 
					AND b.platform = "walmart" 
				) a
				LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id
				LEFT JOIN mu_ecang_order c ON b.saleOrderCode = c.saleOrderCode 
			WHERE
				b.payment_id IS NOT NULL UNION ALL
			SELECT
				a.platform,
				a.userAccount,
				a.payment_id,
				b.saleOrderCode,
				b.seller_sku,
				b.warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS wfs_fulfillment,
				c.sku_ddp_unit * b.qty / d.USD ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS wfs_warehouse,
				NULL AS wfs_return_shipping,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wfs_adjustment 
			FROM
				(
				SELECT DISTINCT
					report_id,
					payment_id,
					platform,
					userAccount 
				FROM
					mu_finance_order_sale a
					LEFT JOIN mu_finance_table b ON a.table_id = b.id 
				WHERE
					report_id = ' . $report_id . ' 
					AND b.platform = "walmart" 
				) a
				LEFT JOIN mu_finance_order_outbound b ON b.payment_id = a.payment_id 
				AND b.report_id = ' . $report_id . ' 
				LEFT JOIN mu_finance_store c ON b.store_id = c.id
				LEFT JOIN mu_finance_report d ON b.report_id = d.id 
			WHERE
				b.payment_id IS NOT NULL UNION ALL
			SELECT
				b.platform,
				b.userAccount,
				a.payment_id,
				NULL AS saleOrderCode,
				a.sku seller_sku,
				d.pcr_product_sku warehouse_sku,
				NULL AS sale_qty,
				a.quantity * d.pcr_quantity refund_qty,
				NULL AS sale_amount,
				ROUND( ( product_sales ) * d.pcr_percent * d.pcr_quantity / 100, 7 ) refund_amount,
				NULL AS sale_selling_fees,
				ROUND( selling_fees * d.pcr_percent * d.pcr_quantity / 100, 7 ) refund_selling_fees,
				NULL AS calcuRes,
				NULL AS wfs_fulfillment,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS wfs_warehouse,
				NULL AS wfs_return_shipping,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wfs_adjustment 
			FROM
				mu_finance_order_refund a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN mu_ecang_sku c ON a.sku = c.product_sku 
				AND b.userAccount = c.user_account
				LEFT JOIN mu_ecang_sku_relation d ON c.id = d.sku_id 
			WHERE
				report_id = ' . $report_id . ' 
				AND b.platform = "walmart" UNION ALL
			SELECT
				b.platform,
				b.userAccount,
				a.payment_id,
				NULL AS saleOrderCode,
				a.sku seller_sku,
				c.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				c.total wfs_fulfillment,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS wfs_warehouse,
				NULL AS wfs_return_shipping,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wfs_adjustment 
			FROM
				mu_finance_order_adjustment_wfs a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN mu_finance_order_share c ON a.share_code = c.share_code 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND a.is_fulfillment = 1 
				AND b.platform = "walmart" UNION ALL
			SELECT
				b.platform,
				b.userAccount,
				a.payment_id,
				NULL AS saleOrderCode,
				a.sku seller_sku,
				c.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS wfs_fulfillment,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS wfs_warehouse,
				c.total wfs_return_shipping,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wfs_adjustment 
			FROM
				mu_finance_order_adjustment_wfs a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN mu_finance_order_share c ON a.share_code = c.share_code 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND a.is_return_shipping = 1 
				AND b.platform = "walmart" UNION ALL
			SELECT
				b.platform,
				b.userAccount,
				a.payment_id,
				NULL AS saleOrderCode,
				a.sku seller_sku,
				c.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS wfs_fulfillment,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS wfs_warehouse,
				NULL AS wfs_return_shipping,
				c.total adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wfs_adjustment 
			FROM
				mu_finance_order_adjustment a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN mu_finance_order_share c ON a.share_code = c.share_code 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND b.platform = "walmart" UNION ALL
			SELECT
				"walmart" AS platform,
				b.user_account userAccount,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				b.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS wfs_fulfillment,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS wfs_warehouse,
				NULL AS wfs_return_shipping,
				NULL AS adjustment,
				b.total lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wfs_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND a.platform = "walmart" 
				AND lc_adjustment IS NOT NULL UNION ALL
			SELECT
				"walmart" AS platform,
				b.user_account userAccount,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				b.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS wfs_fulfillment,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS wfs_warehouse,
				NULL AS wfs_return_shipping,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				b.total le_adjustment,
				NULL AS wfs_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND a.platform = "walmart" 
				AND le_adjustment IS NOT NULL UNION ALL
			SELECT
				"walmart" AS platform,
				user_account userAccount,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS wfs_fulfillment,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS wfs_warehouse,
				NULL AS wfs_return_shipping,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				wfs_adjustment wfs_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) b ON a.user_account = b.userAccount 
			WHERE
				report_id = ' . $report_id . ' 
				AND b.platform = "walmart" UNION ALL
			SELECT
				"walmart" AS platform,
				a.user_account userAccount,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				a.vendor_sku seller_sku,
				c.pcr_product_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS wfs_fulfillment,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				ROUND( a.total * c.pcr_percent / 100, 7 ) wfs_warehouse,
				NULL AS adjustment,
				NULL AS wfs_return_shipping,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wfs_adjustment 
			FROM
				mu_finance_warehouse_wfs a
				LEFT JOIN mu_ecang_sku b ON a.user_account = b.user_account 
				AND a.vendor_sku = b.product_sku
				LEFT JOIN mu_ecang_sku_relation c ON b.id = c.sku_id 
			WHERE
				report_id = ' . $report_id . ' UNION ALL
			SELECT
				"walmart" AS platform,
				b.user_account userAccount,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				b.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS wfs_fulfillment,
				NULL AS ddp,
				NULL AS adCost,
				ROUND( SUM( b.total ), 6 ) warehouse_rent,
				NULL AS wfs_warehouse,
				NULL AS adjustment,
				NULL AS wfs_return_shipping,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wfs_adjustment 
			FROM
				mu_finance_warehouse_fbm a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "walmart" 
			GROUP BY
				platform,
				user_account,
				warehouse_sku 
			) a 
		GROUP BY
			platform,
			userAccount,
			warehouse_sku UNION ALL
		SELECT
			"walmart" AS platform,
			d.userAccount userAccount,
			a.warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS calcuRes,
			NULL AS wfs_fulfillment,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS wfs_warehouse,
			NULL AS wfs_return_shipping,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wfs_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			b.qty evaluation_qty,
			ROUND( a.cny_actual_paid / c.USD, 2 ) evaluation_amount 
		FROM
			mu_finance_evaluation a
			LEFT JOIN mu_finance_order_statistics b ON a.payment = b.saleOrderCode
			LEFT JOIN mu_finance_report c ON a.report_id = c.id
			LEFT JOIN mu_ecang_order d ON a.payment = d.saleOrderCode 
		WHERE
			d.fulfillmentType = 1 
			AND a.report_id = ' . $report_id . ' 
			AND b.platform = "walmart" UNION ALL
		SELECT
			"walmart" AS platform,
			user_account userAccount,
			warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS calcuRes,
			NULL AS wfs_fulfillment,
			NULL AS ddp,
			total adCost,
			NULL AS warehouse_rent,
			NULL AS wfs_warehouse,
			NULL AS wfs_return_shipping,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wfs_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_ad_cost 
		WHERE
			report_id = ' . $report_id . ' 
			AND platform = "walmart" UNION ALL
		SELECT
			c.platform AS platform,
			c.userAccount userAccount,
			b.warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS calcuRes,
			NULL AS wfs_fulfillment,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS wfs_warehouse,
			NULL AS wfs_return_shipping,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wfs_adjustment,
			ROUND( b.total / d.USD, 2) operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_expenses a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			LEFT JOIN mu_finance_report d ON a.report_id = d.id
		WHERE
			a.report_id = ' . $report_id . ' 
			AND c.platform = "walmart" UNION ALL
		SELECT
			c.platform AS platform,
			c.userAccount userAccount,
			b.warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS calcuRes,
			NULL AS wfs_fulfillment,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS wfs_warehouse,
			NULL AS wfs_return_shipping,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wfs_adjustment,
			NULL AS operation_expenses,
			ROUND( b.total / d.USD, 2) operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_factory a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			LEFT JOIN mu_finance_report d ON a.report_id = d.id
		WHERE
			a.report_id = ' . $report_id . ' 
			AND c.platform = "walmart" UNION ALL
		SELECT
			c.platform AS platform,
			c.userAccount userAccount,
			b.warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS calcuRes,
			NULL AS wfs_fulfillment,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS wfs_warehouse,
			NULL AS wfs_return_shipping,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wfs_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			ROUND( b.total / d.USD, 2) operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_delivery a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			LEFT JOIN mu_finance_report d ON a.report_id = d.id
		WHERE
			a.report_id = ' . $report_id . ' 
			AND c.platform = "walmart" 
		) a 
	GROUP BY
		platform,
		userAccount,
		warehouse_sku 
	ORDER BY
		platform,
		userAccount,
		warehouse_sku 
	) a
	LEFT JOIN ( SELECT DISTINCT user_account, warehouse_sku, seller, product_name FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) b ON a.userAccount = b.user_account 
	AND a.warehouse_sku = b.warehouse_sku 
GROUP BY
	platform,
	userAccount,
	warehouse_sku,
	sale_amount,
	product_name,
	seller;
        ';
    }

    static public function getWayfairWarehouseSkuSql($report_id): string
    {
        return '
SELECT
	platform,
	userAccount,
	a.warehouse_sku,
	SUM( sale_qty ) sale_qty,
	SUM( refund_qty ) refund_qty,
	SUM( ROUND( sale_amount, 7 ) ) sale_amount,
	SUM( ROUND( refund_amount, 7 ) ) refund_amount,
	SUM( ROUND( sale_selling_fees, 7 ) ) sale_selling_fees,
	SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
	SUM( ROUND( calcuRes, 7 ) ) calcuRes,
	SUM( ROUND( ddp, 2 ) ) ddp,
	SUM( ROUND( adCost, 7 ) ) adCost,
	SUM( ROUND( warehouse_rent, 4 ) ) warehouse_rent,
	SUM( ROUND( adjustment, 6 ) ) adjustment,
	SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
	SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
	SUM( ROUND( operation_expenses, 6 ) ) operation_expenses,
	SUM( ROUND( operation_factory, 6 ) ) operation_factory,
	SUM( ROUND( operation_delivery, 6 ) ) operation_delivery,
	ROUND( SUM( IFNULL( adCost, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ad_percent,
	ROUND( SUM( IFNULL( warehouse_rent, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) warehouse_percent,
	ROUND( SUM( IFNULL( calcuRes, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) tail_percent,
	ROUND( SUM( IFNULL( ddp, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ddp_percent,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ),
		2 
	) profit,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		4 
	) gross_profit_margin,
	SUM( ROUND( evaluation_qty, 3 ) ) evaluation_qty,
	SUM( ROUND( evaluation_amount, 2 ) ) evaluation_amount,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ) + SUM( IFNULL( evaluation_amount, 0 ) ),
		2 
	) profit_include_evaluation,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ) + SUM( IFNULL( evaluation_amount, 0 ) ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		2 
	) gross_profit_margin_include_evaluation,
	b.product_name product_name,
	b.seller seller 
FROM
	(
	SELECT
		platform,
		userAccount,
		warehouse_sku,
		SUM( sale_qty ) sale_qty,
		SUM( refund_qty ) * - 1 refund_qty,
		SUM( ROUND( sale_amount, 7 ) ) sale_amount,
		SUM( ROUND( refund_amount, 7 ) ) refund_amount,
		SUM( ROUND( sale_selling_fees, 7 ) ) * - 1 sale_selling_fees,
		SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
		SUM( ROUND( calcuRes, 7 ) ) * - 1 calcuRes,
		SUM( ROUND( ddp, 2 ) ) * - 1 ddp,
		SUM( ROUND( adCost, 7 ) ) adCost,
		SUM( ROUND( warehouse_rent, 4 ) ) * - 1 warehouse_rent,
		SUM( ROUND( adjustment, 6 ) ) adjustment,
		SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
		SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
		SUM( ROUND( operation_expenses, 6 ) ) operation_expenses,
		SUM( ROUND( operation_factory, 6 ) ) operation_factory,
		SUM( ROUND( operation_delivery, 6 ) ) operation_delivery,
		SUM( ROUND( evaluation_qty, 3 ) ) evaluation_qty,
		SUM( ROUND( evaluation_amount, 2 ) ) * - 1 evaluation_amount 
	FROM
		(
		SELECT
			platform,
			userAccount,
			warehouse_sku,
			SUM( sale_qty ) sale_qty,
			SUM( refund_qty ) refund_qty,
			SUM( ROUND( sale_amount, 7 ) ) sale_amount,
			SUM( ROUND( refund_amount, 7 ) ) refund_amount,
			SUM( ROUND( sale_selling_fees, 7 ) ) sale_selling_fees,
			SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
			SUM( ROUND( calcuRes, 7 ) ) calcuRes,
			SUM( ROUND( ddp, 2 ) ) ddp,
			NULL AS adCost,
			SUM( ROUND( warehouse_rent, 4 ) ) warehouse_rent,
			SUM( ROUND( adjustment, 6 ) ) adjustment,
			SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
			SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			(
			SELECT
				a.platform,
				a.userAccount,
				a.payment_id,
				b.saleOrderCode,
				b.platform_sku seller_sku,
				b.warehouse_sku,
				b.qty sale_qty,
				NULL AS refund_qty,
				ROUND( b.sale_amount, 7 ) sale_amount,
				NULL AS refund_amount,
				ROUND( b.selling_fee, 7 ) sale_selling_fees,
				NULL AS refund_selling_fees,
				c.calcuRes calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment 
			FROM
				(
				SELECT DISTINCT
					report_id,
					payment_id,
					platform,
					userAccount 
				FROM
					mu_finance_order_sale a
					LEFT JOIN mu_finance_table b ON a.table_id = b.id 
				WHERE
					report_id = ' . $report_id . ' 
					AND b.platform = "wayfair" 
				) a
				LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id
				LEFT JOIN mu_ecang_order c ON b.saleOrderCode = c.saleOrderCode 
			WHERE
				b.payment_id IS NOT NULL UNION ALL
			SELECT
				a.platform,
				a.userAccount,
				a.payment_id,
				b.saleOrderCode,
				b.seller_sku,
				b.warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				c.sku_ddp_unit * b.qty / d.USD ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment 
			FROM
				(
				SELECT DISTINCT
					report_id,
					payment_id,
					platform,
					userAccount 
				FROM
					mu_finance_order_sale a
					LEFT JOIN mu_finance_table b ON a.table_id = b.id 
				WHERE
					report_id = ' . $report_id . ' 
					AND b.platform = "wayfair" 
				) a
				LEFT JOIN mu_finance_order_outbound b ON b.payment_id = a.payment_id 
				AND b.report_id = ' . $report_id . '
				LEFT JOIN mu_finance_store c ON b.store_id = c.id
				LEFT JOIN mu_finance_report d ON b.report_id = d.id 
			WHERE
				b.payment_id IS NOT NULL UNION ALL
			SELECT
				b.platform,
				b.userAccount,
				a.payment_id,
				NULL AS saleOrderCode,
				a.sku seller_sku,
				d.pcr_product_sku warehouse_sku,
				NULL AS sale_qty,
				a.quantity * d.pcr_quantity refund_qty,
				NULL AS sale_amount,
				ROUND( ( product_sales ) * d.pcr_percent * d.pcr_quantity / 100, 7 ) refund_amount,
				NULL AS sale_selling_fees,
				ROUND( selling_fees * d.pcr_percent * d.pcr_quantity / 100, 7 ) refund_selling_fees,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment 
			FROM
				mu_finance_order_refund a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN mu_ecang_sku c ON a.sku = c.product_sku 
				AND b.userAccount = c.user_account
				LEFT JOIN mu_ecang_sku_relation d ON c.id = d.sku_id 
			WHERE
				report_id = ' . $report_id . ' 
				AND b.platform = "wayfair" UNION ALL
			SELECT
				b.platform,
				b.userAccount,
				a.payment_id,
				NULL AS saleOrderCode,
				a.sku seller_sku,
				c.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				c.total adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment 
			FROM
				mu_finance_order_adjustment a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN mu_finance_order_share c ON a.share_code = c.share_code 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND b.platform = "wayfair" UNION ALL
			SELECT
				"wayfair" AS platform,
				b.user_account userAccount,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				b.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				b.total lc_adjustment,
				NULL AS le_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND a.platform = "wayfair" 
				AND lc_adjustment IS NOT NULL UNION ALL
			SELECT
				"wayfair" AS platform,
				b.user_account userAccount,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				b.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				b.total le_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND a.platform = "wayfair" 
				AND le_adjustment IS NOT NULL UNION ALL
			SELECT
				"wayfair" AS platform,
				b.user_account userAccount,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				b.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				ROUND( SUM( b.total ), 6 ) warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment 
			FROM
				mu_finance_warehouse_fbm a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "wayfair" 
			GROUP BY
				platform,
				user_account,
				warehouse_sku 
			) a 
		GROUP BY
			platform,
			userAccount,
			warehouse_sku UNION ALL
		SELECT
			"wayfair" AS platform,
			d.userAccount userAccount,
			a.warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS calcuRes,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			b.qty evaluation_qty,
			ROUND( a.cny_actual_paid / c.USD, 2 ) evaluation_amount 
		FROM
			mu_finance_evaluation a
			LEFT JOIN mu_finance_order_statistics b ON a.payment = b.saleOrderCode
			LEFT JOIN mu_finance_report c ON a.report_id = c.id
			LEFT JOIN mu_ecang_order d ON a.payment = d.saleOrderCode 
		WHERE
			d.fulfillmentType = 1 
			AND a.report_id = ' . $report_id . ' 
			AND b.platform = "wayfair" UNION ALL
		SELECT
			"wayfair" AS platform,
			user_account userAccount,
			warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS calcuRes,
			NULL AS ddp,
			total adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_ad_cost 
		WHERE
			report_id = ' . $report_id . ' 
			AND platform = "wayfair" UNION ALL
		SELECT
			c.platform AS platform,
			c.userAccount userAccount,
			b.warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS calcuRes,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			ROUND( b.total / d.USD, 2) operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_expenses a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			LEFT JOIN mu_finance_report d ON a.report_id = d.id
		WHERE
			a.report_id = ' . $report_id . ' 
			AND c.platform = "wayfair" UNION ALL
		SELECT
			c.platform AS platform,
			c.userAccount userAccount,
			b.warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS calcuRes,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS operation_expenses,
			ROUND( b.total / d.USD, 2) operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_factory a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			LEFT JOIN mu_finance_report d ON a.report_id = d.id
		WHERE
			a.report_id = ' . $report_id . ' 
			AND c.platform = "wayfair" UNION ALL
		SELECT
			c.platform AS platform,
			c.userAccount userAccount,
			b.warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS calcuRes,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			ROUND( b.total / d.USD, 2) operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_delivery a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			LEFT JOIN mu_finance_report d ON a.report_id = d.id
		WHERE
			a.report_id = ' . $report_id . ' 
			AND c.platform = "wayfair" 
		) a 
	GROUP BY
		platform,
		userAccount,
		warehouse_sku 
	ORDER BY
		platform,
		userAccount,
		warehouse_sku 
	) a
	LEFT JOIN ( SELECT DISTINCT user_account, warehouse_sku, seller, product_name FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) b ON a.userAccount = b.user_account 
	AND a.warehouse_sku = b.warehouse_sku 
GROUP BY
	platform,
	userAccount,
	warehouse_sku,
	sale_amount,
	product_name,
	seller;
        ';
    }

    static public function getSheinWarehouseSkuSql($report_id): string
    {
        return '
SELECT
	platform,
	userAccount,
	a.warehouse_sku,
	SUM( sale_qty ) sale_qty,
	SUM( refund_qty ) refund_qty,
	SUM( ROUND( sale_amount, 7 ) ) sale_amount,
	SUM( ROUND( refund_amount, 7 ) ) refund_amount,
	SUM( ROUND( sale_selling_fees, 7 ) ) sale_selling_fees,
	SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
	SUM( ROUND( calcuRes, 7 ) ) calcuRes,
	SUM( ROUND( ddp, 2 ) ) ddp,
	SUM( ROUND( adCost, 7 ) ) adCost,
	SUM( ROUND( warehouse_rent, 4 ) ) warehouse_rent,
	SUM( ROUND( adjustment, 6 ) ) adjustment,
	SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
	SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
	SUM( ROUND( operation_expenses, 6 ) ) operation_expenses,
	SUM( ROUND( operation_factory, 6 ) ) operation_factory,
	SUM( ROUND( operation_delivery, 6 ) ) operation_delivery,
	ROUND( SUM( IFNULL( adCost, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ad_percent,
	ROUND( SUM( IFNULL( warehouse_rent, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) warehouse_percent,
	ROUND( SUM( IFNULL( calcuRes, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) tail_percent,
	ROUND( SUM( IFNULL( ddp, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ddp_percent,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ),
		2 
	) profit,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		4 
	) gross_profit_margin,
	SUM( ROUND( evaluation_qty, 3 ) ) evaluation_qty,
	SUM( ROUND( evaluation_amount, 2 ) ) evaluation_amount,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ) + SUM( IFNULL( evaluation_amount, 0 ) ),
		2 
	) profit_include_evaluation,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( operation_expenses, 0 ) ) + SUM( IFNULL( operation_factory, 0 ) ) + SUM( IFNULL( operation_delivery, 0 ) ) + SUM( IFNULL( evaluation_amount, 0 ) ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		2 
	) gross_profit_margin_include_evaluation,
	b.product_name product_name,
	b.seller seller 
FROM
	(
	SELECT
		platform,
		userAccount,
		warehouse_sku,
		SUM( sale_qty ) sale_qty,
		SUM( refund_qty ) * - 1 refund_qty,
		SUM( ROUND( sale_amount, 7 ) ) sale_amount,
		SUM( ROUND( refund_amount, 7 ) ) refund_amount,
		SUM( ROUND( sale_selling_fees, 7 ) ) * - 1 sale_selling_fees,
		SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
		SUM( ROUND( calcuRes, 7 ) ) * - 1 calcuRes,
		SUM( ROUND( ddp, 2 ) ) * - 1 ddp,
		SUM( ROUND( adCost, 7 ) ) adCost,
		SUM( ROUND( warehouse_rent, 4 ) ) * - 1 warehouse_rent,
		SUM( ROUND( adjustment, 6 ) ) adjustment,
		SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
		SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
		SUM( ROUND( operation_expenses, 6 ) ) operation_expenses,
		SUM( ROUND( operation_factory, 6 ) ) operation_factory,
		SUM( ROUND( operation_delivery, 6 ) ) operation_delivery,
		SUM( ROUND( evaluation_qty, 3 ) ) evaluation_qty,
		SUM( ROUND( evaluation_amount, 2 ) ) * - 1 evaluation_amount 
	FROM
		(
		SELECT
			platform,
			userAccount,
			warehouse_sku,
			SUM( sale_qty ) sale_qty,
			SUM( refund_qty ) refund_qty,
			SUM( ROUND( sale_amount, 7 ) ) sale_amount,
			SUM( ROUND( refund_amount, 7 ) ) refund_amount,
			SUM( ROUND( sale_selling_fees, 7 ) ) sale_selling_fees,
			SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
			SUM( ROUND( calcuRes, 7 ) ) calcuRes,
			SUM( ROUND( ddp, 2 ) ) ddp,
			NULL AS adCost,
			SUM( ROUND( warehouse_rent, 4 ) ) warehouse_rent,
			SUM( ROUND( adjustment, 6 ) ) adjustment,
			SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
			SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			(
			SELECT
				a.platform,
				a.userAccount,
				a.payment_id,
				b.saleOrderCode,
				b.platform_sku seller_sku,
				b.warehouse_sku,
				b.qty sale_qty,
				NULL AS refund_qty,
				ROUND( b.sale_amount, 7 ) sale_amount,
				NULL AS refund_amount,
				ROUND( b.selling_fee, 7 ) sale_selling_fees,
				NULL AS refund_selling_fees,
				c.calcuRes calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment 
			FROM
				(
				SELECT DISTINCT
					report_id,
					payment_id,
					platform,
					userAccount 
				FROM
					mu_finance_order_sale a
					LEFT JOIN mu_finance_table b ON a.table_id = b.id 
				WHERE
					report_id = ' . $report_id . ' 
					AND b.platform = "shein" 
				) a
				LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id
				LEFT JOIN mu_ecang_order c ON b.saleOrderCode = c.saleOrderCode 
			WHERE
				b.payment_id IS NOT NULL UNION ALL
			SELECT
				a.platform,
				a.userAccount,
				a.payment_id,
				b.saleOrderCode,
				b.seller_sku,
				b.warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				c.sku_ddp_unit * b.qty / d.USD ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment 
			FROM
				(
				SELECT DISTINCT
					report_id,
					payment_id,
					platform,
					userAccount 
				FROM
					mu_finance_order_sale a
					LEFT JOIN mu_finance_table b ON a.table_id = b.id 
				WHERE
					report_id = ' . $report_id . ' 
					AND b.platform = "shein" 
				) a
				LEFT JOIN mu_finance_order_outbound b ON b.payment_id = a.payment_id 
				AND b.report_id = ' . $report_id . '
				LEFT JOIN mu_finance_store c ON b.store_id = c.id
				LEFT JOIN mu_finance_report d ON b.report_id = d.id 
			WHERE
				b.payment_id IS NOT NULL UNION ALL
			SELECT
				b.platform,
				b.userAccount,
				a.payment_id,
				NULL AS saleOrderCode,
				a.sku seller_sku,
				d.pcr_product_sku warehouse_sku,
				NULL AS sale_qty,
				a.quantity * d.pcr_quantity refund_qty,
				NULL AS sale_amount,
				ROUND( ( product_sales ) * d.pcr_percent * d.pcr_quantity / 100, 7 ) refund_amount,
				NULL AS sale_selling_fees,
				ROUND( selling_fees * d.pcr_percent * d.pcr_quantity / 100, 7 ) refund_selling_fees,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment 
			FROM
				mu_finance_order_refund a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN mu_ecang_sku c ON a.sku = c.product_sku 
				AND b.userAccount = c.user_account
				LEFT JOIN mu_ecang_sku_relation d ON c.id = d.sku_id 
			WHERE
				report_id = ' . $report_id . ' 
				AND b.platform = "shein" UNION ALL
			SELECT
				b.platform,
				b.userAccount,
				a.payment_id,
				NULL AS saleOrderCode,
				a.sku seller_sku,
				c.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				c.total adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment 
			FROM
				mu_finance_order_adjustment a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN mu_finance_order_share c ON a.share_code = c.share_code 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND b.platform = "shein" UNION ALL
			SELECT
				"shein" AS platform,
				b.user_account userAccount,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				b.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				b.total lc_adjustment,
				NULL AS le_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "shein" 
				AND lc_adjustment IS NOT NULL UNION ALL
			SELECT
				"shein" AS platform,
				b.user_account userAccount,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				b.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				b.total le_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "shein" 
				AND le_adjustment IS NOT NULL UNION ALL
			SELECT
				"shein" AS platform,
				b.user_account userAccount,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				b.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				ROUND( SUM( b.total ), 6 ) warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment 
			FROM
				mu_finance_warehouse_fbm a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "shein" 
			GROUP BY
				platform,
				user_account,
				warehouse_sku 
			) a 
		GROUP BY
			platform,
			userAccount,
			warehouse_sku UNION ALL
		SELECT
			"shein" AS platform,
			d.userAccount userAccount,
			a.warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS calcuRes,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			b.qty evaluation_qty,
			ROUND( a.cny_actual_paid / c.USD, 2 ) evaluation_amount 
		FROM
			mu_finance_evaluation a
			LEFT JOIN mu_finance_order_statistics b ON a.payment = b.saleOrderCode
			LEFT JOIN mu_finance_report c ON a.report_id = c.id
			LEFT JOIN mu_ecang_order d ON a.payment = d.saleOrderCode 
		WHERE
			d.fulfillmentType = 1 
			AND a.report_id = ' . $report_id . ' 
			AND b.platform = "shein" UNION ALL
		SELECT
			"shein" AS platform,
			user_account userAccount,
			warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS calcuRes,
			NULL AS ddp,
			total adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_ad_cost 
		WHERE
			report_id = ' . $report_id . ' 
			AND platform = "shein" UNION ALL
		SELECT
			c.platform AS platform,
			c.userAccount userAccount,
			b.warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS calcuRes,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			ROUND( b.total / d.USD, 2) operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_expenses a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			LEFT JOIN mu_finance_report d ON a.report_id = d.id
		WHERE
			a.report_id = ' . $report_id . ' 
			AND c.platform = "shein" UNION ALL
		SELECT
			c.platform AS platform,
			c.userAccount userAccount,
			b.warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS calcuRes,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS operation_expenses,
			ROUND( b.total / d.USD, 2) operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_factory a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			LEFT JOIN mu_finance_report d ON a.report_id = d.id
		WHERE
			a.report_id = ' . $report_id . ' 
			AND c.platform = "shein" UNION ALL
		SELECT
			c.platform AS platform,
			c.userAccount userAccount,
			b.warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS calcuRes,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			ROUND( b.total / d.USD, 2) operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_delivery a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			LEFT JOIN mu_finance_report d ON a.report_id = d.id
		WHERE
			a.report_id = ' . $report_id . ' 
			AND c.platform = "shein" 
		) a 
	GROUP BY
		platform,
		userAccount,
		warehouse_sku 
	ORDER BY
		platform,
		userAccount,
		warehouse_sku 
	) a
	LEFT JOIN ( SELECT DISTINCT user_account, warehouse_sku, seller, product_name FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) b ON a.userAccount = b.user_account 
	AND a.warehouse_sku = b.warehouse_sku 
GROUP BY
	platform,
	userAccount,
	warehouse_sku,
	sale_amount,
	product_name,
	seller;
        ';
    }

    static public function getUserAccountTransfer($report_id): string
    {
        return '
SELECT
	a.platform,
	a.userAccount,
	SUM( b.total ) total 
FROM
	mu_finance_table a
	LEFT JOIN mu_finance_order_transfer b ON a.id = b.table_id 
WHERE
	rid = ' . $report_id . ' 
GROUP BY
	platform,
	userAccount; 
        ';
    }

    static public function getUserAccountSubscription($report_id): string
    {
        return '
SELECT
	a.platform,
	a.userAccount,
	SUM( b.total ) total 
FROM
	mu_finance_table a
	LEFT JOIN mu_finance_order_promotion b ON a.id = b.table_id 
WHERE
	a.rid = ' . $report_id . ' 
	AND b.description = "Subscription" 
GROUP BY
	platform,
	userAccount;
        ';
    }

    static public function getOrderWayfair($report_id): string
    {
        return '
SELECT
	a.invoice_no,
	a.order_no,
	a.invoice_date,
	a.amount,
	a.commission,
	a.shipping,
	a.other,
	a.tax,
	a.collection,
	SUM( b.sale_amount ) sale_amount_core,
	SUM( b.commission ) commission_core,
	SUM( b.collection ) collection_core 
FROM
	mu_finance_order_wayfair a
	LEFT JOIN mu_finance_wayfair_core b ON a.invoice_no = b.invoice_no 
WHERE
	a.report_id = ' . $report_id . ' 
GROUP BY
	a.invoice_no,
	a.order_no,
	a.invoice_date,
	a.amount,
	a.commission,
	a.shipping,
	a.other,
	a.tax,
	a.collection;
        ';
    }

    static public function getOrderResend($month): string
    {
        return '
SELECT
	a.platform,
	a.user_account,
	warehouse_sku,
	SUM( qty ) qty,
	order_status,
	SUM( b.calcuRes ) tail
FROM
	mu_finance_order_statistics a
	LEFT JOIN mu_ecang_order b ON a.saleOrderCode = b.saleOrderCode 
WHERE
	paid_time >= "' . $month . '-01 00:00:00" 
	AND paid_time < "' . date('Y-m', strtotime('+1 month', strtotime($month . '-01'))) . '-01 00:00:00" 
	AND order_type = "resend" 
	AND order_status = "已发货" 
GROUP BY
	warehouse_sku,
	order_status,
	platform,
	user_account UNION ALL
SELECT
	a.platform,
	a.user_account,
	warehouse_sku,
	qty,
	order_status,
	SUM( b.calcuRes ) tail
FROM
	mu_finance_order_statistics a
	LEFT JOIN mu_ecang_order b ON a.saleOrderCode = b.saleOrderCode 
WHERE
	paid_time >= "' . $month . '-01 00:00:00" 
	AND paid_time < "' . date('Y-m', strtotime('+1 month', strtotime($month . '-01'))) . '-01 00:00:00" 
	AND order_type = "resend" 
	AND order_status != "已发货" 
GROUP BY
	warehouse_sku,
	qty,
	order_status,
	platform,
	user_account;
        ';
    }

    static public function getOperationExpenses($report_id): string
    {
        return '
SELECT
	a.type,
	b.user_account,
	b.fulfillment,
	b.warehouse_sku,
	b.total
FROM
	mu_finance_operation_expenses a
	LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
WHERE
	a.report_id = ' . $report_id . ';
        ';
    }

    static public function getOperationFactory($report_id): string
    {
        return '
SELECT
	a.type,
	b.user_account,
	b.fulfillment,
	b.warehouse_sku,
	b.total
FROM
	mu_finance_operation_factory a
	LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
WHERE
	a.report_id = ' . $report_id . ';
        ';
    }

    static public function getOperationDelivery($report_id): string
    {
        return '
SELECT
	a.seller,
	a.platform,
	b.user_account,
	b.fulfillment,
	b.warehouse_sku,
	b.total
FROM
	mu_finance_operation_delivery a
	LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
WHERE
	a.report_id = ' . $report_id . ';
        ';
    }
}
