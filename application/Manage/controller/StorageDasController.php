<?php
namespace app\Manage\controller;

use app\Manage\model\StorageDasModel;
use app\Manage\validate\StorageDasValidate;
use think\exception\DbException;
use think\Session;
use think\Config;

class StorageDasController extends BaseController
{
    /**
     * @throws DbException
     */
    public function index(): \think\response\View
    {
        $where = [];
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['zone'] = ['like', '%' . $keyword . '%'];
        }

        $storage_id = $this->request->get('storage_id', '', 'htmlspecialchars');
        if ($storage_id) {
            $where['storage_id'] = $storage_id;
            $this->assign('storage_id', $storage_id);
        }

        $storage = new StorageDasModel();
        $list = $storage->with(["storage"])->where($where)->order('id asc')->paginate(Config::get('PAGE_NUM'), false, ['query' => ['keyword' => $keyword, 'storage_id' => $storage_id]]);
        $this->assign('list', $list);
        $this->assign('storage', getStorage());

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
            $post['state'] = StorageDasModel::STATE_ACTIVE;
            $dataValidate = new StorageDasValidate();
            if ($dataValidate->scene('add')->check($post)) {
                $model = new StorageDasModel();
                if ($model->allowField(true)->save($post)) {
                    echo json_encode(['code' => 1, 'msg' => '添加成功']);
                } else {
                    echo json_encode(['code' => 0, 'msg' => '添加失败，请重试']);
                }
            } else {
                echo json_encode(['code' => 0, 'msg' => $dataValidate->getError()]);
            }
            exit;
        } else {
            $this->assign('storage', getStorage());
            $this->assign('ahs', getAhs());

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
            $dataValidate = new StorageDasValidate();
            if ($dataValidate->scene('edit')->check($post)) {
                $model = new StorageDasModel();
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
            $info = StorageDasModel::get(['id' => $id,]);
            $this->assign('info', $info);
            $this->assign('storage', getStorage());
            $this->assign('ahs', getAhs());

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
            $block = StorageDasModel::get($post['id']);
            if ($block->delete()) {
                echo json_encode(['code' => 1, 'msg' => '操作成功']);
            } else {
                echo json_encode(['code' => 0, 'msg' => '操作失败，请重试']);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }

    // 状态切换
    /**
     * @throws DbException
     */
    public function status()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $user = StorageDasModel::get($post['id']);
            $user['state'] = $user['state'] == StorageDasModel::STATE_ACTIVE ? 0 : StorageDasModel::STATE_ACTIVE;
            $user->save();
            echo json_encode(['code' => 1, 'msg' => '操作成功']);
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }
}
