<?php
namespace app\Manage\controller;

use app\Manage\model\AccountModel;
use app\Manage\model\FinanceReportSnapshotModel;
use app\Manage\model\FinanceSkuGroupModel;
use app\Manage\model\InfoCategoryModel;
use app\Manage\model\MemberRankModel;
use app\Manage\model\AdminRoleModel;
use app\Manage\model\TaskUserModel;
use \think\Controller;
use think\db\exception\BindParamException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\exception\PDOException;
use \think\Session;
use think\Db;

class CheckController extends BaseController
{
    public function smsSend()
    {
        $phone = $this->request->get('phone');
        // 短信api


        return json(['code' => 1, 'msg' => $phone .'验证码：7307']);
    }

    // 获取所有详情分类
    public function get_full_infoCategory()
    {
    	if ($this->request->isPost()) {
            $keyword = $this->request->param('keyword');
            $cid = $this->request->param('cid');
            $category = new InfoCategoryModel();
            if (!empty($keyword)) {
                $category = $category->where(['language_id' => $this->language_id, 'status' => InfoCategoryModel::STATUS_ACTIVE, 'name' => ['like', '%'.$keyword.'%']])->field('name, id as value')->select();
                if (!empty($cid)) {
                    $cid = explode(',', $cid);
                    array_pop($cid);
                    array_shift($cid);
                    foreach ($category as $k => $v) {
                        if (in_array($v['value'], $cid)) {
                            $category[$k]['selected'] = true;
                        }
                    }
                }
                echo json_encode(array('msg' => 'success', 'code' => 0, 'data' => $category));
                exit;
            } else{
                $category = $category->where(['language_id' => $this->language_id, 'status' => InfoCategoryModel::STATUS_ACTIVE])->field('name, id as value')->select();
                if (!empty($cid)) {
                    $cid = explode(',', $cid);
                    array_pop($cid);
                    array_shift($cid);
                    foreach ($category as $k => $v) {
                        if (in_array($v['value'], $cid)) {
                            $category[$k]['selected'] = true;
                        }
                    }
                }
                echo json_encode(array('msg' => 'success', 'code' => 0, 'data' => $category));
                exit;
            }
        } else {
            echo json_encode(array('msg' => 'failed', 'code' => 1, 'data' => '您的操作有误！'));
            exit;
        }
    }

    // 获取所有用户角色
    public function get_full_adminRole()
    {
        if ($this->request->isPost()) {
            $keyword = $this->request->param('keyword');
            $cid = $this->request->param('cid');
            $category = new AdminRoleModel();
            if (!empty($keyword)) {
                $category = $category->where(['status' => AdminRoleModel::STATUS_ACTIVE, 'name' => ['like', '%'.$keyword.'%']])->field('name, id as value')->select();
                if (!empty($cid)) {
                    $cid = explode(',', $cid);
                    array_pop($cid);
                    array_shift($cid);
                    foreach ($category as $k => $v) {
                        if (in_array($v['value'], $cid)) {
                            $category[$k]['selected'] = true;
                        }
                    }
                }
                echo json_encode(array('msg' => 'success', 'code' => 0, 'data' => $category));
                exit;
            } else{
                $category = $category->where(['status' => AdminRoleModel::STATUS_ACTIVE])->field('name, id as value')->select();
                if (!empty($cid)) {
                    $cid = explode(',', $cid);
                    foreach ($category as $k => $v) {
                        if (in_array($v['value'], $cid)) {
                            $category[$k]['selected'] = true;
                        }
                    }
                }
                echo json_encode(array('msg' => 'success', 'code' => 0, 'data' => $category));
                exit;
            }
        } else {
            echo json_encode(array('msg' => 'failed', 'code' => 1, 'data' => '您的操作有误！'));
            exit;
        }
    }

