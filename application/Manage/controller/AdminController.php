<?php
namespace app\Manage\controller;

use app\Manage\model\AccountModel;
use app\Manage\model\AdminRoleModel;
use app\Manage\model\AdminUserRoleModel;
use app\Manage\model\AdminNodeModel;
use app\Manage\model\AdminAccessModel;
use app\Manage\model\AdminLoginModel;
use app\Manage\validate\AccountValidate;
use app\Manage\validate\AdminRoleValidate;
use app\Manage\validate\AdminNodeValidate;
use app\Manage\validate\AdminAccessValidate;
use think\Exception;
use think\Controller;
use think\Session;
use think\Config;
use think\Db;

class AdminController extends BaseController
{
    public function index()
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['username|nickname|phone|email'] = ['like', '%' . $keyword . '%'];
        }
        $where['id'] = ['neq', 1];

        // 广告列表
        $account = new AccountModel;
        $list = $account->where($where)->order('id asc')->paginate(Config::get('PAGE_NUM'));
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    // 添加
    public function user_add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            if ($post['password'] != $post['repassword']) {
                echo json_encode(['code' => 0, 'msg' => '密码不一致']);
                exit;
            }
            $post['password_hash'] = createNoncestr(32);
            $post['password'] = encPass($post['password'], $post['password_hash']);
            $role = explode(',', $post['role']);
            unset($post['role']);
            $dataValidate = new AccountValidate();
            if ($dataValidate->scene('add')->check($post)) {
                Db::startTrans();
                try {
                    $model = new AccountModel();
                    if ($model->allowField(true)->save($post)) {
                        $id = $model->id;
                        AdminUserRoleModel::where('user_id', '=', $id)->delete();
                        if ($role) {
                            foreach ($role as $k => $v) {
                                $data[] = [
                                    'role_id'   =>  $v,
                                    'user_id'   =>  $id
                                ];
                            }
                            $admin_role = new AdminUserRoleModel();
                            if ($admin_role->saveAll($data)) {
                                Db::commit();
                                echo json_encode(['code' => 1, 'msg' => '添加成功']);
                                exit;
                            } else {
                                throw new Exception("添加失败，请重试");
                            }
                        }
                    } else {
                        throw new Exception("添加失败，请重试");
                    }
                } catch (Exception $e) {
                    Db::rollback();
                    echo json_encode(['code' => 0, 'msg' => $e->getMessage()]);
                    exit;
                }
            } else {
                echo json_encode(['code' => 0, 'msg' => $dataValidate->getError()]);
                exit;
            }
        } else {

            return view();
        }
    }

    // 编辑
    public function user_edit($id)
    {
        $info = AccountModel::with(['user_role'])->where(['id' => $id])->find();
        if ($this->request->isPost()) {
            $post = $this->request->post();
            if ($post['password'] != $post['repassword']) {
                echo json_encode(['code' => 0, 'msg' => '密码不一致']);
                exit;
            }
            if ($post['password']) {
                $post['password'] = encPass($post['password'], $info['password_hash']);
            }
            $role = explode(',', $post['role']);
            unset($post['role']);
            $post = array_filter($post);
            $dataValidate = new AccountValidate();
            if ($dataValidate->scene('edit')->check($post)) {
                Db::startTrans();
                try {
                    $model = new AccountModel();
                    if ($model->allowField(true)->save($post, ['id' => $id])) {
                        AdminUserRoleModel::where('user_id', '=', $id)->delete();
                        if ($role) {
                            foreach ($role as $k => $v) {
                                $data[] = [
                                    'role_id'   =>  $v,
                                    'user_id'   =>  $id
                                ];
                            }
                            $admin_role = new AdminUserRoleModel();
                            if ($admin_role->saveAll($data)) {
                                Db::commit();
                                echo json_encode(['code' => 1, 'msg' => '修改成功']);
                                exit;
                            } else {
                                throw new Exception("修改失败，请重试");
                            }
                        }
                    } else {
                        throw new Exception("修改失败，请重试");
                    }
                } catch (Exception $e) {
                    Db::rollback();
                    echo json_encode(['code' => 0, 'msg' => $e->getMessage()]);
                    exit;
                }
            } else {
                echo json_encode(['code' => 0, 'msg' => $dataValidate->getError()]);
                exit;
            }
        } else {
            $role = array();
            foreach ($info['user_role'] as $k => $v) {
                $role[] = $v['role_id'];
            }
            $info['role'] = implode(',', $role);
            $this->assign('info', $info);

            return view();
        }
    }

    // 删除
    public function user_delete()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $block = AccountModel::get($post['id']);
            if ($block->delete()) {
                echo json_encode(['code' => 1, 'msg' => '操作成功']);
                exit;
            } else {
                echo json_encode(['code' => 0, 'msg' => '操作失败，请重试']);
                exit;
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
            exit;
        }
    }

    // 状态切换
    public function user_status()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $user = AccountModel::get($post['id']);
            if ($user['super'] == 1) {
                echo json_encode(['code' => 0, 'msg' => '无法锁定超级管理员']);
                exit;
            }
            $user['status'] = $user['status'] == AccountModel::STATUS_ACTIVE ? 0 : AccountModel::STATUS_ACTIVE;
            $user->save();
            echo json_encode(['code' => 1, 'msg' => '操作成功']);
            exit;
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
            exit;
        }
    }

    // 登录记录
    public function user_login($id)
    {
        $list = new AdminLoginModel();
        $list = $list->where(['status' => AdminLoginModel::STATUS_ACTIVE, 'aid' => $id])->order('id desc')->paginate(Config::get('PAGE_NUM'));
        $this->assign('list', $list);

        return view();
    }

    // 角色
    public function role()
    {
        // 广告列表
        $account = new AdminRoleModel;
        $list = $account->where($where)->order('sort asc,id asc')->paginate(Config::get('PAGE_NUM'));
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    // 角色添加
    public function role_add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $dataValidate = new AdminRoleValidate();
            if ($dataValidate->scene('add')->check($post)) {
                $model = new AdminRoleModel();
                if ($model->allowField(true)->save($post)) {
                    echo json_encode(['code' => 1, 'msg' => '添加成功']);
                    exit;
                } else {
                    echo json_encode(['code' => 0, 'msg' => '添加失败，请重试']);
                    exit;
                }
            } else {
                echo json_encode(['code' => 0, 'msg' => $dataValidate->getError()]);
                exit;
            }
        } else {

            return view();
        }
    }

    // 编辑
    public function role_edit($id)
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $dataValidate = new AdminRoleValidate();
            if ($dataValidate->scene('edit')->check($post)) {
                $model = new AdminRoleModel();
                if ($model->allowField(true)->save($post, ['id' => $id])) {
                    echo json_encode(['code' => 1, 'msg' => '修改成功']);
                    exit;
                } else {
                    echo json_encode(['code' => 0, 'msg' => '修改失败，请重试']);
                    exit;
                }
            } else {
                echo json_encode(['code' => 0, 'msg' => $dataValidate->getError()]);
                exit;
            }
        } else {
            $info = AdminRoleModel::get(['id' => $id,]);
            $this->assign('info', $info);

            return view();
        }
    }

    // 删除
    public function role_delete()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $block = AdminRoleModel::get($post['id']);
            if ($block->delete()) {
                echo json_encode(['code' => 1, 'msg' => '操作成功']);
                exit;
            } else {
                echo json_encode(['code' => 0, 'msg' => '操作失败，请重试']);
                exit;
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
            exit;
        }
    }

    // 排序
    public function role_sort()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $category = new AdminRoleModel();
            $data = [];
            foreach ($post['sort'] as $k => $v) {
                $data[] = ['id' => $k, 'sort' => $v];
            }
            $category->saveAll($data);
            echo json_encode(['code' => 1, 'msg' => '操作成功']);
            exit;
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
            exit;
        }
    }

    // 状态切换
    public function role_status()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $user = AdminRoleModel::get($post['id']);
            $user['status'] = $user['status'] == AdminRoleModel::STATUS_ACTIVE ? 0 : AdminRoleModel::STATUS_ACTIVE;
            $user->save();
            echo json_encode(['code' => 1, 'msg' => '操作成功']);
            exit;
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
            exit;
        }
    }

    // 节点
    public function node()
    {
        // 广告列表
        $list = AdminNodeModel::node_format();
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    // 角色添加
    public function node_add($id = 1)
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $parent = AdminNodeModel::get(['id' => $id]);
            $post['level'] = ++ $parent['level'];
            $dataValidate = new AdminNodeValidate();
            if ($dataValidate->scene('add')->check($post)) {
                $model = new AdminNodeModel();
                if ($model->allowField(true)->save($post)) {
                    echo json_encode(['code' => 1, 'msg' => '添加成功']);
                    exit;
                } else {
                    echo json_encode(['code' => 0, 'msg' => '添加失败，请重试']);
                    exit;
                }
            } else {
                echo json_encode(['code' => 0, 'msg' => $dataValidate->getError()]);
                exit;
            }
        } else {
            $node = AdminNodeModel::node_format();
            $this->assign('node', $node);
            $this->assign('id', $id);

            return view();
        }
    }

    // 编辑
    public function node_edit($id)
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            if ($post['parent_id'] == $id) {
                echo json_encode(['code' => 0, 'msg' => '无法选择自己作为父级节点']);
                exit;
            }
            $parent = AdminNodeModel::get(['id' => $post['parent_id']]);
            if ($parent['level'] == 3) {
                echo json_encode(['code' => 0, 'msg' => '请选择正确的父级节点']);
                exit;
            } else {
                $post['level'] = ++ $parent['level'];
            }
            $dataValidate = new AdminNodeValidate();
            if ($dataValidate->scene('edit')->check($post)) {
                $model = new AdminNodeModel();
                if ($model->allowField(true)->save($post, ['id' => $id])) {
                    echo json_encode(['code' => 1, 'msg' => '修改成功']);
                    exit;
                } else {
                    echo json_encode(['code' => 0, 'msg' => '修改失败，请重试']);
                    exit;
                }
            } else {
                echo json_encode(['code' => 0, 'msg' => $dataValidate->getError()]);
                exit;
            }
        } else {
            $node = AdminNodeModel::node_format();
            $this->assign('node', $node);
            $info = AdminNodeModel::get(['id' => $id,]);
            $this->assign('info', $info);

            return view();
        }
    }

    // 删除
    public function node_delete()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $node = AdminNodeModel::get($post['id']);
            if ($node->delete()) {
                echo json_encode(['code' => 1, 'msg' => '操作成功']);
                exit;
            } else {
                echo json_encode(['code' => 0, 'msg' => '操作失败，请重试']);
                exit;
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
            exit;
        }
    }

    // 排序
    public function node_sort()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $node = new AdminNodeModel();
            $data = [];
            foreach ($post['sort'] as $k => $v) {
                $data[] = ['id' => $k, 'sort' => $v];
            }
            $node->saveAll($data);
            echo json_encode(['code' => 1, 'msg' => '操作成功']);
            exit;
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
            exit;
        }
    }

    // 状态切换
    public function node_status()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $user = AdminNodeModel::get($post['id']);
            $user['status'] = $user['status'] == AdminNodeModel::STATUS_ACTIVE ? 0 : AdminNodeModel::STATUS_ACTIVE;
            $user->save();
            echo json_encode(['code' => 1, 'msg' => '操作成功']);
            exit;
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
            exit;
        }
    }

    // 角色权限
    public function role_access($id)
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $access = $post['access'];
            Db::startTrans();
            try {
                AdminAccessModel::where('role_id', '=', $id)->delete();
                if ($access) {
                    foreach ($access as $k => $v) {
                        $ex = explode('_', $v);
                        $data = [
                            'role_id'   =>  $id,
                            'node_id'   =>  $ex[0],
                            'level'     =>  $ex[1],
                        ];
                        $dataValidate = new AdminAccessValidate();
                        if ($dataValidate->scene('add')->check($data)) {
                            $model = new AdminAccessModel();
                            if ($model->allowField(true)->save($data)) {

                            } else {
                                throw new Exception("添加失败，请重试");
                            }
                        } else {
                            throw new Exception("操作失败，请重试");
                        }
                    }
                }
                Db::commit();
                echo json_encode(['code' => 1, 'msg' => '操作成功']);
                exit;
            } catch (Exception $e) {
                Db::rollback();
                echo json_encode(['code' => 0, 'msg' => $e->getMessage()]);
                exit;
            }
        } else {
            $role = AdminRoleModel::get(['id' => $id]);
            $this->assign('role', $role);

            $access = AdminRoleModel::with(['access'])->where(['id' => $role['id']])->find()->toArray();
            $access_list = array_column($access['access'], 'node_id');
            $list = AdminNodeModel::get_node_access($access_list);
            $this->assign('list', $list);

            return view();
        }
    }
}
