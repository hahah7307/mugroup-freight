<?php
namespace app\Manage\command;

use app\Manage\model\InventoryTurnoverModel;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class InventoryTurnover extends Command
{
    protected function configure()
    {
        $this->setName('InventoryTurnover')->setDescription('Here is the InventoryTurnover');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        $model = new InventoryTurnoverModel();
        $data = $model->where(['date' => date('Ymd')])->find();
        if ($data) {
            $output->writeln("success");exit();
        }

        Db::startTrans();
        try {
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
	created_date = ' . date('Ymd') . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19 UNION ALL
SELECT
	SUM( sellable_quantity ) AS value,
	ROUND( SUM( sellable_quantity * b.sp_unit_price ), 4) AS sum
FROM
	mu_lc_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.product_sku = b.productSku 
WHERE
	created_date = ' . date('Ymd') . '
	AND b.saleStatus != 18
	AND b.saleStatus != 19
	) a;
        ');

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
	created_date = ' . date('Ymd', strtotime('-1 month')) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19 UNION ALL
SELECT
	SUM( sellable_quantity ) AS value,
	ROUND( SUM( sellable_quantity * b.sp_unit_price ), 4) AS sum
FROM
	mu_lc_inventory_batch a
	LEFT JOIN mu_ecang_product b ON a.product_sku = b.productSku 
WHERE
	created_date = ' . date('Ymd', strtotime('-1 month')) . ' 
	AND b.saleStatus != 18
	AND b.saleStatus != 19
	) a;
        ');

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
	AND a.datePaidPlatform >= "' . date('Y-m-d H:i:s', strtotime('-1 month')) . '" 
	AND a.datePaidPlatform < "' . date('Y-m-d H:i:s') . ' ";
        ');
            $data = [
                'turnover'      =>  round($monthQty[0]['qty'] / (($last_sum[0]['value'] + $sum[0]['value']) / 2) * 12, 2),
                'date'          =>  date('Ymd'),
                'created_date'  =>  date('Y-m-d H:i:s')
            ];
            if (!$model->insert($data)) {
                throw new Exception("Failed!");
            }

            Db::commit();
            $output->writeln("success");exit();
        } catch (\SoapFault $e) {
            Db::rollback();
            dump('SoapFault:'.$e);
        } catch (\Exception $e) {
            Db::rollback();
            dump('Exception:'.$e);
        }
    }
}