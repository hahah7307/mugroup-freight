<?php
namespace app\Home\controller;

use app\Manage\model\OrderModel;
use think\Controller;
use think\exception\DbException;

class ApiController extends Controller
{
    /**
     * @throws DbException
     */
    public function getSkuSaleState()
    {
        $ip = get_real_ip();
        if (!in_array($ip, ['127.0.0.1', '122.227.159.146', '139.224.106.228', '47.100.200.11'])) {
            echo json_encode(['code' => 100, 'msg' => '未被允许的请求！']);
            exit();
        }

        $post = $this->request->post();
        $model = new OrderModel();
        $list = $model->query('
SELECT
	*
FROM
(
SELECT
	d.state_tag name,
	SUM( c.qty ) value
FROM
	mu_ecang_order a
	LEFT JOIN mu_ecang_order_address b ON a.id = b.order_id
	LEFT JOIN mu_ecang_order_detail c ON a.id = c.order_id
	LEFT JOIN mu_storage_state d ON b.state = d.state 
WHERE
	a.`status` = 4 
	AND a.datePaidPlatform >= "' . $post['sale_start'] . ' 00:00:00" 
	AND a.datePaidPlatform <= "' . $post['sale_end'] . ' 23:59:59" 
	AND b.countryCode = "US" 
	AND c.warehouseSku IN ( ' . $post['skuStr'] . ' ) 
	AND d.state_tag NOT IN ( "CA", "GA", "NJ", "TX" ) 
GROUP BY
	state_tag UNION ALL
SELECT
	d.warehouse_state name,
	SUM( c.qty ) value	
FROM
	mu_ecang_order a
	LEFT JOIN mu_ecang_order_address b ON a.id = b.order_id
	LEFT JOIN mu_ecang_order_detail c ON a.id = c.order_id
	LEFT JOIN mu_storage_state d ON b.state = d.state 
WHERE
	a.`status` = 4 
	AND a.datePaidPlatform >= "' . $post['sale_start'] . ' 00:00:00" 
	AND a.datePaidPlatform <= "' . $post['sale_end'] . ' 23:59:59" 
	AND b.countryCode = "US" 
	AND c.warehouseSku IN ( ' . $post['skuStr'] . ' ) 
GROUP BY
	warehouse_state
) a
ORDER BY value DESC;
        ');

        $sumData = $model->query('
SELECT
	SUM( b.qty ) qty
FROM
	mu_ecang_order a
	LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id 
WHERE
	a.`status` = 4 
	AND a.datePaidPlatform >= "' . $post['sale_start'] . ' 00:00:00" 
	AND a.datePaidPlatform <= "' . $post['sale_end'] . ' 23:59:59" 
	AND b.warehouseSku IN ( ' . $post['skuStr'] . ' );
        ');

        echo json_encode(['code' => 200, 'data' => ['list' => $list, 'sumData' => $sumData]]);
        exit();
    }
    /**
     * @throws DbException
     */
    public function getSkuSaleStateExport()
    {
        $ip = get_real_ip();
        if (!in_array($ip, ['127.0.0.1', '122.227.159.146', '139.224.106.228', '47.100.200.11'])) {
            echo json_encode(['code' => 100, 'msg' => '未被允许的请求！']);
            exit();
        }

        $post = $this->request->post();
        $model = new OrderModel();
        $list = $model->query('
SELECT
	d.warehouse_state name,
	SUM( c.qty ) value,
	CONCAT(
		ROUND(
			SUM( c.qty ) / (
			SELECT
				SUM( b.qty ) 
			FROM
				mu_ecang_order a
				LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id 
			WHERE
				a.`status` = 4 
				AND a.datePaidPlatform >= "' . $post['sale_start'] . ' 00:00:00" 
				AND a.datePaidPlatform <= "' . $post['sale_end'] . ' 23:59:59" 
				AND b.warehouseSku IN ( ' . input('skuStr') . ' ) 
			) * 100, 2 ), "%" 
	) percent 
FROM
	mu_ecang_order a
	LEFT JOIN mu_ecang_order_address b ON a.id = b.order_id
	LEFT JOIN mu_ecang_order_detail c ON a.id = c.order_id
	LEFT JOIN mu_storage_state d ON b.state = d.state 
WHERE
	a.`status` = 4 
	AND a.datePaidPlatform >= "' . $post['sale_start'] . ' 00:00:00" 
	AND a.datePaidPlatform <= "' . $post['sale_end'] . ' 23:59:59" 
	AND b.countryCode = "US" 
	AND c.warehouseSku IN ( ' . input('skuStr') . ' ) 
GROUP BY
	warehouse_state 
ORDER BY
	value DESC;
        ');

        echo json_encode(['code' => 200, 'data' => ['list' => $list]]);
        exit();
    }

    /**
     * @throws DbException
     */
    public function getSkuDailySales()
    {
        $ip = get_real_ip();
        if (!in_array($ip, ['127.0.0.1', '122.227.159.146', '139.224.106.228', '47.100.200.11'])) {
            echo json_encode(['code' => 100, 'msg' => '未被允许的请求！']);
            exit();
        }

        $post = $this->request->post();
        $sale_next = date('Y-m-d', strtotime('+1 day', strtotime($post['sale_end'])));
        $model = new OrderModel();
        $list = $model->query('
SELECT
    month,
    userAccount,
    total_qty,
    total_qty /
    (
        DATEDIFF(
            LEAST(LAST_DAY(STR_TO_DATE(CONCAT(month, "-01"), "%Y-%m-%d")), "' . $post['sale_end'] . '"),
            GREATEST(STR_TO_DATE(CONCAT(month, "-01"), "%Y-%m-%d"), "' . $post['sale_start'] . '")
        ) + 1
    ) AS avg_daily_qty
FROM (
    SELECT
        DATE_FORMAT(a.datePaidPlatform, "%Y-%m") AS month,
        a.userAccount,
        SUM(b.qty) AS total_qty
    FROM mu_ecang_order a
    LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id
    WHERE
        b.warehouseSku = "' . $post['sku'] . '"
        AND a.datePaidPlatform >= "' . $post['sale_start'] . '"
        AND a.datePaidPlatform < "' . $sale_next . '"
    GROUP BY
        month,
        a.userAccount
) t;
        ');

        echo json_encode(['code' => 200, 'data' => ['list' => $list]]);
        exit();
    }
}
