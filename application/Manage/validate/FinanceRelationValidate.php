<?php

namespace app\Manage\validate;

use think\Validate;

class FinanceRelationValidate extends Validate
{
    protected $rule = [
        'seller_sku'                =>  'require',
        'warehouse_sku'             =>  'require',
        'qty'                       =>  'require',
        'percent'                   =>  'require',
        'user_account'              =>  'require',
        'seller'                    =>  'require',
    ];

    protected $message = [
        
    ];

    protected $field = [
        'seller_sku'                =>  '店铺SKU',
        'warehouse_sku'             =>  '仓库SKU',
        'qty'                       =>  '数量',
        'percent'                   =>  '占比',
        'user_account'              =>  '店铺名',
        'seller'                    =>  '运营人员',
    ];

    protected $scene = [
        'add'           =>  ['seller_sku', 'warehouse_sku', 'qty', 'percent', 'user_account', 'seller'],
        'edit'          =>  ['seller_sku', 'warehouse_sku', 'qty', 'percent', 'user_account', 'seller'],
    ];
}
