<?php
namespace app\Manage\controller;

use app\Manage\model\FinanceReportSnapshotModel;
use think\exception\DbException;
use think\Session;
use think\Config;

class FinanceReportController extends BaseController
{
    /**
     * @throws DbException
     */
    public function index(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $where = empty($keyword) ? "" : ' WHERE warehouse_sku LIKE "%' . $keyword . '%" OR seller LIKE "%' . $keyword . '%"';

        $model = new FinanceReportSnapshotModel();
        $month = $model->query('SELECT DISTINCT `month` FROM mu_finance_report_snapshot ORDER BY `month` ASC;');
        $this->assign('month', '"' . implode('","', array_column($month,'month')) . '"');

        $qty = $model->query('SELECT SUM(sale_qty) sum, `month`, platform FROM mu_finance_report_snapshot' . $where . ' GROUP BY month, platform ORDER BY `month` ASC;');
        $this->assign('qtySeries', self::javascriptFormat($month, $qty));

        $amount = $model->query('SELECT SUM(sale_amount) sum, `month`, platform FROM mu_finance_report_snapshot' . $where . ' GROUP BY month, platform ORDER BY `month` ASC;');
        $this->assign('amountSeries', self::javascriptFormat($month, $amount));

        $profit = $model->query('SELECT SUM(profit) sum, `month`, platform FROM mu_finance_report_snapshot' . $where . ' GROUP BY month, platform ORDER BY `month` ASC;');
        $this->assign('profitSeries', self::javascriptFormat($month, $profit));

        $adCost = $model->query('SELECT SUM(adCost) * -1 sum, `month`, platform FROM mu_finance_report_snapshot' . $where . ' GROUP BY month, platform ORDER BY `month` ASC;');
        $this->assign('adCostSeries', self::javascriptFormat($month, $adCost));

        $warehouseRent = $model->query('SELECT SUM(warehouse_rent) * -1 sum, `month`, platform FROM mu_finance_report_snapshot' . $where . ' GROUP BY month, platform ORDER BY `month` ASC;');
        $this->assign('warehouseRentSeries', self::javascriptFormat($month, $warehouseRent));

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    static private function javascriptFormat($month, $qty): string
    {
        $qtyArr = [];
        $sumArr = [];
        $initArr = [];
        foreach ($qty as $item) {
            $qtyArr[$item['platform']][$item['month']] = $item['sum'];
            $sumArr[$item['month']] += round($item['sum'], 2);
            $initArr[$item['month']] += 0;
        }

        foreach ($qtyArr as $platform => $value) {
            foreach ($month as $mon) {
                if (!array_key_exists($mon['month'], $value)) {
                    $qtyArr[$platform][$mon['month']] = '0.000';
                }
            }
            ksort($qtyArr[$platform]);
        }

        $scriptArr = [];
        foreach ($qtyArr as $key => $value) {
            $scriptArr[] = "
{
    name: '" . $key . "',
    type: 'bar',
    stack: 'Ad',
    barWidth: 50,
    emphasis: {
        focus: 'series'
    },
    data: [" . implode(',', $value) . "]
}";
        }
        $scriptArr[] = "
{
      name: '',
      type: 'bar',
      stack: 'Ad',
      data: [" . implode(',', array_values($initArr)) . "],
      label: {
        normal: {
          show: true,
          position: 'top',
          formatter: function (params) {
            var total = [" . implode(',', array_values($sumArr)) . "];
            return total[params.dataIndex];
          },
          fontSize: 14,
          fontWeight: 'bold',
          textStyle: { color: 'grey' }
       }
    }
}        
        ";

        return implode(',', $scriptArr);
    }
}
