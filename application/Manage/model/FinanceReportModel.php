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

    static public function getReportSql($report_id): string
    {
        return "
        SELECT
            new.platform 平台,
            new.userAccount 店铺,
            new.warehouseSku 仓库SKU,
            IFNULL( SUM( new.salesOrderQty ), 0 ) 销售数量,
            IFNULL( SUM( new.salesRetundQty ), 0 ) 退款数量,
            IFNULL( SUM( new.salesOrderAmount ), 0 ) 销售总额,
            IFNULL( SUM( new.salesRefundAmount ), 0 ) 退款总额,
            IFNULL( SUM( new.selling_fees ), 0 ) 佣金,
            IFNULL( SUM( new.fba_fees ) * -1, 0 ) 亚马逊尾程,
            IFNULL( ROUND( SUM( new.orderUnitTail ), 4 ) * -1, 0 ) 海外仓尾程
        FROM
            (
            SELECT
                o.platform,
                o.userAccount,
                o.warehouseSku,
                o.salesQty salesOrderQty,
                NULL AS salesRetundQty,
                ROUND( o.salesAmount, 4 ) salesOrderAmount,
                NULL AS salesRefundAmount,
                o.selling_fees,
                o.fba_fees,
                ROUND( o.unitTail, 4 ) orderUnitTail,
                NULL AS retundUnitTail 
            FROM
                (
                SELECT
                    e.platform platform,
                    e.userAccount userAccount,
                    e.warehouseSku warehouseSku,
                    e.order_type order_type,
                    SUM( e.qty ) salesQty,
                    SUM( ROUND( e.product_sales + e.shipping_credits + e.gift_wrap_credits + e.regulatory_fee + e.promotional_rebates, 4 ) ) salesAmount,
                    SUM( e.selling_fees ) selling_fees,
                    SUM( e.fba_fees ) fba_fees,
                    SUM( e.unitTail ) unitTail,
                    SUM( e.product_sales ) product_sales,
                    SUM( e.shipping_credits ) shipping_credits,
                    SUM( e.gift_wrap_credits ) gift_wrap_credits,
                    SUM( e.regulatory_fee ) regulatory_fee,
                    SUM( e.promotional_rebates ) promotional_rebates 
                FROM
                    (
                    SELECT
                        c.platform platform,
                        c.userAccount userAccount,
                        a.payment_id payment_id,
                        c.saleOrderCode saleOrderCode,
                        d.warehouseSku warehouseSku,
                        a.order_type order_type,
                        d.qty qty,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.product_sales,
                            4 
                        ) product_sales,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.shipping_credits,
                            4 
                        ) shipping_credits,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.gift_wrap_credits,
                            4 
                        ) gift_wrap_credits,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.regulatory_fee,
                            4 
                        ) regulatory_fee,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.promotional_rebates,
                            4 
                        ) promotional_rebates,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.selling_fees,
                            4 
                        ) selling_fees,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.fba_fees,
                            4 
                        ) fba_fees,
                        ROUND( d.unitPrice * d.qty / ( c.amountpaid - c.shipFee ) * c.calcuRes, 4 ) unitTail 
                    FROM
                        mu_finance_order a
                        LEFT JOIN mu_finance_order_outbound b ON a.id = b.finance_order_id
                        LEFT JOIN mu_ecang_order c ON b.ecang_order_id = c.id
                        LEFT JOIN mu_ecang_order_detail d ON d.order_id = c.id 
                    WHERE
                        order_type = \"Order\" 
                        AND a.rid = " . $report_id . " 
                        AND c.STATUS = 4 
                    GROUP BY
                        platform,
                        userAccount,
                        payment_id,
                        saleOrderCode,
                        warehouseSku,
                        order_type,
                        qty,
                        product_sales,
                        shipping_credits,
                        gift_wrap_credits,
                        regulatory_fee,
                        promotional_rebates,
                        selling_fees,
                        fba_fees,
                        unitTail,
                        unitPrice 
                    ) e 
                GROUP BY
                    platform,
                    userAccount,
                    warehouseSku,
                    order_type 
                ) AS o
                LEFT JOIN (
                SELECT
                    e.platform platform,
                    e.userAccount userAccount,
                    e.warehouseSku warehouseSku,
                    e.order_type order_type,
                    SUM( e.qty ) salesQty,
                    SUM( ROUND( e.product_sales + e.shipping_credits + e.gift_wrap_credits + e.regulatory_fee + e.promotional_rebates, 4 ) ) salesAmount,
                    SUM( e.selling_fees ) selling_fees,
                    SUM( e.fba_fees ) fba_fees,
                    SUM( e.unitTail ) unitTail,
                    SUM( e.product_sales ) product_sales,
                    SUM( e.shipping_credits ) shipping_credits,
                    SUM( e.gift_wrap_credits ) gift_wrap_credits,
                    SUM( e.regulatory_fee ) regulatory_fee,
                    SUM( e.promotional_rebates ) promotional_rebates 
                FROM
                    (
                    SELECT
                        c.platform platform,
                        c.userAccount userAccount,
                        a.payment_id payment_id,
                        c.saleOrderCode saleOrderCode,
                        d.warehouseSku warehouseSku,
                        a.order_type order_type,
                        d.qty qty,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.product_sales,
                            4 
                        ) product_sales,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.shipping_credits,
                            4 
                        ) shipping_credits,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.gift_wrap_credits,
                            4 
                        ) gift_wrap_credits,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.regulatory_fee,
                            4 
                        ) regulatory_fee,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.promotional_rebates,
                            4 
                        ) promotional_rebates,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.selling_fees,
                            4 
                        ) selling_fees,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.fba_fees,
                            4 
                        ) fba_fees,
                        ROUND( d.unitPrice * d.qty / ( c.amountpaid - c.shipFee ) * c.calcuRes, 4 ) unitTail 
                    FROM
                        mu_finance_order a
                        LEFT JOIN mu_finance_order_outbound b ON a.id = b.finance_order_id
                        LEFT JOIN mu_ecang_order c ON b.ecang_order_id = c.id
                        LEFT JOIN mu_ecang_order_detail d ON d.order_id = c.id 
                    WHERE
                        order_type = \"Refund\" 
                        AND a.rid = " . $report_id . " 
                        AND c.STATUS = 4 
                    GROUP BY
                        platform,
                        userAccount,
                        payment_id,
                        saleOrderCode,
                        warehouseSku,
                        order_type,
                        qty,
                        product_sales,
                        shipping_credits,
                        gift_wrap_credits,
                        regulatory_fee,
                        promotional_rebates,
                        selling_fees,
                        fba_fees,
                        unitTail,
                        unitPrice 
                    ) e 
                GROUP BY
                    platform,
                    userAccount,
                    warehouseSku,
                    order_type 
                ) r ON o.userAccount = r.userAccount 
                AND o.warehouseSku = r.warehouseSku UNION
            SELECT
                r.platform,
                r.userAccount,
                r.warehouseSku,
                NULL AS salesOrderQty,
                r.salesQty salesRetundQty,
                NULL AS salesOrderAmount,
                ROUND( r.salesAmount, 4 ) salesRefundAmount,
                r.selling_fees,
                r.fba_fees,
                NULL AS orderUnitTail,
                ROUND( r.unitTail, 4 ) retundUnitTail 
            FROM
                (
                SELECT
                    e.platform platform,
                    e.userAccount userAccount,
                    e.warehouseSku warehouseSku,
                    e.order_type order_type,
                    SUM( e.qty ) salesQty,
                    SUM( ROUND( e.product_sales + e.shipping_credits + e.gift_wrap_credits + e.regulatory_fee + e.promotional_rebates, 4 ) ) salesAmount,
                    SUM( e.selling_fees ) selling_fees,
                    SUM( e.fba_fees ) fba_fees,
                    SUM( e.unitTail ) unitTail,
                    SUM( e.product_sales ) product_sales,
                    SUM( e.shipping_credits ) shipping_credits,
                    SUM( e.gift_wrap_credits ) gift_wrap_credits,
                    SUM( e.regulatory_fee ) regulatory_fee,
                    SUM( e.promotional_rebates ) promotional_rebates 
                FROM
                    (
                    SELECT
                        c.platform platform,
                        c.userAccount userAccount,
                        a.payment_id payment_id,
                        c.saleOrderCode saleOrderCode,
                        d.warehouseSku warehouseSku,
                        a.order_type order_type,
                        d.qty qty,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.product_sales,
                            4 
                        ) product_sales,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.shipping_credits,
                            4 
                        ) shipping_credits,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.gift_wrap_credits,
                            4 
                        ) gift_wrap_credits,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.regulatory_fee,
                            4 
                        ) regulatory_fee,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.promotional_rebates,
                            4 
                        ) promotional_rebates,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.selling_fees,
                            4 
                        ) selling_fees,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.fba_fees,
                            4 
                        ) fba_fees,
                        ROUND( d.unitPrice * d.qty / ( c.amountpaid - c.shipFee ) * c.calcuRes, 4 ) unitTail 
                    FROM
                        mu_finance_order a
                        LEFT JOIN mu_finance_order_outbound b ON a.id = b.finance_order_id
                        LEFT JOIN mu_ecang_order c ON b.ecang_order_id = c.id
                        LEFT JOIN mu_ecang_order_detail d ON d.order_id = c.id 
                    WHERE
                        order_type = \"Order\" 
                        AND a.rid = " . $report_id . "
                        AND c.STATUS = 4 
                    GROUP BY
                        platform,
                        userAccount,
                        payment_id,
                        saleOrderCode,
                        warehouseSku,
                        order_type,
                        qty,
                        product_sales,
                        shipping_credits,
                        gift_wrap_credits,
                        regulatory_fee,
                        promotional_rebates,
                        selling_fees,
                        fba_fees,
                        unitTail,
                        unitPrice 
                    ) e 
                GROUP BY
                    platform,
                    userAccount,
                    warehouseSku,
                    order_type 
                ) AS o
                RIGHT JOIN (
                SELECT
                    e.platform platform,
                    e.userAccount userAccount,
                    e.warehouseSku warehouseSku,
                    e.order_type order_type,
                    SUM( e.qty ) salesQty,
                    SUM( ROUND( e.product_sales + e.shipping_credits + e.gift_wrap_credits + e.regulatory_fee + e.promotional_rebates, 4 ) ) salesAmount,
                    SUM( e.selling_fees ) selling_fees,
                    SUM( e.fba_fees ) fba_fees,
                    SUM( e.unitTail ) unitTail,
                    SUM( e.product_sales ) product_sales,
                    SUM( e.shipping_credits ) shipping_credits,
                    SUM( e.gift_wrap_credits ) gift_wrap_credits,
                    SUM( e.regulatory_fee ) regulatory_fee,
                    SUM( e.promotional_rebates ) promotional_rebates 
                FROM
                    (
                    SELECT
                        c.platform platform,
                        c.userAccount userAccount,
                        a.payment_id payment_id,
                        c.saleOrderCode saleOrderCode,
                        d.warehouseSku warehouseSku,
                        a.order_type order_type,
                        d.qty qty,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.product_sales,
                            4 
                        ) product_sales,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.shipping_credits,
                            4 
                        ) shipping_credits,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.gift_wrap_credits,
                            4 
                        ) gift_wrap_credits,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.regulatory_fee,
                            4 
                        ) regulatory_fee,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.promotional_rebates,
                            4 
                        ) promotional_rebates,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.selling_fees,
                            4 
                        ) selling_fees,
                        ROUND(
                            d.unitPrice * d.qty / ( a.product_sales + a.gift_wrap_credits + a.regulatory_fee + a.promotional_rebates ) * a.fba_fees,
                            4 
                        ) fba_fees,
                        ROUND( d.unitPrice * d.qty / ( c.amountpaid - c.shipFee ) * c.calcuRes, 4 ) unitTail 
                    FROM
                        mu_finance_order a
                        LEFT JOIN mu_finance_order_outbound b ON a.id = b.finance_order_id
                        LEFT JOIN mu_ecang_order c ON b.ecang_order_id = c.id
                        LEFT JOIN mu_ecang_order_detail d ON d.order_id = c.id 
                    WHERE
                        order_type = \"Refund\" 
                        AND a.rid = " . $report_id . " 
                        AND c.STATUS = 4 
                    GROUP BY
                        platform,
                        userAccount,
                        payment_id,
                        saleOrderCode,
                        warehouseSku,
                        order_type,
                        qty,
                        product_sales,
                        shipping_credits,
                        gift_wrap_credits,
                        regulatory_fee,
                        promotional_rebates,
                        selling_fees,
                        fba_fees,
                        unitTail,
                        unitPrice 
                    ) e 
                GROUP BY
                    platform,
                    userAccount,
                    warehouseSku,
                    order_type 
                ) r ON o.userAccount = r.userAccount 
                AND o.warehouseSku = r.warehouseSku 
            ) new 
        GROUP BY
            platform,
            userAccount,
            warehouseSku 
        ORDER BY
            userAccount,
            warehouseSku;
        ";
    }
}
