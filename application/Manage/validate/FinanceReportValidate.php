<?php

namespace app\Manage\validate;

use think\Validate;
use think\Db;

class FinanceReportValidate extends Validate
{
    protected $rule = [
        'name'              =>  'require',
    ];

    protected $message = [
        
    ];

    protected $field = [
        'name'              =>  '所属仓库',
    ];

    protected $scene = [
        'add'           =>  ['name'],
        'edit'          =>  ['name'],
    ];
}
