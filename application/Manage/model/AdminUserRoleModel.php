<?php

namespace app\Manage\model;

use think\Model;
use think\Session;

class AdminUserRoleModel extends Model
{
    const STATUS_ACTIVE = 1;

    protected $name = 'admin_user_role';

    protected $resultSetType = 'collection';

    // public function adminAccess()
    // {
    //     return $this->hasMany('AdminAccessModel', 'role_id', 'id');
    // }

    public function role()
    {
        return $this->hasOne('AdminRoleModel', 'id', 'role_id');
    }
}
