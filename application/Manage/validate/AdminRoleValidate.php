<?php

namespace app\Manage\validate;

use think\Validate;
use think\Db;

class AdminRoleValidate extends Validate
{
    protected $rule = [
        'name'          =>  'require',
        'code'          =>  'require',
    ];

    protected $message = [
        
    ];

    protected $field = [
        'name'          =>  '用户名',
        'code'          =>  '加密密码',
    ];

    protected $scene = [
        'add'           =>  ['name', 'code'],
        'edit'          =>  ['name', 'code'],
    ];
}
