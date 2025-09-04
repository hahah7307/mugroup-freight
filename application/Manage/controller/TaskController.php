<?php
namespace app\Manage\controller;

use app\Manage\model\TaskModel;
use app\Manage\validate\TaskValidate;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Session;
use think\Config;

class TaskController extends BaseController
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
            $where['title'] = ['like', '%' . $keyword . '%'];
        }

        $status = $this->request->get('status', '-1', 'htmlspecialchars');
        if ($status != '-1') {
            $where['status'] = $status;
        }
        $this->assign('status', $status);

        $user_master = $this->request->get('user_master', '', 'htmlspecialchars');
        if (!empty($user_master)) {
            $where['user_master'] = $user_master;
        }
        $this->assign('user_master', $user_master);

        $create_id = $this->request->get('create_id', '', 'htmlspecialchars');
        if (!empty($create_id)) {
            $where['create_id'] = $create_id;
        }
        $this->assign('create_id', $create_id);

        $time = $this->request->get('time', 1, 'intval');
        if ($time == 1) {
            $time_name = 'create_time';
        } elseif ($time == 2) {
            $time_name = 'ect_time';
        } elseif ($time == 3) {
            $time_name = 'act_time';
        } else {
            $time_name = 'create_time';
        }
        $this->assign('time', $time);

        $start_time = $this->request->get('start_time', '', 'htmlspecialchars');
        if (!empty($start_time)) {
            $where[$time_name] = ['egt', $start_time];
        }
        $this->assign('start_time', $start_time);

        $end_time = $this->request->get('end_time', '', 'htmlspecialchars');
        if (!empty($end_time)) {
            $where[$time_name] = ['elt', $end_time];
        }
        $this->assign('end_time', $end_time);

        $task = new TaskModel();
        $list = $task->with(['account'])->where($where)->order('status asc, create_time asc')->paginate(Config::get('PAGE_NUM'), false, ['query' => ['keyword' => $keyword]]);
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
            $post['status'] = TaskModel::STATE_ACTIVE;
            $post['task_no'] = self::generateNewNo();
            $post['create_time'] = date('Y-m-d H:i:s', time());
            $post['create_id'] = Session::get(Config::get('USER_LOGIN_FLAG'));
            $dataValidate = new TaskValidate();
            if ($dataValidate->scene('add')->check($post)) {
                $model = new TaskModel();
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
