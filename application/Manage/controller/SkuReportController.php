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

        $sale_start = $this->request->get('sale_start', '', 'htmlspecialchars');
        $this->assign('sale_start', $sale_start);
        $start_time = empty($sale_start) ? '' : 'AND a.dateWarehouseShipping >="' . $sale_start . '"';

        $sale_end = $this->request->get('sale_end', date('Y-m-d 00:00:00'), 'htmlspecialchars');
        $this->assign('sale_end', $sale_end);

        $qty_order = $this->request->get('qty_order', 'DESC', 'htmlspecialchars');
        $this->assign('qty_order', $qty_order);

        $qty_start = $this->request->get('qty_start', '', 'htmlspecialchars');
        $this->assign('qty_start', $qty_start);
        $qty_time = empty($qty_start) ? '' : 'AND a.dateWarehouseShipping >="' . $qty_start . '"';

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
                a.`status` = 4 ' .
            $start_time .
            'AND a.dateWarehouseShipping < "' . $sale_end . '"' . '
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
                a.`status` = 4 ' .
            $qty_time .
            'AND a.dateWarehouseShipping < "' . $qty_end . '"' . '
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
        $start = $this->request->get('start', '', 'htmlspecialchars');
        $this->assign('start', $start);
        $start_time = empty($start) ? '' : 'AND a.dateWarehouseShipping >="' . $start . '"';

        $end = $this->request->get('end', date('Y-m-d 00:00:00'), 'htmlspecialchars');
        $this->assign('end', $end);

        $model = new ProductModel();
        $list = $model->query('
            SELECT
            a.platform,
            DATE_FORMAT( a.dateWarehouseShipping, "%Y%m" ) MONTH,
            SUM( b.qty ) qty 
        FROM
            mu_ecang_order a
            LEFT JOIN mu_ecang_order_detail b ON a.id = b.order_id 
        WHERE
            b.warehouseSku = "' . $sku . '" 
            AND a.`status` = 4 ' .
            $start_time .
            'AND a.dateWarehouseShipping < "' . $end . '"' . '
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

        return view();
    }

    /**
     * @throws PDOException
     * @throws BindParamException
     */
    public function category(): \think\response\View
    {
        $start = $this->request->get('start', '', 'htmlspecialchars');
        $this->assign('start', $start);
        $start_time = empty($start) ? '' : 'AND a.dateWarehouseShipping >="' . $start . '"';

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
    'AND a.dateWarehouseShipping < "' . $end . '"' . ' 
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
            'AND a.dateWarehouseShipping < "' . $end . '"' . ' 
GROUP BY
	category_name_2;
        ');

        $this->assign('category_1', json_encode($category_1));
        $this->assign('category_2', json_encode($category_2));

        return view();
    }
}
