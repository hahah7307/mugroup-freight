<?php

namespace app\Manage\validate;

use think\Validate;

class SkuValidate extends Validate
{
    protected $rule = [
        'product_sku'           =>  'require',
        'user_account'          =>  'require',
    ];

    protected $message = [
        
    ];

    protected $field = [
        'product_sku'           =>  'Sku名称',
        'user_account'          =>  '店铺名',
    ];

    protected $scene = [
        'add'           =>  ['product_sku', 'user_account'],
        'edit'          =>  ['product_sku', 'user_account'],
    ];
}
