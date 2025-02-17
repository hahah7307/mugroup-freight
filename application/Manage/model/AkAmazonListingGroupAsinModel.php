<?php

namespace app\Manage\model;

use think\Model;

class AkAmazonListingGroupAsinModel extends Model
{
    protected $name = 'ak_amazon_listing_group_asin';

    protected $resultSetType = 'collection';

    public function listing(): \think\model\relation\HasOne
    {
        return $this->hasOne('AkAmazonListingModel', 'listing_id', 'listing_id')->where(['created_date' => date('Ymd')]);
    }
}
