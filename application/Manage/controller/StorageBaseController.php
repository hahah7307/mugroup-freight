<?php
namespace app\Manage\controller;

use app\Manage\model\StorageBaseModel;
use app\Manage\validate\StorageBaseValidate;
use think\exception\DbException;
use think\Session;
use think\Config;

class StorageBaseController extends BaseController
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
            $where['lbs_weight|kg_weight|zone'] = ['like', '%' . $keyword . '%'];
        }

        $storage = new StorageBaseModel();
        $list = $storage->with(["storage"])->where($where)->order('id asc')->paginate(Config::get('PAGE_NUM'), false, ['query' => ['keyword' => $keyword]]);
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
            $post['state'] = StorageBaseModel::STATE_ACTIVE;
            $post['created_ip'] = get_real_ip();
            $dataValidate = new StorageBaseValidate();
            if ($dataValidate->scene('add')->check($post)) {
                $model = new StorageBaseModel();
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
            $post['created_ip'] = get_real_ip();
            $dataValidate = new StorageBaseValidate();
            if ($dataValidate->scene('edit')->check($post)) {
                $model = new StorageBaseModel();
                if ($model->allowField(true)->save($post, ['id' => $id])) {
                    echo json_encode(['code' => 1, 'msg' => '修改成功']);
                } else {
                    echo json_encode(['code' => 0, 'msg' => '修改失败，请重试']);
                }
            } else {
                echo json_encode(['code' => 0, 'msg' => $dataValidate->getError()]);
            }
            exit;
        } else {
            $info = StorageBaseModel::get(['id' => $id,]);
            $this->assign('info', $info);
            $this->assign('storage', getStorage());
            $this->assign('storageArea', getStorageArea());

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
            $block = StorageBaseModel::get($post['id']);
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
            $user = StorageBaseModel::get($post['id']);
            $user['state'] = $user['state'] == StorageBaseModel::STATE_ACTIVE ? 0 : StorageBaseModel::STATE_ACTIVE;
            $user->save();
            echo json_encode(['code' => 1, 'msg' => '操作成功']);
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }
}
