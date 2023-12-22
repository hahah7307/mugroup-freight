<?php

namespace app\Manage\validate;

use app\Manage\model\DeliverFeeModel;
use think\exception\DbException;
use think\Validate;
use think\Db;

class DeliverValidate extends Validate
{
    protected $rule = [
        'weight'        =>  'require|checkWeight',
        'value'         =>  'require',
    ];

    protected $message = [
        
    ];

    protected $field = [
        'weight'        =>  '重量',
        'value'         =>  '费用',
    ];

    protected $scene = [
        'add'           =>  ['weight', 'value'],
        'edit'          =>  ['weight', 'value'],
    ];

    /**
     * @throws DbException
     */
    protected function checkWeight($value, $rule, $data)
    {
        $data = DeliverFeeModel::get(['weight' => $value, 'storage_id' => input('storage_id')]);
        return $rule = !empty($data) ? '重量值已存在' : true;
    }
}
