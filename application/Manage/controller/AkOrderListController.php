<?php
namespace app\Manage\controller;

use app\Manage\model\AkOrderListModel;
use think\exception\DbException;
use think\Session;
use think\Config;

class AkOrderListController extends BaseController
{
    /**
     * @throws DbException
     */
    public function index(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['global_order_no|store_name|platform_order_no'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $start = $this->request->get('start', date('Y-m-d'), 'htmlspecialchars');
        $this->assign('start', $start);

        $end = $this->request->get('end', date('Y-m-d'), 'htmlspecialchars');
        $this->assign('end', $end);

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        $model = new AkOrderListModel();
        $list = $model->where($where)
            ->where('global_payment_time', '>', $start . ' 16:00:00')
            ->where('global_payment_time', '<', date('Y-m-d', strtotime('+1 day', strtotime($end))) . ' 16:00:00')
            ->order('id desc')
            ->paginate($page_num, false, ['query' => ['keyword' => $keyword, 'page_num' => $page_num, 'start' => $start, 'end' => $end]]);
        $sum = $model->where($where)
            ->where('status', '<>', 7)
            ->where('global_payment_time', '>', $start . ' 16:00:00')
            ->where('global_payment_time', '<', date('Y-m-d', strtotime('+1 day', strtotime($end))) . ' 16:00:00')
            ->field('SUM(unit_price_amount * quantity) AS total')
            ->find();
        $this->assign('list', $list);
        $this->assign('sum', $sum['total']);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }
}
