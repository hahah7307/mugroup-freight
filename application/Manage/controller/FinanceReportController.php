<?php
namespace app\Manage\controller;

use app\Manage\model\FinanceReportSnapshotModel;
use app\Manage\model\FinanceSkuGroupModel;
use think\db\exception\BindParamException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\exception\PDOException;
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
            if (substr($group_name, 0, 4) == '----') {
                $group_name = substr($group_name, 4);
                $field_name = 'group_name_origin';
            } else {
                $field_name = 'group_name';
            }
            $skuGroupObj = new FinanceSkuGroupModel();
            $list = $skuGroupObj->where([$field_name =>$group_name ])->column('sku');
            $list_detail = $skuGroupObj->where([$field_name =>$group_name ])->select();
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

        $platform = $model->query('SELECT DISTINCT platform FROM mu_finance_report_snapshot ORDER BY platform ASC;');
        $this->assign('platform', '"' . implode('","', array_column($platform,'platform')) . '"');

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

        $day_qty = $model->query('SELECT ROUND(SUM(sale_qty) / DAY(LAST_DAY(CONCAT(`month`, "-01")))) sum, `month`, platform FROM mu_finance_report_snapshot' . $where . ' GROUP BY month, platform ORDER BY `month` ASC;');
        $this->assign('dayQtySeries', self::javascriptFormat($month, $day_qty));

        $day_amount = $model->query('SELECT ROUND(SUM(sale_amount) / DAY(LAST_DAY(CONCAT(`month`, "-01"))), 2) sum, `month`, platform FROM mu_finance_report_snapshot' . $where . ' GROUP BY month, platform ORDER BY `month` ASC;');
        $this->assign('dayAmountSeries', self::javascriptFormat($month, $day_amount));

        // 广告占比
        $adCostProfit = $model->query('SELECT IFNULL(ROUND(SUM(adCost) * -1 / SUM(sale_amount), 2), 0) profit, `month`, platform FROM mu_finance_report_snapshot GROUP BY month, platform ORDER BY `month` ASC;');

        $adCostProfitData = [];
        foreach ($adCostProfit as $profitItem) {
            foreach ($month as $item) {
                foreach ($platform as $plat) {
                    if ($profitItem['month'] == $item['month']
                        && $profitItem['platform'] == $plat['platform']
                    ) {
                        $adCostProfitData[$plat['platform']][$item['month']][] = $profitItem['profit'];
                    }
                }
            }
        }
        foreach ($adCostProfitData as $k => $platformItem) {
            foreach ($platformItem as $key => $item) {
                foreach ($month as $mon) {
                    if ($key == $mon['month']) {
                        $adCostProfitData[$k][$mon['month']] = $item[0];
                    } else {
                        if (!isset($adCostProfitData[$k][$mon['month']])) {
                            $adCostProfitData[$k][$mon['month']] = '0.00';
                        }
                    }
                }
            }
        }
        ksort($adCostProfitData);
        $adCostProfitDataString = [];
        foreach ($adCostProfitData as $platformTag => $v) {
            $adCostProfitDataString[] = '{ name: "' . $platformTag . '", type: "bar", emphasis: {focus: "series"}, data: [' . implode(',', $v) . '] }';
        }
        $this->assign('adCostProfitDataString', implode(',', $adCostProfitDataString));

        // 仓储占比
        $warehouseRentProfit = $model->query('SELECT IFNULL(ROUND(SUM(warehouse_rent) * -1 / SUM(sale_amount), 2), 0) profit, `month`, platform FROM mu_finance_report_snapshot GROUP BY month, platform ORDER BY `month` ASC;');

        $warehouseRentProfitData = [];
        foreach ($warehouseRentProfit as $profitItem) {
            foreach ($month as $item) {
                foreach ($platform as $plat) {
                    if ($profitItem['month'] == $item['month']
                        && $profitItem['platform'] == $plat['platform']
                    ) {
                        $warehouseRentProfitData[$plat['platform']][$item['month']][] = $profitItem['profit'];
                    }
                }
            }
        }
        foreach ($warehouseRentProfitData as $k => $platformItem) {
            foreach ($platformItem as $key => $item) {
                foreach ($month as $mon) {
                    if ($key == $mon['month']) {
                        $warehouseRentProfitData[$k][$mon['month']] = $item[0];
                    } else {
                        if (!isset($warehouseRentProfitData[$k][$mon['month']])) {
                            $warehouseRentProfitData[$k][$mon['month']] = '0.00';
                        }
                    }
                }
            }
        }
        ksort($warehouseRentProfitData);
        $warehouseRentProfitDataString = [];
        foreach ($warehouseRentProfitData as $platform => $v) {
            $warehouseRentProfitDataString[] = '{ name: "' . $platform . '", type: "bar", emphasis: {focus: "series"}, data: [' . implode(',', $v) . '] }';
        }
        $this->assign('warehouseRentProfitDataString', implode(',', $warehouseRentProfitDataString));

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
        $month1 = input('month1', date('Y-m', strtotime('-3 months', time())), 'htmlspecialchars');
        $this->assign('month1', $month1);

        $month2 = input('month2', date('Y-m', strtotime('-3 months', time())), 'htmlspecialchars');
        $this->assign('month2', $month2);

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
	`month` >= "' . $month1 . '" 
    AND `month` <= "' . $month2 . '" 
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
        $this->assign('ratio_1', round(array_sum(array_column($profit_2, 'value')) / array_sum(array_column($profit_1, 'value')), 4));
        $this->assign('ratio_1_show', min(max((round(array_sum(array_column($profit_2, 'value')) / array_sum(array_column($profit_1, 'value')), 4) * 100) ** 2 / 100 * 80, 20), 100) . '%');

        $profit_4 = [];
        $profit_3 = $model->query('
SELECT
	SUM( profit ) `value`,
	group_name_origin name 
FROM
	mu_finance_report_snapshot a
	LEFT JOIN mu_finance_sku_group b ON a.warehouse_sku = b.sku 
WHERE
	`month` >= "' . $month1 . '" 
    AND `month` <= "' . $month2 . '" 
    AND b.sku IS NOT NULL
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
        $this->assign('ratio_3', round(array_sum(array_column($profit_4, 'value')) / array_sum(array_column($profit_3, 'value')), 4));
        $this->assign('ratio_3_show', min(max((round(array_sum(array_column($profit_4, 'value')) / array_sum(array_column($profit_3, 'value')), 4) * 100) ** 2 / 100 * 80, 20), 100) . '%');

        $profit_6 = [];
        $profit_5 = $model->query('
SELECT
	SUM( profit ) `value`,
	group_name_origin name 
FROM
	mu_finance_report_snapshot a
	LEFT JOIN mu_finance_sku_group b ON a.warehouse_sku = b.sku 
WHERE
	`month` >= "' . $month1 . '" 
    AND `month` <= "' . $month2 . '" 
	AND b.group_name = "' . $category . '"
    AND b.sku IS NOT NULL
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
        $this->assign('ratio_5', round(array_sum(array_column($profit_6, 'value')) / array_sum(array_column($profit_5, 'value')), 4));
        $this->assign('ratio_5_show', min(max((round(array_sum(array_column($profit_6, 'value')) / array_sum(array_column($profit_5, 'value')), 4) * 100) ** 2 / 100 * 80, 20), 80) . '%');

        $qty_1 = $model->query('
SELECT
	SUM( sale_qty ) `value`,
	group_name name 
FROM
	mu_finance_report_snapshot a
	LEFT JOIN mu_finance_sku_group b ON a.warehouse_sku = b.sku 
WHERE
	`month` >= "' . $month1 . '" 
    AND `month` <= "' . $month2 . '" 
    AND b.sku IS NOT NULL
GROUP BY
	group_name
ORDER BY
    value DESC;
        ');

        $this->assign('qty_1', json_encode(array_values($qty_1)));

        $qty_3 = $model->query('
SELECT
	SUM( sale_qty ) `value`,
	group_name_origin name 
FROM
	mu_finance_report_snapshot a
	LEFT JOIN mu_finance_sku_group b ON a.warehouse_sku = b.sku 
WHERE
	`month` >= "' . $month1 . '" 
    AND `month` <= "' . $month2 . '" 
    AND b.sku IS NOT NULL
GROUP BY
	group_name_origin
ORDER BY
    value DESC;
        ');

        $this->assign('qty_3', json_encode(array_values($qty_3)));

        $qty_5 = $model->query('
SELECT
	SUM( sale_qty ) `value`,
	group_name_origin name 
FROM
	mu_finance_report_snapshot a
	LEFT JOIN mu_finance_sku_group b ON a.warehouse_sku = b.sku 
    AND b.sku IS NOT NULL
WHERE
	`month` >= "' . $month1 . '" 
    AND `month` <= "' . $month2 . '" 
	AND b.group_name = "' . $category . '"
GROUP BY
	group_name_origin
ORDER BY
    value DESC;
        ');

        $this->assign('qty_5', json_encode(array_values($qty_5)));

        $amount_1 = $model->query('
SELECT
	SUM( sale_amount ) `value`,
	group_name name 
FROM
	mu_finance_report_snapshot a
	LEFT JOIN mu_finance_sku_group b ON a.warehouse_sku = b.sku 
    AND b.sku IS NOT NULL
WHERE
	`month` >= "' . $month1 . '" 
    AND `month` <= "' . $month2 . '" 
GROUP BY
	group_name
ORDER BY
    value DESC;
        ');

        $this->assign('amount_1', json_encode(array_values($amount_1)));

        $amount_3 = $model->query('
SELECT
	SUM( sale_amount ) `value`,
	group_name_origin name 
FROM
	mu_finance_report_snapshot a
	LEFT JOIN mu_finance_sku_group b ON a.warehouse_sku = b.sku 
    AND b.sku IS NOT NULL
WHERE
	`month` >= "' . $month1 . '" 
    AND `month` <= "' . $month2 . '" 
GROUP BY
	group_name_origin
ORDER BY
    value DESC;
        ');

        $this->assign('amount_3', json_encode(array_values($amount_3)));

        $amount_5 = $model->query('
SELECT
	SUM( sale_amount ) `value`,
	group_name_origin name 
FROM
	mu_finance_report_snapshot a
	LEFT JOIN mu_finance_sku_group b ON a.warehouse_sku = b.sku 
WHERE
	`month` >= "' . $month1 . '" 
    AND `month` <= "' . $month2 . '" 
	AND b.group_name = "' . $category . '"
    AND b.sku IS NOT NULL
GROUP BY
	group_name_origin
ORDER BY
    value DESC;
        ');

        $this->assign('amount_5', json_encode(array_values($amount_5)));
        $this->assign('category', $category);

        return view();
    }

    /**
     * @throws DataNotFoundException
     * @throws BindParamException
     * @throws PDOException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function platform_profit(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $where = empty($keyword) ? "" : ' WHERE warehouse_sku LIKE "%' . $keyword . '%" OR seller LIKE "%' . $keyword . '%"';

        $currentPlatform = $this->request->get('currentPlatform', 'amazon-FBM', 'htmlspecialchars');
        $this->assign('currentPlatform', $currentPlatform);
        if (!empty($currentPlatform)) {
            $platformList = explode(',', $currentPlatform);
            $platform_sql = ' platform IN("' . implode('","', $platformList) . '")';
            if (empty($where)) {
                $where .= ' WHERE' . $platform_sql;
            } else {
                $where .= ' AND ' . $platform_sql;
            }
        }

        $group_name = $this->request->get('group_name', '', 'htmlspecialchars');
        $this->assign('group_name', $group_name);
        if (!empty($group_name)) {
            if (substr($group_name, 0, 4) == '----') {
                $group_name = substr($group_name, 4);
                $field_name = 'group_name_origin';
            } else {
                $field_name = 'group_name';
            }
            $skuGroupObj = new FinanceSkuGroupModel();
            $list = $skuGroupObj->where([$field_name =>$group_name ])->column('sku');
            $list_detail = $skuGroupObj->where([$field_name =>$group_name ])->select();
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

        $platform = $model->query('SELECT DISTINCT platform FROM mu_finance_report_snapshot ORDER BY platform ASC;');
        $this->assign('platform', '"' . implode('","', array_column($platform,'platform')) . '"');

        $amount = $model->query('SELECT SUM(sale_amount) sum, `month`, platform FROM mu_finance_report_snapshot' . $where . ' GROUP BY month, platform ORDER BY `month` ASC;');
        $this->assign('amountSeries', self::javascriptFormat($month, $amount));

        $profit = $model->query('SELECT SUM(profit) sum, `month`, platform FROM mu_finance_report_snapshot' . $where . ' GROUP BY month, platform ORDER BY `month` ASC;');
        $this->assign('profitSeries', self::javascriptFormat($month, $profit));

        $profitMargin = $model->query('SELECT ROUND(SUM(profit) * 100 / SUM(sale_amount), 2) sum, `month`, platform FROM mu_finance_report_snapshot' . $where . ' GROUP BY month, platform ORDER BY `month` ASC;');
        $this->assign('profitMarginSeries', self::javascriptFormat($month, $profitMargin));

        $sku_group = $model->query('SELECT DISTINCT group_name FROM mu_finance_sku_group ORDER BY group_name ASC;');
        $this->assign('sku_group', $sku_group);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    public function sku_detail($group_name = "儿童梳妆台"): \think\response\View
    {
        $model = new FinanceSkuGroupModel();
        $list = $model->query('
SELECT
	b.productSku,
	b.productTitle,
	c.user_name 
FROM
	mu_finance_sku_group a
	LEFT JOIN mu_ecang_product b ON a.sku = b.productSku
	LEFT JOIN mu_ecang_user c ON b.personSellerId = c.user_id 
WHERE
	a.group_name_origin = "' . $group_name . '" 
	AND b.productSku IS NOT NULL
ORDER BY
	c.user_name ASC;
        ');
        $this->assign('list', $list);
        $this->assign('group_name', $group_name);

        return view();
    }
}
