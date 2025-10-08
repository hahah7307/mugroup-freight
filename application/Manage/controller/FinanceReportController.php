<?php
namespace app\Manage\controller;

use app\Manage\model\FinanceReportSnapshotModel;
use app\Manage\model\FinanceSkuGroupModel;
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

        $group_name = $this->request->get('group_name', '', 'htmlspecialchars');
        $this->assign('group_name', $group_name);
        if (!empty($group_name)) {
            $skuGroupObj = new FinanceSkuGroupModel();
            $list = $skuGroupObj->where(['group_name' =>$group_name ])->column('sku');
            $list_detail = $skuGroupObj->where(['group_name' =>$group_name ])->select();
            $skuTitle = [];
            foreach ($list_detail as $item) {
                $skuTitle[] = $item['sku'] . ':' . $item['product_name'];
            }
            $this->assign('sku_detail', implode("<br/>", $skuTitle));
            $group_sql = ' warehouse_sku IN("' . implode('","', $list) . '") ';
            if (empty($where)) {
                $group_sql = ' WHERE' . $group_sql;
            } else {
                $group_sql = ' AND ' . $group_sql;
            }
        } else {
            $group_sql = '';
        }
        $where .= $group_sql;

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

        $sku_group = $model->query('SELECT DISTINCT group_name FROM mu_finance_sku_group ORDER BY group_name ASC;');
        $this->assign('sku_group', $sku_group);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    static private function javascriptFormat($month, $qty): string
    {
        $qtyArr = [];
        $sumArr = [];
        $initArr = [];
        $positionArr = [];
        foreach ($qty as $item) {
            foreach ($month as $mon) {
                if ($mon['month'] == $item['month']) {
                    $qtyArr[$item['platform']][$mon['month']] += $item['sum'];
                    $sumArr[$mon['month']] += round($item['sum'], 2);
                    $positionArr[$mon['month']] += max(0, round($item['sum'], 2));
                } else {
                    $qtyArr[$item['platform']][$mon['month']] += 0;
                    $sumArr[$mon['month']] += 0;
                    $positionArr[$mon['month']] += 0;
                }
                $initArr[$mon['month']] += 0;
            }
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
            formatter: function (params) {
                var total = [" . implode(',', array_values($sumArr)) . "];
                return total[params.dataIndex].toLocaleString();
            },
            position: 'top',
            fontSize: 14,
            fontWeight: 'bold',
            textStyle: { color: 'black' }
        }
    }
}
        ";

        return implode(',', $scriptArr);
    }

    /**
     * @throws DbException
     */
    public function sku_group(): \think\response\View
    {
        $model = new FinanceSkuGroupModel();
        $list = $model->paginate(20);
        $this->assign('list', $list);

        return view();
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    public function pie_chart($category = "儿童产品"): \think\response\View
    {
        $month = input('month', date('Y-m', strtotime('-3 months', time())), 'htmlspecialchars');
        $this->assign('month', $month);

        $model = new FinanceSkuGroupModel();
        $profit_2 = [];
        $profit_1 = $model->query('
SELECT
	SUM( profit ) `value`,
	group_name name 
FROM
	mu_finance_report_snapshot a
	LEFT JOIN mu_finance_sku_group b ON a.warehouse_sku = b.sku 
WHERE
	`month` = "' . $month . '" 
GROUP BY
	group_name
ORDER BY
    value DESC;
        ');
        foreach($profit_1 as $key => $item) {
            if ($item['value'] < 0) {
                $profit_2[] = ['value' => $item['value'] * -1, 'name' => $item['name']];
                unset($profit_1[$key]);
            }
        }

        $this->assign('profit_1', json_encode(array_values($profit_1)));
        $this->assign('profit_2', json_encode(array_values($profit_2)));

        $profit_4 = [];
        $profit_3 = $model->query('
SELECT
	SUM( profit ) `value`,
	group_name_origin name 
FROM
	mu_finance_report_snapshot a
	LEFT JOIN mu_finance_sku_group b ON a.warehouse_sku = b.sku 
WHERE
	`month` = "' . $month . '" 
GROUP BY
	group_name_origin
ORDER BY
    value DESC;
        ');
        foreach($profit_3 as $key => $item) {
            if ($item['value'] < 0) {
                $profit_4[] = ['value' => $item['value'] * -1, 'name' => $item['name']];
                unset($profit_3[$key]);
            }
        }

        $this->assign('profit_3', json_encode(array_values($profit_3)));
        $this->assign('profit_4', json_encode(array_values($profit_4)));

        $profit_6 = [];
        $profit_5 = $model->query('
SELECT
	SUM( profit ) `value`,
	group_name_origin name 
FROM
	mu_finance_report_snapshot a
	LEFT JOIN mu_finance_sku_group b ON a.warehouse_sku = b.sku 
WHERE
	`month` = "' . $month . '" 
	AND b.group_name = "' . $category . '"
GROUP BY
	group_name_origin
ORDER BY
    value DESC;
        ');
        foreach($profit_5 as $key => $item) {
            if ($item['value'] < 0) {
                $profit_6[] = ['value' => $item['value'] * -1, 'name' => $item['name']];
                unset($profit_5[$key]);
            }
        }

        $this->assign('profit_5', json_encode(array_values($profit_5)));
        $this->assign('profit_6', json_encode(array_values($profit_6)));
        $this->assign('category', $category);

        return view();
    }
}
