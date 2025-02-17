<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class AkAmazonListingGroupModel extends Model
{
    const STATUS_ACTIVE = 1;

    protected $name = 'ak_amazon_listing_group';

    protected $resultSetType = 'collection';

    /**
     * @throws DbException
     */
    static public function generateListingGroups()
    {
        return self::all(['status' => 1]);
    }
}
