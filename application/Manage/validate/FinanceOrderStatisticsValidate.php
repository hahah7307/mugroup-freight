<?php

namespace app\Manage\validate;

use think\Validate;

class FinanceOrderStatisticsValidate extends Validate
{
    protected $rule = [
        'payment_id'        =>  'require',
        'saleOrderCode'     =>  'require',
        'seller_sku'        =>  'require',
        'platform_sku'      =>  'require',
        'qty'               =>  'require',
        'sale_unit'         =>  'require',
        'sale_amount'       =>  'require',
        'sale_shipping'     =>  'require',
        'selling_fee'       =>  'require',
        'fba_fee'           =>  'require',
        'tax'               =>  'require'
    ];

    protected $message = [
        
    ];

    protected $field = [
        'payment_id'        =>  'PAYMENT',
        'saleOrderCode'     =>  '参考号',
        'seller_sku'        =>  '销售SKU',
        'platform_sku'      =>  '仓库SKU',
        'qty'               =>  '数量',
        'sale_unit'         =>  '单价',
        'sale_amount'       =>  '总销售额',
        'sale_shipping'     =>  '运费',
        'selling_fee'       =>  '佣金',
        'fba_fee'           =>  'FBA尾程',
        'tax'               =>  '税'
    ];

    protected $scene = [
        'edit'          =>  ['payment_id', 'saleOrderCode', 'seller_sku', 'platform_sku', 'qty', 'sale_unit', 'sale_amount', 'selling_fee'],
    ];
}
