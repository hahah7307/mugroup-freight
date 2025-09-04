<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class TaskModel extends Model
{
    const STATE_ACTIVE = 0;

    protected $name = 'task';

    protected $resultSetType = 'collection';

    protected $autoWriteTimestamp = false; // 不自动写入时间戳

    protected $dateFormat = false;         // 不做自动格式化

    public function account(): \think\model\relation\HasOne
    {
        return $this->hasOne('AccountModel', 'id', 'create_id');
    }
}
