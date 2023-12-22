<?php

namespace app\Manage\model;

use think\Model;
use think\Session;

class AdminRoleModel extends Model
{
    const STATUS_ACTIVE = 1;

    protected $name = 'admin_role';

    protected $resultSetType = 'collection';

    public function access()
    {
        return $this->hasMany('AdminAccessModel', 'role_id', 'id');
    }

    public function accessLevel()
    {
        return $this->hasMany('AdminAccessModel', 'role_id', 'id')->where(['level' => 3]);
    }
}
