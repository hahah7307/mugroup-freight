<?php
namespace app\Manage\controller;

use app\Manage\model\InfoCategoryModel;
use app\Manage\model\MemberRankModel;
use app\Manage\model\AdminRoleModel;
use \think\Controller;
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
}
