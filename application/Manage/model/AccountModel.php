<?php

namespace app\Manage\model;

use think\Model;
use think\Session;

class AccountModel extends Model
{
    const STATUS_ACTIVE = 1;

    protected $name = 'admin_user';

    protected $resultSetType = 'collection';

    protected $insert = ['created_at', 'updated_at'];

    protected $update = ['updated_at'];

    protected function setCreatedAtAttr()
    {
        return time();
    }

    protected function setUpdatedAtAttr()
    {
        return time();
    }

    public function userRole()
    {
        return $this->hasMany('AdminUserRoleModel', 'user_id', 'id');
    }

    // 获取用户的所有权限
    static public function account_access($id)
    {
        $access = self::with(['userRole.role.accessLevel.adminNode.parentNode'])->where(['status' => self::STATUS_ACTIVE, 'id' => $id])->find();
        $accessList = array();
        foreach ($access['user_role'] as $ka => $va) {
            foreach ($va['role']['access_level'] as $kb => $vb) {
                $accessList[] = strtolower($vb['admin_node']['parent_node']['code'] . '/' . $vb['admin_node']['code']);
            }
        }
        return array_unique($accessList);
    }

    // 验证用户权限
    static public function action_access($controller, $action, $access, $user)
    {
        if ($user['super'] == 1) {
            return true;
        } else {
            if (in_array(strtolower($controller), config('ACCESS_CONTROLLER'))) {
                return true;
            } else {
                if (in_array(strtolower($controller . '/' . $action), config('ACCESS_ACTION'))) {
                    return true;
                } else {
                    if (in_array(strtolower($controller . '/' . $action), $access)) {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        }
    }
}
