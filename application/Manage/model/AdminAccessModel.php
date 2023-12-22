<?php

namespace app\Manage\model;

use think\Model;
use think\Session;

class AdminAccessModel extends Model
{
    const STATUS_ACTIVE = 1;

    protected $name = 'admin_access';

    protected $resultSetType = 'collection';

    public function adminNode()
    {
        return $this->hasOne('AdminNodeModel', 'id', 'node_id');
    }
}
