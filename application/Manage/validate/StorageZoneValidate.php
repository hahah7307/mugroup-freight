<?php

namespace app\Manage\validate;

use think\Validate;

class StorageZoneValidate extends Validate
{
    protected $rule = [
        'storage_id'        =>  'require',
        'type'              =>  'require',
        'area_id'           =>  'require',
        'zip_code'          =>  'require',
        'zone'              =>  'require',
    ];

    protected $message = [
        
    ];

    protected $field = [
        'storage_id'        =>  '所属仓库',
        'type'              =>  '子仓库位置',
        'area_id'           =>  '子仓库ID',
        'zip_code'          =>  '开始邮编',
        'zone'              =>  'Zone',
    ];

    protected $scene = [
        'add'           =>  ['storage_id', 'type', 'area_id', 'zip_code', 'zone'],
        'edit'          =>  ['storage_id', 'type', 'area_id', 'zip_code', 'zone'],
    ];
}
