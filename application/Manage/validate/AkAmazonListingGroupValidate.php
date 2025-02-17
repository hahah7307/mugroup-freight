<?php

namespace app\Manage\validate;

use think\Validate;
use think\Db;

class AkAmazonListingGroupValidate extends Validate
{
    protected $rule = [
        'group_name'    =>  'require',
        'desc'          =>  'require',
        'status'        =>  'require'
    ];

    protected $message = [
        
    ];

    protected $field = [
        'group_name'    =>  '产品组名称',
        'desc'          =>  '产品组描述',
        'status'        =>  '状态',
    ];

    protected $scene = [
        'add'           =>  ['group_name', 'status'],
        'edit'          =>  ['group_name'],
    ];
}
