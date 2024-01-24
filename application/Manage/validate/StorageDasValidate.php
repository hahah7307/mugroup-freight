<?php

namespace app\Manage\validate;

use think\Validate;

class StorageDasValidate extends Validate
{
    protected $rule = [
        'storage_id'        =>  'require',
        'type'              =>  'require',
        'zip_code'          =>  'require',
    ];

    protected $message = [
        
    ];

    protected $field = [
        'storage_id'        =>  '所属仓库',
        'type'              =>  '偏远地区类型',
        'zip_code'          =>  '邮编',
    ];

    protected $scene = [
        'add'           =>  ['storage_id', 'type', 'zip_code'],
        'edit'          =>  ['storage_id', 'type', 'zip_code'],
    ];
}
