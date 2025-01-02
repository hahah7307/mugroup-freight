<?php
namespace app\Manage\command;

use app\Manage\model\FinanceWildberriesFeeModel;
use Exception;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class FinanceWildberriesFeeEmpty extends Command
{
    protected function configure()
    {
        $this->setName('FinanceWildberriesFeeEmpty')->setDescription('Here is the FinanceWildberriesFeeEmpty');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        Db::startTrans();
        try {
            $model = new FinanceWildberriesFeeModel();
            $updateData = $model->query('
SELECT DISTINCT
	a.id,
	NULL AS report_id,
	NULL AS calculate_month 
FROM
	mu_finance_wildberries_fee a
	LEFT JOIN mu_finance_wildberries_order_notify b ON a.id = b.fee_id 
WHERE
	a.report_id IS NOT NULL 
	AND a.calculate_month IS NOT NULL 
	AND b.report_id IS NULL;
            ');

            if (!$model->saveAll($updateData)) {
                throw new Exception("更新失败或无需更新");
            }

            Db::commit();
            $output->writeln("success");
        } catch (\Exception $e) {
            Db::rollback();
            $output->writeln($e->getMessage());
        }
    }
}