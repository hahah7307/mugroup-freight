<?php
namespace app\Manage\controller;

use app\Manage\model\StorageAreaModel;
use app\Manage\validate\StorageAreaValidate;
use think\exception\DbException;
use think\Session;
use think\Config;

class StorageAreaController extends BaseController
{
    /**
     * @throws DbException
     */
    public function index()
    {
        $where = [];
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['username|nickname|phone|email'] = ['like', '%' . $keyword . '%'];
        }

        // 仓库列表
        $storage = new StorageAreaModel();
        $list = $storage->with("storage")->where($where)->order('id asc')->paginate(Config::get('PAGE_NUM'));
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    // 添加

    /**
     * @throws DbException
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post['state'] = StorageAreaModel::STATE_ACTIVE;
            $dataValidate = new StorageAreaValidate();
            if ($dataValidate->scene('add')->check($post)) {
                $model = new StorageAreaModel();
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
            $this->assign('storage', getStorage());

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
            $dataValidate = new StorageAreaValidate();
            if ($dataValidate->scene('edit')->check($post)) {
                $model = new StorageAreaModel();
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
            $info = StorageAreaModel::get(['id' => $id,]);
            $this->assign('info', $info);
            $this->assign('storage', getStorage());

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
            $block = StorageAreaModel::get($post['id']);
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
            $user = StorageAreaModel::get($post['id']);
            $user['state'] = $user['state'] == StorageAreaModel::STATE_ACTIVE ? 0 : StorageAreaModel::STATE_ACTIVE;
            $user->save();
            echo json_encode(['code' => 1, 'msg' => '操作成功']);
            exit;
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
            exit;
        }
    }
}
