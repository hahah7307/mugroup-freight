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
	SUM( tail ) tail,
	SUM( calcuRes ) calcuRes,
	paid_time 
FROM
	(
	SELECT
		"Order" AS type,
		a.platform,
		a.userAccount,
		a.payment_id payment,
		b.payment_id,
		b.paid_time,
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
		NULL AS tail,
		NULL AS calcuRes 
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
			AND platform != "wildberries" 
		) a
		LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id
		LEFT JOIN mu_finance_order_outbound e ON b.saleOrderCode = e.saleOrderCode 
		AND b.warehouse_sku = e.warehouse_sku
		LEFT JOIN mu_finance_store f ON e.store_id = f.id 
		WHERE e.report_id = ' . $report_id . '  UNION ALL
	SELECT
		"Order" AS type,
		a.platform,
		a.userAccount,
		a.payment_id payment,
		b.payment_id,
		b.paid_time,
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
		c.calcuRes tail,
	IF
		( b.is_unaccrue, 0, c.calcuRes ) calcuRes 
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
			AND platform != "wildberries" 
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
		a.date paid_time,
		NULL AS saleOrderCode,
		a.sku seller_sku,
		c.warehouse_sku warehouse_sku,
		NULL AS sale_qty,
		a.quantity * c.qty refund_qty,
		NULL AS sale_amount,
		ROUND( ( product_sales + shipping_credits + gift_wrap_credits + regulatory_fee + promotional_rebates ) * c.percent, 7 ) refund_amount,
		NULL AS sale_selling_fees,
		ROUND( a.selling_fees * c.percent, 7 ) refund_selling_fees,
		ROUND( a.fba_fees * c.percent, 7 ) fba_fees,
		NULL AS ddp,
		NULL AS tail,
		NULL AS calcuRes 
	FROM
		mu_finance_order_refund a
		LEFT JOIN mu_finance_table b ON a.table_id = b.id
		LEFT JOIN ( SELECT DISTINCT user_account, seller_sku, warehouse_sku, qty, percent, seller FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) c ON b.userAccount = c.user_account 
		AND a.sku = c.seller_sku 
	WHERE
		report_id = ' . $report_id . ' 
		AND b.platform != "wildberries" 
	) a 
