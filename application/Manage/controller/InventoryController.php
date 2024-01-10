<?php
namespace app\Manage\controller;

use app\Manage\model\InventoryAdjustmentModel;
use app\Manage\model\InventoryBatchModel;
use think\exception\DbException;
use think\Session;
use think\Config;

class InventoryController extends BaseController
{
    /**
     * @throws DbException
     */
    public function index(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['ib_id|productSku|referenceNo|roCode|poCode'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $lc_code = $this->request->get('lc_code', '', 'htmlspecialchars');
        $this->assign('lc_code', $lc_code);
        if ($lc_code) {
            $where['lcCode'] = $lc_code;
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 库存数据列表
        $inventory = new InventoryBatchModel();
        $list = $inventory->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'page_num' => $page_num, 'lc_code' => $lc_code]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws DbException
     */
    public function adjustment(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['productSku|roCode|poCode'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $lc_code = $this->request->get('lc_code', '', 'htmlspecialchars');
        $this->assign('lc_code', $lc_code);
        if ($lc_code) {
            $where['lcCode'] = $lc_code;
        }

        $applicationCode = $this->request->get('applicationCode', '', 'htmlspecialchars');
        $this->assign('applicationCode', $applicationCode);
        if ($applicationCode) {
            $where['applicationCode'] = $applicationCode;
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 库存数据调整列表
        $inventory = new InventoryAdjustmentModel();
        $list = $inventory->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'page_num' => $page_num, 'lc_code' => $lc_code, 'applicationCode' => $applicationCode]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }
}
