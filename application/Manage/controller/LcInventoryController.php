<?php
namespace app\Manage\controller;

use app\Manage\model\LcInventoryBatchModel;
use think\exception\DbException;
use think\Session;
use think\Config;

class LcInventoryController extends BaseController
{
    /**
     * @throws DbException
     */
    public function index(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['receiving_code|product_sku|lc_code'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $warehouse_code = $this->request->get('warehouse_code', '', 'htmlspecialchars');
        $this->assign('warehouse_code', $warehouse_code);
        if ($warehouse_code) {
            $where['warehouse_code'] = $warehouse_code;
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 库存数据列表
        $inventory = new LcInventoryBatchModel();
        $list = $inventory->with(['receiving'])->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'page_num' => $page_num, 'warehouse_code' => $warehouse_code]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }
}
