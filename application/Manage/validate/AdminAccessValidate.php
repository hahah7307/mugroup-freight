<?php

namespace app\Manage\validate;

use think\Validate;
use think\Db;

class AdminAccessValidate extends Validate
{
    protected $rule = [
        'role_id'           =>  'require',
        'node_id'           =>  'require',
        'level'             =>  'number',
    ];

    protected $message = [
        
    ];

    protected $field = [
        'role_id'           =>  '角色id',
        'node_id'           =>  '节点id',
        'level'             =>  '节点等级',
    ];

    protected $scene = [
        'add'           =>  ['role_id', 'node_id', 'level'],
        'edit'          =>  ['role_id', 'node_id', 'level'],
    ];
}
