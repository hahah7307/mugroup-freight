<?php
namespace app\Manage\controller;

use app\Manage\model\ApiClient;
use app\Manage\model\CostEstimateLeModel;
use app\Manage\model\CostEstimateWydModel;
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

    /**
     * @throws DbException
     */
    public function cost_estimate_wyd(): \think\response\View
    {
        $where = [];
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['warehouseCode'] = ['like', '%' . $keyword . '%'];
        }

        $cost = new CostEstimateWydModel();
        $list = $cost->where($where)->order('id desc')->paginate(Config::get('PAGE_NUM'), false, ['query' => ['keyword' => $keyword]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    // 添加
    public function cost_estimate_wyd_add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            array_filter($post);
            $apiRes = ApiClient::WydWarehouseApi("/oms/openapi/fee/v1/queryTrial", "POST", $post);
            if ($apiRes['code'] == 0) {
                echo json_encode(['code' => 0, 'msg' => $apiRes['msg']]);
            } elseif ($apiRes['code'] == 1) {
                $data = json_decode($apiRes['data'], true);
                $post['wrapperList'] = json_encode($data['wrapperList']);
                $post['classType'] = $data['classType'];
                $post['created_time'] = date('Y-m-d H:i:s');
                $model = new CostEstimateWydModel();
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
}
