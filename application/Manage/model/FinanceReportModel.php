<?php

namespace app\Manage\model;

use think\Model;

class FinanceReportModel extends Model
{
    const STATE_ACTIVE = 1;

    protected $name = 'finance_report';

    protected $resultSetType = 'collection';

    protected $insert = ['created_at', 'updated_at'];

    protected $update = ['updated_at'];

    protected function setCreatedAtAttr()
    {
        return date('Y-m-d H:i:s');
    }

    protected function setUpdatedAtAttr()
    {
        return date('Y-m-d H:i:s');
    }
}
