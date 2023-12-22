<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class DeliverFeeModel extends Model
{
    const STATE_ACTIVE = 1;

    protected $name = 'deliver_fee';

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

    /**
     * @throws DbException
     */
    static public function get_deliver_fees($storage_id)
    {
        return self::all(['state' => self::STATE_ACTIVE, 'storage_id' => $storage_id]);
    }

    /**
     * @throws DbException
     */
    static public function w2deliverFee($storage_id, $w = 1)
    {
        return $storage_rules = self::get(['state' => self::STATE_ACTIVE, 'storage_id' => $storage_id, 'weight' => $w])->getData('value');
    }
}
