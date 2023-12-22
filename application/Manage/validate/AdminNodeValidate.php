<?php

namespace app\Manage\validate;

use think\Validate;
use think\Db;

class AdminNodeValidate extends Validate
{
    protected $rule = [
        'parent_id'     =>  'require|number',
        'name'          =>  'require',
        'code'          =>  'require',
        'level'         =>  'number',
    ];

    protected $message = [
        
    ];

    protected $field = [
        'parent_id'     =>  '父级节点',
        'name'          =>  '用户名',
        'code'          =>  '加密密码',
        'level'         =>  '等级',
    ];

    protected $scene = [
        'add'           =>  ['parent_id', 'name', 'code', 'level'],
        'edit'          =>  ['parent_id', 'name', 'code', 'level'],
    ];
}
