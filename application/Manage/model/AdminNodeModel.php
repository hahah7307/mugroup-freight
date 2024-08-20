<?php

namespace app\Manage\model;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Model;

class AdminNodeModel extends Model
{
    const STATUS_ACTIVE = 1;
    const STATUS_SHOW = 0;

    protected $name = 'admin_node';

    protected $resultSetType = 'collection';

    public function parentNode(): \think\model\relation\HasOne
    {
        return $this->hasOne('AdminNodeModel', 'id', 'parent_id');
    }

    // 无限递归+排序
    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    static public function node_format()
    {
        return self::list_sort();
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    protected static function list_sort($list = [], $pid = 0, $level = 1)
    {
        $adminNodeObj = new AdminNodeModel();
        $category = $adminNodeObj->where(['status' => ['egt', self::STATUS_SHOW], 'parent_id' => $pid])->order('sort asc')->select();
        if ($category) {
            foreach ($category as $v) {
                $v['level'] = $level;
                $v['node_name'] = str_repeat('&nbsp;', ($level -1) * 6 + 1) . "↳" . $v['name'];
                $list[] = $v;
                $list = self::list_sort($list, $v['id'], $level + 1);
            }
        }
        return $list;
    }

    // 获取所有权限
    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    static public function get_node_access($arr)
    {
        return self::access_format($arr, 1);
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    protected static function access_format($arr, $level, $pid = 0)
    {
        $adminNodeObj = new AdminNodeModel();
        $node = $adminNodeObj->order('sort asc')->where(['level' => $level, 'parent_id' => $pid, 'status' => self::STATUS_ACTIVE])->select();
        if ($node) {
            $level ++;
            foreach ($node as $k => $v) {
                $node[$k]['access'] = in_array($v['id'], $arr) ? 1 : 0;
                $node[$k]['child'] = self::access_format($arr, $level, $v['id']);
            }
        }
        return $node;
    }
}
