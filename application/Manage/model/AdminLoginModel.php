<?php

namespace app\Manage\model;

use think\Model;
use think\Session;

class AdminLoginModel extends Model
{
    const STATUS_ACTIVE = 1;

    protected $name = 'admin_login';

    protected $resultSetType = 'collection';

    protected $insert = ['created_at'];

    protected function setCreatedAtAttr()
    {
        return time();
    }
}
