<?php
namespace app\Manage\controller;

use app\Manage\model\DateStockCalculateModel;
use think\exception\DbException;
use think\Session;
use think\Config;

class DateStockController extends BaseController
{
    /**
     * @throws DbException
     */
    public function index(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['product_sku'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $date = $this->request->get('date', '', 'htmlspecialchars');
        $this->assign('date', $date);
        if ($date) {
            $where['date'] = $date;
        }

        $warehouse_id = $this->request->get('warehouse_id', '', 'htmlspecialchars');
        $this->assign('warehouse_id', $warehouse_id);
        if ($warehouse_id) {
            $where['warehouse_id'] = $warehouse_id;
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 库存数据列表
        $inventory = new DateStockCalculateModel();
        $where['stock|storage_stock'] = ['gt', 0];
        $list = $inventory->with(['receiving', 'consume'])->where($where)->order('id asc')->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'date' => $date,  'warehouse_id' => $warehouse_id, 'page_num' => $page_num]]);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }
}
