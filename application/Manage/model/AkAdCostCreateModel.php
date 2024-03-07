<?php

namespace app\Manage\model;

use think\Model;

class AkAdCostCreateModel extends Model
{
    protected $name = 'ak_ad_cost_create';

    protected $resultSetType = 'collection';

    static public function newOne($month): AkAdCostCreateModel
    {
        $month = date('Ym', strtotime($month . '-01'));
        $params = [
            'id'            =>  1,
            'month'         =>  $month,
            'page'          =>  1,
            'is_finished'   =>  0
        ];
        return self::update($params);
    }
}
