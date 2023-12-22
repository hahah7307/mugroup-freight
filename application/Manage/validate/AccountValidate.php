<?php

namespace app\Manage\validate;

use think\Validate;
use think\Db;

class AccountValidate extends Validate
{
    protected $rule = [
        'username'          =>  'require',
        'password'          =>  'require',
        'password_hash'     =>  'require',
        'phone'             =>  'require|number',
        'email'             =>  'require',
        'nickname'          =>  'require',
    ];

    protected $message = [
        
    ];

    protected $field = [
        'username'          =>  '用户名',
        'password'          =>  '加密密码',
        'password_hash'     =>  '盐值',
        'phone'             =>  '手机号码',
        'email'             =>  '邮箱',
        'nickname'          =>  '用户昵称',
    ];

    protected $scene = [
        'add'           =>  ['username', 'password', 'password_hash', 'phone', 'email', 'nickname'],
        'password'      =>  ['password'],
        'edit'          =>  ['username', 'phone', 'email', 'nickname'],
    ];
}
