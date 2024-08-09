<?php

namespace app\Manage\model;

use think\Model;

class FinanceSkuRelationModel extends Model
{
    protected $name = 'finance_sku_relation';

    protected $resultSetType = 'collection';

    protected $insert = ['created_date', 'updated_date'];

    protected $update = ['updated_date'];

    protected function setCreatedDateAttr()
    {
        return date('Y-m-d H:i:s');
    }

    protected function setUpdatedDateAttr()
    {
        return date('Y-m-d H:i:s');
    }
}
