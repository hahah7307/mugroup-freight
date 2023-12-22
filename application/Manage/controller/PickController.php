<?php
namespace app\Manage\controller;

use app\Manage\model\StorageRuleModel;
use app\Manage\validate\StorageRuleValidate;
use think\exception\DbException;
use think\Session;
use think\Config;

class PickController extends BaseController
{
    /**
     * @throws DbException
     */
    public function index()
    {
        $where['storage_id'] = input('id');

        // 拣货费列表
        $storage = new StorageRuleModel();
        $list = $storage->where($where)->order('id asc')->paginate(Config::get('PAGE_NUM'));
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        $this->assign('storage_id', input('id'));

        return view();
    }

    // 添加
    public function add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post['state'] = StorageRuleModel::STATE_ACTIVE;
            $post['storage_id'] = input('storage_id');
            $post['condition'] = json_encode(['min' => $post['min'], 'max' => $post['max']]);
            $dataValidate = new StorageRuleValidate();
            if ($dataValidate->scene('add')->check($post)) {
                $model = new StorageRuleModel();
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
            $this->assign('storage_id', input('storage_id'));

            return view();
        }
    }

    // 编辑

    /**
     * @throws DbException
     */
    public function edit($id)
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post['condition'] = json_encode(['min' => $post['min'], 'max' => $post['max']]);
            $dataValidate = new StorageRuleValidate();
            if ($dataValidate->scene('edit')->check($post)) {
                $model = new StorageRuleModel();
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
            $info = StorageRuleModel::get(['id' => $id,]);
            $this->assign('info', $info);

            return view();
        }
    }

    // 删除

    /**
     * @throws DbException
     */
    public function delete()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $block = StorageRuleModel::get($post['id']);
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

    /**
     * @throws DbException
     */
    public function status()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $user = StorageRuleModel::get($post['id']);
            $user['state'] = $user['state'] == StorageRuleModel::STATE_ACTIVE ? 0 : StorageRuleModel::STATE_ACTIVE;
            $user->save();
            echo json_encode(['code' => 1, 'msg' => '操作成功']);
            exit;
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
            exit;
        }
    }
}
