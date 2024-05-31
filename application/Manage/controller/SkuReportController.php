<?php
namespace app\Manage\controller;

use app\Manage\model\ProductModel;
use think\db\exception\BindParamException;
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
        $sale_day = $this->request->get('sale_day', date('Y-m-d', strtotime('-2 day')), 'htmlspecialchars');
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
	created_date = ' . $sale_day_num . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19 UNION ALL
SELECT
	SUM( sellable_quantity ) AS value,
	ROUND( SUM( sellable_quantity * b.sp_unit_price ), 4) AS sum
FROM
	mu_lc_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.product_sku = b.productSku 
WHERE
	created_date = ' . $sale_day_num . '
	AND b.saleStatus != 18
	AND b.saleStatus != 19
	) a;        
        ');
        $this->assign('sum', $sum);

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
	created_date = ' . date('Ymd', strtotime('-1 month', strtotime($sale_day_num))) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19 UNION ALL
SELECT
	SUM( sellable_quantity ) AS value,
	ROUND( SUM( sellable_quantity * b.sp_unit_price ), 4) AS sum
FROM
	mu_lc_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.product_sku = b.productSku 
WHERE
	created_date = ' . date('Ymd', strtotime('-1 month', strtotime($sale_day_num))) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19
	) a;        
        ');
        $this->assign('last_sum', $last_sum);

        $monthQty = $model->query('
SELECT
	SUM( b.qty ) qty
FROM
	mu_ecang_order a
	LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id 
WHERE
	a.`status` = 4 
	AND a.dateWarehouseShipping >= "' . date('Y-m-d H:i:s', strtotime('-1 month', strtotime($sale_day_num))) . '" 
	AND a.dateWarehouseShipping < "' . date('Y-m-d H:i:s', strtotime($sale_day_num)) . ' ";      
        ');
        $this->assign('monthQty', $monthQty);

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
     * @throws DbException
     */
    public function growth(): \think\response\View
    {
        $start = $this->request->get('start', date('Y-m-01 00:00:00'), 'htmlspecialchars');
        $this->assign('start', $start);
        $end = $this->request->get('end', date('Y-m-d 00:00:00'), 'htmlspecialchars');
        $this->assign('end', $end);

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
		a.dateWarehouseShipping >= "' . $start . '" 
		AND a.dateWarehouseShipping < "' . $end . '" 
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
		a.dateWarehouseShipping >= "' . $last_start . '" 
		AND a.dateWarehouseShipping < "' . $last_end . '" 
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
	diff_rate ' . $last_order . ';
        ');
        $this->assign('saleList', $saleList);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws DbException
     */
    public function wayfair(): \think\response\View
    {
        $start = $this->request->get('start', date('Y-m-01 00:00:00'), 'htmlspecialchars');
        $this->assign('start', $start);
        $end = $this->request->get('end', date('Y-m-d 00:00:00'), 'htmlspecialchars');
        $this->assign('end', $end);

        $last_order = $this->request->get('last_order', 'DESC', 'htmlspecialchars');
        $this->assign('last_order', $last_order);
        $last_start = date('Y-m-01 00:00:00', strtotime('-1 month', strtotime($start)));
        $last_end = date('Y-m-d 00:00:00', strtotime('-1 month', strtotime($end)));

        $last2_order = $this->request->get('last2_order', 'DESC', 'htmlspecialchars');
        $this->assign('last2_order', $last2_order);
        $last2_start = date('Y-m-01 00:00:00', strtotime('-2 month', strtotime($start)));
        $last2_end = date('Y-m-d 00:00:00', strtotime('-2 month', strtotime($end)));

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
		a.dateWarehouseShipping >= "' . $start . '" 
		AND a.dateWarehouseShipping < "' . $end . '" 
		AND a.platform = "wayfairnew" 
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
		a.dateWarehouseShipping >= "' . $last_start . '" 
		AND a.dateWarehouseShipping < "' . $last_end . '" 
		AND a.platform = "wayfairnew" 
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
	diff_rate ' . $last_order . ';
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
		a.dateWarehouseShipping >= "' . $start . '" 
		AND a.dateWarehouseShipping < "' . $end . '" 
		AND a.platform = "wayfairnew" 
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
		a.dateWarehouseShipping >= "' . $last2_start . '" 
		AND a.dateWarehouseShipping < "' . $last2_end . '" 
		AND a.platform = "wayfairnew" 
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
	diff_rate ' . $last2_order . ';
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
		a.dateWarehouseShipping >= "' . $last_week . '" 
		AND a.dateWarehouseShipping < "' . $end . '" 
		AND a.platform = "wayfairnew" 
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
		a.dateWarehouseShipping >= "' . $last2_week . '" 
		AND a.dateWarehouseShipping < "' . $last_week . '" 
		AND a.platform = "wayfairnew" 
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
	diff_rate ' . $week_order . ';
        ');
        $this->assign('weekList', $weekList);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
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
}
