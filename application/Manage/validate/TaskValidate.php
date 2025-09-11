<?php

namespace app\Manage\validate;

use think\Validate;
use think\Db;

class TaskValidate extends Validate
{
    protected $rule = [
        'task_no'           =>  'require',
        'title'             =>  'require',
        'user_master'       =>  'require',
        'ect_time'          =>  'require',
        'create_time'       =>  'require',
        'create_id'         =>  'require',
        'status'            =>  'require',
    ];

    protected $message = [
        
    ];

    protected $field = [
        'task_no'           =>  '编号',
        'title'             =>  '事项内容',
        'user_master'       =>  '主负责人',
        'user_support'      =>  '相关人员',
        'ect_time'          =>  '预计完成时间',
        'act_time'          =>  '实际完成时间',
        'remarks'           =>  '备注',
        'create_time'       =>  '创建时间',
        'create_id'         =>  '创建人',
        'status'            =>  '状态',
    ];

    protected $scene = [
        'add'           =>  ['task_no', 'title', 'user_master', 'ect_time', 'create_time', 'create_id', 'status'],
        'edit'          =>  ['title', 'user_master'],
    ];
}
