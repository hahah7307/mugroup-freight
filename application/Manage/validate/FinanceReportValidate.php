<?php

namespace app\Manage\validate;

use think\Validate;

class FinanceReportValidate extends Validate
{
    protected $rule = [
        'name'              =>  'require',
        'month'             =>  'require',
    ];

    protected $message = [
        
    ];

    protected $field = [
        'name'              =>  '报表名称',
        'month'             =>  '月份',
    ];

    protected $scene = [
        'add'           =>  ['name', 'month'],
        'edit'          =>  ['name', 'month'],
    ];
}
