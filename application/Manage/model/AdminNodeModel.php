<?php

namespace app\Manage\model;

use think\Model;
use think\Session;

class AdminNodeModel extends Model
{
    const STATUS_ACTIVE = 1;
    const STATUS_SHOW = 0;

    protected $name = 'admin_node';

    protected $resultSetType = 'collection';

    public function parentNode()
    {
        return $this->hasOne('AdminNodeModel', 'id', 'parent_id');
    }

    // 无限递归+排序
    static public function node_format()
    {
        return self::list_sort([], 0, 1);
    }

    protected function list_sort($list = [], $pid = 0, $level = 1)
    {
        $category = self::with([])->where(['status' => ['egt', self::STATUS_SHOW], 'parent_id' => $pid])->order('sort asc')->select()->toArray();
        if ($category) {
            foreach ($category as $k => $v) {
                $v['level'] = $level;
                $v['node_name'] = str_repeat('&nbsp;', ($level -1) * 6 + 1) . "↳" . $v['name'];
                $list[] = $v;
                $list = self::list_sort($list, $v['id'], $level + 1);
            }
        }
        return $list;
    }

    // 获取所有权限
    static public function get_node_access($arr)
    {
        $node = self::access_format($arr, 0, 1);
        return $node;
    }

    protected function access_format($arr, $pid = 0, $level)
    {
        $node = self::all(['level' => $level, 'parent_id' => $pid, 'status' => self::STATUS_ACTIVE])->toArray();
        if ($node) {
            $level ++;
            foreach ($node as $k => $v) {
                $node[$k]['access'] = in_array($v['id'], $arr) ? 1 : 0;
                $node[$k]['child'] = self::access_format($arr, $v['id'], $level);
            }
        }
        return $node;
    }
}
