<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class CostEstimateLeModel extends Model
{
    protected $name = 'cost_estimate_le';

    protected $resultSetType = 'collection';
}
