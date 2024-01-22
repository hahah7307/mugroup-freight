<?php

namespace app\Manage\validate;

use think\Validate;

class StorageAhsRuleValidate extends Validate
{
    protected $rule = [
        'storage_id'        =>  'require',
        'ahs_id'            =>  'require',
        'zone'              =>  'require',
        'value'             =>  'require',
    ];

    protected $message = [
        
    ];

    protected $field = [
        'storage_id'        =>  '所属仓库',
        'ahs_id'            =>  '所属规则',
        'zone'              =>  'Zone',
        'value'             =>  '金额',
    ];

    protected $scene = [
        'add'           =>  ['storage_id', 'ahs_id', 'zone', 'value'],
        'edit'          =>  ['storage_id', 'ahs_id', 'zone', 'value'],
    ];
}
