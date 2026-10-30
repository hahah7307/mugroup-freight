<?php
namespace app\Manage\controller;

use app\Manage\model\ApiClient;
use app\Manage\model\CostEstimateLeModel;
use app\Manage\model\TaskModel;
use app\Manage\validate\TaskValidate;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Session;
use think\Config;

class CostController extends BaseController
{
    /**
     * @throws DbException
     */
    public function cost_estimate_le(): \think\response\View
    {
        $where = [];
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['warehouseCode'] = ['like', '%' . $keyword . '%'];
        }

        $cost = new CostEstimateLeModel();
        $list = $cost->where($where)->order('id desc')->paginate(Config::get('PAGE_NUM'), false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    // 添加
    public function cost_estimate_le_add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            array_filter($post);
            $apiRes = ApiClient::LeWarehouseApi("https://app.lecangs.com/api/tms/expenseTrial/api/trial", "POST", $post);
            if ($apiRes['code'] == 0) {
                echo json_encode(['code' => 0, 'msg' => $apiRes['msg']]);
            } elseif ($apiRes['code'] == 1) {
                $data = $apiRes['data'];
                $post['currency'] = $data[0]['currency'];
                $post['totalValue'] = $data[0]['totalValue'];
                $post['expenseDetailVoList'] = json_encode($data[0]['expenseDetailVoList']);
                $post['operationTime'] = date('Y-m-d H:i:s');
                $model = new CostEstimateLeModel();
                if ($model->insert($post)) {
                    echo json_encode(['code' => 1, 'msg' => '添加成功']);
                } else {
                    echo json_encode(['code' => 0, 'msg' => '添加失败，请重试']);
                }
            } else {
                echo json_encode(['code' => 0, 'msg' => '异常请求']);
            }
            exit();
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
            if (!empty($post['act_time'])) {
                $post['status'] = 1;
            }
            if (empty($post['act_time'])) {
                $post['act_time'] = null;
            }
            $dataValidate = new TaskValidate();
            if ($dataValidate->scene('edit')->check($post)) {
                $model = new TaskModel();
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
            $info = TaskModel::get(['id' => $id,]);
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
            $block = TaskModel::get($post['id']);
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

    // 快速完成
    /**
     * @throws DbException
     */
    public function complete()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $block = TaskModel::get($post['id']);
            if ($block['status'] == 1) {
                echo json_encode(['code' => 0, 'msg' => '已完成无需操作']);
                exit;
            }
            if ($block->update(['status' => 1, 'act_time' => date('Y-m-d H:i:s')], ['id' => $post['id']])) {
                echo json_encode(['code' => 1, 'msg' => '操作成功']);
            } else {
                echo json_encode(['code' => 0, 'msg' => '操作失败，请重试']);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }

    /**
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */
    static public function generateNewNo(): string
    {
        $date = date('Ymd');
        $model = new TaskModel();
        $list = $model->where(['task_no' => ['like', $date . "%"]])->order('task_no desc')->find();
        if (empty($list)) {
            return $date . "001";
        } else {
            return $date . sprintf("%03d", intval(substr($list['task_no'], 8, 3)) + 1);
        }
    }
}
