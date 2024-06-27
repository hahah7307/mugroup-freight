<?php

namespace app\Manage\model;

use think\Model;

class AdminUserSmsModel extends Model
{
    protected $name = 'admin_user_sms';

    protected $resultSetType = 'collection';

    protected $insert = ['created_at'];

    protected function setCreatedAtAttr()
    {
        return date('Y-m-d H:i:s');
    }
}
