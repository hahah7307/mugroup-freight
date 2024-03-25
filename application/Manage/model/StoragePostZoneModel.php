<?php

namespace app\Manage\model;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Model;

class StoragePostZoneModel extends Model
{
    const STATE_ACTIVE = 1;

    protected $name = 'storage_post_zone';

    protected $resultSetType = 'collection';
}
