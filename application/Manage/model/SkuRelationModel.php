<?php

namespace app\Manage\model;

use think\Model;

class SkuRelationModel extends Model
{
    protected $name = 'ecang_sku_relation';

    protected $resultSetType = 'collection';

    protected $insert = ['pcr_update_time'];

    protected $update = ['pcr_update_time'];

    protected function setPcrUpdateTimeAttr()
    {
        return date('Y-m-d H:i:s');
    }
}
