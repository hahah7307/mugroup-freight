<?php
namespace app\Manage\controller;

use app\Manage\model\SkuModel;
use app\Manage\model\SkuRelationModel;
use app\Manage\model\SkuRelationUpdateModel;
use app\Manage\validate\SkuRelationValidate;
use app\Manage\validate\SkuValidate;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Session;
use think\Config;

class SkuRelationController extends BaseController
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
            $where['product_sku|user_account'] = ['like', '%' . $keyword . '%'];
        }

        $storage = new SkuModel();
        $list = $storage->with(['warehouse_sku'])->where($where)->order('id asc')->paginate(Config::get('PAGE_NUM'));
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    // 添加
    public function add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $dataValidate = new SkuValidate();
            if ($dataValidate->scene('add')->check($post)) {
                $model = new SkuModel();
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
    /**
     * @throws DbException
     */
    public function edit($id)
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $dataValidate = new SkuValidate();
            if ($dataValidate->scene('edit')->check($post)) {
                $model = new SkuModel();
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
            $info = SkuModel::get(['id' => $id,]);
            $this->assign('info', $info);

            return view();
        }
    }

    // 删除
    public function delete()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $block = SkuModel::get($post['id']);
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

    /**
     * @throws DbException
     */
    public function relation(): \think\response\View
    {
        $id = input('id');
        $this->assign('id', $id);
        $where['sku_id'] = $id;

        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['pcr_product_sku'] = ['like', '%' . $keyword . '%'];
        }

        $storage = new SkuRelationModel();
        $list = $storage->where($where)->order('id asc')->paginate(Config::get('PAGE_NUM'));
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    // 添加
    public function relation_add($id)
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post['sku_id'] = $id;
            $dataValidate = new SkuRelationValidate();
            if ($dataValidate->scene('add')->check($post)) {
                $model = new SkuRelationModel();
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
            $this->assign('id', $id);

            return view();
        }
    }

    // 编辑
    /**
     * @throws DbException
     */
    public function relation_edit($id)
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $dataValidate = new SkuRelationValidate();
            if ($dataValidate->scene('edit')->check($post)) {
                $model = new SkuRelationModel();
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
            $info = SkuRelationModel::get(['id' => $id,]);
            $this->assign('info', $info);

            return view();
        }
    }

    // 删除

    /**
     * @throws DbException
     */
    public function relation_delete()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $block = SkuRelationModel::get($post['id']);
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

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function relation_update()
    {
        if ($this->request->isPost()) {
            $updateObj = new SkuRelationUpdateModel();
            $ecUpdate = $updateObj->find(1);
            $newDate = date("Ymd", strtotime("-1 day"));
            if ($updateObj->update(['date' => $newDate, 'is_open' => 1], ['id' => $ecUpdate['id']])) {
                echo json_encode(['code' => 1, 'msg' => '操作成功，数据抓取中']);
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
}
