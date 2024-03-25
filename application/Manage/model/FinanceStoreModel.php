<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class FinanceStoreModel extends Model
{
    protected $name = 'finance_store';

    protected $resultSetType = 'collection';
}
