<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class StorageBaseUKModel extends Model
{
    const STATE_ACTIVE = 1;

    protected $name = 'storage_base_uk';

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

    public function storage(): \think\model\relation\HasOne
    {
        return $this->hasOne("StorageModel", "id", "storage_id");
    }

    // 获取基础运费
    /**
     * @throws DbException
     */
    static public function getBaseUK($storage, $order, $detail)
    {
        $condition['storage_id'] = $storage;
        $condition['state'] = self::STATE_ACTIVE;
        $condition['start_at'] = ['lt', $order['dateWarehouseShipping']];
        $condition['end_at'] = ['egt', $order['dateWarehouseShipping']];
        $condition['shipping_method'] = $order['shippingMethod'];
        $baseUK = new StorageBaseUKModel();
        $list = $baseUK->where($condition)->order('level asc')->select();
        $price = 0;
        foreach ($list as $rule) {
            $ruleCondition = json_decode($rule['condition'], true);
            if ($ruleCondition['max'] == 0 && $detail['product']['productWeight'] > $ruleCondition['min']) {
                $price = $rule['value'];
                break;
            } elseif ($detail['product']['productWeight'] > $ruleCondition['min'] && $detail['product']['productWeight'] <= $ruleCondition['max']) {
                $price = $rule['value'];
                break;
            }
            unset($rule);
        }

        return $price;
    }
}