    // 获取所有会员职位
    public function get_full_memberRank()
    {
        if ($this->request->isPost()) {
            $keyword = $this->request->param('keyword');
            $rank = $this->request->param('rank');
            $language = $this->language_id;
            $memberRank = new MemberRankModel();
            if (!empty($keyword)) {
                $list = $memberRank->where(['status' => MemberRankModel::STATUS_ACTIVE, 'name' => ['like', '%'.$keyword.'%']])->field('name, id as value')->select();
                if (!empty($rank)) {
                    $rank = explode(',', $rank);
                    array_pop($rank);
                    array_shift($rank);
                    foreach ($list as $k => $v) {
                        if (in_array($v['value'], $rank)) {
                            $list[$k]['selected'] = true;
                        }
                    }
                }
                echo json_encode(array('msg' => 'success', 'code' => 0, 'data' => $list));
                exit;
            } else{
                $list = $memberRank->where(['status' => MemberRankModel::STATUS_ACTIVE])->field('name, id as value')->select();
                if (!empty($rank)) {
                    $rank = explode(',', $rank);
                    array_pop($rank);
                    array_shift($rank);
                    foreach ($list as $k => $v) {
                        if (in_array($v['value'], $rank)) {
                            $list[$k]['selected'] = true;
                        }
                    }
                }
                echo json_encode(array('msg' => 'success', 'code' => 0, 'data' => $list));
                exit;
            }
        } else {
            echo json_encode(array('msg' => 'failed', 'code' => 1, 'data' => '您的操作有误！'));
            exit;
        }
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function get_task_active_user($selected = '')
    {
        if (!empty($selected)) {
            $selectArr = explode(',', $selected);
        } else {
            $selectArr = [];
        }
        $model = new TaskUserModel();
        $resData = [];
        $list = $model->where(['status' => 1])->select();
        foreach ($list as $item) {
            if (in_array($item['id'], $selectArr)) {
                $resData[] = [
                    'name'      =>  $item['user_name'],
                    'value'     =>  $item['id'],
                    'selected'  =>  true
                ];
            } else {
                $resData[] = [
                    'name'      =>  $item['user_name'],
                    'value'     =>  $item['id']
                ];
            }
        }

        return json_encode($resData);
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function get_task_active_admin($selected = '')
    {
        if (!empty($selected)) {
            $selectArr = explode(',', $selected);
        } else {
            $selectArr = [];
        }
        $model = new AccountModel();
        $resData = [];
        $list = $model->where(['status' => 1])->select();
        foreach ($list as $item) {
            if (in_array($item['id'], $selectArr)) {
                $resData[] = [
                    'name'      =>  $item['nickname'],
                    'value'     =>  $item['id'],
                    'selected'  =>  true
                ];
            } else {
                $resData[] = [
                    'name'      =>  $item['nickname'],
                    'value'     =>  $item['id']
                ];
            }
        }

        return json_encode($resData);
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    public function get_group_name_origin($tag = '----')
    {
        $params = $this->request->param();
        $arr = [];
        $model = new FinanceSkuGroupModel();
        $list = $model->query('
SELECT DISTINCT
	group_name,
	group_name_origin 
FROM
	mu_finance_sku_group 
ORDER BY
	group_name ASC,
	group_name_origin ASC;
        ');
        foreach ($list as $item) {
            $arr[$item['group_name']][] = $item['group_name_origin'];
        }
        $new = [];
        if ($arr) {
            foreach ($arr as $k => $v) {
                if (end($new) != $k) {
                    if ($k == $params['selected'][0]) {
                        $new[] = ['name' => $k, 'value' => $k, 'selected' => true];
                    } else {
                        $new[] = ['name' => $k, 'value' => $k];
                    }
                }
                foreach ($v as $group_name) {
                    if ($tag . $group_name == $params['selected'][0]) {
                        $new[] = ['name' => $tag . $group_name, 'value' => $tag . $group_name, 'selected' => true];
                    } else {
                        $new[] = ['name' => $tag . $group_name, 'value' => $tag . $group_name];
                    }
                }
            }
        }

        return json_encode($new);
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    public function get_group_name_origin_multi($tag = '----')
    {
        $params = $this->request->param();
        $selected = [];
        if (!empty($params['selected'])) {
            $selected = explode(',', $params['selected']);
        }

        $arr = [];
        $model = new FinanceSkuGroupModel();
        $list = $model->query('
SELECT DISTINCT
	group_name,
	group_name_origin 
FROM
	mu_finance_sku_group 
ORDER BY
	group_name ASC,
	group_name_origin ASC;
        ');
        foreach ($list as $item) {
            $arr[$item['group_name']][] = $item['group_name_origin'];
        }
        $new = [];
        if ($arr) {
            foreach ($arr as $k => $v) {
                if (end($new) != $k) {
                    $new[] = ['name' => $k, 'value' => $k, 'disabled' => true];
                }
                foreach ($v as $group_name) {
                    if (in_array($tag . $group_name, $selected)){
                        $new[] = ['name' => $tag . $group_name, 'value' => $tag . $group_name, 'selected' => true];
                    } else {
                        $new[] = ['name' => $tag . $group_name, 'value' => $tag . $group_name];
                    }
                }
            }
        }

        return json_encode($new);
    }

    public function get_platform_finance_snapshot()
    {
        $params = $this->request->param();
        $selected = [];
        if (!empty($params['selected'])) {
            $selected = explode(',', $params['selected']);
        }
        $arr = [];
        $model = new FinanceReportSnapshotModel();
        $list = $model->query('
SELECT DISTINCT platform FROM mu_finance_report_snapshot ORDER BY platform ASC;
        ');
        foreach ($list as $item) {
            if (in_array($item['platform'], $selected)) {
                $arr[] = ['name' => $item['platform'], 'value' => $item['platform'], 'selected' => true];
            } else {
                $arr[] = ['name' => $item['platform'], 'value' => $item['platform']];
            }
        }

        return json_encode($arr);
    }
}
