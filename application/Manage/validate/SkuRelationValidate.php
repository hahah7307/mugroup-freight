<?php

namespace app\Manage\validate;

use think\Validate;

class SkuRelationValidate extends Validate
{
    protected $rule = [
        'pcr_product_sku'           =>  'require',
        'pcr_quantity'              =>  'require',
        'pcr_percent'               =>  'require',
    ];

    protected $message = [
        
    ];

    protected $field = [
        'pcr_product_sku'           =>  '仓库Sku',
        'pcr_quantity'              =>  '店铺名',
        'pcr_percent'               =>  '店铺名',
    ];

    protected $scene = [
        'add'           =>  ['pcr_product_sku', 'pcr_quantity', 'pcr_percent'],
        'edit'          =>  ['pcr_product_sku', 'pcr_quantity', 'pcr_percent'],
    ];
}
