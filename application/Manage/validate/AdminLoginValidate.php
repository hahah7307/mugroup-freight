<?php

namespace app\Manage\validate;

use think\Validate;
use think\Db;

class AdminLoginValidate extends Validate
{
    protected $rule = [
        'aid'               =>  'require|number',
        'login_ip'          =>  'require|number',
    ];

    protected $message = [
        
    ];

    protected $field = [
        'aid'               =>  '用户id',
        'login_ip'          =>  '登录ip',
    ];

    protected $scene = [
        'add'           =>  ['aid', 'login_ip'],
        'edit'          =>  ['aid', 'login_ip'],
    ];
}
