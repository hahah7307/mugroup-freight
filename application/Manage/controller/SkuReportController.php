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
	            qty DESC;
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
    public function category(): \think\response\View
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

        $this->assign('category_1', json_encode($category_1));
        $this->assign('category_2', json_encode($category_2));

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
}
