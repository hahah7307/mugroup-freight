<?php

namespace app\Manage\validate;

use think\Validate;

class StorageBaseValidate extends Validate
{
    protected $rule = [
        'storage_id'            =>  'require',
        'lbs_weight'            =>  'require',
        'kg_weight'             =>  'require',
        'value'                 =>  'require',
        'zone'                  =>  'require',
    ];

    protected $message = [
        
    ];

    protected $field = [
        'storage_id'            =>  '所属仓库',
        'lbs_weight'            =>  '子仓库位置',
        'kg_weight'             =>  '子仓库ID',
        'value'                 =>  '开始邮编',
        'zone'                  =>  'Zone',
    ];

    protected $scene = [
        'add'           =>  ['storage_id', 'lbs_weight', 'kg_weight', 'value', 'zone'],
        'edit'          =>  ['storage_id', 'lbs_weight', 'kg_weight', 'value', 'zone'],
    ];
}
