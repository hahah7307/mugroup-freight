<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class FinanceEvaluationModel extends Model
{
    protected $name = 'finance_evaluation';

    protected $resultSetType = 'collection';
}
