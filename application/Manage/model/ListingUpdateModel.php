<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class ListingUpdateModel extends Model
{
    const STATE_ACTIVE = 1;

    protected $name = 'ecang_listing_update';

    protected $resultSetType = 'collection';
}
