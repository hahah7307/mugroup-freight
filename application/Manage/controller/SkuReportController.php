<?php
namespace app\Manage\controller;

use app\Manage\model\FinanceExcelInit;
use app\Manage\model\ProductModel;
use PHPExcel;
use PHPExcel_IOFactory;
use think\db\exception\BindParamException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\exception\PDOException;
use think\Session;
use think\Config;

class SkuReportController extends BaseController
{
    /**
     * @throws DbException
     */
    public function index(): \think\response\View
    {
        $sale_order = $this->request->get('sale_order', 'DESC', 'htmlspecialchars');
        $this->assign('sale_order', $sale_order);

        $sale_start = $this->request->get('sale_start', date('Y-m-01 00:00:00'), 'htmlspecialchars');
        $this->assign('sale_start', $sale_start);
        $start_time = empty($sale_start) ? '' : 'AND a.datePaidPlatform >="' . $sale_start . '"';

        $sale_end = $this->request->get('sale_end', date('Y-m-d 00:00:00'), 'htmlspecialchars');
        $this->assign('sale_end', $sale_end);

        $qty_order = $this->request->get('qty_order', 'DESC', 'htmlspecialchars');
        $this->assign('qty_order', $qty_order);

        $qty_start = $this->request->get('qty_start', date('Y-m-01 00:00:00'), 'htmlspecialchars');
        $this->assign('qty_start', $qty_start);
        $qty_time = empty($qty_start) ? '' : 'AND a.datePaidPlatform >="' . $qty_start . '"';

        $qty_end = $this->request->get('qty_end', date('Y-m-d 00:00:00'), 'htmlspecialchars');
        $this->assign('qty_end', $qty_end);

        $model = new ProductModel();
        $saleList = $model->query('
            SELECT
                b.warehouseSku,
                c.productImages,
                SUM( ROUND( a.amountpaid, 3 ) ) sale 
            FROM
                mu_ecang_order a
                LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
                LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku
            WHERE
                a.`status` = 4 
                AND c.saleStatus = 2 ' .
            $start_time .
            'AND a.datePaidPlatform < "' . $sale_end . '"' . '
            GROUP BY
                warehouseSku,
                productImages
            ORDER BY
                sale ' . $sale_order . ';
        ');
        $this->assign('saleList', $saleList);

        $qtyList = $model->query('
            SELECT
                b.warehouseSku,
                c.productImages,
	            SUM( ROUND( b.qty, 3 ) ) qty 
            FROM
                mu_ecang_order a
                LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
                LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku
            WHERE
                a.`status` = 4  
                AND c.saleStatus = 2 ' .
            $qty_time .
            'AND a.datePaidPlatform < "' . $qty_end . '"' . '
            GROUP BY
                warehouseSku,
                productImages
            ORDER BY
	            qty ' . $qty_order . ';
        ');
        $this->assign('qtyList', $qtyList);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws DbException
     */
    public function profit_margin(): \think\response\View
    {
        $sale_order = $this->request->get('sale_order', 'DESC', 'htmlspecialchars');
        $this->assign('sale_order', $sale_order);

        $sale_start = $this->request->get('sale_start', date('Y-m', strtotime('-1 month', strtotime(date('Y-m-01 00:00:00')))), 'htmlspecialchars');
        $this->assign('sale_start', $sale_start);

        $sale_end = $this->request->get('sale_end', date('Y-m'), 'htmlspecialchars');
        $this->assign('sale_end', $sale_end);

        $model = new ProductModel();
        $saleList = $model->query('
SELECT
	t.warehouse_sku,
	c.productTitle,
	c.productImages,
	t.amount,
	t.profit,
	t.margin 
FROM
	(
	SELECT
		a.warehouse_sku,
		round( sum( a.amount ), 2 ) amount,
		round( sum( a.profit ), 2 ) profit,
		round( SUM( a.profit ) / sum( a.amount ), 2 ) * 100 AS margin 
	FROM
		mu_finance_report b
		JOIN mu_finance_report_snapshot a ON a.report_id = b.id 
	WHERE
		b.MONTH BETWEEN "' . $sale_start . '" 
		AND "' . $sale_end . '" 
	GROUP BY
		a.warehouse_sku 
	) t
	LEFT JOIN mu_ecang_product c ON c.productSku = t.warehouse_sku 
WHERE
	c.saleStatus != 18 
	AND c.saleStatus != 19 
	AND c.productTitle IS NOT NULL 
ORDER BY
	t.margin ' . $sale_order . ';
        ');
        $this->assign('saleList', $saleList);

        return view();
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    public function quantity(): \think\response\View
    {
        $sku = input('sku');
        if (empty($sku)) {
            $this->error('操作错误！', url('index'));
        }

        $skuObj = new ProductModel();
        $product = $skuObj->where(['productSku' => $sku])->find();
        $start = $this->request->get('start', date('Y-m-01 00:00:00'), 'htmlspecialchars');
        $this->assign('start', $start);
        $start_time = empty($start) ? '' : 'AND a.datePaidPlatform >="' . $start . '"';

        $end = $this->request->get('end', date('Y-m-d 00:00:00'), 'htmlspecialchars');
        $this->assign('end', $end);

        $model = new ProductModel();
        $list = $model->query('
            SELECT
            a.platform,
            DATE_FORMAT( a.datePaidPlatform, "%Y%m" ) MONTH,
            SUM( b.qty ) qty 
        FROM
            mu_ecang_order a
            LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id 
        WHERE
            b.warehouseSku = "' . $sku . '" 
            AND a.`status` = 4 ' .
            $start_time .
            'AND a.datePaidPlatform < "' . $end . '"' . '
        GROUP BY
            platform,
            MONTH;
        ');

        $month = [];
        $data = [];
        $qty = [];
        foreach ($list as $item) {
            $month[$item['MONTH']][] = ['platform' => $item['platform'], 'qty' => $item['qty']];
            $qty[$item['MONTH']] += $item['qty'];
        }
        foreach ($month as $k => $v) {
            $data[] = [
                'month'             =>  $k,
                $v[0]['platform']   =>  $v[0]['qty'],
                $v[1]['platform']   =>  $v[1]['qty'],
                $v[2]['platform']   =>  $v[2]['qty'],
            ];
        }
        $this->assign('product', $product);
        $this->assign('data', json_encode($data));
        $this->assign('qty', implode(',', $qty));

        $list2 = $model->query('
SELECT
	IFNULL(access_user_name, \'["未映射"]\') userAccount,
	DATE_FORMAT( a.datePaidPlatform, "%Y%m" ) MONTH,
	SUM( b.qty ) qty 
FROM
    mu_ecang_order a
    LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id 
	LEFT JOIN (
	SELECT DISTINCT
			b.pcr_product_sku,
			a.user_account,
			access_user_name
		FROM
			mu_ecang_sku a
			LEFT JOIN mu_ecang_sku_relation b ON a.id = b.sku_id
			LEFT JOIN mu_ecang_listing c ON a.product_sku = c.seller_sku
	    WHERE b.pcr_product_sku = "' . $sku . '"
	) c ON b.warehouseSku = c.pcr_product_sku 
	AND a.userAccount = c.user_account
WHERE
    b.warehouseSku = "' . $sku . '" 
    AND a.`status` = 4 ' .
    $start_time .
    'AND a.datePaidPlatform < "' . $end . '"' . '
GROUP BY
    access_user_name,
    MONTH;
        ');

        $list3 = $model->query('
SELECT DISTINCT
	IFNULL(access_user_name, \'["未映射"]\') userAccount
FROM
    mu_ecang_order a
    LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id 
	LEFT JOIN (
	SELECT DISTINCT
			b.pcr_product_sku,
			a.user_account,
			access_user_name
		FROM
			mu_ecang_sku a
			LEFT JOIN mu_ecang_sku_relation b ON a.id = b.sku_id
			LEFT JOIN mu_ecang_listing c ON a.product_sku = c.seller_sku
	    WHERE b.pcr_product_sku = "' . $sku . '"
	) c ON b.warehouseSku = c.pcr_product_sku 
	AND a.userAccount = c.user_account
WHERE
    b.warehouseSku = "' . $sku . '" 
    AND a.`status` = 4 ' .
    $start_time .
    'AND a.datePaidPlatform < "' . $end . '"' . '
ORDER BY
    userAccount;
        ');

        foreach ($list2 as $key => $value) {
            $userAccountList2 = json_decode($value['userAccount'], true);
            $list2[$key]['userAccount'] = empty($userAccountList2) ? "未分配" : $userAccountList2[0];
        }
        foreach ($list3 as $k => $v) {
            $userAccountList3 = json_decode($v['userAccount'], true);
            $list3[$k]['userAccount'] = empty($userAccountList3) ? "未分配" : $userAccountList3[0];
        }

        $this->assign('userAccountArr', $list3);
        $this->assign('userAccount', '"' . implode('","', array_column($list3, 'userAccount')) . '"');

        $month2 = [];
        $data2 = [];
        $qty2 = [];
        foreach ($list2 as $item2) {
            $month2[$item2['MONTH']][] = ['userAccount' => $item2['userAccount'], 'qty' => $item2['qty']];
            $qty2[$item2['MONTH']] += $item2['qty'];
        }
        ksort($month2);
        ksort($qty2);
        foreach ($month2 as $k => $v) {
            $data2[] = [
                'month'                 =>  $k,
                $v[0]['userAccount']    =>  $v[0]['qty'],
                $v[1]['userAccount']    =>  $v[1]['qty'],
                $v[2]['userAccount']    =>  $v[2]['qty'],
                $v[3]['userAccount']    =>  $v[3]['qty'],
                $v[4]['userAccount']    =>  $v[4]['qty'],
            ];
        }
        $this->assign('data2', json_encode($data2));
        $this->assign('qty2', implode(',', $qty2));

        return view();
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    public function category($category = "室内家具"): \think\response\View
    {
        $start = $this->request->get('start', date('Y-m-01 00:00:00'), 'htmlspecialchars');
        $this->assign('start', $start);
        $start_time = empty($start) ? '' : 'AND a.datePaidPlatform >="' . $start . '"';

        $end = $this->request->get('end', date('Y-m-d 00:00:00'), 'htmlspecialchars');
        $this->assign('end', $end);

        $model = new ProductModel();
        $category_1 = $model->query('
SELECT
	SUM( b.qty ) value,
	IFNULL( c.category_name_1, "未分类" ) name 
FROM
	mu_ecang_order a
	LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
	LEFT JOIN mu_api_product_category c ON b.warehouseSku = c.productSku 
WHERE
	STATUS = 4 ' .
    $start_time .
    'AND a.datePaidPlatform < "' . $end . '"' . ' 
GROUP BY
	category_name_1;
        ');

        $category_2 = $model->query('
SELECT
	SUM( b.qty ) value,
	IFNULL( c.category_name_2, "未分类" ) name 
FROM
	mu_ecang_order a
	LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
	LEFT JOIN mu_api_product_category c ON b.warehouseSku = c.productSku 
WHERE
	STATUS = 4 ' .
            $start_time .
            'AND a.datePaidPlatform < "' . $end . '"' . ' 
GROUP BY
	category_name_2;
        ');

        $category_3 = $model->query('
SELECT 
	value,
	name 
FROM
	(
	SELECT
		SUM( b.qty ) 
	VALUE
		,
		IFNULL( c.category_name_1, "未分类" ) name_1,
		IFNULL( c.category_name_2, "未分类" ) name 
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
		LEFT JOIN mu_api_product_category c ON b.warehouseSku = c.productSku 
	WHERE
		STATUS = 4 ' .
            $start_time .
            'AND a.datePaidPlatform < "' . $end . '"' . ' 
	GROUP BY
		category_name_1,
		category_name_2 
	) a 
WHERE
	name_1 = "' . $category . '";
        ');

        $this->assign('category_1', json_encode($category_1));
        $this->assign('category_2', json_encode($category_2));
        $this->assign('category_3', json_encode($category_3));
        $this->assign('category', $category);

        return view();
    }

    /**
     * @throws DbException
     */
    public function daily(): \think\response\View
    {
        $sale_order = $this->request->get('sale_order', 'DESC', 'htmlspecialchars');
        $this->assign('sale_order', $sale_order);

        $sale_day = $this->request->get('sale_day', date('Y-m-d', strtotime('-2 day')), 'htmlspecialchars');
        $this->assign('sale_day', $sale_day);

        $qty_order = $this->request->get('qty_order', 'DESC', 'htmlspecialchars');
        $this->assign('qty_order', $qty_order);

        $qty_start = $this->request->get('qty_start', 5, 'intval');
        $this->assign('qty_start', $qty_start);

        $qty_end = $this->request->get('qty_end', 10, 'intval');
        $this->assign('qty_end', $qty_end);

        $model = new ProductModel();
        $saleList = $model->query('
SELECT
	b.warehouseSku,
	c.productImages,
	SUM( qty ) qty 
FROM
	mu_ecang_order a
	LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
	LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
WHERE
	a.datePaidPlatform >= "' . $sale_day . ' 00:00:00" 
	AND a.datePaidPlatform <= "' . $sale_day . ' 23:59:59" 
	AND `status` > 0 
	AND `status` < 8 
    AND c.saleStatus = 2 
GROUP BY
	warehouseSku,
	productImages 
ORDER BY
	qty ' . $sale_order . ';
        ');
        $this->assign('saleList', $saleList);

        $min = min($qty_start, $qty_end);
        $max = max($qty_start, $qty_end);
        $qtyList = $model->query('
SELECT
	* 
FROM
	(
	SELECT
		b.warehouseSku,
		c.productImages,
		SUM( qty ) qty 
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
		LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
	WHERE
		a.datePaidPlatform >= "' . $sale_day . ' 00:00:00" 
	AND a.datePaidPlatform <= "' . $sale_day . ' 23:59:59" AND `status` > 0 
	AND `status` < 8 AND c.saleStatus = 2 GROUP BY warehouseSku, productImages ORDER BY qty ' . $sale_order . ' ) a WHERE qty >= ' . $min . ' 
	AND qty <= ' . $max . ' 
ORDER BY
	qty ' . $qty_order . ';
        ');
        $this->assign('qtyList', $qtyList);

        $noList = $model->query('
SELECT
	a.*,
	IFNULL(b.sale_qty, 0)  sale_qty
FROM
	(
	SELECT
		a.productSku,
		b.productImages,
		c.user_name,
		SUM( Sellable ) stock_qty 
	FROM
		mu_ecang_product_inventory a
		LEFT JOIN mu_ecang_product b ON a.productSku = b.productSku
		LEFT JOIN mu_ecang_user c ON b.personSellerId = c.id 
	WHERE
		createdDate = ' . date('Ymd', strtotime($sale_day)) . '
		AND Sellable > 0 
		AND b.saleStatus = 2 
	GROUP BY
		productSku,
		productImages,
		user_name 
	) a
	LEFT JOIN (
	SELECT
		warehouseSku,
		SUM( qty ) sale_qty 
	FROM
		mu_ecang_order_detail b
		LEFT JOIN mu_ecang_order c ON b.order_id = c.id 
	WHERE
		datePaidPlatform >= "' . $sale_day . ' 00:00:00" 
	AND datePaidPlatform <= "' . $sale_day . ' 23:59:59"
	AND `status` > 0 
	AND `status` < 8 
	GROUP BY
	warehouseSku 
	) b ON a.productSku = b.warehouseSku
	WHERE b.warehouseSku IS NULL
	ORDER BY stock_qty DESC;
        ');
        $this->assign('noList', $noList);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws DbException
     */
    public function store(): \think\response\View
    {
        $sale_day = $this->request->get('sale_day', date('Y-m-d', strtotime('-1 day')), 'htmlspecialchars');
        $this->assign('sale_day', $sale_day);
        $sale_day_num = date('Ymd', strtotime($sale_day));

        $model = new ProductModel();
        $storeList = $model->query('
SELECT
	`name`,
	SUM( value ) value
FROM
	(
	SELECT
	CASE
		WHEN
			inventoryAge >= 0 
			AND inventoryAge < 30 THEN 30 WHEN inventoryAge >= 30 
				AND inventoryAge < 60 THEN 60 WHEN inventoryAge >= 60 
					AND inventoryAge < 90 THEN 90 WHEN inventoryAge >= 90 
						AND inventoryAge < 120 THEN 120 WHEN inventoryAge >= 120 
							AND inventoryAge < 150 THEN 150 WHEN inventoryAge >= 150 
								AND inventoryAge < 180 THEN 180 WHEN inventoryAge >= 180 
									AND inventoryAge < 210 THEN 210 WHEN inventoryAge >= 210 
										AND inventoryAge < 240 THEN 240 WHEN inventoryAge >= 240 
											AND inventoryAge < 270 THEN 270 WHEN inventoryAge >= 270 
												AND inventoryAge < 300 THEN 300 WHEN inventoryAge >= 300 
													AND inventoryAge < 330 THEN 330 WHEN inventoryAge >= 330 
														AND inventoryAge < 360 THEN 360 WHEN inventoryAge >= 360 
															AND inventoryAge < 450 THEN 450 WHEN inventoryAge >= 450 
																AND inventoryAge < 540 THEN 540 WHEN inventoryAge >= 540 
																	AND inventoryAge < 630 THEN 630 WHEN inventoryAge >= 630 
																		AND inventoryAge < 720 THEN
																			720 ELSE 750 
																			END AS name,
	SUM( goodsNum ) AS value
FROM
	mu_le_inventory_batch a
	LEFT JOIN mu_ecang_product b ON SUBSTRING( a.lecangsCode, 7 ) = b.productSku 
WHERE
	created_date = ' . $sale_day_num . '  
	AND b.saleStatus != 18
	AND b.saleStatus != 19
GROUP BY name UNION ALL
SELECT
CASE
	WHEN
		stock_age >= 0 
		AND stock_age < 30 THEN 30 WHEN stock_age >= 30 
			AND stock_age < 60 THEN 60 WHEN stock_age >= 60 
				AND stock_age < 90 THEN 90 WHEN stock_age >= 90 
					AND stock_age < 120 THEN 120 WHEN stock_age >= 120 
						AND stock_age < 150 THEN 150 WHEN stock_age >= 150 
							AND stock_age < 180 THEN 180 WHEN stock_age >= 180 
								AND stock_age < 210 THEN 210 WHEN stock_age >= 210 
									AND stock_age < 240 THEN 240 WHEN stock_age >= 240 
										AND stock_age < 270 THEN 270 WHEN stock_age >= 270 
											AND stock_age < 300 THEN 300 WHEN stock_age >= 300 
												AND stock_age < 330 THEN 330 WHEN stock_age >= 330 
													AND stock_age < 360 THEN 360 WHEN stock_age >= 360 
														AND stock_age < 450 THEN 450 WHEN stock_age >= 450 
															AND stock_age < 540 THEN 540 WHEN stock_age >= 540 
																AND stock_age < 630 THEN 630 WHEN stock_age >= 630 
																	AND stock_age < 720 THEN
																		720 ELSE 750 
																		END AS name,
			SUM( sellable_quantity ) AS value
		FROM
			mu_lc_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.product_sku = b.productSku 
		WHERE
			created_date = ' . $sale_day_num . '  
	AND b.saleStatus != 18
	AND b.saleStatus != 19
		GROUP BY
		name  UNION ALL
SELECT
CASE
	WHEN
		storageAge >= 0 
		AND storageAge < 30 THEN 30 WHEN storageAge >= 30 
			AND storageAge < 60 THEN 60 WHEN storageAge >= 60 
				AND storageAge < 90 THEN 90 WHEN storageAge >= 90 
					AND storageAge < 120 THEN 120 WHEN storageAge >= 120 
						AND storageAge < 150 THEN 150 WHEN storageAge >= 150 
							AND storageAge < 180 THEN 180 WHEN storageAge >= 180 
								AND storageAge < 210 THEN 210 WHEN storageAge >= 210 
									AND storageAge < 240 THEN 240 WHEN storageAge >= 240 
										AND storageAge < 270 THEN 270 WHEN storageAge >= 270 
											AND storageAge < 300 THEN 300 WHEN storageAge >= 300 
												AND storageAge < 330 THEN 330 WHEN storageAge >= 330 
													AND storageAge < 360 THEN 360 WHEN storageAge >= 360 
														AND storageAge < 450 THEN 450 WHEN storageAge >= 450 
															AND storageAge < 540 THEN 540 WHEN storageAge >= 540 
																AND storageAge < 630 THEN 630 WHEN storageAge >= 630 
																	AND storageAge < 720 THEN
																		720 ELSE 750 
																		END AS name,
			SUM( inventoryAvailableNum ) AS value
		FROM
			mu_wyd_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.masterSku = b.productSku 
		WHERE
			created_date = ' . $sale_day_num . '
	AND b.saleStatus != 18
	AND b.saleStatus != 19
		GROUP BY
		name
		) a 
	GROUP BY
		`name` 
ORDER BY
	`name`;
        ');
        $this->assign('storeList', json_encode($storeList));

        $storeList2 = $model->query('
SELECT
	`name`,
	SUM( value ) value
FROM
	(
	SELECT
	CASE
		WHEN
			inventoryAge >= 0 
			AND inventoryAge < 30 THEN 30 WHEN inventoryAge >= 30 
				AND inventoryAge < 60 THEN 60 WHEN inventoryAge >= 60 
					AND inventoryAge < 90 THEN 90 WHEN inventoryAge >= 90 
						AND inventoryAge < 120 THEN 120 WHEN inventoryAge >= 120 
							AND inventoryAge < 150 THEN 150 WHEN inventoryAge >= 150 
								AND inventoryAge < 180 THEN 180 WHEN inventoryAge >= 180 
									AND inventoryAge < 210 THEN 210 WHEN inventoryAge >= 210 
										AND inventoryAge < 240 THEN 240 WHEN inventoryAge >= 240 
											AND inventoryAge < 270 THEN 270 WHEN inventoryAge >= 270 
												AND inventoryAge < 300 THEN 300 WHEN inventoryAge >= 300 
													AND inventoryAge < 330 THEN 330 WHEN inventoryAge >= 330 
														AND inventoryAge < 360 THEN 360 WHEN inventoryAge >= 360 
															AND inventoryAge < 450 THEN 450 WHEN inventoryAge >= 450 
																AND inventoryAge < 540 THEN 540 WHEN inventoryAge >= 540 
																	AND inventoryAge < 630 THEN 630 WHEN inventoryAge >= 630 
																		AND inventoryAge < 720 THEN
																			720 ELSE 750 
																			END AS name,
	SUM( goodsNum ) AS value
FROM
	mu_le_inventory_batch a
	LEFT JOIN mu_ecang_product b ON SUBSTRING( a.lecangsCode, 7 ) = b.productSku 
WHERE
	created_date = ' . date('Ymd', strtotime('-2 month', strtotime($sale_day_num))) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19
GROUP BY name UNION ALL
SELECT
CASE
	WHEN
		stock_age >= 0 
		AND stock_age < 30 THEN 30 WHEN stock_age >= 30 
			AND stock_age < 60 THEN 60 WHEN stock_age >= 60 
				AND stock_age < 90 THEN 90 WHEN stock_age >= 90 
					AND stock_age < 120 THEN 120 WHEN stock_age >= 120 
						AND stock_age < 150 THEN 150 WHEN stock_age >= 150 
							AND stock_age < 180 THEN 180 WHEN stock_age >= 180 
								AND stock_age < 210 THEN 210 WHEN stock_age >= 210 
									AND stock_age < 240 THEN 240 WHEN stock_age >= 240 
										AND stock_age < 270 THEN 270 WHEN stock_age >= 270 
											AND stock_age < 300 THEN 300 WHEN stock_age >= 300 
												AND stock_age < 330 THEN 330 WHEN stock_age >= 330 
													AND stock_age < 360 THEN 360 WHEN stock_age >= 360 
														AND stock_age < 450 THEN 450 WHEN stock_age >= 450 
															AND stock_age < 540 THEN 540 WHEN stock_age >= 540 
																AND stock_age < 630 THEN 630 WHEN stock_age >= 630 
																	AND stock_age < 720 THEN
																		720 ELSE 750 
																		END AS name,
			SUM( sellable_quantity ) AS value
		FROM
			mu_lc_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.product_sku = b.productSku 
		WHERE
			created_date = ' . date('Ymd', strtotime('-2 month', strtotime($sale_day_num))) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19
		GROUP BY
		name UNION ALL
SELECT
CASE
	WHEN
		storageAge >= 0 
		AND storageAge < 30 THEN 30 WHEN storageAge >= 30 
			AND storageAge < 60 THEN 60 WHEN storageAge >= 60 
				AND storageAge < 90 THEN 90 WHEN storageAge >= 90 
					AND storageAge < 120 THEN 120 WHEN storageAge >= 120 
						AND storageAge < 150 THEN 150 WHEN storageAge >= 150 
							AND storageAge < 180 THEN 180 WHEN storageAge >= 180 
								AND storageAge < 210 THEN 210 WHEN storageAge >= 210 
									AND storageAge < 240 THEN 240 WHEN storageAge >= 240 
										AND storageAge < 270 THEN 270 WHEN storageAge >= 270 
											AND storageAge < 300 THEN 300 WHEN storageAge >= 300 
												AND storageAge < 330 THEN 330 WHEN storageAge >= 330 
													AND storageAge < 360 THEN 360 WHEN storageAge >= 360 
														AND storageAge < 450 THEN 450 WHEN storageAge >= 450 
															AND storageAge < 540 THEN 540 WHEN storageAge >= 540 
																AND storageAge < 630 THEN 630 WHEN storageAge >= 630 
																	AND storageAge < 720 THEN
																		720 ELSE 750 
																		END AS name,
			SUM( inventoryAvailableNum ) AS value
		FROM
			mu_wyd_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.masterSku = b.productSku 
		WHERE
			created_date = ' . date('Ymd', strtotime('-2 month', strtotime($sale_day_num))) . '
	AND b.saleStatus != 18
	AND b.saleStatus != 19
		GROUP BY
		name
		) a 
	GROUP BY
		`name` 
ORDER BY
	`name`;
        ');

        $storeList3 = $model->query('
SELECT
	`name`,
	SUM( value ) value
FROM
	(
	SELECT
	CASE
		WHEN
			inventoryAge >= 0 
			AND inventoryAge < 30 THEN 30 WHEN inventoryAge >= 30 
				AND inventoryAge < 60 THEN 60 WHEN inventoryAge >= 60 
					AND inventoryAge < 90 THEN 90 WHEN inventoryAge >= 90 
						AND inventoryAge < 120 THEN 120 WHEN inventoryAge >= 120 
							AND inventoryAge < 150 THEN 150 WHEN inventoryAge >= 150 
								AND inventoryAge < 180 THEN 180 WHEN inventoryAge >= 180 
									AND inventoryAge < 210 THEN 210 WHEN inventoryAge >= 210 
										AND inventoryAge < 240 THEN 240 WHEN inventoryAge >= 240 
											AND inventoryAge < 270 THEN 270 WHEN inventoryAge >= 270 
												AND inventoryAge < 300 THEN 300 WHEN inventoryAge >= 300 
													AND inventoryAge < 330 THEN 330 WHEN inventoryAge >= 330 
														AND inventoryAge < 360 THEN 360 WHEN inventoryAge >= 360 
															AND inventoryAge < 450 THEN 450 WHEN inventoryAge >= 450 
																AND inventoryAge < 540 THEN 540 WHEN inventoryAge >= 540 
																	AND inventoryAge < 630 THEN 630 WHEN inventoryAge >= 630 
																		AND inventoryAge < 720 THEN
																			720 ELSE 750 
																			END AS name,
	SUM( goodsNum ) AS value
FROM
	mu_le_inventory_batch a
	LEFT JOIN mu_ecang_product b ON SUBSTRING( a.lecangsCode, 7 ) = b.productSku 
WHERE
	created_date = ' . date('Ymd', strtotime('-1 month', strtotime($sale_day_num))) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19
GROUP BY name UNION ALL
SELECT
CASE
	WHEN
		stock_age >= 0 
		AND stock_age < 30 THEN 30 WHEN stock_age >= 30 
			AND stock_age < 60 THEN 60 WHEN stock_age >= 60 
				AND stock_age < 90 THEN 90 WHEN stock_age >= 90 
					AND stock_age < 120 THEN 120 WHEN stock_age >= 120 
						AND stock_age < 150 THEN 150 WHEN stock_age >= 150 
							AND stock_age < 180 THEN 180 WHEN stock_age >= 180 
								AND stock_age < 210 THEN 210 WHEN stock_age >= 210 
									AND stock_age < 240 THEN 240 WHEN stock_age >= 240 
										AND stock_age < 270 THEN 270 WHEN stock_age >= 270 
											AND stock_age < 300 THEN 300 WHEN stock_age >= 300 
												AND stock_age < 330 THEN 330 WHEN stock_age >= 330 
													AND stock_age < 360 THEN 360 WHEN stock_age >= 360 
														AND stock_age < 450 THEN 450 WHEN stock_age >= 450 
															AND stock_age < 540 THEN 540 WHEN stock_age >= 540 
																AND stock_age < 630 THEN 630 WHEN stock_age >= 630 
																	AND stock_age < 720 THEN
																		720 ELSE 750 
																		END AS name,
			SUM( sellable_quantity ) AS value
		FROM
			mu_lc_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.product_sku = b.productSku 
		WHERE
			created_date = ' . date('Ymd', strtotime('-1 month', strtotime($sale_day_num))) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19
		GROUP BY
		name UNION ALL
SELECT
CASE
	WHEN
		storageAge >= 0 
		AND storageAge < 30 THEN 30 WHEN storageAge >= 30 
			AND storageAge < 60 THEN 60 WHEN storageAge >= 60 
				AND storageAge < 90 THEN 90 WHEN storageAge >= 90 
					AND storageAge < 120 THEN 120 WHEN storageAge >= 120 
						AND storageAge < 150 THEN 150 WHEN storageAge >= 150 
							AND storageAge < 180 THEN 180 WHEN storageAge >= 180 
								AND storageAge < 210 THEN 210 WHEN storageAge >= 210 
									AND storageAge < 240 THEN 240 WHEN storageAge >= 240 
										AND storageAge < 270 THEN 270 WHEN storageAge >= 270 
											AND storageAge < 300 THEN 300 WHEN storageAge >= 300 
												AND storageAge < 330 THEN 330 WHEN storageAge >= 330 
													AND storageAge < 360 THEN 360 WHEN storageAge >= 360 
														AND storageAge < 450 THEN 450 WHEN storageAge >= 450 
															AND storageAge < 540 THEN 540 WHEN storageAge >= 540 
																AND storageAge < 630 THEN 630 WHEN storageAge >= 630 
																	AND storageAge < 720 THEN
																		720 ELSE 750 
																		END AS name,
			SUM( inventoryAvailableNum ) AS value
		FROM
			mu_wyd_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.masterSku = b.productSku 
		WHERE
			created_date = ' . date('Ymd', strtotime('-1 month', strtotime($sale_day_num))) . '
	AND b.saleStatus != 18
	AND b.saleStatus != 19
		GROUP BY
		name
		) a 
	GROUP BY
		`name` 
ORDER BY
	`name`;
        ');

        $storeData[] = [
            'date',
            date('Y-m-d', strtotime('-2 month', strtotime($sale_day))),
            date('Y-m-d', strtotime('-1 month', strtotime($sale_day))),
            $sale_day
        ];
        foreach ($storeList as $key => $item) {
            $storeData[] = [
                $item['name'],
                $storeList2[$key]['value'],
                $storeList3[$key]['value'],
                $item['value']
            ];
        }
        $this->assign('storeData', json_encode($storeData));

        $priceList = $model->query('
SELECT
	`name`,
	ROUND( SUM( value ), 4) value
FROM
	(
	SELECT
	CASE
		WHEN
			inventoryAge >= 0 
			AND inventoryAge < 30 THEN 30 WHEN inventoryAge >= 30 
				AND inventoryAge < 60 THEN 60 WHEN inventoryAge >= 60 
					AND inventoryAge < 90 THEN 90 WHEN inventoryAge >= 90 
						AND inventoryAge < 120 THEN 120 WHEN inventoryAge >= 120 
							AND inventoryAge < 150 THEN 150 WHEN inventoryAge >= 150 
								AND inventoryAge < 180 THEN 180 WHEN inventoryAge >= 180 
									AND inventoryAge < 210 THEN 210 WHEN inventoryAge >= 210 
										AND inventoryAge < 240 THEN 240 WHEN inventoryAge >= 240 
											AND inventoryAge < 270 THEN 270 WHEN inventoryAge >= 270 
												AND inventoryAge < 300 THEN 300 WHEN inventoryAge >= 300 
													AND inventoryAge < 330 THEN 330 WHEN inventoryAge >= 330 
														AND inventoryAge < 360 THEN 360 WHEN inventoryAge >= 360 
															AND inventoryAge < 450 THEN 450 WHEN inventoryAge >= 450 
																AND inventoryAge < 540 THEN 540 WHEN inventoryAge >= 540 
																	AND inventoryAge < 630 THEN 630 WHEN inventoryAge >= 630 
																		AND inventoryAge < 720 THEN
																			720 ELSE 750 
																			END AS name,
	ROUND( SUM( goodsNum * b.sp_unit_price ), 4) AS value
FROM
	mu_le_inventory_batch a
	LEFT JOIN mu_ecang_product b ON SUBSTRING( a.lecangsCode, 7 ) = b.productSku 
WHERE
	created_date = ' . $sale_day_num . '  
	AND b.saleStatus != 18
	AND b.saleStatus != 19
GROUP BY name UNION ALL
SELECT
CASE
	WHEN
		stock_age >= 0 
		AND stock_age < 30 THEN 30 WHEN stock_age >= 30 
			AND stock_age < 60 THEN 60 WHEN stock_age >= 60 
				AND stock_age < 90 THEN 90 WHEN stock_age >= 90 
					AND stock_age < 120 THEN 120 WHEN stock_age >= 120 
						AND stock_age < 150 THEN 150 WHEN stock_age >= 150 
							AND stock_age < 180 THEN 180 WHEN stock_age >= 180 
								AND stock_age < 210 THEN 210 WHEN stock_age >= 210 
									AND stock_age < 240 THEN 240 WHEN stock_age >= 240 
										AND stock_age < 270 THEN 270 WHEN stock_age >= 270 
											AND stock_age < 300 THEN 300 WHEN stock_age >= 300 
												AND stock_age < 330 THEN 330 WHEN stock_age >= 330 
													AND stock_age < 360 THEN 360 WHEN stock_age >= 360 
														AND stock_age < 450 THEN 450 WHEN stock_age >= 450 
															AND stock_age < 540 THEN 540 WHEN stock_age >= 540 
																AND stock_age < 630 THEN 630 WHEN stock_age >= 630 
																	AND stock_age < 720 THEN
																		720 ELSE 750 
																		END AS name,
	ROUND( SUM( sellable_quantity * b.sp_unit_price ), 4) AS value
		FROM
			mu_lc_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.product_sku = b.productSku 
		WHERE
			created_date = ' . $sale_day_num . '  
	AND b.saleStatus != 18
	AND b.saleStatus != 19
		GROUP BY
		name UNION ALL
SELECT
CASE
	WHEN
		storageAge >= 0 
		AND storageAge < 30 THEN 30 WHEN storageAge >= 30 
			AND storageAge < 60 THEN 60 WHEN storageAge >= 60 
				AND storageAge < 90 THEN 90 WHEN storageAge >= 90 
					AND storageAge < 120 THEN 120 WHEN storageAge >= 120 
						AND storageAge < 150 THEN 150 WHEN storageAge >= 150 
							AND storageAge < 180 THEN 180 WHEN storageAge >= 180 
								AND storageAge < 210 THEN 210 WHEN storageAge >= 210 
									AND storageAge < 240 THEN 240 WHEN storageAge >= 240 
										AND storageAge < 270 THEN 270 WHEN storageAge >= 270 
											AND storageAge < 300 THEN 300 WHEN storageAge >= 300 
												AND storageAge < 330 THEN 330 WHEN storageAge >= 330 
													AND storageAge < 360 THEN 360 WHEN storageAge >= 360 
														AND storageAge < 450 THEN 450 WHEN storageAge >= 450 
															AND storageAge < 540 THEN 540 WHEN storageAge >= 540 
																AND storageAge < 630 THEN 630 WHEN storageAge >= 630 
																	AND storageAge < 720 THEN
																		720 ELSE 750 
																		END AS name,
	ROUND( SUM( inventoryAvailableNum * b.sp_unit_price ), 4) AS value
		FROM
			mu_wyd_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.masterSku = b.productSku 
		WHERE
			created_date = ' . $sale_day_num . '  
	AND b.saleStatus != 18
	AND b.saleStatus != 19
		GROUP BY
		name 
		) a 
	GROUP BY
		`name` 
ORDER BY
	`name`;
        ');
        $this->assign('priceList', json_encode($priceList));

        $priceList2 = $model->query('
SELECT
	`name`,
	ROUND( SUM( value ), 4) value
FROM
	(
	SELECT
	CASE
		WHEN
			inventoryAge >= 0 
			AND inventoryAge < 30 THEN 30 WHEN inventoryAge >= 30 
				AND inventoryAge < 60 THEN 60 WHEN inventoryAge >= 60 
					AND inventoryAge < 90 THEN 90 WHEN inventoryAge >= 90 
						AND inventoryAge < 120 THEN 120 WHEN inventoryAge >= 120 
							AND inventoryAge < 150 THEN 150 WHEN inventoryAge >= 150 
								AND inventoryAge < 180 THEN 180 WHEN inventoryAge >= 180 
									AND inventoryAge < 210 THEN 210 WHEN inventoryAge >= 210 
										AND inventoryAge < 240 THEN 240 WHEN inventoryAge >= 240 
											AND inventoryAge < 270 THEN 270 WHEN inventoryAge >= 270 
												AND inventoryAge < 300 THEN 300 WHEN inventoryAge >= 300 
													AND inventoryAge < 330 THEN 330 WHEN inventoryAge >= 330 
														AND inventoryAge < 360 THEN 360 WHEN inventoryAge >= 360 
															AND inventoryAge < 450 THEN 450 WHEN inventoryAge >= 450 
																AND inventoryAge < 540 THEN 540 WHEN inventoryAge >= 540 
																	AND inventoryAge < 630 THEN 630 WHEN inventoryAge >= 630 
																		AND inventoryAge < 720 THEN
																			720 ELSE 750 
																			END AS name,
	ROUND( SUM( goodsNum * b.sp_unit_price ), 4) AS value
FROM
	mu_le_inventory_batch a
	LEFT JOIN mu_ecang_product b ON SUBSTRING( a.lecangsCode, 7 ) = b.productSku 
WHERE
	created_date = ' . date('Ymd', strtotime('-2 month', strtotime($sale_day_num))) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19
GROUP BY name UNION ALL
SELECT
CASE
	WHEN
		stock_age >= 0 
		AND stock_age < 30 THEN 30 WHEN stock_age >= 30 
			AND stock_age < 60 THEN 60 WHEN stock_age >= 60 
				AND stock_age < 90 THEN 90 WHEN stock_age >= 90 
					AND stock_age < 120 THEN 120 WHEN stock_age >= 120 
						AND stock_age < 150 THEN 150 WHEN stock_age >= 150 
							AND stock_age < 180 THEN 180 WHEN stock_age >= 180 
								AND stock_age < 210 THEN 210 WHEN stock_age >= 210 
									AND stock_age < 240 THEN 240 WHEN stock_age >= 240 
										AND stock_age < 270 THEN 270 WHEN stock_age >= 270 
											AND stock_age < 300 THEN 300 WHEN stock_age >= 300 
												AND stock_age < 330 THEN 330 WHEN stock_age >= 330 
													AND stock_age < 360 THEN 360 WHEN stock_age >= 360 
														AND stock_age < 450 THEN 450 WHEN stock_age >= 450 
															AND stock_age < 540 THEN 540 WHEN stock_age >= 540 
																AND stock_age < 630 THEN 630 WHEN stock_age >= 630 
																	AND stock_age < 720 THEN
																		720 ELSE 750 
																		END AS name,
	ROUND( SUM( sellable_quantity * b.sp_unit_price ), 4) AS value
		FROM
			mu_lc_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.product_sku = b.productSku 
		WHERE
			created_date = ' . date('Ymd', strtotime('-2 month', strtotime($sale_day_num))) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19
		GROUP BY
		name UNION ALL
SELECT
CASE
	WHEN
		storageAge >= 0 
		AND storageAge < 30 THEN 30 WHEN storageAge >= 30 
			AND storageAge < 60 THEN 60 WHEN storageAge >= 60 
				AND storageAge < 90 THEN 90 WHEN storageAge >= 90 
					AND storageAge < 120 THEN 120 WHEN storageAge >= 120 
						AND storageAge < 150 THEN 150 WHEN storageAge >= 150 
							AND storageAge < 180 THEN 180 WHEN storageAge >= 180 
								AND storageAge < 210 THEN 210 WHEN storageAge >= 210 
									AND storageAge < 240 THEN 240 WHEN storageAge >= 240 
										AND storageAge < 270 THEN 270 WHEN storageAge >= 270 
											AND storageAge < 300 THEN 300 WHEN storageAge >= 300 
												AND storageAge < 330 THEN 330 WHEN storageAge >= 330 
													AND storageAge < 360 THEN 360 WHEN storageAge >= 360 
														AND storageAge < 450 THEN 450 WHEN storageAge >= 450 
															AND storageAge < 540 THEN 540 WHEN storageAge >= 540 
																AND storageAge < 630 THEN 630 WHEN storageAge >= 630 
																	AND storageAge < 720 THEN
																		720 ELSE 750 
																		END AS name,
	ROUND( SUM( inventoryAvailableNum * b.sp_unit_price ), 4) AS value
		FROM
			mu_wyd_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.masterSku = b.productSku 
		WHERE
			created_date = ' . date('Ymd', strtotime('-2 month', strtotime($sale_day_num))) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19
		GROUP BY
		name 
		) a 
	GROUP BY
		`name` 
ORDER BY
	`name`;
        ');

        $priceList3 = $model->query('
SELECT
	`name`,
	ROUND( SUM( value ), 4) value
FROM
	(
	SELECT
	CASE
		WHEN
			inventoryAge >= 0 
			AND inventoryAge < 30 THEN 30 WHEN inventoryAge >= 30 
				AND inventoryAge < 60 THEN 60 WHEN inventoryAge >= 60 
					AND inventoryAge < 90 THEN 90 WHEN inventoryAge >= 90 
						AND inventoryAge < 120 THEN 120 WHEN inventoryAge >= 120 
							AND inventoryAge < 150 THEN 150 WHEN inventoryAge >= 150 
								AND inventoryAge < 180 THEN 180 WHEN inventoryAge >= 180 
									AND inventoryAge < 210 THEN 210 WHEN inventoryAge >= 210 
										AND inventoryAge < 240 THEN 240 WHEN inventoryAge >= 240 
											AND inventoryAge < 270 THEN 270 WHEN inventoryAge >= 270 
												AND inventoryAge < 300 THEN 300 WHEN inventoryAge >= 300 
													AND inventoryAge < 330 THEN 330 WHEN inventoryAge >= 330 
														AND inventoryAge < 360 THEN 360 WHEN inventoryAge >= 360 
															AND inventoryAge < 450 THEN 450 WHEN inventoryAge >= 450 
																AND inventoryAge < 540 THEN 540 WHEN inventoryAge >= 540 
																	AND inventoryAge < 630 THEN 630 WHEN inventoryAge >= 630 
																		AND inventoryAge < 720 THEN
																			720 ELSE 750 
																			END AS name,
	ROUND( SUM( goodsNum * b.sp_unit_price ), 4) AS value
FROM
	mu_le_inventory_batch a
	LEFT JOIN mu_ecang_product b ON SUBSTRING( a.lecangsCode, 7 ) = b.productSku 
WHERE
	created_date = ' . date('Ymd', strtotime('-1 month', strtotime($sale_day_num))) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19
GROUP BY name UNION ALL
SELECT
CASE
	WHEN
		stock_age >= 0 
		AND stock_age < 30 THEN 30 WHEN stock_age >= 30 
			AND stock_age < 60 THEN 60 WHEN stock_age >= 60 
				AND stock_age < 90 THEN 90 WHEN stock_age >= 90 
					AND stock_age < 120 THEN 120 WHEN stock_age >= 120 
						AND stock_age < 150 THEN 150 WHEN stock_age >= 150 
							AND stock_age < 180 THEN 180 WHEN stock_age >= 180 
								AND stock_age < 210 THEN 210 WHEN stock_age >= 210 
									AND stock_age < 240 THEN 240 WHEN stock_age >= 240 
										AND stock_age < 270 THEN 270 WHEN stock_age >= 270 
											AND stock_age < 300 THEN 300 WHEN stock_age >= 300 
												AND stock_age < 330 THEN 330 WHEN stock_age >= 330 
													AND stock_age < 360 THEN 360 WHEN stock_age >= 360 
														AND stock_age < 450 THEN 450 WHEN stock_age >= 450 
															AND stock_age < 540 THEN 540 WHEN stock_age >= 540 
																AND stock_age < 630 THEN 630 WHEN stock_age >= 630 
																	AND stock_age < 720 THEN
																		720 ELSE 750 
																		END AS name,
	ROUND( SUM( sellable_quantity * b.sp_unit_price ), 4) AS value
		FROM
			mu_lc_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.product_sku = b.productSku 
		WHERE
			created_date = ' . date('Ymd', strtotime('-1 month', strtotime($sale_day_num))) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19
		GROUP BY
		name  UNION ALL
SELECT
CASE
	WHEN
		storageAge >= 0 
		AND storageAge < 30 THEN 30 WHEN storageAge >= 30 
			AND storageAge < 60 THEN 60 WHEN storageAge >= 60 
				AND storageAge < 90 THEN 90 WHEN storageAge >= 90 
					AND storageAge < 120 THEN 120 WHEN storageAge >= 120 
						AND storageAge < 150 THEN 150 WHEN storageAge >= 150 
							AND storageAge < 180 THEN 180 WHEN storageAge >= 180 
								AND storageAge < 210 THEN 210 WHEN storageAge >= 210 
									AND storageAge < 240 THEN 240 WHEN storageAge >= 240 
										AND storageAge < 270 THEN 270 WHEN storageAge >= 270 
											AND storageAge < 300 THEN 300 WHEN storageAge >= 300 
												AND storageAge < 330 THEN 330 WHEN storageAge >= 330 
													AND storageAge < 360 THEN 360 WHEN storageAge >= 360 
														AND storageAge < 450 THEN 450 WHEN storageAge >= 450 
															AND storageAge < 540 THEN 540 WHEN storageAge >= 540 
																AND storageAge < 630 THEN 630 WHEN storageAge >= 630 
																	AND storageAge < 720 THEN
																		720 ELSE 750 
																		END AS name,
	ROUND( SUM( inventoryAvailableNum * b.sp_unit_price ), 4) AS value
		FROM
			mu_wyd_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.masterSku = b.productSku 
		WHERE
			created_date = ' . date('Ymd', strtotime('-1 month', strtotime($sale_day_num))) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19
		GROUP BY
		name 
		) a 
	GROUP BY
		`name` 
ORDER BY
	`name`;
        ');

        $priceData[] = [
            'date',
            date('Y-m-d', strtotime('-2 month', strtotime($sale_day))),
            date('Y-m-d', strtotime('-1 month', strtotime($sale_day))),
            $sale_day
        ];
        foreach ($priceList as $key => $item) {
            $priceData[] = [
                $item['name'],
                $priceList2[$key]['value'],
                $priceList3[$key]['value'],
                $item['value']
            ];
        }
        $this->assign('priceData', json_encode($priceData));

        $sum = $model->query('
SELECT
	SUM( value ) value,
	SUM( sum ) sum,
	SUM( volume ) volume 
FROM
	(
	SELECT
		SUM( goodsNum ) AS value,
		ROUND( SUM( goodsNum * b.sp_unit_price ), 4 ) AS sum,
		ROUND( SUM( goodsNum * b.productLength * productWidth * productHeight / 1000000 ), 4 ) AS volume 
	FROM
		mu_le_inventory_batch a
		LEFT JOIN mu_ecang_product b ON SUBSTRING( a.lecangsCode, 7 ) = b.productSku 
	WHERE
		created_date = ' . $sale_day_num . ' 
		AND b.saleStatus != 18 
		AND b.saleStatus != 19 UNION ALL
	SELECT
		SUM( sellable_quantity ) AS value,
		ROUND( SUM( sellable_quantity * b.sp_unit_price ), 4 ) AS sum,
		ROUND( SUM( sellable_quantity * b.productLength * productWidth * productHeight / 1000000 ), 4 ) AS volume 
	FROM
		mu_lc_inventory_batch a
		LEFT JOIN mu_ecang_product b ON a.product_sku = b.productSku 
	WHERE
		created_date = ' . $sale_day_num . ' 
		AND b.saleStatus != 18 
	AND b.saleStatus != 19 UNION ALL
	SELECT
		SUM( inventoryAvailableNum ) AS value,
		ROUND( SUM( inventoryAvailableNum * b.sp_unit_price ), 4 ) AS sum,
		ROUND( SUM( inventoryAvailableNum * b.productLength * productWidth * productHeight / 1000000 ), 4 ) AS volume 
	FROM
		mu_wyd_inventory_batch a
		LEFT JOIN mu_ecang_product b ON a.masterSku = b.productSku 
	WHERE
		created_date = ' . $sale_day_num . '  
		AND b.saleStatus != 18 
	AND b.saleStatus != 19 
	) a;
        ');
        $this->assign('sum', $sum);

        $le_sum = $model->query('
SELECT
    SUM( goodsNum ) AS value,
    ROUND( SUM( goodsNum * b.sp_unit_price ), 4 ) AS sum,
    ROUND( SUM( goodsNum * b.productLength * productWidth * productHeight / 1000000 ), 4 ) AS volume 
FROM
    mu_le_inventory_batch a
    LEFT JOIN mu_ecang_product b ON SUBSTRING( a.lecangsCode, 7 ) = b.productSku 
WHERE
    created_date = ' . $sale_day_num . ' 
    AND b.saleStatus != 18 
    AND b.saleStatus != 19
        ');
        $this->assign('le_sum', $le_sum);

        $lc_sum = $model->query('
SELECT
    SUM( sellable_quantity ) AS value,
    ROUND( SUM( sellable_quantity * b.sp_unit_price ), 4 ) AS sum,
    ROUND( SUM( sellable_quantity * b.productLength * productWidth * productHeight / 1000000 ), 4 ) AS volume 
FROM
    mu_lc_inventory_batch a
    LEFT JOIN mu_ecang_product b ON a.product_sku = b.productSku 
WHERE
    created_date = ' . $sale_day_num . ' 
    AND b.saleStatus != 18 
	AND b.saleStatus != 19
        ');
        $this->assign('lc_sum', $lc_sum);

        $wyd_sum = $model->query('
SELECT
    SUM( inventoryAvailableNum ) AS value,
    ROUND( SUM( inventoryAvailableNum * b.sp_unit_price ), 4 ) AS sum,
    ROUND( SUM( inventoryAvailableNum * b.productLength * productWidth * productHeight / 1000000 ), 4 ) AS volume 
FROM
    mu_wyd_inventory_batch a
    LEFT JOIN mu_ecang_product b ON a.masterSku = b.productSku 
WHERE
    created_date = ' . $sale_day_num . '  
    AND b.saleStatus != 18 
	AND b.saleStatus != 19 
        ');
        $this->assign('wyd_sum', $wyd_sum);

        $last_sum = $model->query('
SELECT
	SUM( value ) value,
	SUM( sum ) sum
FROM
	(
SELECT
	SUM( goodsNum ) AS value,
	ROUND( SUM( goodsNum * b.sp_unit_price ), 4) AS sum
FROM
	mu_le_inventory_batch a
	LEFT JOIN mu_ecang_product b ON SUBSTRING( a.lecangsCode, 7 ) = b.productSku 
WHERE
	created_date = ' . date('Ymd', strtotime('-7 day', strtotime($sale_day_num))) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19 UNION ALL
SELECT
	SUM( sellable_quantity ) AS value,
	ROUND( SUM( sellable_quantity * b.sp_unit_price ), 4) AS sum
FROM
	mu_lc_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.product_sku = b.productSku 
WHERE
	created_date = ' . date('Ymd', strtotime('-7 day', strtotime($sale_day_num))) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19 UNION ALL
SELECT
	SUM( inventoryAvailableNum ) AS value,
	ROUND( SUM( inventoryAvailableNum * b.sp_unit_price ), 4) AS sum
FROM
	mu_wyd_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.masterSku = b.productSku 
WHERE
	created_date = ' . date('Ymd', strtotime('-7 day', strtotime($sale_day_num))) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19
	) a;
        ');
        $this->assign('last_sum', $last_sum);

        $le_last_sum = $model->query('
SELECT
	SUM( goodsNum ) AS value,
	ROUND( SUM( goodsNum * b.sp_unit_price ), 4) AS sum
FROM
	mu_le_inventory_batch a
	LEFT JOIN mu_ecang_product b ON SUBSTRING( a.lecangsCode, 7 ) = b.productSku 
WHERE
	created_date = ' . date('Ymd', strtotime('-7 day', strtotime($sale_day_num))) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19
        ');
        $this->assign('le_last_sum', $le_last_sum);

        $lc_last_sum = $model->query('
SELECT
	SUM( sellable_quantity ) AS value,
	ROUND( SUM( sellable_quantity * b.sp_unit_price ), 4) AS sum
FROM
	mu_lc_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.product_sku = b.productSku 
WHERE
	created_date = ' . date('Ymd', strtotime('-7 day', strtotime($sale_day_num))) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19
        ');
        $this->assign('lc_last_sum', $lc_last_sum);

        $wyd_last_sum = $model->query('
SELECT
	SUM( inventoryAvailableNum ) AS value,
	ROUND( SUM( inventoryAvailableNum * b.sp_unit_price ), 4) AS sum
FROM
	mu_wyd_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.masterSku = b.productSku 
WHERE
	created_date = ' . date('Ymd', strtotime('-7 day', strtotime($sale_day_num))) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19
        ');
        $this->assign('wyd_last_sum', $wyd_last_sum);

        $monthQty = $model->query('
SELECT
	SUM( b.qty ) qty
FROM
	mu_ecang_order a
	LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id 
WHERE
	a.`status` != 5 
	AND a.`status` != 7 
	AND a.`status` != 0 
	AND a.datePaidPlatform >= "' . date('Y-m-d 16:00:00', strtotime('-7 day', strtotime($sale_day_num))) . '" 
	AND a.datePaidPlatform < "' . date('Y-m-d 16:00:00', strtotime($sale_day_num)) . '";
        ');
        $this->assign('monthQty', $monthQty);

        $le_monthQty = $model->query('
SELECT
	SUM( b.qty ) qty
FROM
	mu_ecang_order a
	LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id 
WHERE
	a.`status` != 5 
	AND a.`status` != 7 
	AND a.`status` != 0 
	AND a.warehouseCode IN("CAP2","LG-USA-PA01","CAP4","LG-TN","LG-USA-MI","SAV","HOU03","LOCTEKOMS_HOU07","LECANGS_HOU05","LECANGS_CAT","LOCTEKOMS_NJF02")
	AND a.datePaidPlatform >= "' . date('Y-m-d 16:00:00', strtotime('-7 day', strtotime($sale_day_num))) . '" 
	AND a.datePaidPlatform < "' . date('Y-m-d 16:00:00', strtotime($sale_day_num)) . '";
        ');
        $this->assign('le_monthQty', $le_monthQty);

        $lc_monthQty = $model->query('
SELECT
	SUM( b.qty ) qty
FROM
	mu_ecang_order a
	LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id 
WHERE
	a.`status` != 5 
	AND a.`status` != 7 
	AND a.`status` != 0 
	AND a.warehouseCode IN("LC-USLAX08","LC-USNJ06","USLAX09","LC-USATL06","LC-USLAX05","LC-USLAX01")
	AND a.datePaidPlatform >= "' . date('Y-m-d 16:00:00', strtotime('-7 day', strtotime($sale_day_num))) . '" 
	AND a.datePaidPlatform < "' . date('Y-m-d 16:00:00', strtotime($sale_day_num)) . '";
        ');
        $this->assign('lc_monthQty', $lc_monthQty);

        $wyd_monthQty = $model->query('
SELECT
	SUM( b.qty ) qty
FROM
	mu_ecang_order a
	LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id 
WHERE
	a.`status` != 5 
	AND a.`status` != 7 
	AND a.`status` != 0 
	AND a.warehouseCode IN("WUYOUDA_CAJW04","WUYOUDA_NJJW03","WUYOUDA_CAJW05")
	AND a.datePaidPlatform >= "' . date('Y-m-d 16:00:00', strtotime('-7 day', strtotime($sale_day_num))) . '" 
	AND a.datePaidPlatform < "' . date('Y-m-d 16:00:00', strtotime($sale_day_num)) . '";
        ');
        $this->assign('wyd_monthQty', $wyd_monthQty);

        $orderQty = $model->query('
SELECT
	COUNT(b.id) count
FROM
	mu_ecang_order a
	LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id 
WHERE
	a.`status` != 5 
	AND a.`status` != 7 
	AND a.`status` != 0 
	AND a.datePaidPlatform >= "' . date('Y-m-d 16:00:00', strtotime('-1 day', strtotime($sale_day_num))) . '" 
	AND a.datePaidPlatform < "' . date('Y-m-d 16:00:00', strtotime($sale_day_num)) . '";
        ');
        $this->assign('orderQty', $orderQty);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    public function inventory($date, $num = 30): \think\response\View
    {
        $seller = empty(input('seller')) ? '' : 'AND c.user_name = "' . input('seller') . '"';
        if ($seller) {
            $this->assign('seller', input('seller'));
        }

        if ($num > 360) {
            $numStart = $num - 90;
        } else {
            $numStart = $num - 30;
        }
        $this->assign('numStart', $numStart);
        $this->assign('num', $num);

        $date = date('Ymd', strtotime($date));
        $this->assign('date', $date);

        $model = new ProductModel();
        $leList = $model->query('
SELECT
	SUM( goodsNum ) goodsNum,
	SUBSTRING( lecangsCode, 7 ) lecangsCode,
	c.user_name,
	b.productImages 
FROM
	mu_le_inventory_batch a
	LEFT JOIN mu_ecang_product b ON SUBSTRING( a.lecangsCode, 7 ) = b.productSku 
	LEFT JOIN mu_ecang_user c ON b.personSellerId = c.user_id
WHERE
	created_date = ' . $date . ' 
	AND inventoryAge >= ' . $numStart . ' 
	AND inventoryAge < ' . $num . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19
	' . $seller . '
GROUP BY
	lecangsCode,
	user_name,
	productImages 
ORDER BY
	goodsNum DESC;
        ');
        $this->assign('leList', $leList);

        $lcList = $model->query('
SELECT
	SUM( sellable_quantity ) sellable_quantity,
	product_sku,
	c.user_name,
	b.productImages 
FROM
	mu_lc_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.product_sku = b.productSku 
	LEFT JOIN mu_ecang_user c ON b.personSellerId = c.user_id
WHERE
	created_date = ' . $date . ' 
	AND stock_age >= ' . $numStart . ' 
	AND stock_age < ' . $num . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19
	' . $seller . '
GROUP BY
	product_sku,
	user_name,
	productImages 
ORDER BY
	sellable_quantity DESC;
        ');
        $this->assign('lcList', $lcList);

        return view();
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    public function inventory_turnover(): \think\response\View
    {
        $model = new ProductModel();
        $data = $model->query('
SELECT
	* 
FROM
	( SELECT turnover, DATE_FORMAT(date,"%Y-%m-%d") date FROM mu_ecang_inventory_turnover ORDER BY date DESC LIMIT 14 ) a 
ORDER BY
	date ASC;
        ');
        $this->assign('turnover', json_encode(array_column($data, 'turnover')));
        $this->assign('date', json_encode(array_column($data, 'date')));

        return view();
    }

    /**
     * @throws DbException
     */
    public function growth(): \think\response\View
    {
        $start = $this->request->get('start', date('Y-m-01 00:00:00'), 'htmlspecialchars');
        $this->assign('start', $start);
        $end = $this->request->get('end', date('Y-m-d 00:00:00'), 'htmlspecialchars');
        $this->assign('end', $end);

        $last_diff = $this->request->get('last_diff', 'diff_rate', 'htmlspecialchars');
        $this->assign('last_diff', $last_diff);
        $last_order = $this->request->get('last_order', 'DESC', 'htmlspecialchars');
        $this->assign('last_order', $last_order);
        $last_start = date('Y-m-01 00:00:00', strtotime('-1 month', strtotime($start)));
        $last_end = date('Y-m-d 00:00:00', strtotime('-1 month', strtotime($end)));

        $model = new ProductModel();
        $saleList = $model->query('
SELECT
	SUM( qty ) current,
	SUM( last_qty ) last,
	SUM( qty ) - SUM( last_qty ) diff,
	ROUND((SUM(qty) - SUM(last_qty)) / SUM(last_qty), 4) diff_rate,
	warehouseSku,
	productImages
FROM
	(
	SELECT
		SUM( b.qty ) qty,
		0 AS last_qty,
		b.warehouseSku,
		c.productImages
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
		LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
	WHERE
		a.datePaidPlatform >= "' . $start . '" 
		AND a.datePaidPlatform < "' . $end . '" 
		AND c.saleStatus = 2 
		AND a.`status` = 4
	GROUP BY
		warehouseSku,
		productImages	UNION ALL
	SELECT 0 AS
		qty,
		SUM( b.qty ) last_qty,
		b.warehouseSku,
		c.productImages
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
		LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
	WHERE
		a.datePaidPlatform >= "' . $last_start . '" 
		AND a.datePaidPlatform < "' . $last_end . '" 
		AND c.saleStatus = 2 
		AND a.`status` = 4
	GROUP BY
		warehouseSku,
		productImages
	) a 
GROUP BY
	warehouseSku,
	productImages
ORDER BY
	' . $last_diff . ' ' . $last_order . ';
        ');
        $this->assign('saleList', $saleList);

        $is_growth = $model->query('
SELECT
	is_growth name,
	COUNT( is_growth ) value 
FROM
	(
	SELECT
		warehouseSku,
		SUM( current ) current,
		SUM( last ) last,
	CASE	
			WHEN SUM( current ) > SUM( last ) THEN
			"增(Sku个数)" 
			WHEN SUM( current ) = SUM( last ) THEN
			"平(Sku个数)" ELSE "减(Sku个数)" 
		END is_growth,
		SUM( current ) - SUM( last ) growth_num 
	FROM
		(
		SELECT
			b.warehouseSku,
			SUM( b.qty ) current,
			0 AS last 
		FROM
			mu_ecang_order a
			LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
			LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
		WHERE
			a.datePaidPlatform >= "' . $start . '" 
			AND a.datePaidPlatform < "' . $end . '" 
			AND a.`status` = 4 
			AND c.saleStatus = 2 
		GROUP BY
			warehouseSku UNION ALL
		SELECT
			b.warehouseSku,
			0 AS current,
			SUM( b.qty ) last 
		FROM
			mu_ecang_order a
			LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
			LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
		WHERE
			a.datePaidPlatform >= "' . $last_start . '" 
			AND a.datePaidPlatform < "' . $last_end . '" 
			AND a.`status` = 4 
			AND c.saleStatus = 2 
		GROUP BY
			warehouseSku 
		) a 
	GROUP BY
		warehouseSku 
	) a 
GROUP BY
	is_growth
ORDER BY
	`name`;
        ');
        $this->assign('is_growth', json_encode($is_growth));

        $growth_num = $model->query('
SELECT
	is_growth name,
	IF
	( SUM( growth_num ) < 0, - SUM( growth_num ), SUM( growth_num ) ) `value` 
FROM
	(
	SELECT
		warehouseSku,
		SUM( current ) current,
		SUM( last ) last,
	IF
		( SUM( current ) > SUM( last ), "增(销量)", "减(销量)" ) is_growth,
		SUM( current ) - SUM( last ) growth_num 
	FROM
		(
		SELECT
			b.warehouseSku,
			SUM( b.qty ) current,
			0 AS last 
		FROM
			mu_ecang_order a
			LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
			LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
		WHERE
			a.datePaidPlatform >= "' . $start . '" 
			AND a.datePaidPlatform < "' . $end . '" 
			AND a.`status` = 4 
			AND c.saleStatus = 2 
		GROUP BY
			warehouseSku UNION ALL
		SELECT
			b.warehouseSku,
			0 AS current,
			SUM( b.qty ) last 
		FROM
			mu_ecang_order a
			LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
			LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
		WHERE
			a.datePaidPlatform >= "' . $last_start . '" 
			AND a.datePaidPlatform < "' . $last_end . '" 
			AND a.`status` = 4 
			AND c.saleStatus = 2 
		GROUP BY
			warehouseSku 
		) a 
	GROUP BY
		warehouseSku 
	) a 
GROUP BY
	is_growth
ORDER BY
	`name`;        
        ');
        $this->assign('growth_num', json_encode($growth_num));

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws DbException
     */
    public function wayfair(): \think\response\View
    {
        $platform = $this->request->get('platform', 'wayfairnew', 'htmlspecialchars');
        $this->assign('platform', $platform);

        $start = $this->request->get('start', date('Y-m-01 00:00:00'), 'htmlspecialchars');
        $this->assign('start', $start);
        $end = $this->request->get('end', date('Y-m-d 00:00:00'), 'htmlspecialchars');
        $this->assign('end', $end);

        $last_diff = $this->request->get('last_diff', 'diff_rate', 'htmlspecialchars');
        $this->assign('last_diff', $last_diff);
        $last_order = $this->request->get('last_order', 'DESC', 'htmlspecialchars');
        $this->assign('last_order', $last_order);
        $last_start = date('Y-m-01 00:00:00', strtotime('-1 month', strtotime($start)));
        $last_end = date('Y-m-d 00:00:00', strtotime('-1 month', strtotime($end)));

        $last2_diff = $this->request->get('last2_diff', 'diff_rate', 'htmlspecialchars');
        $this->assign('last2_diff', $last2_diff);
        $last2_order = $this->request->get('last2_order', 'DESC', 'htmlspecialchars');
        $this->assign('last2_order', $last2_order);
        $last2_start = date('Y-m-01 00:00:00', strtotime('-2 month', strtotime($start)));
        $last2_end = date('Y-m-d 00:00:00', strtotime('-2 month', strtotime($end)));

        $week_diff = $this->request->get('week_diff', 'diff_rate', 'htmlspecialchars');
        $this->assign('week_diff', $week_diff);
        $week_order = $this->request->get('week_order', 'DESC', 'htmlspecialchars');
        $this->assign('week_order', $week_order);
        $last_week = date('Y-m-d 00:00:00', strtotime('-7 day', strtotime($end)));
        $last2_week = date('Y-m-d 00:00:00', strtotime('-14 day', strtotime($end)));

        $model = new ProductModel();
        $saleList = $model->query('
SELECT
	SUM( qty ) current,
	SUM( last_qty ) last,
	SUM( qty ) - SUM( last_qty ) diff,
	ROUND((SUM(qty) - SUM(last_qty)) / SUM(last_qty), 4) diff_rate,
	warehouseSku,
	productImages
FROM
	(
	SELECT
		SUM( b.qty ) qty,
		0 AS last_qty,
		b.warehouseSku,
		c.productImages
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
		LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
	WHERE
		a.datePaidPlatform >= "' . $start . '" 
		AND a.datePaidPlatform < "' . $end . '" 
		AND a.platform = "' . $platform . '" 
		AND c.saleStatus = 2 
		AND a.`status` = 4
	GROUP BY
		warehouseSku,
		productImages	UNION ALL
	SELECT 0 AS
		qty,
		SUM( b.qty ) last_qty,
		b.warehouseSku,
		c.productImages
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
		LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
	WHERE
		a.datePaidPlatform >= "' . $last_start . '" 
		AND a.datePaidPlatform < "' . $last_end . '" 
		AND a.platform = "' . $platform . '" 
		AND c.saleStatus = 2 
		AND a.`status` = 4
	GROUP BY
		warehouseSku,
		productImages
	) a 
GROUP BY
	warehouseSku,
	productImages
ORDER BY
	' . $last_diff . ' ' . $last_order . ';
        ');
        $this->assign('saleList', $saleList);

        $sale2List = $model->query('
SELECT
	SUM( qty ) current,
	SUM( last_qty ) last,
	SUM( qty ) - SUM( last_qty ) diff,
	ROUND((SUM(qty) - SUM(last_qty)) / SUM(last_qty), 4) diff_rate,
	warehouseSku,
	productImages
FROM
	(
	SELECT
		SUM( b.qty ) qty,
		0 AS last_qty,
		b.warehouseSku,
		c.productImages
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
		LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
	WHERE
		a.datePaidPlatform >= "' . $start . '" 
		AND a.datePaidPlatform < "' . $end . '" 
		AND a.platform = "' . $platform . '" 
		AND c.saleStatus = 2 
		AND a.`status` = 4
	GROUP BY
		warehouseSku,
		productImages	UNION ALL
	SELECT 0 AS
		qty,
		SUM( b.qty ) last_qty,
		b.warehouseSku,
		c.productImages
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
		LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
	WHERE
		a.datePaidPlatform >= "' . $last2_start . '" 
		AND a.datePaidPlatform < "' . $last2_end . '" 
		AND a.platform = "' . $platform . '" 
		AND c.saleStatus = 2 
		AND a.`status` = 4
	GROUP BY
		warehouseSku,
		productImages
	) a 
GROUP BY
	warehouseSku,
	productImages
ORDER BY
	' . $last2_diff . ' ' . $last2_order . ';
        ');
        $this->assign('sale2List', $sale2List);

        $weekList = $model->query('
SELECT
	SUM( qty ) current,
	SUM( last_qty ) last,
	SUM( qty ) - SUM( last_qty ) diff,
	ROUND((SUM(qty) - SUM(last_qty)) / SUM(last_qty), 4) diff_rate,
	warehouseSku,
	productImages
FROM
	(
	SELECT
		SUM( b.qty ) qty,
		0 AS last_qty,
		b.warehouseSku,
		c.productImages
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
		LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
	WHERE
		a.datePaidPlatform >= "' . $last_week . '" 
		AND a.datePaidPlatform < "' . $end . '" 
		AND a.platform = "' . $platform . '" 
		AND c.saleStatus = 2 
		AND a.`status` = 4
	GROUP BY
		warehouseSku,
		productImages	UNION ALL
	SELECT 0 AS
		qty,
		SUM( b.qty ) last_qty,
		b.warehouseSku,
		c.productImages
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
		LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
	WHERE
		a.datePaidPlatform >= "' . $last2_week . '" 
		AND a.datePaidPlatform < "' . $last_week . '" 
		AND a.platform = "' . $platform . '" 
		AND c.saleStatus = 2 
		AND a.`status` = 4
	GROUP BY
		warehouseSku,
		productImages
	) a 
GROUP BY
	warehouseSku,
	productImages
ORDER BY
	' . $week_diff . ' ' . $week_order . ';
        ');
        $this->assign('weekList', $weekList);

        $is_growth = $model->query('
SELECT
	is_growth name,
	COUNT( is_growth ) value 
FROM
	(
	SELECT
		warehouseSku,
		SUM( current ) current,
		SUM( last ) last,
	CASE	
			WHEN SUM( current ) > SUM( last ) THEN
			"增(Sku个数)" 
			WHEN SUM( current ) = SUM( last ) THEN
			"平(Sku个数)" ELSE "减(Sku个数)" 
		END is_growth,
		SUM( current ) - SUM( last ) growth_num 
	FROM
		(
		SELECT
			b.warehouseSku,
			SUM( b.qty ) current,
			0 AS last 
		FROM
			mu_ecang_order a
			LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
			LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
		WHERE
			a.datePaidPlatform >= "' . $start . '" 
			AND a.datePaidPlatform < "' . $end . '" 
			AND a.`status` = 4 
			AND c.saleStatus = 2 
			AND a.platform = "' . $platform . '"
		GROUP BY
			warehouseSku UNION ALL
		SELECT
			b.warehouseSku,
			0 AS current,
			SUM( b.qty ) last 
		FROM
			mu_ecang_order a
			LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
			LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
		WHERE
			a.datePaidPlatform >= "' . $last_start . '" 
			AND a.datePaidPlatform < "' . $last_end . '" 
			AND a.`status` = 4 
			AND c.saleStatus = 2 
			AND a.platform = "' . $platform . '"
		GROUP BY
			warehouseSku 
		) a 
	GROUP BY
		warehouseSku 
	) a 
GROUP BY
	is_growth
ORDER BY
	`name`;
        ');
        $this->assign('is_growth', json_encode($is_growth));

        $growth_num = $model->query('
SELECT
	is_growth name,
	IF
	( SUM( growth_num ) < 0, - SUM( growth_num ), SUM( growth_num ) ) `value` 
FROM
	(
	SELECT
		warehouseSku,
		SUM( current ) current,
		SUM( last ) last,
	IF
		( SUM( current ) > SUM( last ), "增(销量)", "减(销量)" ) is_growth,
		SUM( current ) - SUM( last ) growth_num 
	FROM
		(
		SELECT
			b.warehouseSku,
			SUM( b.qty ) current,
			0 AS last 
		FROM
			mu_ecang_order a
			LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
			LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
		WHERE
			a.datePaidPlatform >= "' . $start . '" 
			AND a.datePaidPlatform < "' . $end . '" 
			AND a.`status` = 4 
			AND c.saleStatus = 2 
			AND a.platform = "' . $platform . '"
		GROUP BY
			warehouseSku UNION ALL
		SELECT
			b.warehouseSku,
			0 AS current,
			SUM( b.qty ) last 
		FROM
			mu_ecang_order a
			LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
			LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
		WHERE
			a.datePaidPlatform >= "' . $last_start . '" 
			AND a.datePaidPlatform < "' . $last_end . '" 
			AND a.`status` = 4 
			AND c.saleStatus = 2 
			AND a.platform = "' . $platform . '"
		GROUP BY
			warehouseSku 
		) a 
	GROUP BY
		warehouseSku 
	) a 
GROUP BY
	is_growth
ORDER BY
	`name`;        
        ');
        $this->assign('growth_num', json_encode($growth_num));

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws DbException
     */
    public function wayfair_only(): \think\response\View
    {
        $platform = $this->request->get('platform', 'wayfairnew', 'htmlspecialchars');
        $this->assign('platform', $platform);

        $start = $this->request->get('start', date('Y-m-01 00:00:00'), 'htmlspecialchars');
        $this->assign('start', $start);
        $end = $this->request->get('end', date('Y-m-d 00:00:00'), 'htmlspecialchars');
        $this->assign('end', $end);

        $last_diff = $this->request->get('last_diff', 'diff_rate', 'htmlspecialchars');
        $this->assign('last_diff', $last_diff);
        $last_order = $this->request->get('last_order', 'DESC', 'htmlspecialchars');
        $this->assign('last_order', $last_order);
        $last_start = date('Y-m-01 00:00:00', strtotime('-1 month', strtotime($start)));
        $last_end = date('Y-m-d 00:00:00', strtotime('-1 month', strtotime($end)));

        $last2_diff = $this->request->get('last2_diff', 'diff_rate', 'htmlspecialchars');
        $this->assign('last2_diff', $last2_diff);
        $last2_order = $this->request->get('last2_order', 'DESC', 'htmlspecialchars');
        $this->assign('last2_order', $last2_order);
        $last2_start = date('Y-m-01 00:00:00', strtotime('-2 month', strtotime($start)));
        $last2_end = date('Y-m-d 00:00:00', strtotime('-2 month', strtotime($end)));

        $week_diff = $this->request->get('week_diff', 'diff_rate', 'htmlspecialchars');
        $this->assign('week_diff', $week_diff);
        $week_order = $this->request->get('week_order', 'DESC', 'htmlspecialchars');
        $this->assign('week_order', $week_order);
        $last_week = date('Y-m-d 00:00:00', strtotime('-7 day', strtotime($end)));
        $last2_week = date('Y-m-d 00:00:00', strtotime('-14 day', strtotime($end)));

        $model = new ProductModel();
        $saleList = $model->query('
SELECT
	SUM( qty ) current,
	SUM( last_qty ) last,
	SUM( qty ) - SUM( last_qty ) diff,
	ROUND((SUM(qty) - SUM(last_qty)) / SUM(last_qty), 4) diff_rate,
	warehouseSku,
	productImages
FROM
	(
	SELECT
		SUM( b.qty ) qty,
		0 AS last_qty,
		b.warehouseSku,
		c.productImages
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
		LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
	WHERE
		a.datePaidPlatform >= "' . $start . '" 
		AND a.datePaidPlatform < "' . $end . '" 
		AND a.platform = "' . $platform . '" 
		AND c.saleStatus = 2 
		AND a.`status` = 4
	GROUP BY
		warehouseSku,
		productImages	UNION ALL
	SELECT 0 AS
		qty,
		SUM( b.qty ) last_qty,
		b.warehouseSku,
		c.productImages
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
		LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
	WHERE
		a.datePaidPlatform >= "' . $last_start . '" 
		AND a.datePaidPlatform < "' . $last_end . '" 
		AND a.platform = "' . $platform . '" 
		AND c.saleStatus = 2 
		AND a.`status` = 4
	GROUP BY
		warehouseSku,
		productImages
	) a 
GROUP BY
	warehouseSku,
	productImages
ORDER BY
	' . $last_diff . ' ' . $last_order . ';
        ');
        $this->assign('saleList', $saleList);

        $sale2List = $model->query('
SELECT
	SUM( qty ) current,
	SUM( last_qty ) last,
	SUM( qty ) - SUM( last_qty ) diff,
	ROUND((SUM(qty) - SUM(last_qty)) / SUM(last_qty), 4) diff_rate,
	warehouseSku,
	productImages
FROM
	(
	SELECT
		SUM( b.qty ) qty,
		0 AS last_qty,
		b.warehouseSku,
		c.productImages
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
		LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
	WHERE
		a.datePaidPlatform >= "' . $start . '" 
		AND a.datePaidPlatform < "' . $end . '" 
		AND a.platform = "' . $platform . '" 
		AND c.saleStatus = 2 
		AND a.`status` = 4
	GROUP BY
		warehouseSku,
		productImages	UNION ALL
	SELECT 0 AS
		qty,
		SUM( b.qty ) last_qty,
		b.warehouseSku,
		c.productImages
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
		LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
	WHERE
		a.datePaidPlatform >= "' . $last2_start . '" 
		AND a.datePaidPlatform < "' . $last2_end . '" 
		AND a.platform = "' . $platform . '" 
		AND c.saleStatus = 2 
		AND a.`status` = 4
	GROUP BY
		warehouseSku,
		productImages
	) a 
GROUP BY
	warehouseSku,
	productImages
ORDER BY
	' . $last2_diff . ' ' . $last2_order . ';
        ');
        $this->assign('sale2List', $sale2List);

        $weekList = $model->query('
SELECT
	SUM( qty ) current,
	SUM( last_qty ) last,
	SUM( qty ) - SUM( last_qty ) diff,
	ROUND((SUM(qty) - SUM(last_qty)) / SUM(last_qty), 4) diff_rate,
	warehouseSku,
	productImages
FROM
	(
	SELECT
		SUM( b.qty ) qty,
		0 AS last_qty,
		b.warehouseSku,
		c.productImages
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
		LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
	WHERE
		a.datePaidPlatform >= "' . $last_week . '" 
		AND a.datePaidPlatform < "' . $end . '" 
		AND a.platform = "' . $platform . '" 
		AND c.saleStatus = 2 
		AND a.`status` = 4
	GROUP BY
		warehouseSku,
		productImages	UNION ALL
	SELECT 0 AS
		qty,
		SUM( b.qty ) last_qty,
		b.warehouseSku,
		c.productImages
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
		LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
	WHERE
		a.datePaidPlatform >= "' . $last2_week . '" 
		AND a.datePaidPlatform < "' . $last_week . '" 
		AND a.platform = "' . $platform . '" 
		AND c.saleStatus = 2 
		AND a.`status` = 4
	GROUP BY
		warehouseSku,
		productImages
	) a 
GROUP BY
	warehouseSku,
	productImages
ORDER BY
	' . $week_diff . ' ' . $week_order . ';
        ');
        $this->assign('weekList', $weekList);

        $is_growth = $model->query('
SELECT
	is_growth name,
	COUNT( is_growth ) value 
FROM
	(
	SELECT
		warehouseSku,
		SUM( current ) current,
		SUM( last ) last,
	CASE	
			WHEN SUM( current ) > SUM( last ) THEN
			"增(Sku个数)" 
			WHEN SUM( current ) = SUM( last ) THEN
			"平(Sku个数)" ELSE "减(Sku个数)" 
		END is_growth,
		SUM( current ) - SUM( last ) growth_num 
	FROM
		(
		SELECT
			b.warehouseSku,
			SUM( b.qty ) current,
			0 AS last 
		FROM
			mu_ecang_order a
			LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
			LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
		WHERE
			a.datePaidPlatform >= "' . $start . '" 
			AND a.datePaidPlatform < "' . $end . '" 
			AND a.`status` = 4 
			AND c.saleStatus = 2 
			AND a.platform = "' . $platform . '"
		GROUP BY
			warehouseSku UNION ALL
		SELECT
			b.warehouseSku,
			0 AS current,
			SUM( b.qty ) last 
		FROM
			mu_ecang_order a
			LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
			LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
		WHERE
			a.datePaidPlatform >= "' . $last_start . '" 
			AND a.datePaidPlatform < "' . $last_end . '" 
			AND a.`status` = 4 
			AND c.saleStatus = 2 
			AND a.platform = "' . $platform . '"
		GROUP BY
			warehouseSku 
		) a 
	GROUP BY
		warehouseSku 
	) a 
GROUP BY
	is_growth
ORDER BY
	`name`;
        ');
        $this->assign('is_growth', json_encode($is_growth));

        $growth_num = $model->query('
SELECT
	is_growth name,
	IF
	( SUM( growth_num ) < 0, - SUM( growth_num ), SUM( growth_num ) ) `value` 
FROM
	(
	SELECT
		warehouseSku,
		SUM( current ) current,
		SUM( last ) last,
	IF
		( SUM( current ) > SUM( last ), "增(销量)", "减(销量)" ) is_growth,
		SUM( current ) - SUM( last ) growth_num 
	FROM
		(
		SELECT
			b.warehouseSku,
			SUM( b.qty ) current,
			0 AS last 
		FROM
			mu_ecang_order a
			LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
			LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
		WHERE
			a.datePaidPlatform >= "' . $start . '" 
			AND a.datePaidPlatform < "' . $end . '" 
			AND a.`status` = 4 
			AND c.saleStatus = 2 
			AND a.platform = "' . $platform . '"
		GROUP BY
			warehouseSku UNION ALL
		SELECT
			b.warehouseSku,
			0 AS current,
			SUM( b.qty ) last 
		FROM
			mu_ecang_order a
			LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
			LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
		WHERE
			a.datePaidPlatform >= "' . $last_start . '" 
			AND a.datePaidPlatform < "' . $last_end . '" 
			AND a.`status` = 4 
			AND c.saleStatus = 2 
			AND a.platform = "' . $platform . '"
		GROUP BY
			warehouseSku 
		) a 
	GROUP BY
		warehouseSku 
	) a 
GROUP BY
	is_growth
ORDER BY
	`name`;        
        ');
        $this->assign('growth_num', json_encode($growth_num));

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    public function wayfair_growth($sku, $platform): \think\response\View
    {
        $sale_start = $this->request->get('sale_start', '2024-01-01 00:00:00', 'htmlspecialchars');
        $this->assign('sale_start', $sale_start);
        $sale_start = date('Y-m-d 00:00:00', strtotime('-1 month', strtotime($sale_start)));

        $sale_end = $this->request->get('sale_end', date('Y-m-d 00:00:00'), 'htmlspecialchars');
        $this->assign('sale_end', $sale_end);

        $model = new ProductModel();
        $data = $model->query('
SELECT
	`month`,
	qty,
	lag ( qty ) over ( ORDER BY MONTH ASC ) last_qty,
	ROUND(
		( qty - lag ( qty ) over ( ORDER BY MONTH ASC ) ) / lag ( qty ) over ( ORDER BY MONTH ASC ),
		4 
	) rate 
FROM
	(
	SELECT
		SUM( b.qty ) qty,
		b.warehouseSku,
		DATE_FORMAT( a.createdDate, "%Y-%m" ) MONTH 
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id 
	WHERE
		`status` = 4 
		AND createdDate >= "' . $sale_start . '" 
		AND createdDate < "' . $sale_end . '" 
		AND b.warehouseSku = "' . $sku . '" 
		AND a.platform = "' . $platform . '" 
	GROUP BY
		MONTH,
	warehouseSku 
	) a;
        ');

        $this->assign('month', implode('","', array_column($data, 'month')));
        $this->assign('qty', implode(',', array_column($data, 'qty')));

        array_shift($data);
        $this->assign('month2', implode('","', array_column($data, 'month')));
        $this->assign('rate', implode(',', array_column($data, 'rate')));

        $this->assign('sku', $sku);
        return view();
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    public function sku_growth($sku): \think\response\View
    {
        $sale_start = $this->request->get('sale_start', '2024-01-01 00:00:00', 'htmlspecialchars');
        $this->assign('sale_start', $sale_start);
        $sale_start = date('Y-m-d 00:00:00', strtotime('-1 month', strtotime($sale_start)));

        $sale_end = $this->request->get('sale_end', date('Y-m-d 00:00:00'), 'htmlspecialchars');
        $this->assign('sale_end', $sale_end);

        $model = new ProductModel();
        $data = $model->query('
SELECT
	`month`,
	qty,
	lag ( qty ) over ( ORDER BY MONTH ASC ) last_qty,
	ROUND(
		( qty - lag ( qty ) over ( ORDER BY MONTH ASC ) ) / lag ( qty ) over ( ORDER BY MONTH ASC ),
		4 
	) rate 
FROM
	(
	SELECT
		SUM( b.qty ) qty,
		b.warehouseSku,
		DATE_FORMAT( a.createdDate, "%Y-%m" ) MONTH 
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id 
	WHERE
		`status` = 4 
		AND createdDate >= "' . $sale_start . '" 
		AND createdDate < "' . $sale_end . '" 
		AND b.warehouseSku = "' . $sku . '" 
	GROUP BY
		MONTH,
	warehouseSku 
	) a;
        ');

        $this->assign('month', implode('","', array_column($data, 'month')));
        $this->assign('qty', implode(',', array_column($data, 'qty')));

        array_shift($data);
        $this->assign('month2', implode('","', array_column($data, 'month')));
        $this->assign('rate', implode(',', array_column($data, 'rate')));

        $this->assign('sku', $sku);
        return view();
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    public function stock_sale(): \think\response\View
    {
        $date = $this->request->get('date', date('Y-m-d'));
        $this->assign('date', $date);

        $date = date('Ymd', strtotime($date));
        $last_date = date('Ymd', strtotime('-1 month', strtotime($date)));
        $day = intval((strtotime($date) - strtotime($last_date)) / 24 /60 /60);

        $model = new ProductModel();
        $data1 = $model->query('
SELECT
	a.product_sku,
	a.warehouse_code,
	b.qty month_qty,
	a.qty now_qty,
	b.qty - a.qty sale_qty,
	a.stock_age,
	c.productImages,
	d.user_name 
FROM
	( SELECT SUM( sellable_quantity ) qty, product_sku, warehouse_code, stock_age FROM mu_lc_inventory_batch WHERE created_date = ' . $date . ' GROUP BY product_sku, warehouse_code, stock_age ) a
	LEFT JOIN (
	SELECT
		SUM( sellable_quantity ) qty,
		product_sku,
		warehouse_code,
		stock_age + ' . $day . ' stock_age 
	FROM
		mu_lc_inventory_batch 
	WHERE
		created_date = ' . $last_date . ' 
	GROUP BY
		product_sku,
		warehouse_code,
		stock_age 
	) b ON a.product_sku = b.product_sku 
	AND a.warehouse_code = b.warehouse_code 
	AND a.stock_age = b.stock_age
	LEFT JOIN mu_ecang_product c ON a.product_sku = c.productSku 
	LEFT JOIN mu_ecang_user d ON c.personSellerId = d.user_id 
WHERE
	c.saleStatus != 18 
	AND c.saleStatus != 19 
ORDER BY
	stock_age DESC,
	product_sku,
	warehouse_code;
        ');
        $this->assign('lc_list', $data1);


        $data2 = $model->query('
SELECT
	a.lecangsCode,
	a.warehouseCode,
	b.qty month_qty,
	a.qty now_qty,
	b.qty - a.qty sale_qty,
	a.inventoryAge,
	c.productImages,
	d.user_name 
FROM
	(
	SELECT
		SUM( goodsNum ) qty,
		SUBSTR( lecangsCode FROM 7 ) lecangsCode,
		warehouseCode,
		inventoryAge 
	FROM
		mu_le_inventory_batch 
	WHERE
		created_date = ' . $date . ' 
	GROUP BY
		lecangsCode,
		warehouseCode,
		inventoryAge 
	) a
	LEFT JOIN (
	SELECT
		SUM( goodsNum ) qty,
		SUBSTR( lecangsCode FROM 7 ) lecangsCode,
		warehouseCode,
		inventoryAge + ' . $day . ' inventoryAge 
	FROM
		mu_le_inventory_batch 
	WHERE
		created_date = ' . $last_date . ' 
	GROUP BY
		lecangsCode,
		warehouseCode,
		inventoryAge 
	) b ON a.lecangsCode = b.lecangsCode 
	AND a.warehouseCode = b.warehouseCode 
	AND a.inventoryAge = b.inventoryAge
	LEFT JOIN mu_ecang_product c ON a.lecangsCode = c.productSku 
	LEFT JOIN mu_ecang_user d ON c.personSellerId = d.user_id 
WHERE
	c.saleStatus != 18 
	AND c.saleStatus != 19 
ORDER BY
	inventoryAge DESC,
	lecangsCode,
	warehouseCode;
        ');

        $this->assign('le_list', $data2);
        return view();
    }

    /**
     * @throws DbException
     */
    public function four_zone(): \think\response\View
    {
        $sale_order = $this->request->get('sale_order', 'DESC', 'htmlspecialchars');
        $this->assign('sale_order', $sale_order);

        $sale_start = $this->request->get('sale_start', date('Y-01-01 00:00:00'), 'htmlspecialchars');
        $this->assign('sale_start', $sale_start);

        $sale_end = $this->request->get('sale_end', date('Y-m-d H:i:s'), 'htmlspecialchars');
        $this->assign('sale_end', $sale_end);

        $model = new ProductModel();
        $saleList = $model->query('
SELECT
	a.warehouseSku,
	a.count,
	b.countSum,
	a.count / b.countSum percent,
	c.productImages,
	c.productTitle
FROM
	(
	SELECT
	IF
		( a.zoneFormat < 5, 1, 0 ) fourZone,
		b.warehouseSku,
		COUNT( a.id ) count 
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id 
	WHERE
		a.datePaidPlatform >= "' . $sale_start . '" 
	    AND a.datePaidPlatform < "' . $sale_end . '" 
		AND a.`status` != 0 
	GROUP BY
		warehouseSku,
		fourZone 
	) a
	LEFT JOIN (
	SELECT
		b.warehouseSku,
		COUNT( a.id ) countSum 
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id 
	WHERE
		a.datePaidPlatform >= "' . $sale_start . '" 
	    AND a.datePaidPlatform < "' . $sale_end . '"
		AND a.`status` != 0 
	GROUP BY
		warehouseSku 
	) b ON a.warehouseSku = b.warehouseSku 
LEFT JOIN mu_ecang_product c ON a.warehouseSku = c.productSku
WHERE
	a.fourZone = 1 
ORDER BY
	count ' . $sale_order . ';
        ');
        $this->assign('saleList', $saleList);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    public function four_zone_detail(): \think\response\View
    {
        $sku = input('sku');
        if (empty($sku)) {
            $this->error('操作错误！', url('index'));
        }

        $model = new ProductModel();
        $list = $model->query('
SELECT
	a.warehouseSku,
	a.`month`,
	a.count,
	b.countSum,
	a.count / b.countSum percent 
FROM
	(
	SELECT
	IF
		( a.zoneFormat < 5, 1, 0 ) fourZone,
		b.warehouseSku,
		DATE_FORMAT( a.datePaidPlatform, "%Y-%m" ) `month`,
		COUNT( a.id ) count 
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id 
	WHERE
		a.`status` != 0 
		AND b.warehouseSku = "' . $sku . '" 
	GROUP BY
		warehouseSku,
		fourZone,
	MONTH 
	) a
	LEFT JOIN (
	SELECT
		b.warehouseSku,
		DATE_FORMAT( a.datePaidPlatform, "%Y-%m" ) `month`,
		COUNT( a.id ) countSum 
	FROM
		mu_ecang_order a
		LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id 
	WHERE
		a.`status` != 0 
		AND b.warehouseSku = "' . $sku . '" 
	GROUP BY
		warehouseSku,
	MONTH 
	) b ON a.warehouseSku = b.warehouseSku 
	AND a.`month` = b.`month` 
WHERE
	a.fourZone = 1
ORDER BY `month` ASC;
        ');

        $this->assign('month', '"' . implode('","', array_column($list, 'month')) . '"');
        $this->assign('percent', implode(',', array_column($list, 'percent')));
        $this->assign('product', ProductModel::get(['productSku' => $sku]));
        return view();
    }

    /**
     * @throws DbException
     */
    public function four_warehouse(): \think\response\View
    {
        $sale_day = $this->request->get('sale_day', date('Y-m-d'), 'htmlspecialchars');
        $sale_day_num = date('Ymd', strtotime($sale_day));
        $this->assign('sale_day', $sale_day);
        $two_weeks_day = date('Ymd', strtotime('-2 weeks',strtotime($sale_day)));

        $model = new ProductModel();
        $storePercent = $model->query('
SELECT
	COUNT( warehouseSku ) count,
	(
	SELECT
		COUNT( warehouseSku ) count 
	FROM
		(
		SELECT DISTINCT
			SUBSTR( lecangsCode FROM 7 ) warehouseSku 
		FROM
			mu_le_inventory_batch a
			LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode 
		WHERE
			a.created_date = ' . $sale_day_num . ' 
			AND a.warehouseCode IN ( "CAP", "PAW", "SAV", "HOU03", "HOU07", "HOU05", "NJF02" ) UNION
		SELECT DISTINCT
			masterSku warehouseSku 
		FROM
			mu_wyd_inventory_batch a
			LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode 
		WHERE
			a.created_date = ' . $sale_day_num . ' 
			AND a.warehouseCode IN ( "CAJW04", "NJJW03" ) 
		) a
		LEFT JOIN mu_ecang_product b ON a.warehouseSku = b.productSku 
	WHERE
		b.saleStatus = 2 
	) AS countSum,
	warehouseCount 
FROM
	(
	SELECT
		COUNT( a.warehouseSku ) warehouseCount,
		warehouseSku 
	FROM
		(
		SELECT DISTINCT
			SUBSTR( lecangsCode FROM 7 ) warehouseSku,
			b.warehouseBelong 
		FROM
			mu_le_inventory_batch a
			LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode 
		WHERE
			a.created_date = ' . $sale_day_num . ' 
			AND a.warehouseCode IN ( "CAP", "PAW", "SAV", "HOU03", "HOU07", "HOU05", "NJF02" ) UNION
		SELECT DISTINCT
			masterSku warehouseSku,
			b.warehouseBelong 
		FROM
			mu_wyd_inventory_batch a
			LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode 
		WHERE
			a.created_date = ' . $sale_day_num . ' 
			AND a.warehouseCode IN ( "CAJW04", "NJJW03" ) 
		) a
		LEFT JOIN mu_ecang_product b ON a.warehouseSku = b.productSku 
	WHERE
		b.saleStatus = 2 
	GROUP BY
		warehouseSku 
	) a 
GROUP BY
	warehouseCount,
	countSum 
ORDER BY
	warehouseCount ASC;
        ');
        $this->assign('storePercent', $storePercent);

        $sumPercent = $model->query('
SELECT
	SUM( goodsNum ) goodsNum,
	goodsNumSum,
	warehouseCount 
FROM
	(
	SELECT
		SUM( a.sum ) goodsNum,
		COUNT( a.warehouseBelong ) warehouseCount,
		warehouseSku,
		(
			(
			SELECT
				SUM( goodsNum ) sum 
			FROM
				mu_le_inventory_batch a
				LEFT JOIN mu_ecang_product b ON SUBSTR( a.lecangsCode FROM 7 ) = b.productSku
				LEFT JOIN mu_le_warehouse c ON a.warehouseCode = c.warehouseCode 
			WHERE
				b.saleStatus = 2 
				AND a.warehouseCode IN ( "CAP", "PAW", "SAV", "HOU03", "HOU07", "HOU05", "NJF02" ) 
				AND created_date = ' . $sale_day_num . ' 
				) + (
			SELECT
				SUM( inventoryNum ) sum 
			FROM
				mu_wyd_inventory_batch a
				LEFT JOIN mu_ecang_product b ON a.masterSku = b.productSku
				LEFT JOIN mu_le_warehouse c ON a.warehouseCode = c.warehouseCode 
			WHERE
				b.saleStatus = 2 
				AND a.warehouseCode IN ( "CAJW04", "NJJW03" ) 
				AND created_date = ' . $sale_day_num . ' 
			) 
		) AS goodsNumSum 
	FROM
		(
		SELECT
			SUM( sum ) sum,
			warehouseSku,
			warehouseBelong 
		FROM
			(
			SELECT
				SUM( goodsNum ) sum,
				SUBSTR( lecangsCode FROM 7 ) warehouseSku,
				c.warehouseBelong 
			FROM
				mu_le_inventory_batch a
				LEFT JOIN mu_ecang_product b ON SUBSTR( a.lecangsCode FROM 7 ) = b.productSku
				LEFT JOIN mu_le_warehouse c ON a.warehouseCode = c.warehouseCode 
			WHERE
				b.saleStatus = 2 
				AND a.warehouseCode IN ( "CAP", "PAW", "SAV", "HOU03", "HOU07", "HOU05", "NJF02" ) 
				AND created_date = ' . $sale_day_num . ' 
			GROUP BY
				warehouseSku,
				warehouseBelong UNION ALL
			SELECT
				SUM( inventoryNum ) sum,
				masterSku warehouseSku,
				c.warehouseBelong 
			FROM
				mu_wyd_inventory_batch a
				LEFT JOIN mu_ecang_product b ON a.masterSku = b.productSku
				LEFT JOIN mu_le_warehouse c ON a.warehouseCode = c.warehouseCode 
			WHERE
				b.saleStatus = 2 
				AND a.warehouseCode IN ( "CAJW04", "NJJW03" ) 
				AND created_date = ' . $sale_day_num . ' 
			GROUP BY
				warehouseSku,
				warehouseBelong 
			) a 
		GROUP BY
			warehouseSku,
			warehouseBelong 
		) a 
	GROUP BY
		warehouseSku 
	) a 
GROUP BY
	warehouseCount,
	goodsNumSum 
ORDER BY
	warehouseCount ASC;
        ');
        $this->assign('sumPercent', $sumPercent);


        $kindPic = $model->query('	
SELECT
	a.created_date,
	a.warehouseCount,
	a.count,
	b.skuDayCount 
FROM
	(
	SELECT
		COUNT( warehouseSku ) count,
		warehouseCount,
		created_date 
	FROM
		(
		SELECT
			warehouseSku,
			COUNT( warehouseBelong ) warehouseCount,
			created_date 
		FROM
			(
			SELECT DISTINCT
				SUBSTR( lecangsCode FROM 7 ) warehouseSku,
				b.warehouseBelong,
				created_date 
			FROM
				mu_le_inventory_batch a
				LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode 
			WHERE
				created_date >= ' . $two_weeks_day . ' 
				AND a.warehouseCode IN ( "CAP", "PAW", "SAV", "HOU03", "HOU07", "HOU05", "NJF02" ) 
			GROUP BY
				warehouseSku,
				warehouseBelong,
				created_date UNION
			SELECT DISTINCT
				masterSku warehouseSku,
				b.warehouseBelong,
				created_date 
			FROM
				mu_wyd_inventory_batch a
				LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode 
			WHERE
				created_date >= ' . $two_weeks_day . ' 
				AND a.warehouseCode IN ( "CAJW04", "NJJW03" ) 
			GROUP BY
				warehouseSku,
				warehouseBelong,
				created_date 
			) a
			LEFT JOIN mu_ecang_product b ON a.warehouseSku = b.productSku 
		WHERE
			b.saleStatus = 2 
		GROUP BY
			warehouseSku,
			created_date 
		) a 
	GROUP BY
		created_date,
		warehouseCount 
	) a
	LEFT JOIN (
	SELECT
		COUNT( warehouseSku ) skuDayCount,
		created_date 
	FROM
		(
		SELECT DISTINCT
			SUBSTR( lecangsCode FROM 7 ) warehouseSku,
			created_date 
		FROM
			mu_le_inventory_batch a
			LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode
			LEFT JOIN mu_ecang_product c ON SUBSTR( a.lecangsCode FROM 7 ) = c.productSku 
		WHERE
			created_date >= ' . $two_weeks_day . ' 
			AND a.warehouseCode IN ( "CAP", "PAW", "SAV", "HOU03", "HOU07", "HOU05", "NJF02" ) 
			AND c.saleStatus = 2 
		GROUP BY
			warehouseSku,
			created_date UNION
		SELECT DISTINCT
			masterSku warehouseSku,
			created_date 
		FROM
			mu_wyd_inventory_batch a
			LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode
			LEFT JOIN mu_ecang_product c ON a.masterSku = c.productSku 
		WHERE
			created_date >= ' . $two_weeks_day . ' 
			AND a.warehouseCode IN ( "CAJW04", "NJJW03" ) 
			AND c.saleStatus = 2 
		GROUP BY
			warehouseSku,
			created_date 
		) a 
	GROUP BY
		created_date 
	) b ON a.created_date = b.created_date 
ORDER BY
	created_date ASC,
	warehouseCount ASC;
        ');
        $kindOne = [];
        $kindTwo = [];
        $kindThree = [];
        $kindFour = [];
        foreach ($kindPic as $item) {
            if ($item['warehouseCount'] == 1) {
                $kindOne[] = round($item['count'] / $item['skuDayCount'], 4);
            } elseif ($item['warehouseCount'] == 2) {
                $kindTwo[] = round($item['count'] / $item['skuDayCount'], 4);
            } elseif ($item['warehouseCount'] == 3) {
                $kindThree[] = round($item['count'] / $item['skuDayCount'], 4);
            } elseif ($item['warehouseCount'] == 4) {
                $kindFour[] = round($item['count'] / $item['skuDayCount'], 4);
            }
        }
        $this->assign('kindOne', implode(',', $kindOne));
        $this->assign('kindTwo', implode(',', $kindTwo));
        $this->assign('kindThree', implode(',', $kindThree));
        $this->assign('kindFour', implode(',', $kindFour));
        $this->assign('date', implode(',', array_unique(array_column($kindPic,'created_date'))));
        $this->assign('dateString', "'" . implode("','", array_unique(array_column($kindPic,'created_date'))) . "'");

        $sumPic = $model->query('
SELECT
	a.created_date,
	a.warehouseCount,
	a.goodsNum,
	b.skuDaySum 
FROM
	(
	SELECT
		SUM( goodsNum ) goodsNum,
		warehouseCount,
		created_date 
	FROM
		(
		SELECT
			SUM( goodsNum ) goodsNum,
			COUNT( warehouseBelong ) warehouseCount,
			warehouseSku,
			created_date 
		FROM
			(
			SELECT
				SUM( goodsNum ) goodsNum,
				warehouseBelong,
				warehouseSku,
				created_date 
			FROM
				(
				SELECT
					SUM( goodsNum ) goodsNum,
					SUBSTR( lecangsCode FROM 7 ) warehouseSku,
					warehouseBelong,
					created_date 
				FROM
					mu_le_inventory_batch a
					LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode
					LEFT JOIN mu_ecang_product c ON SUBSTR( a.lecangsCode FROM 7 ) = c.productSku 
				WHERE
					created_date >= ' . $two_weeks_day . ' 
					AND a.warehouseCode IN ( "CAP", "PAW", "SAV", "HOU03", "HOU07", "HOU05", "NJF02" ) 
					AND c.saleStatus = 2 
				GROUP BY
					lecangsCode,
					warehouseBelong,
					created_date UNION ALL
				SELECT
					SUM( inventoryAvailableNum ) goodsNum,
					masterSku warehouseSku,
					warehouseBelong,
					created_date 
				FROM
					mu_wyd_inventory_batch a
					LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode
					LEFT JOIN mu_ecang_product c ON masterSku = c.productSku 
				WHERE
					created_date >= ' . $two_weeks_day . ' 
					AND a.warehouseCode IN ( "CAJW04", "NJJW03" ) 
					AND c.saleStatus = 2 
				GROUP BY
					warehouseSku,
					warehouseBelong,
					created_date 
				) a 
			GROUP BY
				warehouseBelong,
				warehouseSku,
				created_date 
			) a 
		GROUP BY
			warehouseSku,
			created_date 
		) a 
	GROUP BY
		warehouseCount,
		created_date 
	) a
	LEFT JOIN (
	SELECT
		SUM( goodsNum ) skuDaySum,
		created_date 
	FROM
		(
		SELECT
			SUM( goodsNum ) goodsNum,
			SUBSTR( lecangsCode FROM 7 ) warehouseSku,
			created_date 
		FROM
			mu_le_inventory_batch a
			LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode
			LEFT JOIN mu_ecang_product c ON SUBSTR( a.lecangsCode FROM 7 ) = c.productSku 
		WHERE
			created_date >= ' . $two_weeks_day . ' 
			AND a.warehouseCode IN ( "CAP", "PAW", "SAV", "HOU03", "HOU07", "HOU05", "NJF02" ) 
			AND c.saleStatus = 2 
		GROUP BY
			warehouseSku,
			created_date UNION ALL
		SELECT
			SUM( inventoryAvailableNum ) goodsNum,
			masterSku warehouseSku,
			created_date 
		FROM
			mu_wyd_inventory_batch a
			LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode
			LEFT JOIN mu_ecang_product c ON masterSku = c.productSku 
		WHERE
			created_date >= ' . $two_weeks_day . ' 
			AND a.warehouseCode IN ( "CAJW04", "NJJW03" ) 
			AND c.saleStatus = 2 
		GROUP BY
			warehouseSku,
			created_date 
		) a 
	GROUP BY
		created_date 
	) b ON a.created_date = b.created_date 
ORDER BY
	created_date ASC,
	warehouseCount ASC;
        ');
        $sumOne = [];
        $sumTwo = [];
        $sumThree = [];
        $sumFour = [];
        foreach ($sumPic as $item) {
            if ($item['warehouseCount'] == 1) {
                $sumOne[] = round($item['goodsNum'] / $item['skuDaySum'], 4);
            } elseif ($item['warehouseCount'] == 2) {
                $sumTwo[] = round($item['goodsNum'] / $item['skuDaySum'], 4);
            } elseif ($item['warehouseCount'] == 3) {
                $sumThree[] = round($item['goodsNum'] / $item['skuDaySum'], 4);
            } elseif ($item['warehouseCount'] == 4) {
                $sumFour[] = round($item['goodsNum'] / $item['skuDaySum'], 4);
            }
        }
        $this->assign('sumOne', implode(',', $sumOne));
        $this->assign('sumTwo', implode(',', $sumTwo));
        $this->assign('sumThree', implode(',', $sumThree));
        $this->assign('sumFour', implode(',', $sumFour));
        $this->assign('date2', implode(',', array_unique(array_column($sumPic,'created_date'))));
        $this->assign('dateString2', "'" . implode("','", array_unique(array_column($sumPic,'created_date'))) . "'");

        $userStore = $model->query('
SELECT
	COUNT( warehouseSku ) count,
	warehouseCount,
	a.user_name,
	b.userCount 
FROM
	(
	SELECT
		COUNT( a.warehouseSku ) warehouseCount,
		warehouseSku,
		c.user_name 
	FROM
		(
		SELECT DISTINCT
			SUBSTR( lecangsCode FROM 7 ) warehouseSku,
			warehouseBelong 
		FROM
			mu_le_inventory_batch a
			LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode 
		WHERE
			created_date = ' . $sale_day_num . ' 
			AND a.warehouseCode IN ( "CAP", "PAW", "SAV", "HOU03", "HOU07", "HOU05", "NJF02" ) UNION
		SELECT DISTINCT
			masterSku warehouseSku,
			warehouseBelong 
		FROM
			mu_wyd_inventory_batch a
			LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode 
		WHERE
			created_date = ' . $sale_day_num . ' 
			AND a.warehouseCode IN ( "CAJW04", "NJJW03" ) 
		) a
		LEFT JOIN mu_ecang_product b ON a.warehouseSku = b.productSku
		LEFT JOIN mu_ecang_user c ON b.personSellerId = c.user_id 
	WHERE
		b.saleStatus = 2 
	GROUP BY
		warehouseSku,
		user_name 
	) a
	LEFT JOIN (
	SELECT
		COUNT( a.warehouseSku ) userCount,
		c.user_name 
	FROM
		(
		SELECT DISTINCT
			SUBSTR( lecangsCode FROM 7 ) warehouseSku 
		FROM
			mu_le_inventory_batch a
			LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode 
		WHERE
			created_date = ' . $sale_day_num . ' 
			AND a.warehouseCode IN ( "CAP", "PAW", "SAV", "HOU03", "HOU07", "HOU05", "NJF02" ) UNION
		SELECT DISTINCT
			masterSku warehouseSku 
		FROM
			mu_wyd_inventory_batch a
			LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode 
		WHERE
			created_date = ' . $sale_day_num . ' 
			AND a.warehouseCode IN ( "CAJW04", "NJJW03" ) 
		) a
		LEFT JOIN mu_ecang_product b ON a.warehouseSku = b.productSku
		LEFT JOIN mu_ecang_user c ON b.personSellerId = c.user_id 
	WHERE
		b.saleStatus = 2 
	GROUP BY
		user_name 
	) b ON a.user_name = b.user_name 
GROUP BY
	warehouseCount,
	user_name,
	userCount 
ORDER BY
	warehouseCount DESC,
	count DESC;
        ');
        $sellerData = [];
        foreach ($userStore as $value) {
            $sellerData[$value['user_name']][$value['warehouseCount']] = number_format($value['count'] / $value['userCount'], 4);
        }
        $this->assign('sellerData', $sellerData);

        $userStoreSum = $model->query('
SELECT
	SUM( goodsNum ) sum,
	warehouseCount,
	a.user_name,
	b.goodsSumAll 
FROM
	(
	SELECT
		COUNT( a.warehouseSku ) warehouseCount,
		SUM( a.goodsNum ) goodsNum,
		warehouseSku,
		c.user_name 
	FROM
		(
		SELECT
			SUM( a.goodsNum ) goodsNum,
			warehouseSku,
			warehouseBelong 
		FROM
			(
			SELECT
				SUM( goodsNum ) goodsNum,
				SUBSTR( lecangsCode FROM 7 ) warehouseSku,
				warehouseBelong 
			FROM
				mu_le_inventory_batch a
				LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode 
			WHERE
				created_date = ' . $sale_day_num . ' 
				AND a.warehouseCode IN ( "CAP", "PAW", "SAV", "HOU03", "HOU07", "HOU05", "NJF02" ) 
			GROUP BY
				warehouseSku,
				warehouseBelong UNION ALL
			SELECT
				SUM( inventoryAvailableNum ) goodsNum,
				masterSku warehouseSku,
				warehouseBelong 
			FROM
				mu_wyd_inventory_batch a
				LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode 
			WHERE
				created_date = ' . $sale_day_num . ' 
				AND a.warehouseCode IN ( "CAJW04", "NJJW03" ) 
			GROUP BY
				warehouseSku,
				warehouseBelong 
			) a 
		GROUP BY
			warehouseSku,
			warehouseBelong 
		) a
		LEFT JOIN mu_ecang_product b ON a.warehouseSku = b.productSku
		LEFT JOIN mu_ecang_user c ON b.personSellerId = c.user_id 
	WHERE
		b.saleStatus = 2 
	GROUP BY
		warehouseSku,
		user_name 
	) a
	LEFT JOIN (
	SELECT
		SUM( a.goodsNum ) goodsSumAll,
		c.user_name 
	FROM
		(
		SELECT
			SUM( a.goodsNum ) goodsNum,
			warehouseSku,
			warehouseBelong 
		FROM
			(
			SELECT
				SUM( goodsNum ) goodsNum,
				SUBSTR( lecangsCode FROM 7 ) warehouseSku,
				warehouseBelong 
			FROM
				mu_le_inventory_batch a
				LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode 
			WHERE
				created_date = ' . $sale_day_num . ' 
				AND a.warehouseCode IN ( "CAP", "PAW", "SAV", "HOU03", "HOU07", "HOU05", "NJF02" ) 
			GROUP BY
				warehouseSku,
				warehouseBelong UNION ALL
			SELECT
				SUM( inventoryAvailableNum ) goodsNum,
				masterSku warehouseSku,
				warehouseBelong 
			FROM
				mu_wyd_inventory_batch a
				LEFT JOIN mu_le_warehouse b ON a.warehouseCode = b.warehouseCode 
			WHERE
				created_date = ' . $sale_day_num . ' 
				AND a.warehouseCode IN ( "CAJW04", "NJJW03" ) 
			GROUP BY
				warehouseSku,
				warehouseBelong 
			) a 
		GROUP BY
			warehouseSku,
			warehouseBelong 
		) a
		LEFT JOIN mu_ecang_product b ON a.warehouseSku = b.productSku
		LEFT JOIN mu_ecang_user c ON b.personSellerId = c.user_id 
	WHERE
		b.saleStatus = 2 
	GROUP BY
		user_name 
	) b ON a.user_name = b.user_name 
GROUP BY
	warehouseCount,
	user_name,
	goodsSumAll 
ORDER BY
	warehouseCount DESC,
	sum DESC;
        ');
        $sellerSumData = [];
        foreach ($userStoreSum as $value) {
            $sellerSumData[$value['user_name']][$value['warehouseCount']] = number_format($value['sum'] / $value['goodsSumAll'], 4);
        }
        $this->assign('sellerSumData', $sellerSumData);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws DataNotFoundException
     * @throws \PHPExcel_Writer_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function four_warehouse_sku_export($sale_day)
    {
        $sale_day_num = date('Ymd', strtotime($sale_day));

        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();
        $financeExcelInit = new FinanceExcelInit($objPHPExcel);
        $financeExcelInit->getFourWarehouseSkuSql(0, $sale_day_num);
        $objPHPExcel = $financeExcelInit->excelSheetSet();

        // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        $filename = date("YmdHis") . time() . mt_rand(100000, 999999);
        ob_end_clean();
        header('Content-Disposition:attachment;filename="'.$filename.'.xls"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }

    /**
     * @throws DataNotFoundException
     * @throws \PHPExcel_Writer_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function four_warehouse_sku_sum_export($sale_day)
    {
        $sale_day_num = date('Ymd', strtotime($sale_day));

        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();
        $financeExcelInit = new FinanceExcelInit($objPHPExcel);
        $financeExcelInit->getFourWarehouseSkuSumSql(0, $sale_day_num);
        $objPHPExcel = $financeExcelInit->excelSheetSet();

        // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        $filename = date("YmdHis") . time() . mt_rand(100000, 999999);
        ob_end_clean();
        header('Content-Disposition:attachment;filename="'.$filename.'.xls"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    public function amount_paid(): \think\response\View
    {
        $platform = $this->request->get('platform', 'wayfairnew', 'htmlspecialchars');
        $this->assign('platform', $platform);

        $sale_day = $this->request->get('sale_day', date('Y-m-d'), 'htmlspecialchars');
        $this->assign('sale_day', $sale_day);
        $six_month_day = date('Y-m-01', strtotime('-6 months', strtotime($sale_day)));

        $model = new ProductModel();
        $list = $model->query('
SELECT
	DATE_FORMAT( datePaidPlatform, "%Y-%m" ) month,
	ROUND( SUM( amountpaid ), 2 ) amount 
FROM
	mu_ecang_order 
WHERE
	platform = "' . $platform . '" 
	AND `status` = 4 
	AND datePaidPlatform >= "' . $six_month_day . ' 00:00:00" 
	AND datePaidPlatform < "' . $sale_day . ' 00:00:00" 
GROUP BY
	month
ORDER BY
    month ASC;
        ');
        $this->assign('month', '"' . implode('","', array_column($list, 'month')) . '"');
        $this->assign('amount', implode(',', array_column($list, 'amount')));

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    public function warehouse_tail(): \think\response\View
    {
        $model = new ProductModel();
        $list = $model->query('
SELECT
	SUM(total) sum,
	`month`,
	warehouseName 
FROM
	mu_finance_warehouse_tail 
GROUP BY
	`month`,
	warehouseName 
ORDER BY
	`month` ASC;
        ');

        $data = [];
        foreach ($list as $item) {
            $data[$item['month']][] = ['warehouseName' => $item['warehouseName'], 'sum' => $item['sum']];
        }

        unset($data[202310]);
        unset($data[202311]);
        foreach ($data as $k => $v) {
            $sum[] = [$k, $v[0]['sum'], $v[1]['sum']];
        }
        array_unshift($sum, ['month', '良仓', '乐歌']);
        $this->assign('sum', json_encode($sum));

        return view();
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    public function warehouse_rent(): \think\response\View
    {
        $model = new ProductModel();
        $list = $model->query('
SELECT
  DATE_FORMAT(CONCAT(b.`month`, "-01"), "%Y%m") AS `month`,
  IF(a.warehouse_code IN ("PAW", "CAP", "SAV", "HOU03", "HOU07", "HOU05"), "LE", "LC") AS warehouseName,
  CASE
    WHEN age BETWEEN 0 AND 29 THEN "000-030"
    WHEN age BETWEEN 30 AND 59 THEN "030-060"
    WHEN age BETWEEN 60 AND 89 THEN "060-090"
    WHEN age BETWEEN 90 AND 119 THEN "090-120"
    WHEN age BETWEEN 120 AND 149 THEN "120-150"
    WHEN age BETWEEN 150 AND 179 THEN "150-180"
    WHEN age BETWEEN 180 AND 209 THEN "180-210"
    WHEN age BETWEEN 210 AND 239 THEN "210-240"
    WHEN age BETWEEN 240 AND 269 THEN "240-270"
    WHEN age BETWEEN 270 AND 299 THEN "270-300"
    WHEN age BETWEEN 300 AND 329 THEN "300-330"
    WHEN age BETWEEN 330 AND 359 THEN "330-360"
    WHEN age BETWEEN 360 AND 449 THEN "360-450"
    WHEN age BETWEEN 450 AND 539 THEN "450-540"
    WHEN age BETWEEN 540 AND 719 THEN "540-720"
    ELSE "720+"
  END AS age_other,
  SUM(total) AS sum
FROM mu_finance_warehouse a
LEFT JOIN mu_finance_report b ON a.report_id = b.id
WHERE a.report_id >= 4
GROUP BY `month`, warehouseName, age_other;

        ');

        $monthList = $model->query('SELECT DISTINCT REPLACE ( `month`, "-", "" ) `month` FROM mu_finance_report WHERE id >= 4 ORDER BY `month` ASC;');
        $warehouseName = ['LC', 'LE'];
        $ageOther = ['000-030', '030-060','060-090','090-120','120-150','150-180','180-210','210-240','240-270','270-300','300-330','330-360','360-450','450-540','540-720', '720+'];
        $this->assign('month', implode(',', array_column($monthList, 'month')));

        $data = [];
        foreach ($monthList as $month) {
            foreach ($warehouseName as $warehouse) {
                foreach ($ageOther as $ageRange) {
                    $init = 0;
                    foreach ($list as $item) {
                        if ($item['month'] == $month['month']
                            && $item['warehouseName'] == $warehouse
                            && $item['age_other'] == $ageRange
                        ) {
                            $data[$warehouse][$ageRange][] = intval($item['sum']);
                            $init ++;
                        }
                    }
                    if ($init == 0) {
                        $data[$warehouse][$ageRange][] = 0;
                    }
                }
            }
        }

        $dataString = [];
        foreach ($data as $warehouse => $v) {
            foreach ($v as $range => $item) {
                $dataString[] = '{ name: "' . $warehouse . '：' . $range . '", type: "bar", stack: "' . $warehouse . '", data: [' . implode(',', $item) . '] }';
            }
        }

        $lc_list = $model->query('
SELECT
  DATE_FORMAT(CONCAT(b.`month`, "-01"), "%Y%m") AS `month`,
  FLOOR(SUM(total)) AS sum
FROM mu_finance_warehouse a
LEFT JOIN mu_finance_report b ON a.report_id = b.id
WHERE a.report_id >= 4
AND a.warehouse_code NOT IN ("PAW", "CAP", "SAV", "HOU03", "HOU07", "HOU05")
GROUP BY `month`;
        ');
        $initArr = [];
        foreach ($lc_list as $item) {
            $initArr[] = 0;
        }
        $dataString[] = "
{
    name: '',
    type: 'bar',
    stack: 'LC',
    data: [" . implode(',', $initArr) . "],
    label: {
        normal: {
            show: true,
            formatter: function (params) {
                var total = [" . implode(',', array_column($lc_list, 'sum')) . "];
                return total[params.dataIndex].toLocaleString();
            },
            position: 'top',
            fontSize: 14,
            textStyle: { color: 'black' }
        }
    }
}
        ";

        $le_list = $model->query('
SELECT
  DATE_FORMAT(CONCAT(b.`month`, "-01"), "%Y%m") AS `month`,
  FLOOR(SUM(total)) AS sum
FROM mu_finance_warehouse a
LEFT JOIN mu_finance_report b ON a.report_id = b.id
WHERE a.report_id >= 4
AND a.warehouse_code IN ("PAW", "CAP", "SAV", "HOU03", "HOU07", "HOU05")
GROUP BY `month`;
        ');
        $dataString[] = "
{
    name: '',
    type: 'bar',
    stack: 'LE',
    data: [" . implode(',', $initArr) . "],
    label: {
        normal: {
            show: true,
            formatter: function (params) {
                var total = [" . implode(',', array_column($le_list, 'sum')) . "];
                return total[params.dataIndex].toLocaleString();
            },
            position: 'top',
            fontSize: 14,
            textStyle: { color: 'black' }
        }
    }
}
        ";

        $this->assign('sum', implode(',', $dataString));

        return view();
    }

    /**
     * @throws DbException
     */
    public function platform_sale(): \think\response\View
    {
        $platform = $this->request->get('platform');
        if (!empty($platform)) {
            $platformList = explode(',', $platform);
            $platformWhere = 'AND a.platform IN ( "'. implode('","', $platformList) .'" )';
        } else {
            $platformList = "";
            $platformWhere = '';
        }
        $this->assign('platform', '"' . implode('","', $platformList) . '"');

        $start = $this->request->get('start', date('Y-m-01 00:00:00'), 'htmlspecialchars');
        $this->assign('start', $start);
        $end = $this->request->get('end', date('Y-m-d 00:00:00'), 'htmlspecialchars');
        $this->assign('end', $end);

        $order_field = $this->request->get('order_field', 'qty', 'htmlspecialchars');
        $this->assign('order_field', $order_field);

        $order_type = $this->request->get('order_type', 'DESC', 'htmlspecialchars');
        $this->assign('order_type', $order_type);

        $model = new ProductModel();
        $saleList = $model->query('
SELECT
	SUM( b.qty ) qty,
	ROUND( SUM( a.amountpaid ), 2 ) amount,
	b.warehouseSku,
	MAX( c.productImages ) productImages
FROM
	mu_ecang_order a
	JOIN mu_ecang_order_detail b ON a.id = b.order_id
	JOIN mu_ecang_product c ON b.warehouseSku = c.productSku
WHERE
	a.datePaidPlatform >= "' . $start . '"
	AND a.datePaidPlatform < "' . $end . '" 
	' . $platformWhere . ' 
	AND c.saleStatus = 2
	AND a.`status` = 4
GROUP BY
	b.warehouseSku,
	c.productImages
ORDER BY
	 ' . $order_field . ' ' . $order_type . ';
        ');
        $this->assign('saleList', $saleList);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws BindParamException
     * @throws PDOException
     */
    public function sku_sale_qty(): \think\response\View
    {
        $type = $this->request->get('type', '', 'intval');
        $this->assign('type', $type);
        $dateFormat = $type ? "%Y-%m" : "%Y-%m-%d";

        $sku = $this->request->get('sku', '', 'htmlspecialchars');
        $this->assign('sku', $sku);
        if (!empty($sku)) {
            $skuList = array_filter(preg_split('/\r\n|\r|\n/', $sku));
            $skuSql = 'AND b.warehouseSku IN ("' . implode('", "', $skuList) . '")';
        } else {
            $skuSql = '';
        }

        $name = $this->request->get('name', '', 'htmlspecialchars');
        $this->assign('name', $name);

        $start_date = $this->request->get('start_date', date('Y-m-01', strtotime('-1 day')), 'htmlspecialchars');
        $this->assign('start_date', $start_date);

        $end_date = $this->request->get('end_date', date('Y-m-d', strtotime('-1 day')), 'htmlspecialchars');
        $this->assign('end_date', $end_date);
        $end_date_top = date('Y-m-d', strtotime('+1 day', strtotime($end_date)));

        $model = new ProductModel();
        $storeList = $model->query('
SELECT
	DATE_FORMAT( a.datePaidPlatform, "' . $dateFormat . '" ) `name`,
	SUM( b.qty ) value
FROM
	mu_ecang_order a FORCE INDEX ( idx_ecang_order_status_date )
	LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
	LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
WHERE
	c.productTitle LIKE "%' . $name . '%" 
	AND c.saleStatus IN ( 2, 16, 18 ) 
	AND a.`status` = 4 
	AND a.datePaidPlatform >= "' . $start_date . '" 
	AND a.datePaidPlatform < "' . $end_date_top . '" 
	' . $skuSql . '
GROUP BY
	`name`;
        ');

        $storeList2 = $model->query('
SELECT
	DATE_FORMAT( a.datePaidPlatform, "' . $dateFormat . '" ) `name`,
	SUM( b.qty ) value
FROM
	mu_ecang_order a FORCE INDEX ( idx_ecang_order_status_date )
	LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
	LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
WHERE
	c.productTitle LIKE "%' . $name . '%" 
	AND c.saleStatus IN ( 2, 16, 18 ) 
	AND a.`status` = 4 
	AND a.datePaidPlatform >= "' . date('Y-m-d', strtotime('-1 year', strtotime($start_date))) . '" 
	AND a.datePaidPlatform < "' . date('Y-m-d', strtotime('-1 year', strtotime($end_date_top))) . '" 
	' . $skuSql . '
GROUP BY
	`name`;
        ');

        $storeList3 = $model->query('
SELECT
	DATE_FORMAT( a.datePaidPlatform, "' . $dateFormat . '" ) `name`,
	SUM( b.qty ) value
FROM
	mu_ecang_order a FORCE INDEX ( idx_ecang_order_status_date )
	LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
	LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
WHERE
	c.productTitle LIKE "%' . $name . '%" 
	AND c.saleStatus IN ( 2, 16, 18 ) 
	AND a.`status` = 4 
	AND a.datePaidPlatform >= "' . date('Y-m-d', strtotime('-2 year', strtotime($start_date))) . '" 
	AND a.datePaidPlatform < "' . date('Y-m-d', strtotime('-2 year', strtotime($end_date_top))) . '" 
	' . $skuSql . '
GROUP BY
	`name`;
        ');

        $storeData[] = [
            'date',
            '2024',
            '2025',
            '2026'
        ];

        $count = max(count($storeList), count($storeList2), count($storeList3));
        for ($i = 0; $i < $count; $i ++) {
            $storeData[] = [
                $type ? substr($storeList2[$i]['name'], -2) . '月' : substr($storeList2[$i]['name'], -5),
                $storeList3[$i]['value'] ?? 0,
                $storeList2[$i]['value'] ?? 0,
                $storeList[$i]['value'] ?? 0
            ];
        }
        $this->assign('storeData', json_encode($storeData));

        $average = $model->query('
WITH RECURSIVE months AS ( SELECT DATE( "' . $start_date . '" ) AS month_start UNION ALL SELECT DATE_ADD( month_start, INTERVAL 1 MONTH ) FROM months WHERE month_start < "' . $end_date . '" ) SELECT
DATE_FORMAT( m.month_start, "%Y-%m" ) AS name,
ROUND(IFNULL( SUM( b.qty ), 0 ) / ( DATEDIFF( LEAST( LAST_DAY( m.month_start ), "' . $end_date . '" ), GREATEST( m.month_start, "' . $start_date . '" ) ) + 1 ),2) AS value 
FROM
	months m
	LEFT JOIN mu_ecang_order a FORCE INDEX ( idx_ecang_order_status_date ) ON a.datePaidPlatform >= GREATEST( m.month_start, "' . $start_date . '" ) 
	AND a.datePaidPlatform < DATE_ADD( LEAST( LAST_DAY( m.month_start ), "' . $end_date . '" ), INTERVAL 1 DAY ) 
	AND a.STATUS = 4
	LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
	LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
WHERE
	c.productTitle LIKE "%' . $name .  '%" 
	AND c.saleStatus IN ( 2, 16, 18 ) 
	' . $skuSql . '
GROUP BY
	m.month_start 
ORDER BY
	m.month_start;
        ');

        $average2 = $model->query('
WITH RECURSIVE months AS ( SELECT DATE( "' . date('Y-m-d', strtotime('-1 year', strtotime($start_date))) . '" ) AS month_start UNION ALL SELECT DATE_ADD( month_start, INTERVAL 1 MONTH ) FROM months WHERE month_start < "' . date('Y-m-d', strtotime('-1 year', strtotime($end_date))) . '" ) SELECT
DATE_FORMAT( m.month_start, "%Y-%m" ) AS name,
ROUND(IFNULL( SUM( b.qty ), 0 ) / ( DATEDIFF( LEAST( LAST_DAY( m.month_start ), "' . date('Y-m-d', strtotime('-1 year', strtotime($end_date))) . '" ), GREATEST( m.month_start, "' . date('Y-m-d', strtotime('-1 year', strtotime($start_date))) . '" ) ) + 1 ),2) AS value 
FROM
	months m
	LEFT JOIN mu_ecang_order a FORCE INDEX ( idx_ecang_order_status_date ) ON a.datePaidPlatform >= GREATEST( m.month_start, "' . date('Y-m-d', strtotime('-1 year', strtotime($start_date))) . '" ) 
	AND a.datePaidPlatform < DATE_ADD( LEAST( LAST_DAY( m.month_start ), "' . date('Y-m-d', strtotime('-1 year', strtotime($end_date))) . '" ), INTERVAL 1 DAY ) 
	AND a.STATUS = 4
	LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
	LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
WHERE
	c.productTitle LIKE "%' . $name .  '%" 
	AND c.saleStatus IN ( 2, 16, 18 ) 
	' . $skuSql . '
GROUP BY
	m.month_start 
ORDER BY
	m.month_start;
        ');

        $average3 = $model->query('
WITH RECURSIVE months AS ( SELECT DATE( "' . date('Y-m-d', strtotime('-2 year', strtotime($start_date))) . '" ) AS month_start UNION ALL SELECT DATE_ADD( month_start, INTERVAL 1 MONTH ) FROM months WHERE month_start < "' . date('Y-m-d', strtotime('-2 year', strtotime($end_date))) . '" ) SELECT
DATE_FORMAT( m.month_start, "%Y-%m" ) AS name,
ROUND(IFNULL( SUM( b.qty ), 0 ) / ( DATEDIFF( LEAST( LAST_DAY( m.month_start ), "' . date('Y-m-d', strtotime('-2 year', strtotime($end_date))) . '" ), GREATEST( m.month_start, "' . date('Y-m-d', strtotime('-2 year', strtotime($start_date))) . '" ) ) + 1 ),2) AS value 
FROM
	months m
	LEFT JOIN mu_ecang_order a FORCE INDEX ( idx_ecang_order_status_date ) ON a.datePaidPlatform >= GREATEST( m.month_start, "' . date('Y-m-d', strtotime('-2 year', strtotime($start_date))) . '" ) 
	AND a.datePaidPlatform < DATE_ADD( LEAST( LAST_DAY( m.month_start ), "' . date('Y-m-d', strtotime('-2 year', strtotime($end_date))) . '" ), INTERVAL 1 DAY ) 
	AND a.STATUS = 4
	LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
	LEFT JOIN mu_ecang_product c ON b.warehouseSku = c.productSku 
WHERE
	c.productTitle LIKE "%' . $name .  '%" 
	AND c.saleStatus IN ( 2, 16, 18 ) 
	' . $skuSql . '
GROUP BY
	m.month_start 
ORDER BY
	m.month_start;
        ');

        $averageData[] = [
            'date',
            '2024',
            '2025',
            '2025'
        ];

        $count = max(count($average), count($average2), count($average3));
        for ($i = 0; $i < $count; $i ++) {
            $averageData[] = [
                substr($average2[$i]['name'], -2) . '月',
                $average3[$i]['value'] ?? 0,
                $average2[$i]['value'] ?? 0,
                $average[$i]['value'] ?? 0
            ];
        }
        $this->assign('averageData', json_encode($averageData));

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws BindParamException
     * @throws PDOException
     */
    public function sku_sale_profit(): \think\response\View
    {
        $sku = $this->request->get('sku', '', 'htmlspecialchars');
        $this->assign('sku', $sku);
        if (!empty($sku)) {
            $skuList = array_filter(preg_split('/\r\n|\r|\n/', $sku));
            $skuSql = 'AND a.warehouse_sku IN ("' . implode('", "', $skuList) . '")';
        } else {
            $skuSql = '';
        }

        $name = $this->request->get('name', '', 'htmlspecialchars');
        $this->assign('name', $name);

        $start_date = $this->request->get('start_date', date('Y-m', strtotime('-1 month')), 'htmlspecialchars');
        $this->assign('start_date', $start_date);

        $end_date = $this->request->get('end_date', date('Y-m', strtotime('-1 month')), 'htmlspecialchars');
        $this->assign('end_date', $end_date);
        $end_date_top = date('Y-m-d', strtotime('+1 day', strtotime($end_date . '-01')));

        $model = new ProductModel();
        $storeList = $model->query('
SELECT
	SUM( profit ) value,
	`month` `name`
FROM
	mu_finance_report_snapshot a
	LEFT JOIN mu_ecang_product b ON a.warehouse_sku = b.productSku 
WHERE
	b.productTitle LIKE "%' . $name . '%" 
	AND b.saleStatus IN ( 2, 16, 18 ) 
	AND a.`month` >= "' . $start_date . '"
	AND a.`month` <= "' . $end_date_top . '"
	' . $skuSql . '
GROUP BY
	`name` 
ORDER BY
	`name` ASC;
        ');

        $storeList2 = $model->query('
SELECT
	SUM( profit ) value,
	`month` `name`
FROM
	mu_finance_report_snapshot a
	LEFT JOIN mu_ecang_product b ON a.warehouse_sku = b.productSku 
WHERE
	b.productTitle LIKE "%' . $name . '%" 
	AND b.saleStatus IN ( 2, 16, 18 ) 
	AND a.`month` >= "' . date('Y-m', strtotime('-1 year', strtotime($start_date . '-01'))) . '"
	AND a.`month` <= "' . date('Y-m', strtotime('-1 year', strtotime($end_date_top . '-01'))) . '"
	' . $skuSql . '
GROUP BY
	`name` 
ORDER BY
	`name` ASC;
        ');

        $storeList3 = $model->query('
SELECT
	SUM( profit ) value,
	`month` `name`
FROM
	mu_finance_report_snapshot a
	LEFT JOIN mu_ecang_product b ON a.warehouse_sku = b.productSku 
WHERE
	b.productTitle LIKE "%' . $name . '%" 
	AND b.saleStatus IN ( 2, 16, 18 ) 
	AND a.`month` >= "' . date('Y-m', strtotime('-2 year', strtotime($start_date . '-01'))) . '"
	AND a.`month` <= "' . date('Y-m', strtotime('-2 year', strtotime($end_date_top . '-01'))) . '"
	' . $skuSql . '
GROUP BY
	`name` 
ORDER BY
	`name` ASC;
        ');

        $storeData[] = [
            'date',
            '2024',
            '2025',
            '2026'
        ];

        $count = max(count($storeList), count($storeList2), count($storeList3));
        for ($i = 0; $i < $count; $i ++) {
            $storeData[] = [
                substr($storeList2[$i]['name'], -2) . '月',
                $storeList3[$i]['value'] ?? 0,
                $storeList2[$i]['value'] ?? 0,
                $storeList[$i]['value'] ?? 0
            ];
        }
        $this->assign('storeData', json_encode($storeData));

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws DbException
     */
    public function warehouse_fbm(): \think\response\View
    {
        $sale_order = $this->request->get('sale_order', 'DESC', 'htmlspecialchars');
        $this->assign('sale_order', $sale_order);

        $sale_start = $this->request->get('sale_start', date('Y-m', strtotime('-1 month', strtotime(date('Y-m-01 00:00:00')))), 'htmlspecialchars');
        $this->assign('sale_start', $sale_start);

        $sale_end = $this->request->get('sale_end', date('Y-m'), 'htmlspecialchars');
        $this->assign('sale_end', $sale_end);

        $percent_order = $this->request->get('percent_order', 'DESC', 'htmlspecialchars');
        $this->assign('percent_order', $percent_order);

        $percent_start = $this->request->get('percent_start', date('Y-m', strtotime('-1 month', strtotime(date('Y-m-01 00:00:00')))), 'htmlspecialchars');
        $this->assign('percent_start', $percent_start);

        $percent_end = $this->request->get('percent_end', date('Y-m'), 'htmlspecialchars');
        $this->assign('percent_end', $percent_end);

        $model = new ProductModel();
        $saleList = $model->query('
SELECT
    t.main_sku,
    c.productTitle,
    c.productImages,
    t.total
FROM (
    SELECT
        a.main_sku,
        SUM(a.total) AS total
    FROM mu_finance_report b
    JOIN mu_finance_warehouse_fbm a
        ON a.report_id = b.id
    WHERE b.month BETWEEN "' . $sale_start . '" AND "' . $sale_end . '"
    GROUP BY a.main_sku
) t
LEFT JOIN mu_ecang_product c
    ON c.productSku = t.main_sku
ORDER BY t.total ' . $sale_order . ';
        ');
        $this->assign('saleList', $saleList);

        $percentList = $model->query('
SELECT
	a.*,
	b.total,
	b.total / a.sale_amount * 100 percent,
	c.productTitle,
	c.productImages 
FROM
	(
	SELECT
		b.warehouse_sku,
		SUM( b.sale_amount ) sale_amount 
	FROM
		(
		SELECT DISTINCT
			payment_id 
		FROM
			mu_finance_report a
			LEFT JOIN mu_finance_order_sale b ON b.report_id = a.id 
		WHERE
			a.`month` BETWEEN "' . $percent_start . '" AND "' . $percent_end . '"
		) a
		LEFT JOIN mu_finance_order_statistics b ON a.payment_id = b.payment_id 
	WHERE
		b.payment_id IS NOT NULL 
	GROUP BY
		warehouse_sku 
	) a
	LEFT JOIN (
	SELECT
		a.main_sku,
		SUM( a.total ) AS total 
	FROM
		mu_finance_report b
		JOIN mu_finance_warehouse_fbm a ON a.report_id = b.id 
	WHERE
		b.MONTH BETWEEN "' . $percent_start . '" AND "' . $percent_end . '"
	GROUP BY
		a.main_sku 
	) b ON a.warehouse_sku = b.main_sku
	LEFT JOIN mu_ecang_product c ON c.productSku = a.warehouse_sku 
WHERE
	c.saleStatus != 18 
	AND c.saleStatus != 19 
	AND c.saleStatus != 20 
ORDER BY
	percent ' . $percent_order . ';
        ');
        $this->assign('percentList', $percentList);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }
}
