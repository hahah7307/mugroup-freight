<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class ListingModel extends Model
{
    const STATE_ACTIVE = 1;

    protected $name = 'ecang_listing';

    protected $resultSetType = 'collection';
}
