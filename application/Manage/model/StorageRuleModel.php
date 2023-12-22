<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class StorageRuleModel extends Model
{
    const STATE_ACTIVE = 1;

    protected $name = 'storage_rule';

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
    static public function get_storage_rules($storage_id)
    {
        return self::all(['state' => self::STATE_ACTIVE, 'storage_id' => $storage_id]);
    }

    /**
     * @throws DbException
     */
    static public function w2outbound($storage_id, $w)
    {
        $storage_rules = self::all(['state' => self::STATE_ACTIVE, 'storage_id' => $storage_id]);
        $price = 0;
        foreach ($storage_rules as $rule) {
            $ruleCondition = json_decode($rule->condition, true);
            if ($ruleCondition['max'] == 0 && $w > $ruleCondition['min']) {
                $price = $rule->value;
                break;
            } elseif ($w > $ruleCondition['min'] && $w <= $ruleCondition['max']) {
                $price = $rule->value;
                break;
            }
        }

        return $price;
    }
}