GROUP BY
	type,
	platform,
	userAccount,
	payment,
	payment_id,
	saleOrderCode,
	seller_sku,
	warehouse_sku,
	paid_time;
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
	b.warehouse_sku warehouse_sku,
	a.quantity * b.qty quantity,
	a.payment_amount * b.percent payment_amount,
	a.payment_selling_fees * b.percent payment_selling_fees,
	a.payment_fba_fees * b.percent payment_fba_fees,
	a.outbound_amount * b.percent * - 1 outbound_amount,
	a.outbound_selling_fee * b.percent outbound_selling_fee,
	a.outbound_fba_fee * b.percent outbound_fba_fee 
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
	WHERE
		c.platform != "wildberries" 
	) a
    LEFT JOIN ( SELECT DISTINCT user_account, seller_sku, warehouse_sku, qty, percent, seller FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) b ON a.userAccount = b.user_account 
    AND a.sku = b.seller_sku 
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
	SUM( IFNULL( fba_sale_qty, 0 ) + IFNULL ( fba_refund_qty, 0 ) ) fba_qty_amount,
	SUM( ROUND( fba_sale_amount, 7 ) ) fba_sale_amount,
	SUM( ROUND( fba_sale_tax, 7 ) ) fba_sale_tax,
	SUM( ROUND( fba_refund_amount, 7 ) ) fba_refund_amount,
	SUM( ROUND( IFNULL( fba_sale_amount, 0 ) + IFNULL( fba_sale_tax, 0 ) + IFNULL( fba_refund_amount, 0 ), 7 ) ) fba_amount,
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
		SUM( IFNULL( fba_sale_amount, 0 ) ) + SUM( IFNULL( fba_sale_tax, 0 ) ) + SUM( IFNULL( fba_refund_amount, 0 ) ) + SUM( IFNULL( fba_sale_selling_fees, 0 ) ) + SUM( IFNULL( fba_refund_selling_fees, 0 ) ) + SUM( IFNULL( fba_fees, 0 ) ) + SUM( IFNULL( fba_refund_fees, 0 ) ) + SUM( IFNULL( fba_refund_other, 0 ) ) + SUM( IFNULL( fba_ddp, 0 ) ) + SUM( IFNULL( fba_adCost, 0 ) ) + SUM( IFNULL( fba_inventory, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( liquidation, 0 ) ) + SUM( IFNULL( promotion, 0 ) ) + SUM( IFNULL( shipping_service, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ),
		2 
	) profit,
	ROUND(
		(
			SUM( IFNULL( fba_sale_amount, 0 ) ) + SUM( IFNULL( fba_sale_tax, 0 ) ) + SUM( IFNULL( fba_refund_amount, 0 ) ) + SUM( IFNULL( fba_sale_selling_fees, 0 ) ) + SUM( IFNULL( fba_refund_selling_fees, 0 ) ) + SUM( IFNULL( fba_fees, 0 ) ) + SUM( IFNULL( fba_refund_fees, 0 ) ) + SUM( IFNULL( fba_refund_other, 0 ) ) + SUM( IFNULL( fba_ddp, 0 ) ) + SUM( IFNULL( fba_adCost, 0 ) ) + SUM( IFNULL( fba_inventory, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( liquidation, 0 ) ) + SUM( IFNULL( promotion, 0 ) ) + SUM( IFNULL( shipping_service, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) 
		) / SUM( IFNULL( fba_sale_amount, 0 ) ),
		4 
	) gross_profit_margin,
	SUM( ROUND( evaluation_qty, 3 ) ) evaluation_qty,
	SUM( ROUND( evaluation_amount, 2 ) ) evaluation_amount,
	ROUND(
		SUM( IFNULL( fba_sale_amount, 0 ) ) + SUM( IFNULL( fba_sale_tax, 0 ) ) + SUM( IFNULL( fba_refund_amount, 0 ) ) + SUM( IFNULL( fba_sale_selling_fees, 0 ) ) + SUM( IFNULL( fba_refund_selling_fees, 0 ) ) + SUM( IFNULL( fba_fees, 0 ) ) + SUM( IFNULL( fba_refund_fees, 0 ) ) + SUM( IFNULL( fba_refund_other, 0 ) ) + SUM( IFNULL( fba_ddp, 0 ) ) + SUM( IFNULL( fba_adCost, 0 ) ) + SUM( IFNULL( fba_inventory, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( liquidation, 0 ) ) + SUM( IFNULL( promotion, 0 ) ) + SUM( IFNULL( shipping_service, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + SUM( IFNULL( evaluation_amount, 0 ) ),
		2 
	) profit_include_evaluation,
	ROUND(
		(
			SUM( IFNULL( fba_sale_amount, 0 ) ) + SUM( IFNULL( fba_sale_tax, 0 ) ) + SUM( IFNULL( fba_refund_amount, 0 ) ) + SUM( IFNULL( fba_sale_selling_fees, 0 ) ) + SUM( IFNULL( fba_refund_selling_fees, 0 ) ) + SUM( IFNULL( fba_fees, 0 ) ) + SUM( IFNULL( fba_refund_fees, 0 ) ) + SUM( IFNULL( fba_refund_other, 0 ) ) + SUM( IFNULL( fba_ddp, 0 ) ) + SUM( IFNULL( fba_adCost, 0 ) ) + SUM( IFNULL( fba_inventory, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( liquidation, 0 ) ) + SUM( IFNULL( promotion, 0 ) ) + SUM( IFNULL( shipping_service, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + SUM( IFNULL( evaluation_amount, 0 ) ) 
		) / SUM( IFNULL( fba_sale_amount, 0 ) ),
		2 
	) gross_profit_margin_include_evaluation,
	b.product_name product_name,
	b.seller seller,
	d.user_name purchaser 
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
				c.warehouse_sku warehouse_sku,
				NULL AS fba_sale_qty,
				a.quantity * c.qty fba_refund_qty,
				NULL AS fba_sale_amount,
				NULL AS fba_sale_tax,
				ROUND( ( product_sales + shipping_credits + gift_wrap_credits + regulatory_fee + promotional_rebates ) * c.percent, 7 ) fba_refund_amount,
				NULL AS fba_sale_selling_fees,
				ROUND( a.selling_fees * c.percent, 7 ) fba_refund_selling_fees,
				NULL AS fba_fees,
				ROUND( a.fba_fees * c.percent, 7 ) fba_refund_fees,
				ROUND( other * c.percent, 7 ) fba_refund_other,
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
                LEFT JOIN ( SELECT DISTINCT user_account, seller_sku, warehouse_sku, qty, percent, seller FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) c ON b.userAccount = c.user_account 
                AND a.sku = c.seller_sku 
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
				AND b.platform = "amazon" 
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
			SUM( a.totalAdsCost * d.percent ) fba_adCost,
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
			NULL AS fba_inventory,
			NULL AS adjustment,
			NULL AS liquidation,
			SUM( ( a.sharedLdFee + a.sharedCouponFee + a.sharedVineFee ) * d.percent ) promotion,
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
	        AND a.sharedLdFee + a.sharedCouponFee + a.sharedVineFee != 0 
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
			NULL AS fba_inventory,
			NULL AS adjustment,
			SUM( ( a.sharedLiquidationsFees + a.fbaLiquidationProceeds ) * d.percent ) liquidation,
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
	        AND a.sharedLiquidationsFees + a.fbaLiquidationProceeds != 0
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
			NULL AS fba_inventory,
			NULL AS adjustment,
			SUM( (a.taxCollected + a.taxRefunded) * d.percent ) liquidation,
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
			AND a.countryCode != "US" 
			AND (a.taxCollected + a.taxRefunded) != 0 
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
					a.sharedFbaStorageFee + a.sharedLabelingFee + a.fbaStorageFee + a.longTermStorageFee + a.sharedFbaDisposalFee + a.sharedAmazonPartneredCarrierShipmentFee + a.sharedFbaInboundConvenienceFee + a.sharedFbaInboundDefectFee + a.sharedFbaRemovalFee 
				) * d.percent 
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
			AND a.sharedFbaStorageFee + a.sharedLabelingFee + a.fbaStorageFee + a.longTermStorageFee + a.sharedFbaDisposalFee + a.sharedAmazonPartneredCarrierShipmentFee + a.sharedFbaInboundConvenienceFee + a.sharedFbaInboundDefectFee + a.sharedFbaRemovalFee != 0 
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
			LEFT JOIN mu_finance_order_statistics b ON a.payment = b.payment_id AND a.warehouse_sku = b.warehouse_sku
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
			b.total operation_expenses,
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
			b.total operation_factory,
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
			b.total operation_delivery,
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
	LEFT JOIN mu_ecang_product c ON a.warehouse_sku = c.productSku
	LEFT JOIN mu_ecang_user d ON c.personOpraterId = d.user_id 
GROUP BY
	platform,
	userAccount,
	warehouse_sku,
	fba_sale_amount,
	product_name,
	seller,
	purchaser;
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
	SUM( IFNULL( fbm_sale_qty, 0 ) + IFNULL ( fbm_refund_qty, 0 ) ) fbm_qty_amount,
	SUM( ROUND( fbm_sale_amount, 7 ) ) fbm_sale_amount,
	SUM( ROUND( fbm_sale_tax, 7 ) ) fbm_sale_tax,
	SUM( ROUND( fbm_refund_amount, 7 ) ) fbm_refund_amount,
	SUM( ROUND( IFNULL( fbm_sale_amount, 0 ) + IFNULL( fbm_sale_tax, 0 ) + IFNULL( fbm_refund_amount, 0 ), 7 ) ) fbm_amount,
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
	SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
	SUM( ROUND( operation_expenses, 6 ) ) operation_expenses,
	SUM( ROUND( operation_factory, 6 ) ) operation_factory,
	SUM( ROUND( operation_delivery, 6 ) ) operation_delivery,
	ROUND( SUM( IFNULL( fbm_adCost, 0 ) ) / SUM( IFNULL( fbm_sale_amount, 0 ) ) * - 1, 4 ) ad_percent,
	ROUND( SUM( IFNULL( warehouse_rent, 0 ) ) / SUM( IFNULL( fbm_sale_amount, 0 ) ) * - 1, 4 ) inventory_percent,
	ROUND( SUM( IFNULL( calcuRes, 0 ) ) / SUM( IFNULL( fbm_sale_amount, 0 ) ) * - 1, 4 ) tail_percent,
	ROUND( SUM( IFNULL( fbm_ddp, 0 ) ) / SUM( IFNULL( fbm_sale_amount, 0 ) ) * - 1, 4 ) ddp_percent,
	ROUND(
		SUM( IFNULL( fbm_sale_amount, 0 ) ) + SUM( IFNULL( fbm_sale_tax, 0 ) ) + SUM( IFNULL( fbm_refund_amount, 0 ) ) + SUM( IFNULL( fbm_sale_selling_fees, 0 ) ) + SUM( IFNULL( fbm_refund_selling_fees, 0 ) ) + SUM( IFNULL( fbm_refund_other, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( fbm_ddp, 0 ) ) + SUM( IFNULL( fbm_adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( liquidation, 0 ) ) + SUM( IFNULL( promotion, 0 ) ) + SUM( IFNULL( shipping_service, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ),
		2 
	) profit,
	ROUND(
		(
			SUM( IFNULL( fbm_sale_amount, 0 ) ) + SUM( IFNULL( fbm_sale_tax, 0 ) ) + SUM( IFNULL( fbm_refund_amount, 0 ) ) + SUM( IFNULL( fbm_sale_selling_fees, 0 ) ) + SUM( IFNULL( fbm_refund_selling_fees, 0 ) ) + SUM( IFNULL( fbm_refund_other, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( fbm_ddp, 0 ) ) + SUM( IFNULL( fbm_adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( liquidation, 0 ) ) + SUM( IFNULL( promotion, 0 ) ) + SUM( IFNULL( shipping_service, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) 
		) / SUM( IFNULL( fbm_sale_amount, 0 ) ),
		4 
	) gross_profit_margin,
	SUM( ROUND( evaluation_qty, 3 ) ) evaluation_qty,
	SUM( ROUND( evaluation_amount, 2 ) ) evaluation_amount,
	ROUND(
		SUM( IFNULL( fbm_sale_amount, 0 ) ) + SUM( IFNULL( fbm_sale_tax, 0 ) ) + SUM( IFNULL( fbm_refund_amount, 0 ) ) + SUM( IFNULL( fbm_sale_selling_fees, 0 ) ) + SUM( IFNULL( fbm_refund_selling_fees, 0 ) ) + SUM( IFNULL( fbm_refund_other, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( fbm_ddp, 0 ) ) + SUM( IFNULL( fbm_adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( liquidation, 0 ) ) + SUM( IFNULL( promotion, 0 ) ) + SUM( IFNULL( shipping_service, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + SUM( IFNULL( evaluation_amount, 0 ) ),
		2 
	) profit_include_evaluation,
	ROUND(
		(
			SUM( IFNULL( fbm_sale_amount, 0 ) ) + SUM( IFNULL( fbm_sale_tax, 0 ) ) + SUM( IFNULL( fbm_refund_amount, 0 ) ) + SUM( IFNULL( fbm_sale_selling_fees, 0 ) ) + SUM( IFNULL( fbm_refund_selling_fees, 0 ) ) + SUM( IFNULL( fbm_refund_other, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( fbm_ddp, 0 ) ) + SUM( IFNULL( fbm_adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( liquidation, 0 ) ) + SUM( IFNULL( promotion, 0 ) ) + SUM( IFNULL( shipping_service, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + SUM( IFNULL( evaluation_amount, 0 ) ) 
		) / SUM( IFNULL( fbm_sale_amount, 0 ) ),
		4 
	) gross_profit_margin_include_evaluation,
	b.product_name product_name,
	b.seller seller,
	d.user_name purchaser 
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
		SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
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
			SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
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
			IF
				( b.is_unaccrue, 0, c.calcuRes ) calcuRes,
				NULL AS fbm_ddp,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS liquidation,
				NULL AS promotion,
				NULL AS shipping_service,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
				c.warehouse_sku warehouse_sku,
				NULL AS fbm_sale_qty,
				a.quantity * c.qty fbm_refund_qty,
				NULL AS fbm_sale_amount,
				NULL AS fbm_sale_tax,
				ROUND( ( product_sales + shipping_credits + gift_wrap_credits + regulatory_fee + promotional_rebates ) * c.percent, 7 ) fbm_refund_amount,
				NULL AS fbm_sale_selling_fees,
				ROUND( a.selling_fees * c.percent, 7 ) fbm_refund_selling_fees,
				ROUND( other * c.percent, 7 ) fbm_refund_other,
				NULL AS calcuRes,
				NULL AS fbm_ddp,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS liquidation,
				NULL AS promotion,
				NULL AS shipping_service,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_order_refund a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
                LEFT JOIN ( SELECT DISTINCT user_account, seller_sku, warehouse_sku, qty, percent, seller FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) c ON b.userAccount = c.user_account 
                AND a.sku = c.seller_sku 
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
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
				b.total le_adjustment,
				NULL AS wyd_adjustment 
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
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS liquidation,
				NULL AS promotion,
				NULL AS shipping_service,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				b.total wyd_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND a.platform = "amazon" 
				AND wyd_adjustment IS NOT NULL UNION ALL
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
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
			SUM( a.totalAdsCost * d.percent ) fbm_adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS liquidation,
			NULL AS promotion,
			NULL AS shipping_service,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
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
			NULL AS fbm_adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS liquidation,
			SUM( ( a.sharedLdFee + a.sharedCouponFee + a.sharedVineFee ) * d.percent ) promotion,
			NULL AS shipping_service,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
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
	        AND a.sharedLdFee + a.sharedCouponFee + a.sharedVineFee != 0
			AND is_fba = 0 
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
			NULL AS fbm_adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			SUM( ( a.sharedLiquidationsFees + a.fbaLiquidationProceeds ) * d.percent ) liquidation,
			NULL AS promotion,
			NULL AS shipping_service,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
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
	        AND a.sharedLiquidationsFees + a.fbaLiquidationProceeds != 0
			AND is_fba = 0 
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
			NULL AS fbm_adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			SUM( (a.taxCollected + a.taxRefunded) * d.percent ) liquidation,
			NULL AS promotion,
			NULL AS shipping_service,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
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
			AND a.countryCode != "US" 
			AND (a.taxCollected + a.taxRefunded) != 0 
			AND is_fba = 0 
		GROUP BY
			platform,
			userAccount,
			warehouse_sku UNION ALL
		SELECT
			platform platform,
			user_account userAccount,
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
			NULL AS fbm_adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS liquidation,
			total promotion,
			NULL AS shipping_service,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_ad_cost 
		WHERE
			report_id = ' . $report_id . ' 
			AND platform = "amazon" UNION ALL
		SELECT
			"amazon" AS platform,
			b.user_account,
			a.warehouse_sku,
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
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			b.qty evaluation_qty,
			ROUND( a.cny_actual_paid / c.USD, 2 ) evaluation_amount 
		FROM
			mu_finance_evaluation a
			LEFT JOIN mu_finance_order_statistics b ON a.payment = b.payment_id AND a.warehouse_sku = b.warehouse_sku
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
			NULL AS wyd_adjustment,
			b.total operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_expenses a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
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
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			b.total operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_factory a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
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
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			b.total operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_delivery a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
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
	LEFT JOIN mu_ecang_product c ON a.warehouse_sku = c.productSku
	LEFT JOIN mu_ecang_user d ON c.personOpraterId = d.user_id 
GROUP BY
	platform,
	userAccount,
	warehouse_sku,
	fbm_sale_amount,
	product_name,
	seller,
	purchaser;
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
	SUM( IFNULL( sale_qty, 0 ) + IFNULL ( refund_qty, 0 ) ) qty_amount,
	SUM( ROUND( sale_amount, 7 ) ) sale_amount,
	SUM( ROUND( refund_amount, 7 ) ) refund_amount,
	SUM( ROUND( IFNULL( sale_amount, 0 ) + IFNULL( refund_amount, 0 ), 7 ) ) amount,
	SUM( ROUND( sale_selling_fees, 7 ) ) sale_selling_fees,
	SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
	SUM( ROUND( calcuRes, 7 ) ) calcuRes,
	SUM( ROUND( wfs_fulfillment, 6 ) ) wfs_fulfillment,
	SUM( ROUND( ddp, 2 ) ) ddp,
	SUM( ROUND( adCost, 7 ) ) adCost,
	SUM( ROUND( warehouse_rent, 6 ) ) warehouse_rent,
	SUM( ROUND( wfs_warehouse, 8 ) ) wfs_warehouse,
	SUM( ROUND( wfs_return_shipping, 6 ) ) wfs_return_shipping,
	SUM( ROUND( adjustment, 6 ) ) adjustment,
	SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
	SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
	SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
	SUM( ROUND( wfs_adjustment, 6 ) ) wfs_adjustment,
	SUM( ROUND( operation_expenses, 6 ) ) operation_expenses,
	SUM( ROUND( operation_factory, 6 ) ) operation_factory,
	SUM( ROUND( operation_delivery, 6 ) ) operation_delivery,
	ROUND( SUM( IFNULL( adCost, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ad_percent,
	ROUND( SUM( IFNULL( warehouse_rent, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) warehouse_percent,
	ROUND( SUM( IFNULL( calcuRes, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) tail_percent,
	ROUND( SUM( IFNULL( ddp, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ddp_percent,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( wfs_fulfillment, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( wfs_warehouse, 0 ) ) + SUM( IFNULL( wfs_return_shipping, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + SUM( IFNULL( wfs_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ),
		2 
	) profit,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( wfs_fulfillment, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( wfs_warehouse, 0 ) ) + SUM( IFNULL( wfs_return_shipping, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + SUM( IFNULL( wfs_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		4 
	) gross_profit_margin,
	SUM( ROUND( evaluation_qty, 3 ) ) evaluation_qty,
	SUM( ROUND( evaluation_amount, 2 ) ) evaluation_amount,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( wfs_fulfillment, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( wfs_warehouse, 0 ) ) + SUM( IFNULL( wfs_return_shipping, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + SUM( IFNULL( wfs_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + SUM( IFNULL( evaluation_amount, 0 ) ),
		2 
	) profit_include_evaluation,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( wfs_fulfillment, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( wfs_warehouse, 0 ) ) + SUM( IFNULL( wfs_return_shipping, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + SUM( IFNULL( wfs_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + SUM( IFNULL( evaluation_amount, 0 ) ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		2 
	) gross_profit_margin_include_evaluation,
	b.product_name product_name,
	b.seller seller,
	d.user_name purchaser 
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
		SUM( ROUND( warehouse_rent, 6 ) ) * - 1 warehouse_rent,
		SUM( ROUND( wfs_warehouse, 8 ) ) * - 1 wfs_warehouse,
		SUM( ROUND( wfs_return_shipping, 6 ) ) wfs_return_shipping,
		SUM( ROUND( adjustment, 6 ) ) adjustment,
		SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
		SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
		SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
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
			SUM( ROUND( warehouse_rent, 6) ) warehouse_rent,
			SUM( ROUND( wfs_warehouse, 8 ) ) wfs_warehouse,
			SUM( ROUND( wfs_return_shipping, 6 ) ) wfs_return_shipping,
			SUM( ROUND( adjustment, 6 ) ) adjustment,
			SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
			SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
			SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
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
			IF
				( b.is_unaccrue, 0, c.calcuRes ) calcuRes,
				NULL AS wfs_fulfillment,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS wfs_warehouse,
				NULL AS wfs_return_shipping,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment,
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
				b.payment_id IS NOT NULL
				AND c.`status` = 4 UNION ALL
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
				NULL AS wyd_adjustment,
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
				c.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				a.quantity * c.qty refund_qty,
				NULL AS sale_amount,
				ROUND( ( product_sales ) * c.percent, 7 ) refund_amount,
				NULL AS sale_selling_fees,
				ROUND( a.selling_fees * c.percent, 7 ) refund_selling_fees,
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
				NULL AS wyd_adjustment,
				NULL AS wfs_adjustment 
			FROM
				mu_finance_order_refund a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN ( SELECT DISTINCT user_account, seller_sku, warehouse_sku, qty, percent, seller FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) c ON b.userAccount = c.user_account 
				AND a.sku = c.seller_sku 
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
				NULL AS wyd_adjustment,
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
				NULL AS wyd_adjustment,
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
				NULL AS wyd_adjustment,
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
				NULL AS wyd_adjustment,
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
				NULL AS wyd_adjustment,
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
				NULL AS le_adjustment,
				b.total wyd_adjustment,
				NULL AS wfs_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND a.platform = "walmart" 
				AND wyd_adjustment IS NOT NULL UNION ALL
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
				NULL AS wyd_adjustment,
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
				ROUND( a.total * b.percent, 7 ) wfs_warehouse,
				NULL AS adjustment,
				NULL AS wfs_return_shipping,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment,
				NULL AS wfs_adjustment 
			FROM
				mu_finance_warehouse_wfs a
				LEFT JOIN ( SELECT DISTINCT user_account, seller_sku, warehouse_sku, qty, percent, seller FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) b ON a.user_account = b.user_account 
				AND a.vendor_sku = b.seller_sku 
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
				NULL AS wyd_adjustment,
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
			NULL AS wyd_adjustment,
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
			NULL AS wyd_adjustment,
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
			NULL AS wyd_adjustment,
			NULL AS wfs_adjustment,
			b.total operation_expenses,
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
			NULL AS wyd_adjustment,
			NULL AS wfs_adjustment,
			NULL AS operation_expenses,
			b.total operation_factory,
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
			NULL AS wyd_adjustment,
			NULL AS wfs_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			b.total operation_delivery,
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
	LEFT JOIN mu_ecang_product c ON a.warehouse_sku = c.productSku
	LEFT JOIN mu_ecang_user d ON c.personOpraterId = d.user_id 
GROUP BY
	platform,
	userAccount,
	warehouse_sku,
	sale_amount,
	product_name,
	seller,
	purchaser;
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
	SUM( IFNULL( sale_qty, 0 ) + IFNULL ( refund_qty, 0 ) ) qty_amount,
	SUM( ROUND( sale_amount, 7 ) ) sale_amount,
	SUM( ROUND( refund_amount, 7 ) ) refund_amount,
	SUM( ROUND( IFNULL( sale_amount, 0 ) + IFNULL( refund_amount, 0 ), 7 ) ) amount,
	SUM( ROUND( sale_selling_fees, 7 ) ) sale_selling_fees,
	SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
	SUM( ROUND( calcuRes, 7 ) ) calcuRes,
	SUM( ROUND( ddp, 2 ) ) ddp,
	SUM( ROUND( adCost, 7 ) ) adCost,
	SUM( ROUND( warehouse_rent, 6 ) ) warehouse_rent,
	SUM( ROUND( adjustment, 6 ) ) adjustment,
	SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
	SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
	SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
	SUM( ROUND( operation_expenses, 6 ) ) operation_expenses,
	SUM( ROUND( operation_factory, 6 ) ) operation_factory,
	SUM( ROUND( operation_delivery, 6 ) ) operation_delivery,
	ROUND( SUM( IFNULL( adCost, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ad_percent,
	ROUND( SUM( IFNULL( warehouse_rent, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) warehouse_percent,
	ROUND( SUM( IFNULL( calcuRes, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) tail_percent,
	ROUND( SUM( IFNULL( ddp, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ddp_percent,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ),
		2 
	) profit,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		4 
	) gross_profit_margin,
	SUM( ROUND( evaluation_qty, 3 ) ) evaluation_qty,
	SUM( ROUND( evaluation_amount, 2 ) ) evaluation_amount,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + SUM( IFNULL( evaluation_amount, 0 ) ),
		2 
	) profit_include_evaluation,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + SUM( IFNULL( evaluation_amount, 0 ) ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		2 
	) gross_profit_margin_include_evaluation,
	b.product_name product_name,
	b.seller seller,
	d.user_name purchaser 
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
		SUM( ROUND( warehouse_rent, 6 ) ) * - 1 warehouse_rent,
		SUM( ROUND( adjustment, 6 ) ) adjustment,
		SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
		SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
		SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
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
			SUM( ROUND( warehouse_rent, 6 ) ) warehouse_rent,
			SUM( ROUND( adjustment, 6 ) ) adjustment,
			SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
			SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
			SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
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
			IF
				( b.is_unaccrue, 0, c.calcuRes ) calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
				c.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				a.quantity * c.qty refund_qty,
				NULL AS sale_amount,
				ROUND( ( product_sales ) * c.percent, 7 ) refund_amount,
				NULL AS sale_selling_fees,
				ROUND( a.selling_fees * c.percent, 7 ) refund_selling_fees,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_order_refund a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN ( SELECT DISTINCT user_account, seller_sku, warehouse_sku, qty, percent, seller FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) c ON b.userAccount = c.user_account 
				AND a.sku = c.seller_sku 
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
				NULL AS le_adjustment,
				NULL AS wyd_adjustment
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
				NULL AS wyd_adjustment,
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
				b.total le_adjustment,
				NULL AS wyd_adjustment 
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
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				b.total wyd_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND a.platform = "wayfair" 
				AND wyd_adjustment IS NOT NULL UNION ALL
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
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
			b.user_account userAccount,
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
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			b.qty evaluation_qty,
			ROUND( a.cny_actual_paid / c.USD, 2 ) evaluation_amount 
		FROM
			mu_finance_evaluation a
			LEFT JOIN mu_finance_order_statistics b ON a.payment = b.payment_id AND a.warehouse_sku = b.warehouse_sku
			LEFT JOIN mu_finance_report c ON a.report_id = c.id
			LEFT JOIN mu_ecang_order d ON a.payment = d.saleOrderCode 
		WHERE
			a.report_id = ' . $report_id . ' 
			AND b.platform = "wayfairnew" UNION ALL
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
			NULL AS wyd_adjustment,
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
			NULL AS wyd_adjustment,
			b.total operation_expenses,
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
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			b.total operation_factory,
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
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			b.total operation_delivery,
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
	LEFT JOIN mu_ecang_product c ON a.warehouse_sku = c.productSku
	LEFT JOIN mu_ecang_user d ON c.personOpraterId = d.user_id 
GROUP BY
	platform,
	userAccount,
	warehouse_sku,
	sale_amount,
	product_name,
	seller,
	purchaser;
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
	SUM( IFNULL( sale_qty, 0 ) + IFNULL ( refund_qty, 0 ) ) qty_amount,
	SUM( ROUND( sale_amount, 7 ) ) sale_amount,
	SUM( ROUND( refund_amount, 7 ) ) refund_amount,
	SUM( ROUND( IFNULL( sale_amount, 0 ) + IFNULL( refund_amount, 0 ), 7 ) ) amount,
	SUM( ROUND( sale_selling_fees, 7 ) ) sale_selling_fees,
	SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
	SUM( ROUND( calcuRes, 7 ) ) calcuRes,
	SUM( ROUND( ddp, 2 ) ) ddp,
	SUM( ROUND( adCost, 7 ) ) adCost,
	SUM( ROUND( warehouse_rent, 6 ) ) warehouse_rent,
	SUM( ROUND( adjustment, 6 ) ) adjustment,
	SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
	SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
	SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
	SUM( ROUND( operation_expenses, 6 ) ) operation_expenses,
	SUM( ROUND( operation_factory, 6 ) ) operation_factory,
	SUM( ROUND( operation_delivery, 6 ) ) operation_delivery,
	ROUND( SUM( IFNULL( adCost, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ad_percent,
	ROUND( SUM( IFNULL( warehouse_rent, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) warehouse_percent,
	ROUND( SUM( IFNULL( calcuRes, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) tail_percent,
	ROUND( SUM( IFNULL( ddp, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ddp_percent,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ),
		2 
	) profit,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		4 
	) gross_profit_margin,
	SUM( ROUND( evaluation_qty, 3 ) ) evaluation_qty,
	SUM( ROUND( evaluation_amount, 2 ) ) evaluation_amount,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + SUM( IFNULL( evaluation_amount, 0 ) ),
		2 
	) profit_include_evaluation,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + SUM( IFNULL( evaluation_amount, 0 ) ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		2 
	) gross_profit_margin_include_evaluation,
	b.product_name product_name,
	b.seller seller,
	d.user_name purchaser 
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
		SUM( ROUND( warehouse_rent, 6 ) ) * - 1 warehouse_rent,
		SUM( ROUND( adjustment, 6 ) ) adjustment,
		SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
		SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
		SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
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
			SUM( ROUND( warehouse_rent, 6 ) ) warehouse_rent,
			SUM( ROUND( adjustment, 6 ) ) adjustment,
			SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
			SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
			SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
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
			IF
				( b.is_unaccrue, 0, c.calcuRes ) calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
				b.payment_id IS NOT NULL
				AND c.`status` = 4 UNION ALL
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
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
				c.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				a.quantity * c.qty refund_qty,
				NULL AS sale_amount,
				ROUND( ( product_sales ) * c.percent, 7 ) refund_amount,
				NULL AS sale_selling_fees,
				ROUND( a.selling_fees * c.percent, 7 ) refund_selling_fees,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_order_refund a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN ( SELECT DISTINCT user_account, seller_sku, warehouse_sku, qty, percent, seller FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) c ON b.userAccount = c.user_account 
				AND a.sku = c.seller_sku 
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
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
				b.total le_adjustment,
				NULL AS wyd_adjustment 
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
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				b.total wyd_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "shein" 
				AND wyd_adjustment IS NOT NULL UNION ALL
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
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
			NULL AS wyd_adjustment,
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
			NULL AS wyd_adjustment,
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
			NULL AS wyd_adjustment,
			b.total operation_expenses,
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
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			b.total operation_factory,
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
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			b.total operation_delivery,
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
	LEFT JOIN mu_ecang_product c ON a.warehouse_sku = c.productSku
	LEFT JOIN mu_ecang_user d ON c.personOpraterId = d.user_id 
GROUP BY
	platform,
	userAccount,
	warehouse_sku,
	sale_amount,
	product_name,
	seller,
	purchaser;
        ';
    }

    static public function getTemuWarehouseSkuSql($report_id): string
    {
        return '
SELECT
	platform,
	userAccount,
	a.warehouse_sku,
	SUM( sale_qty ) sale_qty,
	SUM( refund_qty ) refund_qty,
	SUM( IFNULL( sale_qty, 0 ) + IFNULL ( refund_qty, 0 ) ) qty_amount,
	SUM( ROUND( sale_amount, 7 ) ) sale_amount,
	SUM( ROUND( refund_amount, 7 ) ) refund_amount,
	SUM( ROUND( IFNULL( sale_amount, 0 ) + IFNULL( refund_amount, 0 ), 7 ) ) amount,
	SUM( ROUND( sale_selling_fees, 7 ) ) sale_selling_fees,
	SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
	SUM( ROUND( calcuRes, 7 ) ) calcuRes,
	SUM( ROUND( waybill, 7 ) ) waybill,
	SUM( ROUND( ddp, 2 ) ) ddp,
	SUM( ROUND( adCost, 7 ) ) adCost,
	SUM( ROUND( warehouse_rent, 6 ) ) warehouse_rent,
	SUM( ROUND( adjustment, 6 ) ) adjustment,
	SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
	SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
	SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
	SUM( ROUND( operation_expenses, 6 ) ) operation_expenses,
	SUM( ROUND( operation_factory, 6 ) ) operation_factory,
	SUM( ROUND( operation_delivery, 6 ) ) operation_delivery,
	ROUND( SUM( IFNULL( adCost, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ad_percent,
	ROUND( SUM( IFNULL( warehouse_rent, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) warehouse_percent,
	ROUND(
		SUM( IFNULL( calcuRes, 0 ) + IFNULL( waybill, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1,
		4 
	) tail_percent,
	ROUND( SUM( IFNULL( ddp, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ddp_percent,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( waybill, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ),
		2 
	) profit,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( waybill, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		4 
	) gross_profit_margin,
	SUM( ROUND( evaluation_qty, 3 ) ) evaluation_qty,
	SUM( ROUND( evaluation_amount, 2 ) ) evaluation_amount,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( waybill, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + SUM( IFNULL( evaluation_amount, 0 ) ),
		2 
	) profit_include_evaluation,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( waybill, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + SUM( IFNULL( evaluation_amount, 0 ) ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		2 
	) gross_profit_margin_include_evaluation,
	b.product_name product_name,
	b.seller seller,
	d.user_name purchaser 
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
		SUM( ROUND( waybill, 7 ) ) * - 1 waybill,
		SUM( ROUND( ddp, 2 ) ) * - 1 ddp,
		SUM( ROUND( adCost, 7 ) ) adCost,
		SUM( ROUND( warehouse_rent, 6 ) ) * - 1 warehouse_rent,
		SUM( ROUND( adjustment, 6 ) ) adjustment,
		SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
		SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
		SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
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
			SUM( ROUND( waybill, 7 ) ) waybill,
			SUM( ROUND( ddp, 2 ) ) ddp,
			NULL AS adCost,
			SUM( ROUND( warehouse_rent, 6 ) ) warehouse_rent,
			SUM( ROUND( adjustment, 6 ) ) adjustment,
			SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
			SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
			SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
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
			IF
				( b.is_unaccrue, 0, c.calcuRes ) calcuRes,
				d.total waybill,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
					AND b.platform = "temu" 
				) a
				LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id
				LEFT JOIN mu_ecang_order c ON b.saleOrderCode = c.saleOrderCode
				LEFT JOIN ( SELECT waybill_number, SUM( total ) total FROM mu_finance_temu_tail GROUP BY waybill_number ) d ON d.waybill_number = b.shipping_no 
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
				NULL AS waybill,
				c.sku_ddp_unit * b.qty / d.USD ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
					AND b.platform = "temu" 
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
				c.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				a.quantity * c.qty refund_qty,
				NULL AS sale_amount,
				ROUND( ( product_sales ) * c.percent, 7 ) refund_amount,
				NULL AS sale_selling_fees,
				ROUND( a.selling_fees * c.percent, 7 ) refund_selling_fees,
				NULL AS calcuRes,
				NULL AS waybill,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_order_refund a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN ( SELECT DISTINCT user_account, seller_sku, warehouse_sku, qty, percent, seller FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) c ON b.userAccount = c.user_account 
				AND a.sku = c.seller_sku 
			WHERE
				report_id = ' . $report_id . ' 
				AND b.platform = "temu" UNION ALL
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
				NULL AS waybill,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				c.total adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_order_adjustment a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN mu_finance_order_share c ON a.share_code = c.share_code 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND b.platform = "temu" UNION ALL
			SELECT
				"temu" AS platform,
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
				NULL AS waybill,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				b.total lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "temu" 
				AND lc_adjustment IS NOT NULL UNION ALL
			SELECT
				"temu" AS platform,
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
				NULL AS waybill,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				b.total le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "temu" 
				AND le_adjustment IS NOT NULL UNION ALL
			SELECT
				"temu" AS platform,
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
				NULL AS waybill,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				b.total wyd_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "temu" 
				AND wyd_adjustment IS NOT NULL UNION ALL
			SELECT
				"temu" AS platform,
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
				NULL AS waybill,
				NULL AS ddp,
				NULL AS adCost,
				ROUND( SUM( b.total ), 6 ) warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_warehouse_fbm a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "temu" 
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
			"temu" AS platform,
			d.userAccount userAccount,
			a.warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS calcuRes,
			NULL AS waybill,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
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
			AND b.platform = "semitemu" UNION ALL
		SELECT
			"temu" AS platform,
			user_account userAccount,
			warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS calcuRes,
			NULL AS waybill,
			NULL AS ddp,
			total adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_ad_cost 
		WHERE
			report_id = ' . $report_id . ' 
			AND platform = "temu" UNION ALL
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
			NULL AS waybill,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
			b.total operation_expenses,
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
			AND c.platform = "temu" UNION ALL
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
			NULL AS waybill,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			b.total operation_factory,
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
			AND c.platform = "temu" UNION ALL
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
			NULL AS waybill,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			b.total operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_delivery a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount
			LEFT JOIN mu_finance_report d ON a.report_id = d.id 
		WHERE
			a.report_id = ' . $report_id . ' 
			AND c.platform = "temu" 
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
	LEFT JOIN mu_ecang_product c ON a.warehouse_sku = c.productSku
	LEFT JOIN mu_ecang_user d ON c.personOpraterId = d.user_id 
GROUP BY
	platform,
	userAccount,
	warehouse_sku,
	sale_amount,
	product_name,
	seller,
	purchaser;
        ';
    }

    static public function getEbayWarehouseSkuSql($report_id): string
    {
        return '
SELECT
	platform,
	userAccount,
	a.warehouse_sku,
	SUM( sale_qty ) sale_qty,
	SUM( refund_qty ) refund_qty,
	SUM( IFNULL( sale_qty, 0 ) + IFNULL ( refund_qty, 0 ) ) qty_amount,
	SUM( ROUND( sale_amount, 7 ) ) sale_amount,
	SUM( ROUND( sale_tax, 7 ) ) sale_tax,
	SUM( ROUND( refund_amount, 7 ) ) refund_amount,
	SUM( ROUND( IFNULL( sale_amount, 0 ) + IFNULL( sale_tax, 0 ) + IFNULL( refund_amount, 0 ), 7 ) ) amount,
	SUM( ROUND( sale_selling_fees, 7 ) ) sale_selling_fees,
	SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
	SUM( ROUND( calcuRes, 7 ) ) calcuRes,
	SUM( ROUND( ddp, 2 ) ) ddp,
	SUM( ROUND( adCost, 7 ) ) adCost,
	SUM( ROUND( warehouse_rent, 6 ) ) warehouse_rent,
	SUM( ROUND( adjustment, 6 ) ) adjustment,
	SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
	SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
	SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
	SUM( ROUND( operation_expenses, 6 ) ) operation_expenses,
	SUM( ROUND( operation_factory, 6 ) ) operation_factory,
	SUM( ROUND( operation_delivery, 6 ) ) operation_delivery,
	ROUND( SUM( IFNULL( adCost, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ad_percent,
	ROUND( SUM( IFNULL( warehouse_rent, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) warehouse_percent,
	ROUND( SUM( IFNULL( calcuRes, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) tail_percent,
	ROUND( SUM( IFNULL( ddp, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ddp_percent,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = 8 ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = 8 ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = 8 ), 2 ),
		2 
	) profit,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = 8 ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = 8 ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = 8 ), 2 ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		4 
	) gross_profit_margin,
	SUM( ROUND( evaluation_qty, 3 ) ) evaluation_qty,
	SUM( ROUND( evaluation_amount, 2 ) ) evaluation_amount,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = 8 ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = 8 ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = 8 ), 2 ) + SUM( IFNULL( evaluation_amount, 0 ) ),
		2 
	) profit_include_evaluation,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = 8 ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = 8 ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = 8 ), 2 ) + SUM( IFNULL( evaluation_amount, 0 ) ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		2 
	) gross_profit_margin_include_evaluation,
	b.product_name product_name,
	b.seller seller,
	d.user_name purchaser 
FROM
	(
	SELECT
		platform,
		userAccount,
		warehouse_sku,
		SUM( sale_qty ) sale_qty,
		SUM( refund_qty ) * - 1 refund_qty,
		SUM( ROUND( sale_amount, 7 ) ) sale_amount,
		SUM( ROUND( sale_tax, 7 ) ) sale_tax,
		SUM( ROUND( refund_amount, 7 ) ) refund_amount,
		SUM( ROUND( sale_selling_fees, 7 ) ) * - 1 sale_selling_fees,
		SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
		SUM( ROUND( calcuRes, 7 ) ) * - 1 calcuRes,
		SUM( ROUND( ddp, 2 ) ) * - 1 ddp,
		SUM( ROUND( adCost, 7 ) ) adCost,
		SUM( ROUND( warehouse_rent, 6 ) ) * - 1 warehouse_rent,
		SUM( ROUND( adjustment, 6 ) ) adjustment,
		SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
		SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
		SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
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
			SUM( ROUND( sale_tax, 7 ) ) sale_tax,
			SUM( ROUND( refund_amount, 7 ) ) refund_amount,
			SUM( ROUND( sale_selling_fees, 7 ) ) sale_selling_fees,
			SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
			SUM( ROUND( calcuRes, 7 ) ) calcuRes,
			SUM( ROUND( ddp, 2 ) ) ddp,
			NULL AS adCost,
			SUM( ROUND( warehouse_rent, 6 ) ) warehouse_rent,
			SUM( ROUND( adjustment, 6 ) ) adjustment,
			SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
			SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
			SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
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
				NULL AS sale_tax,
				NULL AS refund_amount,
				ROUND( b.selling_fee, 7 ) sale_selling_fees,
				NULL AS refund_selling_fees,
			IF
				( b.is_unaccrue, 0, c.calcuRes ) calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
					AND b.platform = "ebay" 
				) a
				LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id
				LEFT JOIN mu_ecang_order c ON b.saleOrderCode = c.saleOrderCode 
			WHERE
				b.payment_id IS NOT NULL
				AND c.`status` = 4 UNION ALL
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
				NULL AS sale_tax,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				c.sku_ddp_unit * b.qty / d.USD ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment
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
					AND b.platform = "ebay" 
				) a
				LEFT JOIN mu_finance_order_outbound b ON b.payment_id = a.payment_id 
				AND b.report_id = ' . $report_id . '
				LEFT JOIN mu_finance_store c ON b.store_id = c.id
				LEFT JOIN mu_finance_report d ON b.report_id = d.id 
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
				ROUND( b.tax * - 1, 7 ) sale_tax,
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
				NULL AS wyd_adjustment 
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
					AND b.platform = "ebay" 
				) a
				LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id UNION ALL
			SELECT
				b.platform,
				b.userAccount,
				a.payment_id,
				NULL AS saleOrderCode,
				a.sku seller_sku,
				c.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				a.quantity * c.qty refund_qty,
				NULL AS sale_amount,
				NULL AS sale_tax,
				ROUND( ( product_sales ) * c.percent, 7 ) refund_amount,
				NULL AS sale_selling_fees,
				ROUND( a.selling_fees * c.percent, 7 ) refund_selling_fees,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_order_refund a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN ( SELECT DISTINCT user_account, seller_sku, warehouse_sku, qty, percent, seller FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) c ON b.userAccount = c.user_account 
				AND a.sku = c.seller_sku 
			WHERE
				report_id = ' . $report_id . ' 
				AND b.platform = "ebay" UNION ALL
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
				NULL AS sale_tax,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				c.total adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_order_adjustment a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN mu_finance_order_share c ON a.share_code = c.share_code 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND b.platform = "ebay" UNION ALL
			SELECT
				"ebay" AS platform,
				b.user_account userAccount,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				b.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS sale_tax,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				b.total lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "ebay" 
				AND lc_adjustment IS NOT NULL UNION ALL
			SELECT
				"ebay" AS platform,
				b.user_account userAccount,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				b.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS sale_tax,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				b.total le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "ebay" 
				AND le_adjustment IS NOT NULL UNION ALL
			SELECT
				"ebay" AS platform,
				b.user_account userAccount,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				b.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS sale_tax,
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
				b.total wyd_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "ebay" 
				AND wyd_adjustment IS NOT NULL UNION ALL
			SELECT
				"ebay" AS platform,
				b.user_account userAccount,
				NULL AS payment_id,
				NULL AS saleOrderCode,
				NULL AS seller_sku,
				b.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				NULL AS refund_qty,
				NULL AS sale_amount,
				NULL AS sale_tax,
				NULL AS refund_amount,
				NULL AS sale_selling_fees,
				NULL AS refund_selling_fees,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				ROUND( SUM( b.total ), 6 ) warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_warehouse_fbm a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "ebay" 
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
			"ebay" AS platform,
			d.userAccount userAccount,
			a.warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS sale_tax,
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
			NULL AS wyd_adjustment,
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
			AND b.platform = "ebay" UNION ALL
		SELECT
			"ebay" AS platform,
			user_account userAccount,
			warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS sale_tax,
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
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_ad_cost 
		WHERE
			report_id = ' . $report_id . ' 
			AND platform = "ebay" UNION ALL
		SELECT
			c.platform AS platform,
			c.userAccount userAccount,
			b.warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS sale_tax,
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
			NULL AS wyd_adjustment,
			b.total operation_expenses,
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
			AND c.platform = "ebay" UNION ALL
		SELECT
			c.platform AS platform,
			c.userAccount userAccount,
			b.warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS sale_tax,
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
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			b.total operation_factory,
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
			AND c.platform = "ebay" UNION ALL
		SELECT
			c.platform AS platform,
			c.userAccount userAccount,
			b.warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS sale_tax,
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
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			b.total operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_delivery a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount
			LEFT JOIN mu_finance_report d ON a.report_id = d.id 
		WHERE
			a.report_id = ' . $report_id . ' 
			AND c.platform = "ebay" 
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
	LEFT JOIN mu_ecang_product c ON a.warehouse_sku = c.productSku
	LEFT JOIN mu_ecang_user d ON c.personOpraterId = d.user_id 
GROUP BY
	platform,
	userAccount,
	warehouse_sku,
	sale_amount,
	product_name,
	seller,
	purchaser;
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
	LEFT JOIN mu_finance_order_subscription b ON a.id = b.table_id 
WHERE
	a.rid = ' . $report_id . ' 
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

    static public function getOrderResend($report_id, $month): string
    {
        return '
SELECT
	a.platform,
	a.user_account,
	a.saleOrderCode,
	a.warehouse_sku,
	qty,
	order_status,
	b.calcuRes tail,
	a.paid_time,
	c.seller,
	e.user_name purchaser 
FROM
	mu_finance_order_statistics a
	LEFT JOIN mu_ecang_order b ON a.saleOrderCode = b.saleOrderCode
	LEFT JOIN ( SELECT DISTINCT user_account, warehouse_sku, seller, product_name FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) c ON a.user_account = c.user_account 
	AND a.warehouse_sku = c.warehouse_sku
	LEFT JOIN mu_ecang_product d ON a.warehouse_sku = d.productSku
	LEFT JOIN mu_ecang_user e ON d.personOpraterId = e.user_id 
WHERE
	paid_time >= "' . $month . '-01 00:00:00" 
	AND paid_time < "' . date('Y-m', strtotime('+1 month', strtotime($month . '-01'))) . '-01 00:00:00" 
	AND order_type IN ("resend", "重发订单")
	AND order_status = "已发货" UNION ALL
SELECT
	a.platform,
	a.user_account,
	a.saleOrderCode,
	a.warehouse_sku,
	qty,
	order_status,
	b.calcuRes tail,
	a.paid_time,
	c.seller,
	e.user_name purchaser 
FROM
	mu_finance_order_statistics a
	LEFT JOIN mu_ecang_order b ON a.saleOrderCode = b.saleOrderCode
	LEFT JOIN ( SELECT DISTINCT user_account, warehouse_sku, seller, product_name FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) c ON a.user_account = c.user_account 
	AND a.warehouse_sku = c.warehouse_sku
	LEFT JOIN mu_ecang_product d ON a.warehouse_sku = d.productSku
	LEFT JOIN mu_ecang_user e ON d.personOpraterId = e.user_id 
WHERE
	paid_time >= "' . $month . '-01 00:00:00" 
	AND paid_time < "' . date('Y-m', strtotime('+1 month', strtotime($month . '-01'))) . '-01 00:00:00" 
	AND order_type IN ("resend", "重发订单")
	AND order_status != "已发货"
ORDER BY
	order_status ASC,
	paid_time ASC;
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
	b.total,
	a.content
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
	b.total,
	a.content
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
	b.total,
	a.tracking_number
FROM
	mu_finance_operation_delivery a
	LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
WHERE
	a.report_id = ' . $report_id . ';
        ';
    }

    static public function getSaleAmountDiffSql($report_id): string
    {
        return '
SELECT
	a.*,
	b.id order_statistics_id,
	b.saleOrderCode,
	b.sale_amount,
	b.selling_fee,
	b.fba_fee,
	b.seller_sku,
	b.warehouse_sku 
FROM
	(
	SELECT DISTINCT
		order_statistic_user_account,
		payment,
		payment_sale_amount,
		payment_selling_fees,
		payment_fba_fees,
		order_statistic_payment,
		order_statistic_sale_amount,
		order_statistic_selling_fees,
		order_statistic_fba_fees,
		order_statistic_tax
	FROM
		(
		SELECT
			a.payment_id order_statistic_payment,
			a.userAccount order_statistic_user_account,
			ROUND( SUM( b.sale_amount ), 7 ) order_statistic_sale_amount,
			ROUND( SUM( b.selling_fee ), 7 ) order_statistic_selling_fees,
			ROUND( SUM( b.fba_fee ), 7 ) order_statistic_fba_fees,
			ROUND( SUM( b.tax ), 7 ) order_statistic_tax 
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
			) a
			LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id 
		GROUP BY
			order_statistic_payment,
			order_statistic_user_account 
		) a
		LEFT JOIN (
		SELECT
			payment_id payment,
			SUM( product_sales + shipping_credits + gift_wrap_credits + regulatory_fee + promotional_rebates ) payment_sale_amount,
			SUM( selling_fees ) payment_selling_fees,
			SUM( fba_fees ) payment_fba_fees 
		FROM
			mu_finance_order_sale 
		WHERE
			report_id = ' . $report_id . ' 
		GROUP BY
			payment 
		) b ON a.order_statistic_payment = b.payment 
	WHERE
		a.order_statistic_sale_amount != b.payment_sale_amount
	) a
	LEFT JOIN mu_finance_order_statistics b ON a.payment = b.payment_id;
        ';
    }

    static public function getSellingFeeDiffSql($report_id): string
    {
        return '
SELECT
	a.*,
	b.id order_statistics_id,
	b.saleOrderCode,
	b.sale_amount,
	b.selling_fee,
	b.fba_fee,
	b.seller_sku,
	b.warehouse_sku 
FROM
	(
	SELECT DISTINCT
		order_statistic_user_account,
		payment,
		payment_sale_amount,
		payment_selling_fees,
		payment_fba_fees,
		order_statistic_payment,
		order_statistic_sale_amount,
		order_statistic_selling_fees,
		order_statistic_fba_fees,
		order_statistic_tax
	FROM
		(
		SELECT
			a.payment_id order_statistic_payment,
			a.userAccount order_statistic_user_account,
			ROUND( SUM( b.sale_amount ), 7 ) order_statistic_sale_amount,
			ROUND( SUM( b.selling_fee ), 7 ) order_statistic_selling_fees,
			ROUND( SUM( b.fba_fee ), 7 ) order_statistic_fba_fees,
			ROUND( SUM( b.tax ), 7 ) order_statistic_tax 
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
			) a
			LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id 
		GROUP BY
			order_statistic_payment,
			order_statistic_user_account 
		) a
		LEFT JOIN (
		SELECT
			payment_id payment,
			SUM( product_sales + shipping_credits + gift_wrap_credits + regulatory_fee + promotional_rebates ) payment_sale_amount,
			SUM( selling_fees ) payment_selling_fees,
			SUM( fba_fees ) payment_fba_fees 
		FROM
			mu_finance_order_sale 
		WHERE
			report_id = ' . $report_id . ' 
		GROUP BY
			payment 
		) b ON a.order_statistic_payment = b.payment 
	WHERE
 		a.order_statistic_selling_fees != b.payment_selling_fees * - 1
	) a
	LEFT JOIN mu_finance_order_statistics b ON a.payment = b.payment_id;
        ';
    }

    static public function getFbaFeeDiffSql($report_id): string
    {
        return '
SELECT
	a.*,
	b.id order_statistics_id,
	b.saleOrderCode,
	b.sale_amount,
	b.selling_fee,
	b.fba_fee,
	b.seller_sku,
	b.warehouse_sku 
FROM
	(
	SELECT DISTINCT
		order_statistic_user_account,
		payment,
		payment_sale_amount,
		payment_selling_fees,
		payment_fba_fees,
		order_statistic_payment,
		order_statistic_sale_amount,
		order_statistic_selling_fees,
		order_statistic_fba_fees,
		order_statistic_tax
	FROM
		(
		SELECT
			a.payment_id order_statistic_payment,
			a.userAccount order_statistic_user_account,
			ROUND( SUM( b.sale_amount ), 7 ) order_statistic_sale_amount,
			ROUND( SUM( b.selling_fee ), 7 ) order_statistic_selling_fees,
			ROUND( SUM( b.fba_fee ), 7 ) order_statistic_fba_fees,
			ROUND( SUM( b.tax ), 7 ) order_statistic_tax 
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
			) a
			LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id 
		GROUP BY
			order_statistic_payment,
			order_statistic_user_account 
		) a
		LEFT JOIN (
		SELECT
			payment_id payment,
			SUM( product_sales + shipping_credits + gift_wrap_credits + regulatory_fee + promotional_rebates ) payment_sale_amount,
			SUM( selling_fees ) payment_selling_fees,
			SUM( fba_fees ) payment_fba_fees 
		FROM
			mu_finance_order_sale 
		WHERE
			report_id = ' . $report_id . ' 
		GROUP BY
			payment 
		) b ON a.order_statistic_payment = b.payment 
	WHERE
 		a.order_statistic_fba_fees != b.payment_fba_fees * -1
	) a
	LEFT JOIN mu_finance_order_statistics b ON a.payment = b.payment_id;
        ';
    }

    static public function generateOutboundSql($report_id): string
    {
        return '
SELECT DISTINCT
	a.report_id,
	a.table_id,
	b.platform,
	b.user_account,
	b.payment_id,
	b.saleOrderCode,
	b.paid_time,
	b.shipping_time,
	b.platform_sku seller_sku,
	b.warehouse_sku,
	a.fulfillment,
	b.qty 
FROM
	mu_finance_order_sale a
	LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id 
WHERE
	a.report_id = ' . $report_id . '
	AND b.saleOrderCode IS NOT NULL
ORDER BY
	b.paid_time;
        ';
    }

    static public function getOrderStatisticSellingFeesAutoEditSql($report_id): string
    {
        return '
SELECT
    ' . $report_id . ' AS report_id,
    "SELLING_FEE" AS type,
	a.*,
	b.id order_statistics_id,
	b.saleOrderCode,
	b.sale_amount,
	b.selling_fee,
	b.fba_fee,
	b.seller_sku,
	b.warehouse_sku 
FROM
	(
	SELECT DISTINCT
		order_statistic_user_account,
		payment,
		payment_sale_amount,
		payment_selling_fees,
		payment_fba_fees 
	FROM
		(
		SELECT
			a.payment_id order_statistic_payment,
			a.userAccount order_statistic_user_account,
			ROUND( SUM( b.sale_amount ), 7 ) order_statistic_sale_amount,
			ROUND( SUM( b.selling_fee ), 7 ) order_statistic_selling_fees,
			ROUND( SUM( b.fba_fee ), 7 ) order_statistic_fba_fees 
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
			) a
			LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id 
		GROUP BY
			order_statistic_payment,
			order_statistic_user_account 
		) a
		LEFT JOIN (
		SELECT
			payment_id payment,
			SUM( product_sales + shipping_credits + gift_wrap_credits + regulatory_fee + promotional_rebates ) payment_sale_amount,
			SUM( selling_fees ) payment_selling_fees,
			SUM( fba_fees ) payment_fba_fees 
		FROM
			mu_finance_order_sale
        WHERE
            report_id = ' . $report_id . ' 
		GROUP BY
			payment 
		) b ON a.order_statistic_payment = b.payment 
	WHERE
		a.order_statistic_selling_fees != b.payment_selling_fees * - 1
	) a
	LEFT JOIN mu_finance_order_statistics b ON a.payment = b.payment_id;
        ';
    }

    static function getOrderStatisticFbaFeesAutoEditSql($report_id): string
    {
        return '
SELECT
	' . $report_id . ' AS report_id,
	"FBA_FEE" AS type,
	a.*,
	b.id order_statistics_id,
	b.saleOrderCode,
	b.sale_amount,
	b.selling_fee,
	b.fba_fee,
	b.seller_sku,
	b.warehouse_sku 
FROM
	(
	SELECT DISTINCT
		order_statistic_user_account,
		payment,
		payment_sale_amount,
		payment_selling_fees,
		payment_fba_fees 
	FROM
		(
		SELECT
			a.payment_id order_statistic_payment,
			a.userAccount order_statistic_user_account,
			ROUND( SUM( b.sale_amount ), 7 ) order_statistic_sale_amount,
			ROUND( SUM( b.selling_fee ), 7 ) order_statistic_selling_fees,
			ROUND( SUM( b.fba_fee ), 7 ) order_statistic_fba_fees 
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
			) a
			LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id 
		GROUP BY
			order_statistic_payment,
			order_statistic_user_account 
		) a
		LEFT JOIN (
		SELECT
			payment_id payment,
			SUM( product_sales + shipping_credits + gift_wrap_credits + regulatory_fee + promotional_rebates ) payment_sale_amount,
			SUM( selling_fees ) payment_selling_fees,
			SUM( fba_fees ) payment_fba_fees 
		FROM
			mu_finance_order_sale
        WHERE
            report_id = ' . $report_id . ' 
		GROUP BY
			payment 
		) b ON a.order_statistic_payment = b.payment 
	WHERE
		a.order_statistic_fba_fees != b.payment_fba_fees * -1
	) a
	LEFT JOIN mu_finance_order_statistics b ON a.payment = b.payment_id;
        ';
    }

    static public function getOrderStatisticsTemuSaleSync($tableIds, $report_id): string
    {
        return '
SELECT
	c.id,
	ROUND( b.total * d.percent / b.qty, 3 ) sale_amount 
FROM
	mu_finance_order_sale a
	LEFT JOIN (
	SELECT
		order_id,
		contribution_sku,
		SUM( quantity_shipped ) qty,
		SUM( base_price_total * quantity_shipped ) total 
	FROM
		mu_finance_order_temu_detail 
	WHERE
		table_id = ' . $tableIds[1] . ' 
	GROUP BY
		order_id,
		contribution_sku 
	) b ON a.payment_id = b.order_id
	LEFT JOIN mu_finance_order_statistics c ON a.payment_id = c.payment_id
	LEFT JOIN mu_finance_sku_relation d ON b.contribution_sku = d.seller_sku 
	AND c.warehouse_sku = d.warehouse_sku 
WHERE
	a.table_id = ' . $tableIds[0] . ' 
	AND d.report_id = ' . $report_id . ' 
	AND d.user_account = "TEMU_TOLEAD_HOME";
        ';
    }

    static public function getWarehouseRentJoinSql($lastDay, $report_id): string
    {
        return '
SELECT
	' . $report_id . ' AS report_id,
	a.sku,
	a.main_platform,
	a.total,
	IFNULL( quantity, 0 ) quantity 
FROM
	( SELECT sku, main_platform, SUM( total ) total FROM mu_finance_warehouse WHERE report_id = ' . $report_id . ' GROUP BY sku, main_platform ) a
	LEFT JOIN ( SELECT sku, SUM( quantity ) quantity FROM mu_finance_warehouse WHERE report_id = ' . $report_id . ' AND date = "' . $lastDay . '" GROUP BY sku ) b ON a.sku = b.sku 
WHERE
	total != 0;
        ';
    }

    static public function getOperationFactoryClaim($month): string
    {
        return '
SELECT
	* 
FROM
	mu_finance_operation_factory_claim 
WHERE
	`month` = ' . $month . ';
        ';
    }

    static public function getOutboundAccountingByReport($report_id): string
    {
        return '
SELECT DISTINCT
	b.platform,
	b.user_account,
	b.payment_id,
	b.saleOrderCode,
	b.paid_time,
	b.shipping_time,
	b.platform_sku seller_sku,
	b.warehouse_sku,
	a.fulfillment,
	b.qty,
	b.is_finished is_accounting 
FROM
	mu_finance_order_sale a
	LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id 
WHERE
	a.report_id = ' . $report_id . ' 
	AND b.saleOrderCode IS NOT NULL 
	AND b.is_finished = 1 
ORDER BY
	b.paid_time;
        ';
    }

    static public function getWildberriesOrderSql($report_id): string
    {
        return '
SELECT
	"Order" type,
	"wildberries" platform,
	"Wildberries" user_account,
	c.order_no payment_id,
	a.description,
	a.sku,
	a.quantity sale_qty,
	0 AS refund_qty,
	a.product_sales sale_amount,
	0 AS refund_amount,
	a.selling_fees sale_selling_fees,
	0 AS refund_selling_fees,
	a.regulatory_fee sale_regulatory_fee,
	0 AS refund_regulatory_fee,
	a.total sale_total,
	0 AS refund_total,
	0 AS shipping_fee,
	DATE_FORMAT(a.date, "%Y-%m-%d") delivery_time 
FROM
	mu_finance_order_sale a
	LEFT JOIN mu_finance_table b ON a.table_id = b.id
	LEFT JOIN mu_finance_wildberries_order c ON a.description = c.fbs_no 
WHERE
	a.report_id = ' . $report_id . ' 
	AND b.platform = "wildberries" UNION ALL
SELECT
	"Refund" type,
	"wildberries" platform,
	"Wildberries" user_account,
	c.order_no payment_id,
	a.description,
	a.sku,
	0 AS sale_qty,
	a.quantity refund_qty,
	0 AS sale_amount,
	a.product_sales refund_amount,
	0 AS sale_selling_fees,
	a.selling_fees refund_selling_fees,
	0 AS sale_regulatory_fee,
	a.regulatory_fee refund_regulatory_fee,
	0 AS sale_total,
	a.total refund_total,
	0 AS shipping_fee,
	DATE_FORMAT(a.date, "%Y-%m-%d") delivery_time 
FROM
	mu_finance_order_refund a
	LEFT JOIN mu_finance_table b ON a.table_id = b.id
	LEFT JOIN mu_finance_wildberries_order c ON a.description = c.fbs_no 
WHERE
	a.report_id = ' . $report_id . ' 
	AND b.platform = "wildberries" UNION ALL
SELECT
	"Logistics" type,
	"wildberries" platform,
	"Wildberries" user_account,
	c.order_no payment_id,
	a.description,
	a.sku,
	0 sale_qty,
	0 AS refund_qty,
	0 sale_amount,
	0 AS refund_amount,
	0 sale_selling_fees,
	0 AS refund_selling_fees,
	0 sale_regulatory_fee,
	0 AS refund_regulatory_fee,
	0 sale_total,
	0 AS refund_total,
	shipping_fee,
	DATE_FORMAT(a.date, "%Y-%m-%d") delivery_time 
FROM
	mu_finance_wildberries_shipping a
	LEFT JOIN mu_finance_table b ON a.table_id = b.id
	LEFT JOIN mu_finance_wildberries_order c ON a.description = c.fbs_no 
WHERE
	a.report_id = ' . $report_id . ' 
	AND b.platform = "wildberries"
        ';
    }

    static public function getWildberriesWarehouseSkuSql($report_id): string
    {
        return '
SELECT
	platform,
	user_account,
	payment_id,
	a.description description,
	b.product_name,
	b.sku,
	sale_qty,
	refund_qty,
	sale_amount,
	refund_amount,
	sale_selling_fees,
	refund_selling_fees,
	sale_regulatory_fee,
	refund_regulatory_fee,
	sale_total,
	refund_total,
	a.shipping_fee,
	IFNULL( b.total * - 1, 0 ) cost,
	IFNULL( b.shipping_fee * - 1, 0 ) domestic_shipping,
	adjustment,
	sale_total + refund_total + a.shipping_fee + IFNULL( b.total * - 1, 0 ) + IFNULL( b.shipping_fee * - 1, 0 ) + adjustment profit,
	ROUND(
		(
			sale_total + refund_total + a.shipping_fee + IFNULL( b.total * - 1, 0 ) + IFNULL( b.shipping_fee * - 1, 0 ) + adjustment 
		) / sale_amount,
		4 
	) gross_profit_margin 
FROM
	(
	SELECT
		platform,
		user_account,
		payment_id,
		description,
		SUM( sale_qty ) sale_qty,
		SUM( refund_qty ) refund_qty,
		SUM( sale_amount ) sale_amount,
		SUM( refund_amount ) refund_amount,
		SUM( sale_selling_fees ) sale_selling_fees,
		SUM( refund_selling_fees ) refund_selling_fees,
		SUM( sale_regulatory_fee ) sale_regulatory_fee,
		SUM( refund_regulatory_fee ) refund_regulatory_fee,
		ROUND( SUM( sale_total ), 2 ) sale_total,
		SUM( refund_total ) refund_total,
		SUM( a.shipping_fee ) shipping_fee,
		SUM( adjustment ) adjustment 
	FROM
		(
		SELECT
			platform,
			user_account,
			payment_id,
			description,
			SUM( sale_qty ) sale_qty,
			SUM( refund_qty ) refund_qty,
			SUM( sale_amount ) sale_amount,
			SUM( refund_amount ) refund_amount,
			SUM( sale_selling_fees ) sale_selling_fees,
			SUM( refund_selling_fees ) refund_selling_fees,
			SUM( sale_regulatory_fee ) sale_regulatory_fee,
			SUM( refund_regulatory_fee ) refund_regulatory_fee,
			ROUND( SUM( sale_total ), 2 ) sale_total,
			SUM( refund_total ) refund_total,
			ROUND( SUM( a.shipping_fee ), 2 ) shipping_fee,
			SUM( adjustment ) adjustment 
		FROM
			(
			SELECT
				platform,
				user_account,
				payment_id,
				description,
				SUM( sale_qty ) sale_qty,
				SUM( refund_qty ) refund_qty,
				SUM( sale_amount ) sale_amount,
				SUM( refund_amount ) refund_amount,
				SUM( sale_selling_fees ) sale_selling_fees,
				SUM( refund_selling_fees ) refund_selling_fees,
				SUM( sale_regulatory_fee ) sale_regulatory_fee,
				SUM( refund_regulatory_fee ) refund_regulatory_fee,
				SUM( sale_total ) sale_total,
				SUM( refund_total ) refund_total,
				SUM( shipping_fee ) shipping_fee,
				SUM( adjustment ) adjustment 
			FROM
				(
				SELECT
					"wildberries" platform,
					"Wildberries" user_account,
					a.description payment_id,
					a.description,
					a.quantity sale_qty,
					0 AS refund_qty,
					a.product_sales sale_amount,
					0 AS refund_amount,
					a.selling_fees sale_selling_fees,
					0 AS refund_selling_fees,
					a.regulatory_fee sale_regulatory_fee,
					0 AS refund_regulatory_fee,
					a.total sale_total,
					0 AS refund_total,
					0 AS shipping_fee,
					0 AS adjustment 
				FROM
					mu_finance_order_sale a
					LEFT JOIN mu_finance_table b ON a.table_id = b.id 
				WHERE
					a.report_id = ' . $report_id . ' 
					AND b.platform = "wildberries" UNION ALL
				SELECT
					"wildberries" platform,
					"Wildberries" user_account,
					a.description payment_id,
					a.description,
					0 AS sale_qty,
					a.quantity refund_qty,
					0 AS sale_amount,
					a.product_sales refund_amount,
					0 AS sale_selling_fees,
					a.selling_fees refund_selling_fees,
					0 AS sale_regulatory_fee,
					a.regulatory_fee refund_regulatory_fee,
					0 AS sale_total,
					a.total refund_total,
					0 AS shipping_fee,
					0 AS adjustment 
				FROM
					mu_finance_order_refund a
					LEFT JOIN mu_finance_table b ON a.table_id = b.id 
				WHERE
					a.report_id = ' . $report_id . ' 
					AND b.platform = "wildberries" UNION ALL
				SELECT
					"wildberries" platform,
					"Wildberries" user_account,
					"" AS payment_id,
					0 AS description,
					0 AS sale_qty,
					0 refund_qty,
					0 AS sale_amount,
					0 refund_amount,
					0 AS sale_selling_fees,
					0 refund_selling_fees,
					0 AS sale_regulatory_fee,
					0 refund_regulatory_fee,
					0 AS sale_total,
					0 refund_total,
					0 AS shipping_fee,
					a.total adjustment 
				FROM
					mu_finance_order_adjustment a
					LEFT JOIN mu_finance_table b ON a.table_id = b.id 
				WHERE
					a.report_id = ' . $report_id . ' 
					AND b.platform = "wildberries" 
				) a 
			GROUP BY
				platform,
				user_account,
				payment_id,
				description 
			) a 
		GROUP BY
			platform,
			user_account,
			payment_id,
			description,
			sale_amount UNION ALL
		SELECT
			"wildberries" platform,
			"Wildberries" user_account,
			a.description payment_id,
			a.description,
			0 sale_qty,
			0 AS refund_qty,
			0 sale_amount,
			0 AS refund_amount,
			0 sale_selling_fees,
			0 AS refund_selling_fees,
			0 sale_regulatory_fee,
			0 AS refund_regulatory_fee,
			0 sale_total,
			0 AS refund_total,
			SUM( shipping_fee ) shipping_fee,
			0 AS adjustment 
		FROM
			mu_finance_wildberries_shipping a
			LEFT JOIN mu_finance_table b ON a.table_id = b.id 
		WHERE
			a.report_id = ' . $report_id . ' 
			AND b.platform = "wildberries" 
		GROUP BY
			platform,
			user_account,
			description 
		) a 
	GROUP BY
		platform,
		user_account,
		payment_id,
		description 
	) a
	LEFT JOIN (
	SELECT
		order_no,
		product_name,
		sku,
		SUM( total ) total,
		SUM( shipping_fee ) shipping_fee 
	FROM
		mu_finance_wildberries_fee 
	WHERE
		report_id = ' . $report_id . ' 
	GROUP BY
		order_no,
		product_name,
	sku 
	) b ON a.payment_id = b.order_no;
        ';
    }

    static public function getWildberriesFeeUpdateSql($report_id, $month): string
    {
        return '
SELECT DISTINCT
	' . $report_id . ' AS report_id,
	"' . $month . '" AS calculate_month,
	b.order_no,
	b.total,
	b.id 
FROM
	(
	SELECT DISTINCT
		description 
	FROM
		mu_finance_order_sale a
		LEFT JOIN mu_finance_table b ON a.table_id = b.id 
	WHERE
		a.report_id = ' . $report_id . ' 
		AND b.platform = "wildberries" UNION ALL
	SELECT DISTINCT
		description 
	FROM
		mu_finance_order_refund a
		LEFT JOIN mu_finance_table b ON a.table_id = b.id 
	WHERE
		a.report_id = ' . $report_id . ' 
		AND b.platform = "wildberries" UNION ALL
	SELECT DISTINCT
		description 
	FROM
		mu_finance_wildberries_shipping a
		LEFT JOIN mu_finance_table b ON a.table_id = b.id 
	WHERE
		a.report_id = ' . $report_id . ' 
		AND b.platform = "wildberries" 
	) a
	LEFT JOIN ( SELECT id, order_no, sum( total ) total FROM mu_finance_wildberries_fee WHERE report_id IS NULL AND calculate_month IS NULL GROUP BY id, order_no ) b ON a.description = b.order_no 
WHERE
	a.description != 0 
	AND b.order_no IS NOT NULL;
        ';
    }

    static public function getWildberriesCostSql(): string
    {
        return '
SELECT
	order_no,
	sku,
	product_name,
	unit_price,
	quantity,
	total,
	DATE_FORMAT( created_date, "%Y-%m-%d" ) created_date,
	`month`,
	calculate_month 
FROM
	mu_finance_wildberries_fee 
ORDER BY
	created_date DESC;
	    ';
    }

    // 野莓平台本月采购成本SQL
    static public function getWildberriesMonthCostSql($monthInt): string
    {
        return '
SELECT
	order_no,
	sku,
	product_name,
	unit_price,
	quantity,
	total,
	DATE_FORMAT( created_date, "%Y-%m-%d" ) created_date,
	`month`,
	calculate_month 
FROM
	mu_finance_wildberries_fee 
WHERE
	`month` = ' . $monthInt . ' 
ORDER BY
	created_date DESC;
	    ';
    }

    // 野莓平台本月核算成本SQL
    static public function getWildberriesMonthCostAccountingSql($monthInt): string
    {
        return '
SELECT
	order_no,
	sku,
	product_name,
	unit_price,
	quantity,
	total,
	DATE_FORMAT( created_date, "%Y-%m-%d" ) created_date,
	`month`,
	calculate_month 
FROM
	mu_finance_wildberries_fee 
WHERE
	calculate_month = ' . $monthInt . ' 
ORDER BY
	created_date DESC;
        ';
    }

    // 野莓平台本月应计提成本SQL
    static public function getWildberriesMonthAccrualSql($monthInt): string
    {
        return '
SELECT
	order_no,
	sku,
	product_name,
	unit_price,
	quantity,
	total,
	DATE_FORMAT( created_date, "%Y-%m-%d" ) created_date,
	`month`,
	calculate_month 
FROM
	mu_finance_wildberries_fee 
WHERE
	`month` < ' . $monthInt . ' 
	AND calculate_month IS NULL 
ORDER BY
	created_date DESC;
        ';
    }

    static public function getWildberriesExpressDeliverySql($month): string
    {
        return '
SELECT
	* 
FROM
	mu_finance_wildberries_express_delivery 
WHERE
	`month` = ' . $month . ';
	    ';
    }

    static public function getWildberriesCostReturnSql($month): string
    {
        return '
SELECT
	* 
FROM
	mu_finance_wildberries_cost_return
WHERE
	`month` = ' . $month . ';
	    ';
    }

    static public function getTiktokWarehouseSkuSql($report_id): string
    {
        return '
SELECT
	platform,
	userAccount,
	a.warehouse_sku,
	SUM( sale_qty ) sale_qty,
	SUM( refund_qty ) refund_qty,
	SUM( IFNULL( sale_qty, 0 ) + IFNULL ( refund_qty, 0 ) ) qty_amount,
	SUM( ROUND( sale_amount, 7 ) ) sale_amount,
	SUM( ROUND( refund_amount, 7 ) ) refund_amount,
	SUM( ROUND( IFNULL( sale_amount, 0 ) + IFNULL( refund_amount, 0 ), 7 ) ) amount,
	SUM( ROUND( sale_selling_fees, 7 ) ) sale_selling_fees,
	SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
	SUM( ROUND( fba_fee, 7 ) ) fba_fee,
	SUM( ROUND( sale_tax, 7 ) ) sale_tax,
	SUM( ROUND( calcuRes, 7 ) ) calcuRes,
	SUM( ROUND( ddp, 2 ) ) ddp,
	SUM( ROUND( adCost, 7 ) ) adCost,
	SUM( ROUND( warehouse_rent, 6 ) ) warehouse_rent,
	SUM( ROUND( adjustment, 6 ) ) adjustment,
	SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
	SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
	SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
	SUM( ROUND( operation_expenses, 6 ) ) operation_expenses,
	SUM( ROUND( operation_factory, 6 ) ) operation_factory,
	SUM( ROUND( operation_delivery, 6 ) ) operation_delivery,
	ROUND( SUM( IFNULL( adCost, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ad_percent,
	ROUND( SUM( IFNULL( warehouse_rent, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) warehouse_percent,
	ROUND( SUM( IFNULL( calcuRes, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) tail_percent,
	ROUND( SUM( IFNULL( ddp, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ddp_percent,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( fba_fee, 0 ) ) + SUM( IFNULL( sale_tax, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ),
		2 
	) profit,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( fba_fee, 0 ) ) + SUM( IFNULL( sale_tax, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		4 
	) gross_profit_margin,
	SUM( ROUND( evaluation_qty, 3 ) ) evaluation_qty,
	SUM( ROUND( evaluation_amount, 2 ) ) evaluation_amount,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( fba_fee, 0 ) ) + SUM( IFNULL( sale_tax, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + SUM( IFNULL( evaluation_amount, 0 ) ),
		2 
	) profit_include_evaluation,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( fba_fee, 0 ) ) + SUM( IFNULL( sale_tax, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( adjustment, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + SUM( IFNULL( evaluation_amount, 0 ) ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		2 
	) gross_profit_margin_include_evaluation,
	b.product_name product_name,
	b.seller seller,
	d.user_name purchaser 
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
		SUM( ROUND( fba_fee, 7 ) ) fba_fee,
		SUM( ROUND( sale_tax, 7 ) ) * -1 sale_tax,
		SUM( ROUND( calcuRes, 7 ) ) * - 1 calcuRes,
		SUM( ROUND( ddp, 2 ) ) * - 1 ddp,
		SUM( ROUND( adCost, 7 ) ) adCost,
		SUM( ROUND( warehouse_rent, 6 ) ) * - 1 warehouse_rent,
		SUM( ROUND( adjustment, 6 ) ) adjustment,
		SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
		SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
		SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
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
			SUM( ROUND( fba_fee, 7 ) ) fba_fee,
			SUM( ROUND( sale_tax, 7 ) ) sale_tax,
			SUM( ROUND( calcuRes, 7 ) ) calcuRes,
			SUM( ROUND( ddp, 2 ) ) ddp,
			NULL AS adCost,
			SUM( ROUND( warehouse_rent, 6 ) ) warehouse_rent,
			SUM( ROUND( adjustment, 6 ) ) adjustment,
			SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
			SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
			SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
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
				b.fba_fee * -1 fba_fee,
				b.tax sale_tax,
			IF
				( b.is_unaccrue, 0, c.calcuRes ) calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
					AND b.platform = "tiktok" 
				) a
				LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id
				LEFT JOIN mu_ecang_order c ON b.saleOrderCode = c.saleOrderCode 
			WHERE
				b.payment_id IS NOT NULL 
				AND c.`status` = 4 UNION ALL
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
				NULL AS fba_fee,
				NULL AS sale_tax,
				NULL AS calcuRes,
				c.sku_ddp_unit * b.qty / d.USD ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
					AND b.platform = "tiktok" 
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
				c.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				a.quantity * c.qty refund_qty,
				NULL AS sale_amount,
				ROUND( ( product_sales ) * c.percent, 7 ) refund_amount,
				NULL AS sale_selling_fees,
				ROUND( a.selling_fees * c.percent, 7 ) refund_selling_fees,
				NULL AS fba_fee,
				NULL AS sale_tax,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_order_refund a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN ( SELECT DISTINCT user_account, seller_sku, warehouse_sku, qty, percent, seller FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) c ON b.userAccount = c.user_account 
				AND a.sku = c.seller_sku 
			WHERE
				report_id = ' . $report_id . ' 
				AND b.platform = "tiktok" UNION ALL
			SELECT
				"tiktok" AS platform,
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
				NULL AS fba_fee,
				NULL AS sale_tax,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				ROUND( SUM( b.total ), 6 ) warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_warehouse_fbm a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "tiktok" 
			GROUP BY
				platform,
				user_account,
				warehouse_sku UNION ALL
			SELECT
				"tiktok" AS platform,
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
				NULL AS fba_fee,
				NULL AS sale_tax,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				b.total adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_order_adjustment a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "tiktok" UNION ALL
			SELECT
				"tiktok" AS platform,
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
				NULL AS fba_fee,
				NULL AS sale_tax,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				b.total lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "tiktok" 
				AND lc_adjustment IS NOT NULL UNION ALL
			SELECT
				"tiktok" AS platform,
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
				NULL AS fba_fee,
				NULL AS sale_tax,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				b.total le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "tiktok" 
				AND le_adjustment IS NOT NULL UNION ALL
			SELECT
				"tiktok" AS platform,
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
				NULL AS fba_fee,
				NULL AS sale_tax,
				NULL AS calcuRes,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS adjustment,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				b.total wyd_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "tiktok" 
				AND wyd_adjustment IS NOT NULL 
			) a 
		GROUP BY
			platform,
			userAccount,
			warehouse_sku UNION ALL
		SELECT
			"tiktok" AS platform,
			d.userAccount userAccount,
			a.warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS fba_fee,
			NULL AS sale_tax,
			NULL AS calcuRes,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
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
			AND b.platform = "tiktok" UNION ALL
		SELECT
			"tiktok" AS platform,
			user_account userAccount,
			warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS fba_fee,
			NULL AS sale_tax,
			NULL AS calcuRes,
			NULL AS ddp,
			total adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_ad_cost 
		WHERE
			report_id = ' . $report_id . ' 
			AND platform = "tiktok" UNION ALL
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
			NULL AS fba_fee,
			NULL AS sale_tax,
			NULL AS calcuRes,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
			b.total operation_expenses,
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
			AND c.platform = "tiktok" UNION ALL
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
			NULL AS fba_fee,
			NULL AS sale_tax,
			NULL AS calcuRes,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			b.total operation_factory,
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
			AND c.platform = "tiktok" UNION ALL
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
			NULL AS fba_fee,
			NULL AS sale_tax,
			NULL AS calcuRes,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS adjustment,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			b.total operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_delivery a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount
			LEFT JOIN mu_finance_report d ON a.report_id = d.id 
		WHERE
			a.report_id = ' . $report_id . ' 
			AND c.platform = "tiktok" 
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
	LEFT JOIN mu_ecang_product c ON a.warehouse_sku = c.productSku
	LEFT JOIN mu_ecang_user d ON c.personOpraterId = d.user_id 
GROUP BY
	platform,
	userAccount,
	warehouse_sku,
	sale_amount,
	product_name,
	seller,
	purchaser;
        ';
    }

    static public function getHomeDepotWarehouseSkuSql($report_id): string
    {
        return '
SELECT
	platform,
	userAccount,
	a.warehouse_sku,
	SUM( sale_qty ) sale_qty,
	SUM( refund_qty ) refund_qty,
	SUM( IFNULL( sale_qty, 0 ) + IFNULL ( refund_qty, 0 ) ) qty_amount,
	SUM( ROUND( sale_amount, 7 ) ) sale_amount,
	SUM( ROUND( refund_amount, 7 ) ) refund_amount,
	SUM( ROUND( IFNULL( sale_amount, 0 ) + IFNULL( refund_amount, 0 ), 7 ) ) amount,
	SUM( ROUND( sale_selling_fees, 7 ) ) sale_selling_fees,
	SUM( ROUND( refund_selling_fees, 7 ) ) refund_selling_fees,
	SUM( ROUND( fba_fee, 7 ) ) fba_fee,
	SUM( ROUND( sale_tax, 7 ) ) sale_tax,
	SUM( ROUND( calcuRes, 7 ) ) calcuRes,
	SUM( ROUND( waybill, 7 ) ) waybill,
	SUM( ROUND( ddp, 2 ) ) ddp,
	SUM( ROUND( adCost, 7 ) ) adCost,
	SUM( ROUND( warehouse_rent, 6 ) ) warehouse_rent,
	SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
	SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
	SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
	SUM( ROUND( operation_expenses, 6 ) ) operation_expenses,
	SUM( ROUND( operation_factory, 6 ) ) operation_factory,
	SUM( ROUND( operation_delivery, 6 ) ) operation_delivery,
	ROUND( SUM( IFNULL( adCost, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ad_percent,
	ROUND( SUM( IFNULL( warehouse_rent, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) warehouse_percent,
	ROUND( SUM( IFNULL( calcuRes, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) tail_percent,
	ROUND( SUM( IFNULL( ddp, 0 ) ) / SUM( IFNULL( sale_amount, 0 ) ) * - 1, 4 ) ddp_percent,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( fba_fee, 0 ) ) + SUM( IFNULL( sale_tax, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( waybill, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ),
		2 
	) profit,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( fba_fee, 0 ) ) + SUM( IFNULL( sale_tax, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( waybill, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		4 
	) gross_profit_margin,
	SUM( ROUND( evaluation_qty, 3 ) ) evaluation_qty,
	SUM( ROUND( evaluation_amount, 2 ) ) evaluation_amount,
	ROUND(
		SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( fba_fee, 0 ) ) + SUM( IFNULL( sale_tax, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( waybill, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + SUM( IFNULL( evaluation_amount, 0 ) ),
		2 
	) profit_include_evaluation,
	ROUND(
		(
			SUM( IFNULL( sale_amount, 0 ) ) + SUM( IFNULL( refund_amount, 0 ) ) + SUM( IFNULL( sale_selling_fees, 0 ) ) + SUM( IFNULL( refund_selling_fees, 0 ) ) + SUM( IFNULL( fba_fee, 0 ) ) + SUM( IFNULL( sale_tax, 0 ) ) + SUM( IFNULL( calcuRes, 0 ) ) + SUM( IFNULL( waybill, 0 ) ) + SUM( IFNULL( ddp, 0 ) ) + SUM( IFNULL( adCost, 0 ) ) + SUM( IFNULL( warehouse_rent, 0 ) ) + SUM( IFNULL( lc_adjustment, 0 ) ) + SUM( IFNULL( le_adjustment, 0 ) ) + SUM( IFNULL( wyd_adjustment, 0 ) ) + ROUND( SUM( IFNULL( operation_expenses, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_factory, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + ROUND( SUM( IFNULL( operation_delivery, 0 ) ) / ( SELECT USD FROM mu_finance_report WHERE id = ' . $report_id . ' ), 2 ) + SUM( IFNULL( evaluation_amount, 0 ) ) 
		) / SUM( IFNULL( sale_amount, 0 ) ),
		2 
	) gross_profit_margin_include_evaluation,
	b.product_name product_name,
	b.seller seller,
	d.user_name purchaser 
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
		SUM( ROUND( fba_fee, 7 ) ) fba_fee,
		SUM( ROUND( sale_tax, 7 ) ) * - 1 sale_tax,
		SUM( ROUND( calcuRes, 7 ) ) * - 1 calcuRes,
		SUM( ROUND( waybill, 7 ) ) * - 1 waybill,
		SUM( ROUND( ddp, 2 ) ) * - 1 ddp,
		SUM( ROUND( adCost, 7 ) ) adCost,
		SUM( ROUND( warehouse_rent, 6 ) ) * - 1 warehouse_rent,
		SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
		SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
		SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
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
			SUM( ROUND( fba_fee, 7 ) ) fba_fee,
			SUM( ROUND( sale_tax, 7 ) ) sale_tax,
			SUM( ROUND( calcuRes, 7 ) ) calcuRes,
			SUM( ROUND( waybill, 7 ) ) waybill,
			SUM( ROUND( ddp, 2 ) ) ddp,
			NULL AS adCost,
			SUM( ROUND( warehouse_rent, 6 ) ) warehouse_rent,
			SUM( ROUND( lc_adjustment, 6 ) ) lc_adjustment,
			SUM( ROUND( le_adjustment, 6 ) ) le_adjustment,
			SUM( ROUND( wyd_adjustment, 6 ) ) wyd_adjustment,
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
				b.fba_fee * - 1 fba_fee,
				b.tax sale_tax,
			IF
				( b.is_unaccrue, 0, c.calcuRes ) calcuRes,
				NULL AS waybill,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
					report_id = ' . $report_id .  '
					AND b.platform = "hd" 
				) a
				LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id
				LEFT JOIN mu_ecang_order c ON b.saleOrderCode = c.saleOrderCode 
			WHERE
				b.payment_id IS NOT NULL 
				AND c.`status` = 4 UNION ALL
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
				NULL AS fba_fee,
				NULL AS sale_tax,
				NULL AS calcuRes,
				NULL AS waybill,
				c.sku_ddp_unit * b.qty / d.USD ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
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
					AND b.platform = "hd" 
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
				c.warehouse_sku warehouse_sku,
				NULL AS sale_qty,
				a.quantity * c.qty refund_qty,
				NULL AS sale_amount,
				ROUND( ( product_sales ) * c.percent, 7 ) refund_amount,
				NULL AS sale_selling_fees,
				ROUND( a.selling_fees * c.percent, 7 ) refund_selling_fees,
				NULL AS fba_fee,
				NULL AS sale_tax,
				NULL AS calcuRes,
				NULL AS waybill,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_order_refund a
				LEFT JOIN mu_finance_table b ON a.table_id = b.id
				LEFT JOIN ( SELECT DISTINCT user_account, seller_sku, warehouse_sku, qty, percent, seller FROM mu_finance_sku_relation WHERE report_id = ' . $report_id . ' ) c ON b.userAccount = c.user_account 
				AND a.sku = c.seller_sku 
			WHERE
				report_id = ' . $report_id . ' 
				AND b.platform = "hd" UNION ALL
			SELECT
				"hd" AS platform,
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
				NULL AS fba_fee,
				NULL AS sale_tax,
				NULL AS calcuRes,
				NULL AS waybill,
				NULL AS ddp,
				NULL AS adCost,
				ROUND( SUM( b.total ), 6 ) warehouse_rent,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_warehouse_fbm a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "hd" 
			GROUP BY
				platform,
				user_account,
				warehouse_sku UNION ALL
			SELECT
				"hd" AS platform,
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
				NULL AS fba_fee,
				NULL AS sale_tax,
				NULL AS calcuRes,
				NULL AS waybill,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				b.total lc_adjustment,
				NULL AS le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "hd" 
				AND lc_adjustment IS NOT NULL UNION ALL
			SELECT
				"hd" AS platform,
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
				NULL AS fba_fee,
				NULL AS sale_tax,
				NULL AS calcuRes,
				NULL AS waybill,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS lc_adjustment,
				b.total le_adjustment,
				NULL AS wyd_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "hd" 
				AND le_adjustment IS NOT NULL UNION ALL
			SELECT
				"hd" AS platform,
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
				NULL AS fba_fee,
				NULL AS sale_tax,
				NULL AS calcuRes,
				NULL AS waybill,
				NULL AS ddp,
				NULL AS adCost,
				NULL AS warehouse_rent,
				NULL AS lc_adjustment,
				NULL AS le_adjustment,
				b.total wyd_adjustment 
			FROM
				mu_finance_order_additional a
				LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code 
				AND a.report_id = b.report_id
				LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount 
			WHERE
				a.report_id = ' . $report_id . ' 
				AND c.platform = "hd" 
				AND wyd_adjustment IS NOT NULL 
			) a 
		GROUP BY
			platform,
			userAccount,
			warehouse_sku UNION ALL
		SELECT
			"hd" AS platform,
			d.userAccount userAccount,
			a.warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS fba_fee,
			NULL AS sale_tax,
			NULL AS calcuRes,
			NULL AS waybill,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
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
			AND b.platform = "hd" UNION ALL
		SELECT
			"hd" AS platform,
			user_account userAccount,
			warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS fba_fee,
			NULL AS sale_tax,
			NULL AS calcuRes,
			NULL AS waybill,
			NULL AS ddp,
			total adCost,
			NULL AS warehouse_rent,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_ad_cost 
		WHERE
			report_id = ' . $report_id . ' 
			AND platform = "hd" UNION ALL
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
			NULL AS fba_fee,
			NULL AS sale_tax,
			NULL AS calcuRes,
			NULL AS waybill,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
			b.total operation_expenses,
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
			AND c.platform = "hd" UNION ALL
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
			NULL AS fba_fee,
			NULL AS sale_tax,
			NULL AS calcuRes,
			NULL AS waybill,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			b.total operation_factory,
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
			AND c.platform = "hd" UNION ALL
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
			NULL AS fba_fee,
			NULL AS sale_tax,
			NULL AS calcuRes,
			NULL AS waybill,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			b.total operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_operation_delivery a
			LEFT JOIN mu_finance_order_share b ON a.share_code = b.share_code
			LEFT JOIN ( SELECT DISTINCT platform, userAccount FROM mu_finance_table WHERE rid = ' . $report_id . ' ) c ON b.user_account = c.userAccount
			LEFT JOIN mu_finance_report d ON a.report_id = d.id 
		WHERE
			a.report_id = ' . $report_id . ' 
			AND c.platform = "hd" UNION ALL
		SELECT
			"hd" AS platform,
			user_account userAccount,
			warehouse_sku warehouse_sku,
			NULL AS sale_qty,
			NULL AS refund_qty,
			NULL AS sale_amount,
			NULL AS refund_amount,
			NULL AS sale_selling_fees,
			NULL AS refund_selling_fees,
			NULL AS fba_fee,
			NULL AS sale_tax,
			NULL AS calcuRes,
			total * - 1 waybill,
			NULL AS ddp,
			NULL AS adCost,
			NULL AS warehouse_rent,
			NULL AS lc_adjustment,
			NULL AS le_adjustment,
			NULL AS wyd_adjustment,
			NULL AS operation_expenses,
			NULL AS operation_factory,
			NULL AS operation_delivery,
			NULL AS evaluation_qty,
			NULL AS evaluation_amount 
		FROM
			mu_finance_hd_tail 
		WHERE
			report_id = ' . $report_id . ' 
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
	LEFT JOIN mu_ecang_product c ON a.warehouse_sku = c.productSku
	LEFT JOIN mu_ecang_user d ON c.personOpraterId = d.user_id 
GROUP BY
	platform,
	userAccount,
	warehouse_sku,
	sale_amount,
	product_name,
	seller,
	purchaser;
        ';
    }

    static public function getFinanceOutboundByReport($report_id): string
    {
        return '
SELECT
	a.warehouse_sku,
	b.productTitle,
	SUM( qty ) sale_qty,
	CONCAT_WS( "-", platform, user_account ) content 
FROM
	mu_finance_order_outbound a
	LEFT JOIN mu_ecang_product b ON a.warehouse_sku = b.productSku 
WHERE
	report_id = ' . $report_id . ' 
GROUP BY
	platform,
	user_account,
	warehouse_sku,
	productTitle,
	content 
ORDER BY
	a.platform ASC,
	a.user_account ASC,
	a.warehouse_sku ASC;
	    ';
    }
}
