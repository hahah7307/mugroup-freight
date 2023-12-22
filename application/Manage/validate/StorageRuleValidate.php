<?php

namespace app\Manage\validate;

use app\Manage\model\DeliverFeeModel;
use think\exception\DbException;
use think\Validate;
use think\Db;

class StorageRuleValidate extends Validate
{
    protected $rule = [
        'name'          =>  'require',
        'description'   =>  'require',
        'value'         =>  'require',
        'min'           =>  'require',
        'max'           =>  'require',
    ];

    protected $message = [
        
    ];

    protected $field = [
        'name'          =>  '名称',
        'description'   =>  '描述',
        'value'         =>  '费用',
        'min'           =>  '最小重量',
        'max'           =>  '最大重量',
    ];

    protected $scene = [
        'add'           =>  ['name', 'description','value','min','max'],
        'edit'          =>  ['name', 'description','value','min','max'],
    ];
}
