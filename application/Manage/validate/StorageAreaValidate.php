<?php

namespace app\Manage\validate;

use think\Validate;
use think\Db;

class StorageAreaValidate extends Validate
{
    protected $rule = [
        'storage_id'        =>  'require',
        'type'              =>  'require',
        'warehouseId'       =>  'require',
        'storage_code'      =>  'require',
        'name'              =>  'require',
    ];

    protected $message = [
        
    ];

    protected $field = [
        'storage_id'        =>  '所属仓库',
        'type'              =>  '子仓库位置',
        'warehouseId'       =>  '子仓库ID',
        'storage_code'      =>  '子仓库代码',
        'name'              =>  '子仓库名称',
    ];

    protected $scene = [
        'add'           =>  ['storage_id', 'type', 'warehouseId', 'storage_code', 'name'],
        'edit'          =>  ['storage_id', 'type', 'warehouseId', 'storage_code', 'name'],
    ];
}
