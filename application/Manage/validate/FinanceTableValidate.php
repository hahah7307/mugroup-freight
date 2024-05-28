<?php

namespace app\Manage\validate;

use think\Validate;

class FinanceTableValidate extends Validate
{
    protected $rule = [
        'table_name'        =>  'require',
        'platform'          =>  'require',
        'userAccount'       =>  'require'
    ];

    protected $message = [
        
    ];

    protected $field = [
        'table_name'        =>  '报表名称',
        'platform'          =>  '所属平台',
        'userAccount'       =>  '所属店铺',
    ];

    protected $scene = [
        'add'           =>  ['table_name'],
        'edit'          =>  ['table_name', 'platform', 'userAccount'],
    ];
}
