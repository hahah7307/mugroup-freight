<?php

namespace app\Manage\validate;

use think\Validate;
use think\Db;

class StorageOutboundValidate extends Validate
{
    protected $rule = [
        'storage_id'            =>  'require',
        'platform_tag'          =>  'require',
        'name'                  =>  'require',
        'description'           =>  'require',
        'short'                 =>  '',
        'condition'             =>  'require',
        'value'                 =>  'require',
        'level'                 =>  'require',
    ];

    protected $message = [
        
    ];

    protected $field = [
        'storage_id'            =>  '仓库ID',
        'platform_tag'          =>  '平台',
        'name'                  =>  '名称',
        'description'           =>  '描述',
        'short'                 =>  '短描述',
        'condition'             =>  'condition',
        'value'                 =>  '金额',
        'level'                 =>  'Level',
    ];

    protected $scene = [
        'add'           =>  ['storage_id', 'platform_tag', 'name', 'description', 'condition', 'value', 'level'],
        'edit'          =>  ['storage_id', 'platform_tag', 'name', 'description', 'condition', 'value', 'level'],
    ];
}
