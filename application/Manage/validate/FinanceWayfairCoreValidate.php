<?php

namespace app\Manage\validate;

use think\Validate;

class FinanceWayfairCoreValidate extends Validate
{
    protected $rule = [
        'invoice_no'        =>  'require',
        'invoice_date'      =>  'require',
        'payment_id'        =>  'require',
        'sale_amount'       =>  'require',
        'status'            =>  'require',
        'commission_rate'   =>  'require',
        'commission'        =>  'require',
        'collection'        =>  'require',
        'calculate_month'   =>  'require',
        'user_account'      =>  'require'
    ];

    protected $message = [
        
    ];

    protected $field = [
        'invoice_no'        =>  '发票号',
        'invoice_date'      =>  '发票日期',
        'payment_id'        =>  '订单号',
        'sale_amount'       =>  '发票总销售',
        'status'            =>  '类型',
        'commission_rate'   =>  '佣金比例',
        'commission'        =>  '发票佣金',
        'collection'        =>  '发票应收',
        'calculate_month'   =>  '核算月份',
        'user_account'      =>  '店铺名'
    ];

    protected $scene = [
        'edit'          =>  ['invoice_no', 'invoice_date', 'payment_id', 'sale_amount', 'status', 'commission_rate', 'commission', 'collection', 'calculate_month', 'user_account'],
    ];
}
